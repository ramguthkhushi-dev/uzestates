<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/enquiries.php';
require_once __DIR__ . '/../../includes/api.php';

require_admin_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_json_error('Method not allowed', 405);
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    api_json_error('Invalid session. Please refresh the page and try again.', 403);
}

$id = (int) ($_POST['id'] ?? 0);
$row = $id > 0 ? enquiry_find($id) : null;

if (!$row) {
    api_json_error('Enquiry not found.', 404);
}

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    enquiry_delete($id);
    api_json_ok([
        'message'  => 'Enquiry deleted.',
        'redirect' => admin_url('enquiries/index.php'),
    ]);
}

$status    = trim($_POST['status'] ?? $row['status']);
$adminNote = $_POST['admin_note'] ?? '';

if (!enquiry_update($id, $status, $adminNote)) {
    api_json_error('Could not update enquiry. Please choose a valid status.');
}

api_json_ok([
    'message'     => 'Enquiry updated.',
    'status'      => $status,
    'statusClass' => enquiry_status_class($status),
]);
