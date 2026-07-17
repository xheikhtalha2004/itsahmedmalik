<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

try {
    require_public_post(32_768);
    if (honeypot_was_filled($_POST)) {
        api_json(201, [
            'ok' => true,
            'code' => 'meeting_requested',
            'message' => 'Your meeting request is pending approval. We will contact you after review.',
        ]);
    }

    $submissionId = strtolower(trim((string) ($_POST['submission_id'] ?? '')));
    enforce_turnstile($_POST, 'meeting', $submissionId);
    $result = submit_meeting($_POST);
    api_json($result['status'], $result['body']);
} catch (Throwable $exception) {
    app_log('meeting_submission_failed', ['exception' => $exception::class]);
    api_json(503, [
        'ok' => false,
        'code' => 'service_unavailable',
        'message' => 'Your meeting request could not be saved right now. Please try again shortly.',
    ]);
}
