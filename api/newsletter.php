<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

try {
    require_public_post(16_384);
    if (honeypot_was_filled($_POST)) {
        api_json(200, [
            'ok' => true,
            'code' => 'newsletter_subscribed',
            'message' => 'You are on the update list.',
        ]);
    }

    enforce_turnstile($_POST, 'newsletter');
    $result = submit_newsletter($_POST);
    api_json($result['status'], $result['body']);
} catch (Throwable $exception) {
    app_log('newsletter_submission_failed', ['exception' => $exception::class]);
    api_json(503, [
        'ok' => false,
        'code' => 'service_unavailable',
        'message' => 'The subscription could not be saved right now. Please try again shortly.',
    ]);
}
