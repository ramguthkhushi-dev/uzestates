<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

function mail_from_address(): string
{
    if (defined('MAIL_FROM_ADDRESS') && MAIL_FROM_ADDRESS !== '') {
        return MAIL_FROM_ADDRESS;
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/:\d+$/', '', $host) ?? 'localhost';

    return 'noreply@' . $host;
}

function mail_from_name(): string
{
    return defined('MAIL_FROM_NAME') && MAIL_FROM_NAME !== ''
        ? MAIL_FROM_NAME
        : APP_NAME;
}

function mail_send(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $fromName = mail_from_name();
    $from     = mail_from_address();

    $headers = 'From: ' . $fromName . ' <' . $from . ">\r\n"
        . 'Content-Type: text/plain; charset=UTF-8';

    if ($replyTo !== null && $replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= "\r\nReply-To: " . $replyTo;
    }

    $subject = str_replace(["\r", "\n"], '', $subject);

    require_once __DIR__ . '/mail-smtp.php';

    if (mail_smtp_configured()) {
        if (mail_smtp_send($to, $subject, $body, $from, $fromName, $replyTo)) {
            return true;
        }
    }

    return @mail($to, $subject, $body, $headers);
}

function enquiry_notification_recipient(): string
{
    if (defined('ENQUIRY_NOTIFY_EMAIL') && ENQUIRY_NOTIFY_EMAIL !== '') {
        return ENQUIRY_NOTIFY_EMAIL;
    }

    require_once __DIR__ . '/settings.php';

    return settings_contact_public()['email'];
}

function enquiry_send_notification(int $id, array $data): void
{
    if (defined('ENQUIRY_NOTIFICATIONS_ENABLED') && !ENQUIRY_NOTIFICATIONS_ENABLED) {
        return;
    }

    $recipient = enquiry_notification_recipient();
    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $adminUrl = absolute_url('admin/enquiries/view.php?id=' . $id);
    $lines    = [
        'A new enquiry was submitted on ' . APP_NAME . '.',
        '',
        'Name: ' . ($data['name'] ?? ''),
        'Phone: ' . (($data['phone'] ?? '') !== '' ? $data['phone'] : '—'),
        'Email: ' . (($data['email'] ?? '') !== '' ? $data['email'] : '—'),
        'Type: ' . ($data['enquiry_type'] ?? 'General'),
    ];

    if (!empty($data['property_title'])) {
        $lines[] = 'Property: ' . $data['property_title'];
    }

    $lines[] = '';
    $lines[] = 'Message:';
    $lines[] = $data['message'] ?? '';
    $lines[] = '';
    $lines[] = 'View in admin:';
    $lines[] = $adminUrl;

    $replyTo = trim($data['email'] ?? '');
    $subject = APP_NAME . ' — New enquiry from ' . ($data['name'] ?? 'visitor');

    mail_send($recipient, $subject, implode("\n", $lines), $replyTo !== '' ? $replyTo : null);
}

function send_password_reset_email(string $email, string $resetUrl): void
{
    $subject = APP_NAME . ' — Password reset';
    $body    = "Hello,\n\nUse the link below to reset your password. This link expires in 1 hour.\n\n"
        . $resetUrl . "\n\nIf you did not request this, you can ignore this email.\n\n"
        . APP_NAME;

    mail_send($email, $subject, $body);
}
