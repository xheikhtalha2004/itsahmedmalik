<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    $config = [
        'app_url' => 'https://example.test',
        'allowed_hosts' => ['example.test'],
        'admin_username' => 'test-admin',
    ];
    if ($key === null || $key === '') {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function app_url(string $path = ''): string
{
    return 'https://example.test/' . ltrim($path, '/');
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

require_once dirname(__DIR__) . '/src/security.php';

$scenario = $argv[1] ?? '';
if ($scenario !== '') {
    register_shutdown_function(static function (): void {
        fwrite(STDERR, 'HTTP_STATUS=' . (string) http_response_code() . "\n");
    });
    http_response_code(200);

    switch ($scenario) {
        case 'auth_missing':
            $_SERVER = [];
            require_admin_auth();
            break;
        case 'auth_mismatch':
            $_SERVER = ['REMOTE_USER' => 'someone-else'];
            require_admin_auth();
            break;
        case 'auth_remote_match':
            $_SERVER = ['REMOTE_USER' => 'test-admin'];
            echo require_admin_auth();
            break;
        case 'auth_php_untrusted':
            $_SERVER = ['PHP_AUTH_USER' => 'test-admin'];
            echo require_admin_auth();
            break;
        case 'auth_php_external_match':
            $_SERVER = ['AUTH_TYPE' => 'Basic', 'PHP_AUTH_USER' => 'test-admin'];
            echo require_admin_auth();
            break;
        case 'auth_redirect_match':
            $_SERVER = ['REDIRECT_REMOTE_USER' => 'test-admin'];
            echo require_admin_auth();
            break;
        case 'post_get':
            $_SERVER = ['REQUEST_METHOD' => 'GET'];
            require_admin_post();
            break;
        case 'post_cross_origin':
            $_SERVER = [
                'REQUEST_METHOD' => 'POST',
                'HTTP_HOST' => 'example.test',
                'HTTP_ORIGIN' => 'https://attacker.example',
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
            ];
            require_admin_post();
            break;
        case 'post_csrf_missing':
            $_SERVER = ['REQUEST_METHOD' => 'POST'];
            $_POST = [];
            require_admin_post();
            break;
        case 'post_valid':
            $_SERVER = [
                'REQUEST_METHOD' => 'POST',
                'HTTP_HOST' => 'example.test',
                'HTTP_ORIGIN' => 'https://example.test',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
            ];
            start_admin_session();
            $_SESSION['csrf_token'] = str_repeat('a', 64);
            $_POST = ['csrf_token' => str_repeat('a', 64)];
            require_admin_post();
            echo 'accepted';
            break;
        default:
            fwrite(STDERR, "Unknown scenario.\n");
            exit(2);
    }
    exit;
}

/** @return array{status:int,stdout:string,exit:int} */
function admin_security_child(string $scenario): array
{
    $process = proc_open(
        [PHP_BINARY, __FILE__, $scenario],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        dirname(__DIR__),
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start admin security scenario.');
    }
    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    preg_match('/HTTP_STATUS=(\d+)/', $stderr, $match);

    return ['status' => isset($match[1]) ? (int) $match[1] : 0, 'stdout' => $stdout, 'exit' => $exit];
}

$cases = [
    'auth_missing' => [403, 'Admin access is not configured correctly.'],
    'auth_mismatch' => [403, 'Admin access is not configured correctly.'],
    'auth_remote_match' => [200, 'test-admin'],
    'auth_php_untrusted' => [403, 'Admin access is not configured correctly.'],
    'auth_php_external_match' => [200, 'test-admin'],
    'auth_redirect_match' => [200, 'test-admin'],
    'post_get' => [405, 'Method not allowed.'],
    'post_cross_origin' => [403, 'Origin rejected.'],
    'post_csrf_missing' => [403, 'This form expired.'],
    'post_valid' => [200, 'accepted'],
];

foreach ($cases as $name => [$status, $output]) {
    $result = admin_security_child($name);
    if ($result['exit'] !== 0 || $result['status'] !== $status || !str_contains($result['stdout'], $output)) {
        throw new RuntimeException("Admin security scenario failed: {$name}.");
    }
}

echo 'Admin auth/origin/CSRF tests passed (' . count($cases) . " scenarios).\n";
