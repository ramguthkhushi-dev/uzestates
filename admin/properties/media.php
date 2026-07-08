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
        $result = property_save_media_from_request($id, $_POST, $_FILES);
        if ($result['success']) {
            flash_set('success', 'Media updated successfully.');
            header('Location: ' . admin_url('properties/index.php'));
            exit;
        }
        $error = $result['error'];
    }
}

$formData   = $property;
$features   = [];
$lots       = [];
$mediaItems = property_media($id);
$isEdit     = true;
$propertyId = $id;
$formMode   = 'media';

admin_render('Property Media · ' . $property['title'], 'properties', static function () use ($error, $formData, $features, $lots, $mediaItems, $isEdit, $propertyId, $formMode, $id): void {
    ?>
    <div class="properties-admin-page properties-admin-page--form">
    <?php
    admin_back_link('All properties', 'properties/index.php');
    if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif;
    require __DIR__ . '/../includes/property-form.php';
    ?>
    </div>
    <?php
});
