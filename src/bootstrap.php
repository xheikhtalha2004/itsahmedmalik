<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$autoload = APP_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

/**
 * Read configuration using dot notation. Production credentials live outside
 * public_html at ../private/app.php; config.php is a local-development option.
 */
function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config;

    if ($config === null) {
        $explicit = getenv('APP_CONFIG_FILE');
        $paths = array_filter([
            is_string($explicit) && $explicit !== '' ? $explicit : null,
            dirname(APP_ROOT) . '/private/app.php',
            APP_ROOT . '/config.php',
            APP_ROOT . '/config.example.php',
        ]);

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $loaded = require $path;
            if (!is_array($loaded)) {
                throw new RuntimeException('Application configuration must return an array.');
            }

            $config = $loaded;
            break;
        }

        if ($config === null) {
            throw new RuntimeException('Application configuration was not found.');
        }
    }

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

function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) app_config('db.host', 'localhost');
    $port = (int) app_config('db.port', 3306);
    $name = (string) app_config('db.name', '');
    $user = (string) app_config('db.user', '');
    $password = (string) app_config('db.password', '');

    if ($name === '' || $user === '' || $password === '') {
        throw new RuntimeException('Database credentials are not configured.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $name,
    );

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $base = rtrim((string) app_config('app_url', ''), '/');
    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

function app_log(string $event, array $context = []): void
{
    $safeContext = array_intersect_key($context, array_flip([
        'record_id',
        'migration',
        'reason',
        'exception',
    ]));
    error_log('[portfolio] ' . json_encode(
        ['event' => $event, 'context' => $safeContext],
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
    ));
}

date_default_timezone_set((string) app_config('timezone', 'Asia/Karachi'));

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/submissions.php';
