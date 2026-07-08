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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Invalid session. Please try again.');
    } elseif (property_delete($id)) {
        flash_set('success', 'Property deleted successfully.');
    } else {
        flash_set('error', 'Could not delete property.');
    }
    header('Location: ' . admin_url('properties/index.php'));
    exit;
}

admin_render('Delete Property', 'properties', static function () use ($property, $id): void {
    ?>
    <div class="properties-admin-page">
    <?php admin_back_link('All properties', 'properties/index.php'); ?>
    <div class="panel panel-danger properties-delete-panel">
      <h2>Delete property?</h2>
      <p class="panel-text">
        You are about to permanently delete <strong><?php echo e($property['title']); ?></strong>.
        All details and media will be removed.
      </p>
      <form method="post" class="delete-form">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <div class="form-actions">
          <button type="submit" class="btn btn-danger">Yes, delete property</button>
          <a href="<?php echo e(admin_url('properties/index.php')); ?>" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
    </div>
    <?php
});
