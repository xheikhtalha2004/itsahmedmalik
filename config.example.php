<?php

declare(strict_types=1);

/**
 * Copy this structure to the private configuration file documented below.
 * Never put real credentials in this repository.
 */
return [
    'env' => 'production',
    'app_url' => 'https://itsahmedmalik.com',
    'timezone' => 'Asia/Karachi',
    'allowed_hosts' => ['itsahmedmalik.com', 'www.itsahmedmalik.com'],
    'admin_username' => 'replace-with-hpanel-username',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u000000000_portfolio',
        'user' => 'u000000000_portfolio',
        'password' => '',
    ],
    'turnstile' => [
        'site_key' => '',
        'secret' => '',
    ],
    'smtp' => [
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'encryption' => 'smtps',
        'username' => 'hello@itsahmedmalik.com',
        'password' => '',
        'from_email' => 'hello@itsahmedmalik.com',
        'from_name' => 'Ahmed Malik',
        'admin_email' => 'hello@itsahmedmalik.com',
    ],
    'services' => ['software', 'ai', 'startup', 'other'],
];
