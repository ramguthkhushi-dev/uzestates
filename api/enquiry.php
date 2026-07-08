<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/enquiries.php';
require_once __DIR__ . '/../includes/api.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_json_error('Method not allowed', 405);
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    api_json_error('Invalid session. Please refresh the page and try again.', 403);
}

$data = $_POST;
if (empty($data['enquiry_type'])) {
    $data['enquiry_type'] = 'General';
}

$result = enquiry_save($data);

if (!$result['success']) {
    api_json_error($result['error'] ?? 'Your enquiry could not be sent. Please try again.');
}

$propertyId = filter_var($data['property_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$message    = $propertyId
    ? 'Thank you. Your enquiry has been sent.'
    : 'Thank you. Your enquiry has been sent. UZ Estates will contact you directly.';

api_json_ok(['message' => $message]);
