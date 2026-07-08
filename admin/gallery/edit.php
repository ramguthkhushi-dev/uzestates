<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/gallery.php';

require_admin();

$id = (int) ($_GET['id'] ?? 0);
$slot = $id > 0 ? gallery_slot_find($id) : null;

if (!$slot) {
    flash_set('error', 'Gallery slot not found.');
    header('Location: ' . admin_url('gallery/index.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session.';
    } else {
        $_POST['id'] = $id;
        $result = gallery_slot_save($_POST, $_FILES['file'] ?? null, $_FILES['thumbnail'] ?? null);
        if ($result['success']) {
            flash_set('success', 'Slot ' . (int) $slot['slot_number'] . ' updated.');
            header('Location: ' . admin_url('gallery/index.php'));
            exit;
        }
        $error = $result['error'];
        $slot = array_merge($slot, $_POST);
    }
}

admin_render('Edit Gallery Slot', 'gallery', static function () use ($error, $slot): void {
    admin_back_link('All gallery slots', 'gallery/index.php');
    if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <div class="panel">
      <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <?php require __DIR__ . '/_form.php'; ?>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save slot</button>
          <a href="<?php echo e(admin_url('gallery/index.php')); ?>" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
    <?php
});
