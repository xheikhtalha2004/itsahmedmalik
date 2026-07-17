<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$candidate = realpath($root . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

if ($candidate !== false
    && str_starts_with($candidate, realpath($root) . DIRECTORY_SEPARATOR)
    && is_file($candidate)
    && !in_array($extension, ['html', 'php'], true)
) {
    return false;
}

require $root . '/index.php';
