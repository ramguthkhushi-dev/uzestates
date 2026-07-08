<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/properties.php';

require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$property = $id > 0 ? property_find($id) : null;

if (!$property) {
    flash_set('error', 'Property not found.');
    header('Location: ' . admin_url('properties/index.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $result = property_save_from_request($id, $_POST, $_FILES);
        if ($result['success']) {
            flash_set('success', 'Property updated successfully.');
            header('Location: ' . admin_url('properties/index.php'));
            exit;
        }
        $error = $result['error'];
    }
}

$formData   = property_parse_basic($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $property);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $formData['price_numeric'] = $property['price_numeric'] ?? null;
}

$features = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $labels = $_POST['feature_label'] ?? [];
    $values = $_POST['feature_value'] ?? [];
    $orders = $_POST['feature_order'] ?? [];
    foreach ($labels as $i => $label) {
        $features[] = [
            'feature_label' => $label,
            'feature_value' => $values[$i] ?? '',
            'display_order' => (int) ($orders[$i] ?? $i),
        ];
    }
} else {
    $features = property_features($id);
}

$lots = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? property_lots_from_post($_POST)
    : property_lots($id);

$lotMediaByLotId = [];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    foreach (property_lot_media_grouped($id) as $lotId => $group) {
        try {
            $stmt = db()->prepare(
                'SELECT * FROM property_media WHERE property_id = :property_id AND lot_id = :lot_id ORDER BY display_order ASC, id ASC'
            );
            $stmt->execute(['property_id' => $id, 'lot_id' => $lotId]);
            $lotMediaByLotId[$lotId] = $stmt->fetchAll();
        } catch (Throwable) {
            $lotMediaByLotId[$lotId] = [];
        }
    }
}

$mediaItems = property_media($id);
$isEdit     = true;
$propertyId = $id;
$formMode   = 'full';

admin_render('Edit Property', 'properties', static function () use ($error, $formData, $features, $lots, $lotMediaByLotId, $mediaItems, $isEdit, $propertyId, $formMode, $id): void {
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
