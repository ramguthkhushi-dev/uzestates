<?php

declare(strict_types=1);

/**
 * CLI smoke tests for admin save/update/delete flows.
 * Run: php scripts/verify-admin-flows.php
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/enquiries.php';
require __DIR__ . '/../includes/properties.php';
require __DIR__ . '/../includes/gallery.php';

$passed = 0;
$failed = 0;

function check(string $label, bool $ok): void
{
    global $passed, $failed;
    if ($ok) {
        echo "PASS  {$label}\n";
        $passed++;
    } else {
        echo "FAIL  {$label}\n";
        $failed++;
    }
}

// Enquiries CRUD
$result = enquiry_save([
    'name'         => 'Admin Flow Test',
    'phone'        => '+23051234567',
    'email'        => 'flow-test@example.com',
    'message'        => 'Automated admin flow verification message.',
    'enquiry_type' => 'General',
]);
check('enquiry_save', $result['success'] === true);

$enquiryId = (int) ($result['id'] ?? 0);
$row = enquiry_find($enquiryId);
check('enquiry_find after save', $row !== null && $row['name'] === 'Admin Flow Test');

check('enquiry_update', enquiry_update($enquiryId, 'Contacted', 'Flow test note'));
$updated = enquiry_find($enquiryId);
check('enquiry_update persisted', ($updated['status'] ?? '') === 'Contacted');

check('enquiry_list filter', count(enquiry_list(['status' => 'Contacted'])) >= 1);
check('enquiry_delete', enquiry_delete($enquiryId));
check('enquiry_delete confirmed', enquiry_find($enquiryId) === null);

// Properties list
try {
    $props = property_list_admin([]);
    check('property_list_admin', is_array($props));
} catch (Throwable $e) {
    check('property_list_admin', false);
}

// Gallery slots
$slots = gallery_slots_all();
check('gallery_slots_all', $slots !== []);
if ($slots !== []) {
    $slot = gallery_slot_find((int) $slots[0]['id']);
    check('gallery_slot_find', $slot !== null);
}

// Flash redirect targets: every admin page that sets success flash should be paired
$flashPairs = [
    'enquiries/index.php'     => true,
    'properties/index.php'    => true,
    'properties/page.php'     => true,
    'pages/home.php'          => true,
    'pages/about.php'         => true,
    'pages/contact.php'       => true,
    'gallery/index.php'       => true,
    'gallery/page.php'        => true,
];

foreach ($flashPairs as $file => $expected) {
    $path = APP_ROOT . '/admin/' . $file;
    $content = is_file($path) ? file_get_contents($path) : '';
    check("flash_get(success) in {$file}", str_contains($content, "flash_get('success')") === $expected);
}

// Save redirects: CRUD pages should redirect to list
$redirectChecks = [
    'properties/add.php'    => "admin_url('properties/index.php')",
    'properties/edit.php'   => "admin_url('properties/index.php')",
    'properties/media.php'  => "admin_url('properties/index.php')",
    'properties/delete.php' => "admin_url('properties/index.php')",
    'gallery/edit.php'      => "admin_url('gallery/index.php')",
    'enquiries/view.php'    => "admin_url('enquiries/index.php')",
];

foreach ($redirectChecks as $file => $needle) {
    $path = APP_ROOT . '/admin/' . $file;
    $content = is_file($path) ? file_get_contents($path) : '';
    check("list redirect in {$file}", str_contains($content, $needle));
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
