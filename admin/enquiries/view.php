<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/enquiries.php';

require_admin();

$id = (int) ($_GET['id'] ?? 0);
$row = $id > 0 ? enquiry_find($id) : null;

if (!$row) {
    flash_set('error', 'Enquiry not found.');
    header('Location: ' . admin_url('enquiries/index.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'save';
        if ($action === 'delete') {
            enquiry_delete($id);
            flash_set('success', 'Enquiry deleted.');
            header('Location: ' . admin_url('enquiries/index.php'));
            exit;
        }
        $status = trim($_POST['status'] ?? $row['status']);
        if (!enquiry_update($id, $status, $_POST['admin_note'] ?? '')) {
            $error = 'Could not update enquiry. Please choose a valid status.';
        } else {
            flash_set('success', 'Enquiry updated.');
            header('Location: ' . admin_url('enquiries/index.php'));
            exit;
        }
    }
}

$adminExtraScripts = [
    BASE_URL . '/admin/assets/enquiries.js?v=' . (int) @filemtime(__DIR__ . '/../assets/enquiries.js'),
];

admin_render('Enquiry #' . $id, 'enquiries', static function () use ($row, $id, $error): void {
    $whatsappUrl = enquiry_whatsapp_url($row['phone'] ?? null);
    admin_back_link('All enquiries', 'enquiries/index.php');
    if ($error): ?>
      <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-toolbar panel-toolbar-tight">
        <h2><?php echo e($row['name']); ?></h2>
        <span class="status-badge <?php echo e(enquiry_status_class($row['status'] ?? '')); ?>" data-enquiry-status><?php echo e($row['status']); ?></span>
      </div>
      <dl class="detail-list">
        <dt>Phone</dt>
        <dd>
          <?php if (!empty($row['phone'])): ?>
            <a href="tel:<?php echo e(preg_replace('/\s+/', '', $row['phone'])); ?>"><?php echo e($row['phone']); ?></a>
            <?php if ($whatsappUrl): ?>
              · <a href="<?php echo e($whatsappUrl); ?>" target="_blank" rel="noopener">WhatsApp</a>
            <?php endif; ?>
          <?php else: ?>
            —
          <?php endif; ?>
        </dd>
        <dt>Email</dt>
        <dd>
          <?php if (!empty($row['email'])): ?>
            <a href="mailto:<?php echo e($row['email']); ?>"><?php echo e($row['email']); ?></a>
          <?php else: ?>
            —
          <?php endif; ?>
        </dd>
        <dt>Type</dt><dd><?php echo e($row['enquiry_type'] ?? 'General'); ?></dd>
        <dt>Property</dt>
        <dd>
          <?php if (!empty($row['property_title'])): ?>
            <?php if (!empty($row['property_id'])): ?>
              <a href="<?php echo e(admin_url('properties/edit.php?id=' . (int) $row['property_id'])); ?>"><?php echo e($row['property_title']); ?></a>
              · <a href="<?php echo e(BASE_URL . '/property-details.php?id=' . (int) $row['property_id']); ?>" target="_blank" rel="noopener">View on site</a>
            <?php else: ?>
              <?php echo e($row['property_title']); ?>
            <?php endif; ?>
          <?php else: ?>
            —
          <?php endif; ?>
        </dd>
        <dt>Submitted</dt><dd><?php echo e(date('d M Y, H:i', strtotime($row['created_at']))); ?></dd>
        <?php if (!empty($row['updated_at']) && $row['updated_at'] !== $row['created_at']): ?>
          <dt>Last updated</dt><dd><?php echo e(date('d M Y, H:i', strtotime($row['updated_at']))); ?></dd>
        <?php endif; ?>
      </dl>
      <div class="detail-message">
        <h3>Message</h3>
        <p><?php echo nl2br(e($row['message'])); ?></p>
      </div>

      <form method="post" class="admin-form" data-ajax-form data-enquiry-form data-enquiry-id="<?php echo (int) $id; ?>" data-enquiry-api="<?php echo e(BASE_URL); ?>/admin/api/enquiry.php">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <label class="form-field">
          <span>Status</span>
          <select name="status">
            <?php foreach (enquiry_statuses() as $s): ?>
              <option value="<?php echo e($s); ?>"<?php echo ($row['status'] ?? '') === $s ? ' selected' : ''; ?>><?php echo e($s); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="form-field">
          <span>Admin note (internal)</span>
          <textarea name="admin_note" rows="4"><?php echo e($row['admin_note'] ?? ''); ?></textarea>
        </label>
        <div class="form-actions">
          <button type="submit" name="action" value="save" class="btn btn-primary">Save</button>
          <a href="<?php echo e(admin_url('enquiries/index.php')); ?>" class="btn btn-outline">Cancel</a>
          <button type="submit" name="action" value="delete" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
    <?php
});
