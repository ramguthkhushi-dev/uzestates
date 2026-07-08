<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/properties.php';

require_admin();

$error    = null;
$formData = property_parse_basic([]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $result = property_save_from_request(null, $_POST, $_FILES);
        if ($result['success']) {
            flash_set('success', 'Property created successfully.');
            header('Location: ' . admin_url('properties/index.php'));
            exit;
        }
        $error    = $result['error'];
        $formData = property_parse_basic($_POST);
    }
}

$features = [];
$labels   = $_POST['feature_label'] ?? [];
$values   = $_POST['feature_value'] ?? [];
$orders   = $_POST['feature_order'] ?? [];
if ($labels !== []) {
    foreach ($labels as $i => $label) {
        $features[] = [
            'feature_label' => $label,
            'feature_value' => $values[$i] ?? '',
            'display_order' => (int) ($orders[$i] ?? $i),
        ];
    }
}

$lots = property_lots_from_post($_POST);
$lotMediaByLotId = [];
$mediaItems = [];
$isEdit     = false;
$propertyId = null;
$formMode   = 'full';

admin_render('Add Property', 'properties', static function () use ($error, $formData, $features, $lots, $lotMediaByLotId, $mediaItems, $isEdit, $propertyId, $formMode): void {
    ?>
    <div class="properties-admin-page properties-admin-page--form">
    <?php
    admin_back_link('All properties', 'properties/index.php');
    if ($error): ?>
      <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif;
    require __DIR__ . '/../includes/property-form.php';
    ?>
    </div>
    <?php
});
