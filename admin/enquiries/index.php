<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/enquiries.php';
require_once __DIR__ . '/../../includes/properties.php';

require_admin();

$filters = [
    'status'       => trim($_GET['status'] ?? ''),
    'enquiry_type' => trim($_GET['enquiry_type'] ?? ''),
    'property_id'  => (int) ($_GET['property_id'] ?? 0) ?: '',
];

$enquiries  = enquiry_list($filters);
$properties = property_list_all();
$success    = flash_get('success');
$error      = flash_get('error');
$hasFilters = ($filters['status'] !== '' || $filters['enquiry_type'] !== '' || $filters['property_id'] !== '');

$adminExtraScripts = [
    BASE_URL . '/admin/assets/enquiries-index.js?v=' . (int) @filemtime(__DIR__ . '/../assets/enquiries-index.js'),
];

admin_render('Enquiries', 'enquiries', static function () use ($enquiries, $filters, $properties, $success, $error, $hasFilters): void {
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

    <div class="panel" data-enquiries-panel data-enquiries-api="<?php echo e(BASE_URL); ?>/admin/api/enquiries-list.php">
      <div class="panel-toolbar">
        <h2>All enquiries</h2>
        <span class="panel-meta" data-enquiries-count><?php echo count($enquiries); ?> shown</span>
      </div>

      <form method="get" class="filter-bar" data-enquiries-filter>
        <select name="status">
          <option value="">All statuses</option>
          <?php foreach (enquiry_statuses() as $s): ?>
            <option value="<?php echo e($s); ?>"<?php echo $filters['status'] === $s ? ' selected' : ''; ?>><?php echo e($s); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="enquiry_type">
          <option value="">All types</option>
          <?php foreach (enquiry_types() as $t): ?>
            <option value="<?php echo e($t); ?>"<?php echo $filters['enquiry_type'] === $t ? ' selected' : ''; ?>><?php echo e($t); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="property_id">
          <option value="">All properties</option>
          <?php foreach ($properties as $p): ?>
            <option value="<?php echo (int) $p['id']; ?>"<?php echo (string) $filters['property_id'] === (string) $p['id'] ? ' selected' : ''; ?>><?php echo e($p['title']); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <?php if ($hasFilters): ?>
          <a href="<?php echo e(admin_url('enquiries/index.php')); ?>" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
      </form>

      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>Name</th><th>Phone</th><th>Email</th><th>Property</th><th>Type</th><th>Status</th><th>Date</th><th></th></tr>
          </thead>
          <tbody data-enquiries-tbody>
            <?php if ($enquiries === []): ?>
              <tr><td colspan="8">No enquiries yet.</td></tr>
            <?php else: foreach ($enquiries as $row): ?>
              <tr>
                <td><a href="<?php echo e(admin_url('enquiries/view.php?id=' . (int) $row['id'])); ?>" class="table-link"><?php echo e($row['name']); ?></a></td>
                <td>
                  <?php if (!empty($row['phone'])): ?>
                    <a href="tel:<?php echo e(preg_replace('/\s+/', '', $row['phone'])); ?>" class="table-link"><?php echo e($row['phone']); ?></a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($row['email'])): ?>
                    <a href="mailto:<?php echo e($row['email']); ?>" class="table-link"><?php echo e($row['email']); ?></a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?php echo e($row['property_title'] ?: '—'); ?></td>
                <td><?php echo e($row['enquiry_type'] ?? 'General'); ?></td>
                <td><span class="status-badge <?php echo e(enquiry_status_class($row['status'] ?? '')); ?>"><?php echo e($row['status']); ?></span></td>
                <td><?php echo e(date('d M Y', strtotime($row['created_at']))); ?></td>
                <td><a href="<?php echo e(admin_url('enquiries/view.php?id=' . (int) $row['id'])); ?>" class="btn btn-outline btn-sm">View</a></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
});
