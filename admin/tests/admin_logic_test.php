<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    return $key === 'admin_username' ? 'test-admin' : $default;
}

function app_url(string $path = ''): string
{
    return 'https://example.test/' . ltrim($path, '/');
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/src/admin.php';

$sanitized = admin_sanitize_html('<p onclick="bad()">Safe <strong>text</strong><script>alert(1)</script><a href="javascript:alert(1)">link</a></p>');
assert(str_contains($sanitized, '<strong>text</strong>'));
assert(!str_contains($sanitized, 'onclick'));
assert(!str_contains($sanitized, '<script'));
assert(!str_contains($sanitized, 'javascript:'));

$timezone = new DateTimeZone('Asia/Karachi');
$candidate = (new DateTimeImmutable('tomorrow 10:00', $timezone));
while ((int) $candidate->format('N') > 5) {
    $candidate = $candidate->modify('+1 day');
}
$utc = admin_meeting_utc($candidate->format('Y-m-d'), '10:00');
assert((new DateTimeImmutable($utc, new DateTimeZone('UTC')))->format('s') === '00');

$rejected = false;
try {
    admin_meeting_utc($candidate->format('Y-m-d'), '10:15');
} catch (InvalidArgumentException) {
    $rejected = true;
}
assert($rejected);

assert(admin_csv_cell('=IMPORTXML("x")') === "'=IMPORTXML(\"x\")");

$adminTemplate = file_get_contents(dirname(__DIR__) . '/index.php');
assert(is_string($adminTemplate));
assert(!str_contains($adminTemplate, 'Plain HTML editor fallback'));
assert(str_contains($adminTemplate, 'Restore the pinned Trix assets'));
assert(admin_csv_cell('person@example.test') === 'person@example.test');

echo "admin logic checks passed\n";
