<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function admin_url(string $path = ''): string
{
    return rtrim(BASE_URL . '/admin/' . ltrim($path, '/'), '/');
}

function admin_auth_stylesheet_url(): string
{
    $file = APP_ROOT . '/admin/assets/admin-auth.css';
    $version = is_file($file) ? (int) filemtime($file) : 1;

    return admin_url('assets/admin-auth.css') . '?v=' . $version;
}

function admin_auth_script_url(): string
{
    $file = APP_ROOT . '/admin/assets/admin-auth.js';
    $version = is_file($file) ? (int) filemtime($file) : 1;

    return admin_url('assets/admin-auth.js') . '?v=' . $version;
}

function upload_url(?string $path): ?string
{
    if ($path === null || trim($path) === '') {
        return null;
    }

    $path = ltrim($path, '/');

    if (str_starts_with($path, 'uploads/')) {
        return rtrim(BASE_URL, '/') . '/' . $path;
    }

    return rtrim(UPLOAD_URL, '/') . '/' . $path;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'item';
}

function phone_digits(string $phone): string
{
    return preg_replace('/\D/', '', $phone) ?? '';
}

function phone_local_digits(string $phone): string
{
    $digits = phone_digits($phone);
    if (str_starts_with($digits, '230') && strlen($digits) > 8) {
        $digits = substr($digits, 3);
    }

    return ltrim($digits, '0');
}

function phone_whatsapp_digits(string $phone): string
{
    $digits = phone_digits($phone);
    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '230')) {
        return $digits;
    }

    return '230' . ltrim($digits, '0');
}

function format_phone_display(string $phone): string
{
    $local = phone_local_digits($phone);

    if (strlen($local) >= 8) {
        return '+230 ' . substr($local, 0, 4) . ' ' . substr($local, 4);
    }

    return trim($phone);
}

function phone_tel_href(string $phone): string
{
    $local = phone_local_digits($phone);

    return $local !== '' ? '+230' . $local : '';
}

function phone_tel_local(string $phone): string
{
    return phone_local_digits($phone);
}

function whatsapp_url(?string $number = null): string
{
    $digits = phone_whatsapp_digits($number ?? '');

    return $digits !== '' ? 'https://wa.me/' . $digits : '#';
}

/**
 * @param array<string, mixed> $file
 * @param list<string> $allowedExtensions
 * @return array{path: ?string, error: ?string}
 */
function handle_file_upload(array $file, string $subdir, array $allowedExtensions): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'File upload failed.'];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return ['path' => null, 'error' => 'Invalid file type.'];
    }

    $dir = UPLOAD_PATH . '/' . trim($subdir, '/');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = uniqid('up_', true) . '.' . $ext;
    $dest = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['path' => null, 'error' => 'Could not save uploaded file.'];
    }

    return ['path' => trim($subdir, '/') . '/' . $filename, 'error' => null];
}

function unlink_upload(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    $safe = ltrim(str_replace(['..', '\\'], '', $relativePath), '/');
    $full = UPLOAD_PATH . '/' . $safe;

    if (is_file($full)) {
        unlink($full);
    }
}

function request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        return true;
    }

    return defined('FORCE_HTTPS') && FORCE_HTTPS;
}

function force_https_if_enabled(): void
{
    if (!defined('FORCE_HTTPS') || !FORCE_HTTPS || request_is_https()) {
        return;
    }

    if (PHP_SAPI === 'cli') {
        return;
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
        return;
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';

    header('Location: https://' . $host . $uri, true, 301);
    exit;
}

function absolute_url(string $path): string
{
    $scheme = request_is_https() ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . BASE_URL . '/' . ltrim($path, '/');
}

function is_local_request(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';

    return str_starts_with($host, 'localhost')
        || str_starts_with($host, '127.0.0.1');
}
