<?php

declare(strict_types=1);

// Deliberately inert configuration for API request-process tests. Keeping the
// path explicit prevents the suite from ever falling back to production secrets.
return [
    'env' => 'test',
    'app_url' => 'https://itsahmedmalik.com',
    'timezone' => 'Asia/Karachi',
    'allowed_hosts' => ['itsahmedmalik.com', 'www.itsahmedmalik.com'],
    'admin_username' => 'replace-with-test-username',
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'not_used',
        'user' => 'not_used',
        'password' => 'not_used',
    ],
    'turnstile' => ['site_key' => '', 'secret' => ''],
    'smtp' => [],
    'services' => ['software', 'ai', 'startup', 'other'],
];
