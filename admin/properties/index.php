<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/properties.php';

require_admin();

$filters = [
    'keyword'         => trim($_GET['keyword'] ?? ''),
    'property_type'   => trim($_GET['property_type'] ?? ''),
    'listing_purpose' => trim($_GET['listing_purpose'] ?? ''),
    'status'          => trim($_GET['status'] ?? ''),
    'is_visible'      => $_GET['is_visible'] ?? '',
];

try {
    $properties = property_list_admin($filters);
    $types      = property_distinct_values('property_type');
    $purposes   = property_distinct_values('listing_purpose');
    $statuses   = property_distinct_values('status');
} catch (Throwable $e) {
    $properties = [];
    $types = $purposes = $statuses = [];
    flash_set('error', 'Could not load properties.');
}

$hasFilters = (
    $filters['keyword'] !== ''
    || $filters['property_type'] !== ''
    || $filters['listing_purpose'] !== ''
    || $filters['status'] !== ''
    || $filters['is_visible'] !== ''
);

$success = flash_get('success');
$error   = flash_get('error');
$propertiesAdminTab = 'list';

admin_render('Properties', 'properties', static function () use ($properties, $filters, $types, $purposes, $statuses, $success, $error, $hasFilters): void {
    global $propertiesAdminTab;
    ?>
    <div class="properties-admin-page">
      <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

      <?php require __DIR__ . '/_tabs.php'; ?>

      <div class="properties-header">
        <div class="properties-header-copy">
          <h2 class="properties-heading">All properties</h2>
          <p class="properties-lead"><?php echo count($properties); ?> listing<?php echo count($properties) === 1 ? '' : 's'; ?> shown</p>
        </div>
        <a href="<?php echo e(admin_url('properties/add.php')); ?>" class="btn btn-primary">+ Add property</a>
      </div>

      <form method="get" class="properties-filter">
        <label class="properties-filter-search">
          <span class="visually-hidden">Search</span>
          <input type="text" name="keyword" placeholder="Search by title or location…" value="<?php echo e($filters['keyword']); ?>" />
        </label>
        <select name="property_type" aria-label="Property type">
          <option value="">All types</option>
          <?php foreach ($types as $v): ?>
            <option value="<?php echo e($v); ?>"<?php echo $filters['property_type'] === $v ? ' selected' : ''; ?>><?php echo e($v); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="listing_purpose" aria-label="Listing purpose">
          <option value="">All purposes</option>
          <?php foreach ($purposes as $v): ?>
            <option value="<?php echo e($v); ?>"<?php echo $filters['listing_purpose'] === $v ? ' selected' : ''; ?>><?php echo e($v); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="status" aria-label="Status">
          <option value="">All statuses</option>
          <?php foreach ($statuses as $v): ?>
            <option value="<?php echo e($v); ?>"<?php echo $filters['status'] === $v ? ' selected' : ''; ?>><?php echo e($v); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="is_visible" aria-label="Visibility">
          <option value="">Any visibility</option>
          <option value="1"<?php echo $filters['is_visible'] === '1' ? ' selected' : ''; ?>>Visible</option>
          <option value="0"<?php echo $filters['is_visible'] === '0' ? ' selected' : ''; ?>>Hidden</option>
        </select>
        <div class="properties-filter-actions">
          <button type="submit" class="btn btn-outline btn-sm">Apply</button>
          <?php if ($hasFilters): ?>
            <a href="<?php echo e(admin_url('properties/index.php')); ?>" class="btn btn-outline btn-sm">Clear</a>
          <?php endif; ?>
        </div>
      </form>

      <?php if ($properties === []): ?>
        <div class="properties-empty panel">
          <p class="properties-empty-title">No properties found</p>
          <p class="panel-text">Try adjusting your filters, or add a new listing.</p>
          <a href="<?php echo e(admin_url('properties/add.php')); ?>" class="btn btn-primary">Add property</a>
        </div>
      <?php else: ?>
        <div class="properties-list">
          <?php foreach ($properties as $p): ?>
            <?php
              $lotCount = (int) ($p['lot_count'] ?? 0);
              $cardTitle = property_card_title($p);
              $isVisible = !isset($p['is_visible']) || (bool) $p['is_visible'];
              $editUrl = admin_url('properties/edit.php?id=' . (int) $p['id']);
              $typeSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', (string) ($p['property_type'] ?? 'other')) ?: 'other');
            ?>
            <article class="property-item" data-type="<?php echo e($typeSlug); ?>">
              <a href="<?php echo e($editUrl); ?>" class="property-item-media" tabindex="-1" aria-hidden="true">
                <?php if (!empty($p['thumb'])): ?>
                  <img src="<?php echo e(property_media_public_url($p['thumb'])); ?>" alt="" loading="lazy" />
                <?php else: ?>
                  <span class="property-item-placeholder">No photo</span>
                <?php endif; ?>
              </a>

              <div class="property-item-body">
                <div class="property-item-head">
                  <div class="property-item-titles">
                    <h3 class="property-item-title">
                      <a href="<?php echo e($editUrl); ?>"><?php echo e($p['title']); ?></a>
                    </h3>
                    <?php if ($cardTitle !== $p['title']): ?>
                      <p class="property-item-subtitle">Card title: <?php echo e($cardTitle); ?></p>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($p['property_type'])): ?>
                    <span class="property-item-type"><?php echo e($p['property_type']); ?></span>
                  <?php endif; ?>
                </div>

                <ul class="property-item-meta">
                  <?php if (!empty($p['price'])): ?>
                    <li><strong><?php echo e($p['price']); ?></strong></li>
                  <?php endif; ?>
                  <?php if (!empty($p['location_name'])): ?>
                    <li><?php echo e($p['location_name']); ?></li>
                  <?php endif; ?>
                  <?php if ($lotCount > 0): ?>
                    <li><?php echo $lotCount; ?> lot<?php echo $lotCount === 1 ? '' : 's'; ?></li>
                  <?php endif; ?>
                  <?php if (!empty($p['listing_purpose'])): ?>
                    <li><?php echo e($p['listing_purpose']); ?></li>
                  <?php endif; ?>
                </ul>

                <div class="property-item-tags">
                  <?php if (!empty($p['status'])): ?>
                    <span class="property-tag property-tag--status"><?php echo e($p['status']); ?></span>
                  <?php endif; ?>
                  <span class="property-tag property-tag--<?php echo $isVisible ? 'visible' : 'hidden'; ?>">
                    <?php echo $isVisible ? 'Visible' : 'Hidden'; ?>
                  </span>
                </div>
              </div>

              <div class="property-item-actions">
                <a href="<?php echo e(BASE_URL); ?>/property-details.php?id=<?php echo (int) $p['id']; ?>" class="property-action" target="_blank" rel="noopener" title="View on site">View</a>
                <a href="<?php echo e($editUrl); ?>" class="property-action property-action--primary" title="Edit property">Edit</a>
                <a href="<?php echo e(admin_url('properties/media.php?id=' . (int) $p['id'])); ?>" class="property-action" title="Manage media">Media</a>
                <a href="<?php echo e(admin_url('properties/delete.php?id=' . (int) $p['id'])); ?>" class="property-action property-action--danger" title="Delete property">Delete</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
});
