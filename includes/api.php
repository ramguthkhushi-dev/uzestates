<?php

declare(strict_types=1);

function api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_json_ok(array $data = []): void
{
    api_json(['ok' => true] + $data);
}

function api_json_error(string $error, int $status = 400): void
{
    api_json(['ok' => false, 'error' => $error], $status);
}

function require_admin_api(): void
{
    require_once __DIR__ . '/auth.php';

    start_session();

    if (!admin_logged_in()) {
        api_json_error('Unauthorized', 401);
    }
}
