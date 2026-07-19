<?php

declare(strict_types=1);

function api_headers(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

function api_json(int $status, array $payload): never
{
    api_headers();
    http_response_code($status);
    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
    );
    exit;
}

function require_public_post(int $maximumBytes = 65_536): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        api_json(405, [
            'ok' => false,
            'code' => 'method_not_allowed',
            'message' => 'Use POST for this request.',
        ]);
    }

    $declaredLength = $_SERVER['CONTENT_LENGTH'] ?? null;
    $contentLength = filter_var(
        $declaredLength,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0]],
    );
    if ($declaredLength === null || $declaredLength === '' || $contentLength === false) {
        api_json(411, [
            'ok' => false,
            'code' => 'length_required',
            'message' => 'A valid request length is required.',
        ]);
    }
    if ($contentLength > $maximumBytes) {
        api_json(413, [
            'ok' => false,
            'code' => 'request_too_large',
            'message' => 'The submitted form is too large.',
        ]);
    }

    if (!request_is_same_origin()) {
        api_json(403, [
            'ok' => false,
            'code' => 'origin_rejected',
            'message' => 'This submission could not be verified.',
        ]);
    }
}

function request_is_same_origin(): bool
{
    $secFetch = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
    if ($secFetch === 'cross-site') {
        error_log("[Origin Debug] sec-fetch-site is cross-site");
        return false;
    }

    $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') {
        return true;
    }

    $host = strtolower(rtrim((string) parse_url($origin, PHP_URL_HOST), '.'));
    $scheme = strtolower((string) parse_url($origin, PHP_URL_SCHEME));
    $appScheme = strtolower((string) parse_url((string) app_config('app_url', ''), PHP_URL_SCHEME));
    if ($host === '' || $scheme === '' || $appScheme === '' || !hash_equals($appScheme, $scheme)) {
        error_log("[Origin Debug] Scheme/host empty or mismatch: host=$host, scheme=$scheme, appScheme=$appScheme");
        return false;
    }
    $defaultPort = $scheme === 'https' ? 443 : 80;
    $originPort = (int) (parse_url($origin, PHP_URL_PORT) ?: $defaultPort);
    $appPort = (int) (parse_url((string) app_config('app_url', ''), PHP_URL_PORT) ?: $defaultPort);
    if ($originPort !== $appPort) {
        error_log("[Origin Debug] Port mismatch: originPort=$originPort, appPort=$appPort");
        return false;
    }

    $requestHost = strtolower(rtrim((string) parse_url(
        $appScheme . '://' . (string) ($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? ''),
        PHP_URL_HOST,
    ), '.'));
    if ($requestHost !== '' && !hash_equals($requestHost, $host)) {
        error_log("[Origin Debug] requestHost mismatch: requestHost=$requestHost, host=$host");
        return false;
    }

    $allowed = allowed_hosts();
    if (!in_array($host, $allowed, true)) {
        error_log("[Origin Debug] Host not in allowed_hosts: host=$host, allowed=" . implode(',', $allowed));
        return false;
    }

    return true;
}

/** @return list<string> */
function allowed_hosts(): array
{
    $configured = app_config('allowed_hosts', []);
    $hosts = is_array($configured) ? $configured : [];
    $appHost = parse_url((string) app_config('app_url', ''), PHP_URL_HOST);
    if (is_string($appHost) && $appHost !== '') {
        $hosts[] = $appHost;
    }

    return array_values(array_unique(array_filter(array_map(
        static fn (mixed $host): string => strtolower(rtrim(trim((string) $host), '.')),
        $hosts,
    ))));
}

function honeypot_was_filled(array $input): bool
{
    return trim((string) ($input['website'] ?? '')) !== '';
}

function valid_submission_uuid(string $uuid): bool
{
    return preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        $uuid,
    ) === 1;
}

/**
 * @return array{ok:bool,reason:string}
 */
function verify_turnstile(string $token, string $expectedAction, ?string $idempotencyKey = null): array
{
    $secret = trim((string) app_config('turnstile.secret', ''));
    if ($secret === '' || str_contains($secret, 'replace-')) {
        app_log('turnstile_configuration_error', ['reason' => 'missing_secret']);
        return ['ok' => false, 'reason' => 'configuration_error'];
    }
    if ($token === '' || strlen($token) > 2048) {
        return ['ok' => false, 'reason' => 'challenge_failed'];
    }
    if (!extension_loaded('curl')) {
        app_log('turnstile_configuration_error', ['reason' => 'curl_missing']);
        return ['ok' => false, 'reason' => 'configuration_error'];
    }

    $parameters = [
        'secret' => $secret,
        'response' => $token,
    ];
    $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (filter_var($remoteAddress, FILTER_VALIDATE_IP) !== false) {
        $parameters['remoteip'] = $remoteAddress;
    }
    if ($idempotencyKey !== null && valid_submission_uuid($idempotencyKey)) {
        $parameters['idempotency_key'] = strtolower($idempotencyKey);
    }

    $handle = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    if ($handle === false) {
        return ['ok' => false, 'reason' => 'verification_unavailable'];
    }
    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($parameters, '', '&', PHP_QUERY_RFC3986),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $body = curl_exec($handle);
    $httpStatus = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $transportFailed = $body === false;
    curl_close($handle);

    if ($transportFailed || $httpStatus !== 200 || !is_string($body)) {
        app_log('turnstile_verification_unavailable', ['reason' => 'transport']);
        return ['ok' => false, 'reason' => 'verification_unavailable'];
    }

    try {
        $result = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        app_log('turnstile_verification_unavailable', ['reason' => 'invalid_response']);
        return ['ok' => false, 'reason' => 'verification_unavailable'];
    }

    return validate_turnstile_result($result, $expectedAction);
}

/**
 * Validate the signed result returned by Cloudflare after transport and JSON
 * decoding have succeeded.
 *
 * Keeping this check separate makes the security-critical action and hostname
 * binding executable in tests without weakening or bypassing the live request.
 *
 * @return array{ok:bool,reason:string}
 */
function validate_turnstile_result(mixed $result, string $expectedAction): array
{
    if (!is_array($result) || ($result['success'] ?? false) !== true) {
        return ['ok' => false, 'reason' => 'challenge_failed'];
    }
    if (!hash_equals($expectedAction, (string) ($result['action'] ?? ''))) {
        app_log('turnstile_verification_failed', ['reason' => 'action_mismatch']);
        return ['ok' => false, 'reason' => 'challenge_failed'];
    }

    $hostname = strtolower(rtrim((string) ($result['hostname'] ?? ''), '.'));
    if ($hostname === '' || !in_array($hostname, allowed_hosts(), true)) {
        app_log('turnstile_verification_failed', ['reason' => 'hostname_mismatch']);
        return ['ok' => false, 'reason' => 'challenge_failed'];
    }

    return ['ok' => true, 'reason' => 'verified'];
}

function enforce_turnstile(array $input, string $action, ?string $idempotencyKey = null): void
{
    $verification = verify_turnstile(
        trim((string) ($input['cf-turnstile-response'] ?? '')),
        $action,
        $idempotencyKey,
    );
    if ($verification['ok']) {
        return;
    }

    $unavailable = in_array(
        $verification['reason'],
        ['configuration_error', 'verification_unavailable'],
        true,
    );
    api_json($unavailable ? 503 : 403, [
        'ok' => false,
        'code' => $unavailable ? 'verification_unavailable' : 'verification_failed',
        'message' => $unavailable
            ? 'Verification is temporarily unavailable. Please try again shortly.'
            : 'Please complete the verification and try again.',
    ]);
}

function admin_security_headers(): void
{
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header("Content-Security-Policy: default-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'; img-src 'self' data:; object-src 'none'; script-src 'self'; style-src 'self'");
}

function require_admin_auth(): string
{
    $expected = trim((string) app_config('admin_username', ''));
    $authenticated = trim((string) ($_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? ''));
    $authType = trim((string) ($_SERVER['AUTH_TYPE'] ?? $_SERVER['REDIRECT_AUTH_TYPE'] ?? ''));
    if ($authenticated === '' && strcasecmp($authType, 'Basic') === 0) {
        // PHP_AUTH_USER alone can come from an unverified Authorization header.
        // Accept it only when Apache/LiteSpeed confirms external Basic auth.
        $authenticated = trim((string) ($_SERVER['PHP_AUTH_USER'] ?? ''));
    }

    if ($expected === '' || str_starts_with($expected, 'replace-') || $authenticated === ''
        || !hash_equals($expected, $authenticated)) {
        admin_security_headers();
        http_response_code(403);
        echo 'Admin access is not configured correctly.';
        exit;
    }

    return $authenticated;
}

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    session_name('portfolio_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/admin',
        'secure' => str_starts_with(strtolower(app_url()), 'https://'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function csrf_token(): string
{
    start_admin_session();
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_or_fail(): void
{
    start_admin_session();
    $expected = $_SESSION['csrf_token'] ?? '';
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($expected) || !is_string($submitted) || $expected === ''
        || !hash_equals($expected, $submitted)) {
        admin_security_headers();
        http_response_code(403);
        echo 'This form expired. Go back, reload the page, and try again.';
        exit;
    }
}

function require_admin_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Method not allowed.');
    }
    if (!request_is_same_origin()) {
        http_response_code(403);
        exit('Origin rejected.');
    }
    verify_csrf_or_fail();
}

function admin_redirect(string $path): never
{
    header('Location: ' . app_url('/admin/' . ltrim($path, '/')), true, 303);
    exit;
}
