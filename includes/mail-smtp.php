<?php

declare(strict_types=1);

function mail_smtp_configured(): bool
{
    return defined('SMTP_HOST') && SMTP_HOST !== '';
}

function mail_smtp_send(
    string $to,
    string $subject,
    string $body,
    string $fromEmail,
    string $fromName,
    ?string $replyTo = null
): bool {
    $host = SMTP_HOST;
    $port = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
    $user = defined('SMTP_USER') ? SMTP_USER : '';
    $pass = defined('SMTP_PASS') ? SMTP_PASS : '';
    $enc  = defined('SMTP_ENCRYPTION') ? strtolower(SMTP_ENCRYPTION) : 'tls';

    $remote = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, 20);

    if (!mail_smtp_expect($socket, [220])) {
        fclose($socket);
        return false;
    }

    $ehloHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    fwrite($socket, 'EHLO ' . $ehloHost . "\r\n");
    if (!mail_smtp_expect($socket, [250])) {
        fclose($socket);
        return false;
    }

    if ($enc === 'tls') {
        fwrite($socket, "STARTTLS\r\n");
        if (!mail_smtp_expect($socket, [220])) {
            fclose($socket);
            return false;
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        fwrite($socket, 'EHLO ' . $ehloHost . "\r\n");
        if (!mail_smtp_expect($socket, [250])) {
            fclose($socket);
            return false;
        }
    }

    if ($user !== '') {
        fwrite($socket, "AUTH LOGIN\r\n");
        if (!mail_smtp_expect($socket, [334])) {
            fclose($socket);
            return false;
        }
        fwrite($socket, base64_encode($user) . "\r\n");
        if (!mail_smtp_expect($socket, [334])) {
            fclose($socket);
            return false;
        }
        fwrite($socket, base64_encode($pass) . "\r\n");
        if (!mail_smtp_expect($socket, [235])) {
            fclose($socket);
            return false;
        }
    }

    fwrite($socket, 'MAIL FROM:<' . $fromEmail . ">\r\n");
    if (!mail_smtp_expect($socket, [250])) {
        fclose($socket);
        return false;
    }

    fwrite($socket, 'RCPT TO:<' . $to . ">\r\n");
    if (!mail_smtp_expect($socket, [250, 251])) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "DATA\r\n");
    if (!mail_smtp_expect($socket, [354])) {
        fclose($socket);
        return false;
    }

    $encodedSubject = mail_smtp_encode_header($subject);
    $encodedName    = mail_smtp_encode_header($fromName);
    $headers        = 'From: ' . $encodedName . ' <' . $fromEmail . ">\r\n"
        . 'To: <' . $to . ">\r\n"
        . 'Subject: ' . $encodedSubject . "\r\n"
        . 'MIME-Version: 1.0' . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
        . 'Content-Transfer-Encoding: 8bit';

    if ($replyTo !== null && $replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= "\r\nReply-To: " . $replyTo;
    }

    $message = $headers . "\r\n\r\n" . str_replace(["\r\n", "\r"], "\n", $body);
    $message = str_replace("\n.", "\n..", $message);
    $message = str_replace("\n", "\r\n", $message);

    fwrite($socket, $message . "\r\n.\r\n");
    if (!mail_smtp_expect($socket, [250])) {
        fclose($socket);
        return false;
    }

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return true;
}

/** @param resource $socket */
function mail_smtp_expect($socket, array $codes): bool
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    if ($response === '') {
        return false;
    }

    $code = (int) substr($response, 0, 3);

    return in_array($code, $codes, true);
}

function mail_smtp_encode_header(string $text): string
{
    if (preg_match('/[^\x20-\x7E]/', $text)) {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    return $text;
}
