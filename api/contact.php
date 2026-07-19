<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

try {
    require_public_post(65_536);
    if (honeypot_was_filled($_POST)) {
        api_json(201, [
            'ok' => true,
            'code' => 'contact_received',
            'message' => 'Thanks — your message has been received.',
        ]);
    }

    $submissionId = strtolower(trim((string) ($_POST['submission_id'] ?? '')));
    enforce_turnstile($_POST, 'contact', $submissionId);
    $result = submit_contact($_POST);
    api_json($result['status'], $result['body']);
} catch (Throwable $exception) {
    app_log('contact_submission_failed', [
        'exception' => $exception::class,
        'reason' => $exception->getMessage()
    ]);
    api_json(503, [
        'ok' => false,
        'code' => 'service_unavailable',
        'message' => 'Your message could not be saved right now. Please try again shortly.',
    ]);
}
