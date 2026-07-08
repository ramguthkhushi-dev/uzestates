<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/gallery.php';

require_admin();

$slots   = gallery_slots_all();
$success = flash_get('success');
$error   = flash_get('error');
$galleryAdminTab = 'slots';

admin_render('Gallery', 'gallery', static function () use ($slots, $success, $error): void {
    global $galleryAdminTab;
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

    <?php require __DIR__ . '/_tabs.php'; ?>

    <div class="panel">
      <div class="panel-toolbar">
        <h2>Gallery slot manager</h2>
        <span class="panel-meta"><?php echo count($slots); ?> slots</span>
      </div>
      <p class="panel-text">Fixed layout. Edit slot content only; size and position stay locked.</p>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Slot</th>
              <th>Name</th>
              <th>Size</th>
              <th>Content type</th>
              <th>Title</th>
              <th>Visible</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($slots as $slot): ?>
              <?php
                $preview = gallery_slot_poster_url($slot);
                if ($preview === null && ($slot['media_type'] ?? '') === 'image') {
                    $preview = gallery_media_url($slot['file_path'] ?? null);
                }
              ?>
              <tr>
                <td><?php echo (int) $slot['slot_number']; ?></td>
                <td><?php echo e($slot['slot_name']); ?></td>
                <td><?php echo e(gallery_slot_size_label($slot['slot_size'])); ?></td>
                <td><?php echo e(gallery_type_label($slot['media_type'] ?? '')); ?></td>
                <td><?php echo e($slot['title'] ?: '—'); ?></td>
                <td><?php echo !empty($slot['is_visible']) ? 'Yes' : 'No'; ?></td>
                <td class="table-actions">
                  <a href="<?php echo e(admin_url('gallery/edit.php?id=' . (int) $slot['id'])); ?>" class="btn btn-outline btn-sm">Edit slot</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
});
