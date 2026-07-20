<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

/** @return array{ok:bool,error:?string} */
function mail_admin_contact(array $submission): array
{
    $adminUrl = app_url('/admin/?section=contacts');
    $html = mail_html_layout(
        'New contact message',
        '<p><strong>Name:</strong> ' . e($submission['full_name'] ?? '') . '</p>'
        . '<p><strong>Email:</strong> ' . e($submission['email'] ?? '') . '</p>'
        . '<p><strong>Phone:</strong> ' . e(($submission['phone'] ?? '') ?: 'Not provided') . '</p>'
        . '<p><strong>Service:</strong> ' . e($submission['service_code'] ?? '') . '</p>'
        . '<p><strong>Message:</strong><br>'
        . nl2br(e($submission['message'] ?? ''), false) . '</p>'
        . '<p><a href="' . e($adminUrl) . '">Open contacts in the admin panel</a></p>',
    );
    $plain = sprintf(
        "New contact message\n\nName: %s\nEmail: %s\nPhone: %s\nService: %s\n\n%s\n\n%s",
        $submission['full_name'] ?? '',
        $submission['email'] ?? '',
        ($submission['phone'] ?? '') ?: 'Not provided',
        $submission['service_code'] ?? '',
        $submission['message'] ?? '',
        $adminUrl,
    );

    return smtp_send(
        (string) app_config('smtp.admin_email', ''),
        'New portfolio contact message',
        $html,
        $plain,
        (string) ($submission['email'] ?? ''),
        (string) ($submission['full_name'] ?? ''),
    );
}

/** @return array{ok:bool,error:?string} */
function mail_admin_meeting(array $meeting): array
{
    $requested = format_meeting_time((string) ($meeting['requested_start_at'] ?? ''));
    $adminUrl = app_url('/admin/?section=meetings');
    $html = mail_html_layout(
        'New meeting request',
        '<p><strong>Name:</strong> ' . e($meeting['full_name'] ?? '') . '</p>'
        . '<p><strong>Email:</strong> ' . e($meeting['email'] ?? '') . '</p>'
        . '<p><strong>Phone:</strong> ' . e($meeting['phone'] ?? '') . '</p>'
        . '<p><strong>Requested time:</strong> ' . e($requested) . '</p>'
        . '<p><a href="' . e($adminUrl) . '">Review pending meetings</a></p>',
    );
    $plain = sprintf(
        "New meeting request\n\nName: %s\nEmail: %s\nPhone: %s\nRequested time: %s\n\n%s",
        $meeting['full_name'] ?? '',
        $meeting['email'] ?? '',
        $meeting['phone'] ?? '',
        $requested,
        $adminUrl,
    );

    return smtp_send(
        (string) app_config('smtp.admin_email', ''),
        'New portfolio meeting request',
        $html,
        $plain,
        (string) ($meeting['email'] ?? ''),
        (string) ($meeting['full_name'] ?? ''),
    );
}

/** @return array{ok:bool,error:?string} */
function mail_meeting_approval(array $meeting, ?string $customMessage = null): array
{
    $approved = format_meeting_time((string) ($meeting['approved_start_at'] ?? ''));
    $name = trim((string) ($meeting['full_name'] ?? ''));

    if ($customMessage !== null && trim($customMessage) !== '') {
        $msg = trim($customMessage);
        $htmlContent = nl2br(e($msg), false);
        $plain = $msg;
    } else {
        $htmlContent = '<p>Hi ' . e($name) . ',</p>'
            . '<p>I hope you\'re doing well. I\'m writing to let you know that our meeting is confirmed for <strong>' . e($approved) . '</strong>.</p>'
            . '<p>Looking forward to speaking with you! If you need to reschedule or have any questions, feel free to reply to this email directly.</p>'
            . '<p>Best regards,<br>Ahmed Malik</p>';
        $plain = sprintf(
            "Hi %s,\n\nI hope you're doing well. I'm writing to let you know that our meeting is confirmed for %s.\n\nLooking forward to speaking with you! If you need to reschedule or have any questions, feel free to reply to this email directly.\n\nBest regards,\nAhmed Malik",
            $name,
            $approved,
        );
    }

    $html = mail_html_layout('Meeting Confirmed', $htmlContent);

    return smtp_send(
        (string) ($meeting['email'] ?? ''),
        'Your meeting with Ahmed Malik is confirmed',
        $html,
        $plain,
    );
}

function format_meeting_time(string $utcDateTime): string
{
    try {
        $utc = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
        $local = $utc->setTimezone(new DateTimeZone((string) app_config('timezone', 'Asia/Karachi')));
        return $local->format('l, F j, Y \a\t g:i A T');
    } catch (Throwable) {
        return 'Time unavailable';
    }
}

function mail_html_layout(string $heading, string $content): string
{
    return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . e($heading) . '</title></head>'
        . '<body style="margin:0;background:#0b0b0d;color:#f4f4f5;font-family:Arial,sans-serif">'
        . '<div style="max-width:640px;margin:0 auto;padding:32px">'
        . '<h1 style="font-size:24px">' . e($heading) . '</h1>'
        . '<div style="line-height:1.6">' . $content . '</div>'
        . '<p style="margin-top:32px;color:#a1a1aa">itsahmedmalik.com</p>'
        . '</div></body></html>';
}

/** @return array{ok:bool,error:?string} */
function smtp_send(
    string $recipient,
    string $subject,
    string $html,
    string $plain,
    ?string $replyEmail = null,
    ?string $replyName = null,
): array {
    if (!class_exists(PHPMailer::class)) {
        app_log('mail_delivery_failed', ['reason' => 'dependency_missing']);
        return ['ok' => false, 'error' => 'dependency_missing'];
    }

    $username = trim((string) app_config('smtp.username', ''));
    $password = (string) app_config('smtp.password', '');
    $from = trim((string) app_config('smtp.from_email', $username));
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)
        || !filter_var($from, FILTER_VALIDATE_EMAIL)
        || $username === '' || $password === '') {
        app_log('mail_delivery_failed', ['reason' => 'not_configured']);
        return ['ok' => false, 'error' => 'not_configured'];
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) app_config('smtp.host', 'smtp.hostinger.com');
        $mail->Port = (int) app_config('smtp.port', 465);
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = strtolower((string) app_config('smtp.encryption', 'smtps')) === 'starttls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Timeout = 15;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($from, (string) app_config('smtp.from_name', 'Ahmed Malik'));
        $mail->addAddress($recipient);
        if ($replyEmail !== null && filter_var($replyEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyEmail, trim((string) $replyName));
        }
        $mail->isHTML(true);
        $mail->Subject = str_replace(["\r", "\n"], ' ', $subject);
        $mail->Body = $html;
        $mail->AltBody = $plain;
        $mail->send();

        return ['ok' => true, 'error' => null];
    } catch (Throwable $exception) {
        app_log('mail_delivery_failed', [
            'reason' => 'smtp_error',
            'exception' => $exception::class,
        ]);
        return ['ok' => false, 'error' => 'delivery_failed'];
    }
}
