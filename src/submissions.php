<?php

declare(strict_types=1);

/** @return array{status:int,body:array<string,mixed>} */
function submit_contact(array $input): array
{
    $uuid = strtolower(trim((string) ($input['submission_id'] ?? '')));
    $fullName = normalize_single_line($input['full_name'] ?? '');
    $email = normalize_email($input['email'] ?? '');
    $phone = normalize_single_line($input['phone'] ?? '');
    $service = trim((string) ($input['service'] ?? ''));
    $message = normalize_multiline($input['message'] ?? '');
    $errors = [];

    if (!valid_submission_uuid($uuid)) {
        $errors['submission_id'] = 'Refresh the page and try again.';
    }
    if (!text_length_between($fullName, 2, 100)) {
        $errors['full_name'] = 'Enter a name between 2 and 100 characters.';
    }
    if (!valid_email($email)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($phone !== '' && !valid_phone($phone)) {
        $errors['phone'] = 'Enter a valid phone number.';
    }
    $services = app_config('services', ['software', 'ai', 'startup', 'other']);
    if (!is_array($services) || !in_array($service, $services, true)) {
        $errors['service'] = 'Choose one of the listed services.';
    }
    if (!text_length_between($message, 10, 5_000)) {
        $errors['message'] = 'Enter a message between 10 and 5,000 characters.';
    }
    if ($errors !== []) {
        return submission_validation_failed($errors);
    }

    $pdo = db();
    try {
        $statement = $pdo->prepare(
            "INSERT INTO contact_submissions
                (submission_uuid, full_name, email, phone, service_code, message, status, created_at, updated_at)
             VALUES
                (UNHEX(REPLACE(:uuid, '-', '')), :full_name, :email, :phone, :service, :message,
                 'new', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
        );
        $statement->execute([
            'uuid' => $uuid,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'service' => $service,
            'message' => $message,
        ]);
    } catch (PDOException $exception) {
        if (is_duplicate_key($exception)) {
            return submission_already_received('contact_already_received');
        }
        throw $exception;
    }

    $id = (int) $pdo->lastInsertId();
    $mailResult = mail_admin_contact([
        'id' => $id,
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'service_code' => $service,
        'message' => $message,
    ]);
    record_contact_mail_result($id, $mailResult);

    return [
        'status' => 201,
        'body' => [
            'ok' => true,
            'code' => 'contact_received',
            'message' => 'Thanks — your message has been received.',
        ],
    ];
}

/** @return array{status:int,body:array<string,mixed>} */
function submit_meeting(array $input): array
{
    $uuid = strtolower(trim((string) ($input['submission_id'] ?? '')));
    $fullName = normalize_single_line($input['full_name'] ?? '');
    $email = normalize_email($input['email'] ?? '');
    $phone = normalize_single_line($input['phone'] ?? '');
    $date = trim((string) ($input['date'] ?? $input['meeting_date'] ?? ''));
    $time = trim((string) ($input['time'] ?? $input['meeting_time'] ?? ''));
    $errors = [];

    if (!valid_submission_uuid($uuid)) {
        $errors['submission_id'] = 'Refresh the page and try again.';
    }
    if (!text_length_between($fullName, 2, 100)) {
        $errors['full_name'] = 'Enter a name between 2 and 100 characters.';
    }
    if (!valid_email($email)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($phone === '' || !valid_phone($phone)) {
        $errors['phone'] = 'Enter a valid phone number.';
    }

    $meetingTime = validate_meeting_start($date, $time);
    if (!$meetingTime['ok']) {
        $errors['date'] = $meetingTime['message'];
    }
    if ($errors !== []) {
        return submission_validation_failed($errors);
    }

    $requestedStart = (string) $meetingTime['utc'];
    $pdo = db();
    try {
        $statement = $pdo->prepare(
            "INSERT INTO meeting_requests
                (submission_uuid, full_name, email, phone, requested_start_at, status, created_at, updated_at)
             VALUES
                (UNHEX(REPLACE(:uuid, '-', '')), :full_name, :email, :phone, :requested_start,
                 'pending', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
        );
        $statement->execute([
            'uuid' => $uuid,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'requested_start' => $requestedStart,
        ]);
    } catch (PDOException $exception) {
        if (is_duplicate_key($exception)) {
            return submission_already_received('meeting_already_received');
        }
        throw $exception;
    }

    $id = (int) $pdo->lastInsertId();
    $mailResult = mail_admin_meeting([
        'id' => $id,
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'requested_start_at' => $requestedStart,
    ]);
    record_meeting_request_mail_result($id, $mailResult);

    return [
        'status' => 201,
        'body' => [
            'ok' => true,
            'code' => 'meeting_requested',
            'message' => 'Your meeting request is pending approval. We will contact you after review.',
        ],
    ];
}

/** @return array{status:int,body:array<string,mixed>} */
function submit_newsletter(array $input): array
{
    $email = normalize_email($input['email'] ?? '');
    if (!valid_email($email)) {
        return submission_validation_failed([
            'email' => 'Enter a valid email address.',
        ]);
    }

    $sourcePath = normalize_source_path($input['source_path'] ?? '/');
    $statement = db()->prepare(
        "INSERT INTO newsletter_subscribers
            (email, source_path, status, first_subscribed_at, last_submitted_at)
         VALUES (:email, :source_path, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id),
            source_path = VALUES(source_path),
            status = 'active',
            last_submitted_at = UTC_TIMESTAMP()"
    );
    $statement->execute([
        'email' => $email,
        'source_path' => $sourcePath,
    ]);
    $created = $statement->rowCount() === 1;

    return [
        'status' => $created ? 201 : 200,
        'body' => [
            'ok' => true,
            'code' => $created ? 'newsletter_subscribed' : 'newsletter_already_subscribed',
            'message' => 'You are on the update list.',
        ],
    ];
}

/**
 * Validate and convert a PKT meeting choice to the UTC database representation.
 *
 * @return array{ok:bool,utc:?string,message:string}
 */
function validate_meeting_start(string $date, string $time): array
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1
        || preg_match('/^(?:[01]\d|2[0-3]):(?:00|30)$/', $time) !== 1) {
        return [
            'ok' => false,
            'utc' => null,
            'message' => 'Choose a valid date and a 30-minute time.',
        ];
    }

    $timezone = new DateTimeZone((string) app_config('timezone', 'Asia/Karachi'));
    $local = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $timezone);
    $parseErrors = DateTimeImmutable::getLastErrors();
    if (!$local instanceof DateTimeImmutable
        || ($parseErrors !== false && ($parseErrors['warning_count'] > 0 || $parseErrors['error_count'] > 0))
        || $local->format('Y-m-d H:i') !== $date . ' ' . $time) {
        return [
            'ok' => false,
            'utc' => null,
            'message' => 'Choose a valid meeting date and time.',
        ];
    }
    if ((int) $local->format('N') > 5) {
        return [
            'ok' => false,
            'utc' => null,
            'message' => 'Meetings can be requested Monday through Friday.',
        ];
    }

    $now = new DateTimeImmutable('now', $timezone);
    if ($local <= $now) {
        return [
            'ok' => false,
            'utc' => null,
            'message' => 'Choose a future meeting time.',
        ];
    }
    if ($local > $now->modify('+90 days')) {
        return [
            'ok' => false,
            'utc' => null,
            'message' => 'Meetings can be requested up to 90 days ahead.',
        ];
    }

    return [
        'ok' => true,
        'utc' => $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        'message' => '',
    ];
}

function normalize_single_line(mixed $value): string
{
    $value = trim(str_replace("\0", '', (string) $value));
    $normalized = preg_replace('/\s+/u', ' ', $value);
    return is_string($normalized) ? $normalized : '';
}

function normalize_multiline(mixed $value): string
{
    $value = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], (string) $value);
    return trim($value);
}

function normalize_email(mixed $value): string
{
    return mb_strtolower(trim((string) $value), 'UTF-8');
}

function valid_email(string $email): bool
{
    return $email !== ''
        && strlen($email) <= 254
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function valid_phone(string $phone): bool
{
    if (strlen($phone) > 32 || preg_match('/^[0-9+().\-\s]+$/', $phone) !== 1) {
        return false;
    }
    $digits = preg_replace('/\D/', '', $phone);
    return is_string($digits) && strlen($digits) >= 7 && strlen($digits) <= 20;
}

function text_length_between(string $value, int $minimum, int $maximum): bool
{
    $length = mb_strlen($value, 'UTF-8');
    return $length >= $minimum && $length <= $maximum;
}

function normalize_source_path(mixed $value): string
{
    $raw = trim((string) $value);
    $path = parse_url($raw, PHP_URL_PATH);
    if (!is_string($path) || $path === '' || $path[0] !== '/') {
        $path = '/';
    }
    return mb_substr($path, 0, 255, 'UTF-8');
}

/** @return array{status:int,body:array<string,mixed>} */
function submission_validation_failed(array $errors): array
{
    return [
        'status' => 422,
        'body' => [
            'ok' => false,
            'code' => 'validation_failed',
            'message' => 'Check the highlighted fields and try again.',
            'errors' => $errors,
        ],
    ];
}

/** @return array{status:int,body:array<string,mixed>} */
function submission_already_received(string $code): array
{
    return [
        'status' => 200,
        'body' => [
            'ok' => true,
            'code' => $code,
            'message' => 'This submission has already been received.',
        ],
    ];
}

function is_duplicate_key(PDOException $exception): bool
{
    return (string) $exception->getCode() === '23000'
        && (int) ($exception->errorInfo[1] ?? 0) === 1062;
}

/** @param array{ok:bool,error:?string} $result */
function record_contact_mail_result(int $id, array $result): void
{
    try {
        $statement = db()->prepare(
            'UPDATE contact_submissions
             SET admin_notified_at = :notified_at, admin_notification_error = :error, updated_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );
        $statement->execute([
            'notified_at' => $result['ok'] ? gmdate('Y-m-d H:i:s') : null,
            'error' => $result['ok'] ? null : mb_substr((string) $result['error'], 0, 500),
            'id' => $id,
        ]);
    } catch (Throwable $exception) {
        app_log('contact_mail_status_failed', [
            'record_id' => $id,
            'exception' => $exception::class,
        ]);
    }
}

/** @param array{ok:bool,error:?string} $result */
function record_meeting_request_mail_result(int $id, array $result): void
{
    try {
        $statement = db()->prepare(
            'UPDATE meeting_requests
             SET request_notified_at = :notified_at, request_notification_error = :error, updated_at = UTC_TIMESTAMP()
             WHERE id = :id'
        );
        $statement->execute([
            'notified_at' => $result['ok'] ? gmdate('Y-m-d H:i:s') : null,
            'error' => $result['ok'] ? null : mb_substr((string) $result['error'], 0, 500),
            'id' => $id,
        ]);
    } catch (Throwable $exception) {
        app_log('meeting_mail_status_failed', [
            'record_id' => $id,
            'exception' => $exception::class,
        ]);
    }
}
