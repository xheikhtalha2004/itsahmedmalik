<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this test from the command line.\n");
    exit(2);
}

$scenario = $argv[1] ?? '';
$apiScenarios = [
    'api_contact_honeypot' => ['contact.php', 100, 'https://itsahmedmalik.com'],
    'api_meeting_honeypot' => ['meeting.php', 100, 'https://itsahmedmalik.com'],
    'api_newsletter_honeypot' => ['newsletter.php', 100, 'https://itsahmedmalik.com'],
    'api_contact_oversize' => ['contact.php', 65_537, 'https://itsahmedmalik.com'],
    'api_meeting_oversize' => ['meeting.php', 32_769, 'https://itsahmedmalik.com'],
    'api_newsletter_oversize' => ['newsletter.php', 16_385, 'https://itsahmedmalik.com'],
    'api_contact_cross_origin' => ['contact.php', 100, 'https://attacker.example'],
    'api_contact_turnstile_unavailable' => ['contact.php', 100, 'https://itsahmedmalik.com'],
];

if (isset($apiScenarios[$scenario])) {
    [$endpoint, $contentLength, $origin] = $apiScenarios[$scenario];
    putenv('APP_CONFIG_FILE=' . __DIR__ . '/fixtures/app_no_secrets.php');
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_LENGTH'] = (string) $contentLength;
    $_SERVER['HTTP_HOST'] = 'itsahmedmalik.com';
    $_SERVER['HTTP_ORIGIN'] = $origin;
    $_SERVER['HTTP_SEC_FETCH_SITE'] = $origin === 'https://attacker.example' ? 'cross-site' : 'same-origin';
    $_POST = str_contains($scenario, 'honeypot') ? ['website' => 'robot'] : [];
    register_shutdown_function(static function (): void {
        fwrite(STDERR, 'HTTP_STATUS=' . (string) http_response_code() . "\n");
    });
    require dirname(__DIR__) . '/api/' . $endpoint;
    exit(0);
}

$GLOBALS['submissionTestConfig'] = [
    'app_url' => 'https://itsahmedmalik.com',
    'timezone' => 'Asia/Karachi',
    'allowed_hosts' => [
        'ITSahmedmalik.com.',
        'www.itsahmedmalik.com',
        'www.itsahmedmalik.com.',
        '',
    ],
    'services' => ['software', 'ai', 'startup', 'other'],
    'turnstile' => ['secret' => 'test-secret'],
];
$GLOBALS['submissionTestLogs'] = [];

if (!function_exists('app_config')) {
    function app_config(?string $key = null, mixed $default = null): mixed
    {
        $value = $GLOBALS['submissionTestConfig'];
        if ($key === null || $key === '') {
            return $value;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('app_log')) {
    function app_log(string $event, array $context = []): void
    {
        $GLOBALS['submissionTestLogs'][] = [$event, $context];
    }
}

require_once dirname(__DIR__) . '/src/security.php';
require_once dirname(__DIR__) . '/src/submissions.php';

if ($scenario !== '') {
    register_shutdown_function(static function (): void {
        fwrite(STDERR, 'HTTP_STATUS=' . (string) http_response_code() . "\n");
    });

    switch ($scenario) {
        case 'request_method':
            $_SERVER = ['REQUEST_METHOD' => 'GET'];
            require_public_post();
            break;
        case 'request_too_large':
            $_SERVER = ['REQUEST_METHOD' => 'POST', 'CONTENT_LENGTH' => '65537'];
            require_public_post();
            break;
        case 'request_missing_length':
            $_SERVER = ['REQUEST_METHOD' => 'POST'];
            require_public_post();
            break;
        case 'request_malformed_length':
            $_SERVER = ['REQUEST_METHOD' => 'POST', 'CONTENT_LENGTH' => 'chunked'];
            require_public_post();
            break;
        case 'request_cross_origin':
            $_SERVER = [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_LENGTH' => '20',
                'HTTP_HOST' => 'itsahmedmalik.com',
                'HTTP_ORIGIN' => 'https://attacker.example',
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
            ];
            require_public_post();
            break;
        case 'request_valid':
            $_SERVER = [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_LENGTH' => '20',
                'HTTP_HOST' => 'itsahmedmalik.com',
                'HTTP_ORIGIN' => 'https://itsahmedmalik.com',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
            ];
            http_response_code(200);
            require_public_post();
            echo '{"ok":true,"code":"accepted"}';
            break;
        case 'enforce_empty_turnstile':
            enforce_turnstile([], 'contact');
            break;
        case 'enforce_unconfigured_turnstile':
            $GLOBALS['submissionTestConfig']['turnstile']['secret'] = '';
            enforce_turnstile(['cf-turnstile-response' => 'token'], 'contact');
            break;
        default:
            fwrite(STDERR, "Unknown child scenario: {$scenario}\n");
            exit(2);
    }

    exit(0);
}

$assertions = 0;

/** @throws RuntimeException */
function expect(bool $condition, string $message): void
{
    global $assertions;
    $assertions++;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string,string> */
function validation_errors(array $result): array
{
    expect(($result['status'] ?? null) === 422, 'Expected a validation failure response.');
    $errors = $result['body']['errors'] ?? null;
    expect(is_array($errors), 'Validation response must contain an errors object.');
    return $errors;
}

/** @return array{status:int,body:array<string,mixed>,stderr:string,exit:int} */
function run_child_scenario(string $name): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open([PHP_BINARY, __FILE__, $name], $descriptors, $pipes, dirname(__DIR__));
    if (!is_resource($process)) {
        throw new RuntimeException("Could not start child scenario {$name}.");
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    $decoded = json_decode((string) $stdout, true);
    if (!is_array($decoded)) {
        throw new RuntimeException("Child scenario {$name} returned invalid JSON: {$stdout}; {$stderr}");
    }
    preg_match('/HTTP_STATUS=(\d+)/', (string) $stderr, $matches);

    return [
        'status' => isset($matches[1]) ? (int) $matches[1] : 0,
        'body' => $decoded,
        'stderr' => (string) $stderr,
        'exit' => $exit,
    ];
}

function origin_result(array $server): bool
{
    $original = $_SERVER;
    $_SERVER = $server;
    try {
        return request_is_same_origin();
    } finally {
        $_SERVER = $original;
    }
}

function next_weekday(DateTimeImmutable $start, int $direction = 1): DateTimeImmutable
{
    $candidate = $start;
    while ((int) $candidate->format('N') > 5) {
        $candidate = $candidate->modify(($direction > 0 ? '+' : '-') . '1 day');
    }
    return $candidate;
}

try {
    expect(
        allowed_hosts() === ['itsahmedmalik.com', 'www.itsahmedmalik.com'],
        'Allowed hosts should be normalized and de-duplicated.',
    );
    expect(origin_result([]), 'Requests without browser origin metadata should remain accepted.');
    expect(!origin_result(['HTTP_SEC_FETCH_SITE' => 'cross-site']), 'Sec-Fetch-Site cross-site must be rejected.');
    expect(origin_result([
        'HTTP_HOST' => 'itsahmedmalik.com',
        'HTTP_ORIGIN' => 'https://itsahmedmalik.com',
    ]), 'The configured apex origin should be accepted.');
    expect(origin_result([
        'HTTP_HOST' => 'www.itsahmedmalik.com',
        'HTTP_ORIGIN' => 'https://WWW.ITSAHMEDMALIK.COM.',
    ]), 'Configured host matching should be case-insensitive and tolerate a trailing dot.');
    expect(!origin_result([
        'HTTP_HOST' => 'itsahmedmalik.com',
        'HTTP_ORIGIN' => 'https://www.itsahmedmalik.com',
    ]), 'The Origin must match the current request host.');
    expect(!origin_result([
        'HTTP_HOST' => 'itsahmedmalik.com',
        'HTTP_ORIGIN' => 'http://itsahmedmalik.com',
    ]), 'An HTTP origin must not match an HTTPS application.');
    expect(!origin_result([
        'HTTP_HOST' => 'itsahmedmalik.com',
        'HTTP_ORIGIN' => 'https://itsahmedmalik.com:444',
    ]), 'A non-application origin port must be rejected.');
    expect(!origin_result([
        'HTTP_HOST' => 'attacker.example',
        'HTTP_ORIGIN' => 'https://attacker.example',
    ]), 'An origin outside the hostname allowlist must be rejected.');
    expect(!origin_result(['HTTP_ORIGIN' => 'not a URL']), 'A malformed origin must be rejected.');

    expect(honeypot_was_filled(['website' => 'spam']), 'A populated honeypot should be detected.');
    expect(!honeypot_was_filled(['website' => " \t\n"]), 'Whitespace-only honeypot input should be empty.');
    expect(valid_submission_uuid('550e8400-e29b-41d4-a716-446655440000'), 'A UUIDv4 should be accepted.');
    expect(valid_submission_uuid('550E8400-E29B-41D4-B716-446655440000'), 'UUID matching should be case-insensitive.');
    expect(!valid_submission_uuid('550e8400-e29b-11d4-a716-446655440000'), 'A non-v4 UUID should be rejected.');
    expect(!valid_submission_uuid('550e8400-e29b-41d4-7716-446655440000'), 'An invalid UUID variant should be rejected.');
    expect(!valid_submission_uuid('../550e8400-e29b-41d4-a716-446655440000'), 'UUID traversal-like input should be rejected.');

    expect(normalize_single_line("  Ahmed\0  Malik\n ") === 'Ahmed Malik', 'Single-line text should collapse whitespace and NULs.');
    expect(normalize_multiline(" first\r\nsecond\rthird\0 ") === "first\nsecond\nthird", 'Multiline text should normalize line endings and NULs.');
    expect(normalize_email('  USER@Example.COM ') === 'user@example.com', 'Email normalization should trim and lowercase.');
    expect(valid_email('person@example.com'), 'A normal email should validate.');
    expect(!valid_email('not-an-email'), 'Malformed email should fail validation.');
    expect(!valid_email(str_repeat('a', 245) . '@example.com'), 'Emails over 254 bytes should fail validation.');
    expect(valid_phone('+92 (300) 123-4567'), 'A supported formatted phone should validate.');
    expect(!valid_phone('call-me-now'), 'Alphabetic phone input should fail validation.');
    expect(!valid_phone('+12 34'), 'Phones with fewer than seven digits should fail validation.');
    expect(!valid_phone(str_repeat('1', 21)), 'Phones with more than twenty digits should fail validation.');
    expect(text_length_between('éé', 2, 2), 'Text limits should count Unicode characters.');
    expect(normalize_source_path('https://example.test/contact.html?from=footer') === '/contact.html', 'Newsletter source should retain only its path.');
    expect(normalize_source_path('relative/path') === '/', 'Invalid newsletter source paths should fall back to root.');
    expect(mb_strlen(normalize_source_path('/' . str_repeat('a', 300)), 'UTF-8') === 255, 'Newsletter source paths should be capped at 255 characters.');

    $validContact = [
        'submission_id' => '550e8400-e29b-41d4-a716-446655440000',
        'full_name' => 'Ahmed Malik',
        'email' => 'AHMED@EXAMPLE.COM',
        'phone' => '+92 300 1234567',
        'service' => 'software',
        'message' => 'A valid message for this portfolio.',
    ];
    $errors = validation_errors(submit_contact(array_replace($validContact, ['submission_id' => 'bad'])));
    expect(array_keys($errors) === ['submission_id'], 'A fully valid contact except UUID should report only UUID.');
    $errors = validation_errors(submit_contact(array_replace($validContact, ['message' => 'short'])));
    expect(isset($errors['message']) && !isset($errors['submission_id']), 'A valid UUID should pass while a short message fails.');
    $errors = validation_errors(submit_contact(array_replace($validContact, [
        'submission_id' => 'bad',
        'full_name' => 'A',
        'email' => 'bad',
        'phone' => 'abc',
        'service' => 'SOFTWARE',
        'message' => str_repeat('x', 5_001),
    ])));
    foreach (['submission_id', 'full_name', 'email', 'phone', 'service', 'message'] as $field) {
        expect(isset($errors[$field]), "Invalid contact {$field} should be reported.");
    }
    $errors = validation_errors(submit_contact(array_replace($validContact, [
        'submission_id' => 'bad',
        'full_name' => str_repeat('n', 100),
        'phone' => '',
        'message' => str_repeat('m', 10),
    ])));
    expect(!isset($errors['full_name']), 'A 100-character contact name should be accepted.');
    expect(!isset($errors['phone']), 'Contact phone should be optional.');
    expect(!isset($errors['message']), 'A 10-character message should be accepted.');

    $timezone = new DateTimeZone('Asia/Karachi');
    $now = new DateTimeImmutable('now', $timezone);
    $validLocal = next_weekday($now->modify('+2 days')->setTime(10, 0));
    $validMeeting = [
        'submission_id' => 'bad',
        'full_name' => 'Ahmed Malik',
        'email' => 'ahmed@example.com',
        'phone' => '+92 300 1234567',
        'date' => $validLocal->format('Y-m-d'),
        'time' => '10:00',
    ];
    $errors = validation_errors(submit_meeting($validMeeting));
    expect(array_keys($errors) === ['submission_id'], 'A valid meeting except UUID should report only UUID.');
    $validated = validate_meeting_start($validLocal->format('Y-m-d'), '10:00');
    expect($validated['ok'] === true, 'A future weekday 30-minute slot should validate.');
    expect($validated['utc'] === $validLocal->format('Y-m-d') . ' 05:00:00', 'PKT meeting time should be stored as UTC.');
    expect(validate_meeting_start($validLocal->format('Y-m-d'), '10:15')['ok'] === false, 'Non-30-minute meeting choices should fail.');
    expect(validate_meeting_start('2026-02-30', '10:00')['ok'] === false, 'Impossible calendar dates should fail.');

    $weekend = $now->modify('next saturday')->setTime(10, 0);
    expect(validate_meeting_start($weekend->format('Y-m-d'), '10:00')['ok'] === false, 'Weekend meeting choices should fail.');
    $past = next_weekday($now->modify('-2 days')->setTime(10, 0), -1);
    expect(validate_meeting_start($past->format('Y-m-d'), '10:00')['ok'] === false, 'Past meeting choices should fail.');
    $tooFar = next_weekday($now->modify('+91 days')->setTime(10, 0));
    expect(validate_meeting_start($tooFar->format('Y-m-d'), '10:00')['ok'] === false, 'Meeting choices over 90 days away should fail.');
    $errors = validation_errors(submit_meeting(array_replace($validMeeting, [
        'full_name' => 'A',
        'email' => 'bad',
        'phone' => '',
        'date' => $weekend->format('Y-m-d'),
    ])));
    foreach (['submission_id', 'full_name', 'email', 'phone', 'date'] as $field) {
        expect(isset($errors[$field]), "Invalid meeting {$field} should be reported.");
    }
    $aliasMeeting = $validMeeting;
    unset($aliasMeeting['date'], $aliasMeeting['time']);
    $aliasMeeting['meeting_date'] = $validLocal->format('Y-m-d');
    $aliasMeeting['meeting_time'] = '10:30';
    $errors = validation_errors(submit_meeting($aliasMeeting));
    expect(!isset($errors['date']), 'Legacy meeting_date and meeting_time field names should remain accepted.');

    $errors = validation_errors(submit_newsletter(['email' => 'broken-address']));
    expect(isset($errors['email']), 'Invalid newsletter emails should fail before database access.');

    $GLOBALS['submissionTestConfig']['turnstile']['secret'] = '';
    expect(verify_turnstile('token', 'contact')['reason'] === 'configuration_error', 'Missing Turnstile secret should fail closed.');
    $GLOBALS['submissionTestConfig']['turnstile']['secret'] = 'test-secret';
    expect(verify_turnstile('', 'contact')['reason'] === 'challenge_failed', 'Missing Turnstile tokens should fail.');
    expect(verify_turnstile(str_repeat('x', 2_049), 'contact')['reason'] === 'challenge_failed', 'Oversized Turnstile tokens should fail.');
    expect(validate_turnstile_result([
        'success' => true,
        'action' => 'contact',
        'hostname' => 'ITSAHMEDMALIK.COM.',
    ], 'contact')['ok'], 'A successful Turnstile result bound to action and hostname should pass.');
    expect(!validate_turnstile_result([
        'success' => true,
        'action' => 'newsletter',
        'hostname' => 'itsahmedmalik.com',
    ], 'contact')['ok'], 'A Turnstile action mismatch should fail.');
    expect(!validate_turnstile_result([
        'success' => true,
        'action' => 'contact',
        'hostname' => 'attacker.example',
    ], 'contact')['ok'], 'A Turnstile hostname mismatch should fail.');
    expect(!validate_turnstile_result(['success' => false], 'contact')['ok'], 'An unsuccessful Turnstile response should fail.');
    expect(!validate_turnstile_result('invalid shape', 'contact')['ok'], 'A malformed Turnstile result should fail.');

    $requestCases = [
        'request_method' => [405, 'method_not_allowed'],
        'request_too_large' => [413, 'request_too_large'],
        'request_missing_length' => [411, 'length_required'],
        'request_malformed_length' => [411, 'length_required'],
        'request_cross_origin' => [403, 'origin_rejected'],
        'request_valid' => [200, 'accepted'],
        'enforce_empty_turnstile' => [403, 'verification_failed'],
        'enforce_unconfigured_turnstile' => [503, 'verification_unavailable'],
        'api_contact_honeypot' => [201, 'contact_received'],
        'api_meeting_honeypot' => [201, 'meeting_requested'],
        'api_newsletter_honeypot' => [200, 'newsletter_subscribed'],
        'api_contact_oversize' => [413, 'request_too_large'],
        'api_meeting_oversize' => [413, 'request_too_large'],
        'api_newsletter_oversize' => [413, 'request_too_large'],
        'api_contact_cross_origin' => [403, 'origin_rejected'],
        'api_contact_turnstile_unavailable' => [503, 'verification_unavailable'],
    ];
    foreach ($requestCases as $name => [$expectedStatus, $expectedCode]) {
        $child = run_child_scenario($name);
        expect($child['exit'] === 0, "Child scenario {$name} should exit successfully.");
        expect($child['status'] === $expectedStatus, "Child scenario {$name} should return HTTP {$expectedStatus}.");
        expect(($child['body']['code'] ?? null) === $expectedCode, "Child scenario {$name} should return {$expectedCode}.");
    }

    echo "Submission/security tests passed ({$assertions} assertions).\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Submission/security test failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
