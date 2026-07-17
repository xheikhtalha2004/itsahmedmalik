<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !is_string(getenv('APP_CONFIG_FILE')) || getenv('APP_CONFIG_FILE') === '') {
    fwrite(STDERR, "Set APP_CONFIG_FILE to a disposable migrated MariaDB configuration.\n");
    exit(2);
}
require_once dirname(__DIR__) . '/src/bootstrap.php';

$assertions = 0;

function submission_db_expect(bool $condition, string $message): void
{
    global $assertions;
    $assertions++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function submission_test_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);

    return substr($hex, 0, 8) . '-'
        . substr($hex, 8, 4) . '-'
        . substr($hex, 12, 4) . '-'
        . substr($hex, 16, 4) . '-'
        . substr($hex, 20, 12);
}

function submission_test_weekday(): DateTimeImmutable
{
    $date = (new DateTimeImmutable('now', new DateTimeZone('Asia/Karachi')))
        ->modify('+2 days')
        ->setTime(10, 0);
    while ((int) $date->format('N') > 5) {
        $date = $date->modify('+1 day');
    }

    return $date;
}

$pdo = db();
$pdo->beginTransaction();

try {
    $email = 'submission-test-' . bin2hex(random_bytes(8)) . '@example.test';
    $first = submit_newsletter([
        'email' => strtoupper($email),
        'source_path' => 'https://itsahmedmalik.com/blog.html?from=test',
    ]);
    submission_db_expect($first['status'] === 201, 'A new newsletter email should return 201.');
    submission_db_expect($first['body']['code'] === 'newsletter_subscribed', 'A new newsletter email should report subscribed.');

    $duplicate = submit_newsletter([
        'email' => $email,
        'source_path' => '/contact.html?from=second-test',
    ]);
    submission_db_expect($duplicate['status'] === 200, 'A duplicate newsletter email should return the same successful 200 response.');
    submission_db_expect($duplicate['body']['code'] === 'newsletter_already_subscribed', 'A duplicate newsletter email should report already subscribed.');

    $newsletterRows = $pdo->prepare(
        'SELECT email, source_path, status FROM newsletter_subscribers WHERE email = :email'
    );
    $newsletterRows->execute(['email' => $email]);
    $storedNewsletter = $newsletterRows->fetchAll();
    submission_db_expect(count($storedNewsletter) === 1, 'A duplicate newsletter submission must keep one database row.');
    submission_db_expect($storedNewsletter[0]['email'] === $email, 'Newsletter email should be normalized to lowercase.');
    submission_db_expect($storedNewsletter[0]['source_path'] === '/contact.html', 'A duplicate newsletter submission should refresh its normalized source path.');
    submission_db_expect($storedNewsletter[0]['status'] === 'active', 'A duplicate newsletter submission should remain active.');

    $contactUuid = submission_test_uuid();
    $insertContact = $pdo->prepare(
        "INSERT INTO contact_submissions
            (submission_uuid, full_name, email, phone, service_code, message, status, created_at, updated_at)
         VALUES
            (UNHEX(REPLACE(:uuid, '-', '')), 'Existing Contact', 'existing@example.test', NULL,
             'software', 'Existing idempotent contact submission.', 'new', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
    );
    $insertContact->execute(['uuid' => $contactUuid]);
    $contactDuplicate = submit_contact([
        'submission_id' => $contactUuid,
        'full_name' => 'Retried Contact',
        'email' => 'retry@example.test',
        'phone' => '',
        'service' => 'software',
        'message' => 'This retry must not create a second contact row.',
    ]);
    submission_db_expect($contactDuplicate['status'] === 200, 'A retried contact UUID should return a successful duplicate response.');
    submission_db_expect($contactDuplicate['body']['code'] === 'contact_already_received', 'A retried contact UUID should report already received.');
    $contactCount = $pdo->prepare(
        "SELECT COUNT(*) FROM contact_submissions WHERE submission_uuid = UNHEX(REPLACE(:uuid, '-', ''))"
    );
    $contactCount->execute(['uuid' => $contactUuid]);
    submission_db_expect((int) $contactCount->fetchColumn() === 1, 'A retried contact UUID must keep one database row.');

    $meetingUuid = submission_test_uuid();
    $meetingLocal = submission_test_weekday();
    $meetingUtc = $meetingLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $insertMeeting = $pdo->prepare(
        "INSERT INTO meeting_requests
            (submission_uuid, full_name, email, phone, requested_start_at, status, created_at, updated_at)
         VALUES
            (UNHEX(REPLACE(:uuid, '-', '')), 'Existing Meeting', 'meeting@example.test', '+923001234567',
             :requested_start, 'pending', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
    );
    $insertMeeting->execute(['uuid' => $meetingUuid, 'requested_start' => $meetingUtc]);
    $meetingDuplicate = submit_meeting([
        'submission_id' => $meetingUuid,
        'full_name' => 'Retried Meeting',
        'email' => 'retry-meeting@example.test',
        'phone' => '+92 300 7654321',
        'date' => $meetingLocal->format('Y-m-d'),
        'time' => $meetingLocal->format('H:i'),
    ]);
    submission_db_expect($meetingDuplicate['status'] === 200, 'A retried meeting UUID should return a successful duplicate response.');
    submission_db_expect($meetingDuplicate['body']['code'] === 'meeting_already_received', 'A retried meeting UUID should report already received.');
    $meetingCount = $pdo->prepare(
        "SELECT COUNT(*) FROM meeting_requests WHERE submission_uuid = UNHEX(REPLACE(:uuid, '-', ''))"
    );
    $meetingCount->execute(['uuid' => $meetingUuid]);
    submission_db_expect((int) $meetingCount->fetchColumn() === 1, 'A retried meeting UUID must keep one database row.');

    $pdo->rollBack();
    echo "Submission MariaDB tests passed ({$assertions} assertions; transaction rolled back).\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Submission MariaDB test failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
