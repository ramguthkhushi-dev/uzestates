<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/enquiries.php';
require_once __DIR__ . '/../../includes/properties.php';
require_once __DIR__ . '/../../includes/api.php';

require_admin_api();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json_error('Method not allowed', 405);
}

$filters = [
    'status'       => trim($_GET['status'] ?? ''),
    'enquiry_type' => trim($_GET['enquiry_type'] ?? ''),
    'property_id'  => (int) ($_GET['property_id'] ?? 0) ?: '',
];

$rows = enquiry_list($filters);
$payload = [];

foreach ($rows as $row) {
    $payload[] = [
        'id'             => (int) $row['id'],
        'name'           => $row['name'],
        'phone'          => $row['phone'] ?? '',
        'email'          => $row['email'] ?? '',
        'property_title' => $row['property_title'] ?? '',
        'enquiry_type'   => $row['enquiry_type'] ?? 'General',
        'status'         => $row['status'] ?? '',
        'status_class'   => enquiry_status_class($row['status'] ?? ''),
        'created_at'     => date('d M Y', strtotime($row['created_at'])),
        'view_url'       => admin_url('enquiries/view.php?id=' . (int) $row['id']),
    ];
}

api_json_ok([
    'count' => count($payload),
    'rows'  => $payload,
]);
