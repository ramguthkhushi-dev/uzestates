<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/properties.php';
require_once __DIR__ . '/../includes/enquiries.php';
require_once __DIR__ . '/../includes/gallery.php';

require_admin();

$stats = [
    'properties'    => 0,
    'available'     => 0,
    'new_enquiries' => 0,
    'gallery'       => 0,
];

try {
    $stats['properties']    = (int) db()->query('SELECT COUNT(*) FROM properties')->fetchColumn();
    $stats['available']     = (int) db()->query("SELECT COUNT(*) FROM properties WHERE status = 'Available'")->fetchColumn();
    $stats['new_enquiries'] = enquiry_count_by_status('New');
    $stats['gallery']       = gallery_slots_visible_count();
} catch (Throwable $e) {
    flash_set('error', 'Could not load dashboard stats.');
}

$recentEnquiries = enquiry_recent(8);
$success         = flash_get('success');
$error           = flash_get('error');
$admin           = current_admin();
$adminName       = trim((string) ($admin['name'] ?? $admin['username'] ?? 'Administrator'));

admin_render('Dashboard', 'dashboard.php', static function () use ($stats, $recentEnquiries, $success, $error, $adminName): void {
    ?>
    <div class="dashboard-page">
      <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

      <section class="dash-hero" aria-label="Welcome">
        <div class="dash-hero-main">
          <p class="dash-hero-kicker">Overview</p>
          <h2 class="dash-hero-title">Welcome back, <?php echo e($adminName); ?></h2>
          <p class="dash-hero-lead">Here's what's happening with your website today.</p>
        </div>
      </section>

      <div class="dash-stats">
        <a href="<?php echo e(admin_url('properties/index.php')); ?>" class="dash-stat-card">
          <span class="dash-stat-icon dash-stat-icon--home" aria-hidden="true"></span>
          <div class="dash-stat-copy">
            <span class="dash-stat-kicker">Properties</span>
            <strong class="dash-stat-num"><?php echo $stats['properties']; ?></strong>
            <span class="dash-stat-sub">Total listings</span>
          </div>
        </a>
        <a href="<?php echo e(admin_url('properties/index.php')); ?>" class="dash-stat-card">
          <span class="dash-stat-icon dash-stat-icon--key" aria-hidden="true"></span>
          <div class="dash-stat-copy">
            <span class="dash-stat-kicker">Available</span>
            <strong class="dash-stat-num"><?php echo $stats['available']; ?></strong>
            <span class="dash-stat-sub">Active listings</span>
          </div>
        </a>
        <a href="<?php echo e(admin_url('enquiries/index.php?status=New')); ?>" class="dash-stat-card<?php echo $stats['new_enquiries'] > 0 ? ' is-alert' : ''; ?>">
          <span class="dash-stat-icon dash-stat-icon--mail" aria-hidden="true"></span>
          <div class="dash-stat-copy">
            <span class="dash-stat-kicker">New enquiries</span>
            <strong class="dash-stat-num"><?php echo $stats['new_enquiries']; ?></strong>
            <span class="dash-stat-sub">Awaiting response</span>
          </div>
        </a>
        <a href="<?php echo e(admin_url('gallery/index.php')); ?>" class="dash-stat-card">
          <span class="dash-stat-icon dash-stat-icon--grid" aria-hidden="true"></span>
          <div class="dash-stat-copy">
            <span class="dash-stat-kicker">Gallery</span>
            <strong class="dash-stat-num"><?php echo $stats['gallery']; ?></strong>
            <span class="dash-stat-sub">Visible slots</span>
          </div>
        </a>
      </div>

      <div class="dash-mid">
        <section class="dash-panel">
          <div class="dash-panel-head">
            <h3>Recent enquiries</h3>
            <a href="<?php echo e(admin_url('enquiries/index.php')); ?>" class="dash-panel-link">View all enquiries →</a>
          </div>

          <?php if ($recentEnquiries === []): ?>
            <p class="dash-muted">No enquiries yet.</p>
          <?php else: ?>
            <div class="table-wrap dash-table-wrap">
              <table class="data-table dash-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Interest</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th aria-hidden="true"><span class="visually-hidden">Open</span></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentEnquiries as $row): ?>
                    <?php $initial = mb_strtoupper(mb_substr(trim($row['name']), 0, 1)); ?>
                    <tr class="dash-table-row" data-href="<?php echo e(admin_url('enquiries/view.php?id=' . (int) $row['id'])); ?>">
                      <td>
                        <span class="dash-table-name">
                          <span class="dash-table-avatar" aria-hidden="true"><?php echo e($initial); ?></span>
                          <?php echo e($row['name']); ?>
                        </span>
                      </td>
                      <td><?php echo e($row['property_title'] ?: ($row['enquiry_type'] ?? 'General')); ?></td>
                      <td><span class="status-badge <?php echo e(enquiry_status_class($row['status'] ?? '')); ?>"><?php echo e($row['status']); ?></span></td>
                      <td class="dash-table-date"><?php echo e(date('d M Y', strtotime($row['created_at']))); ?></td>
                      <td class="dash-table-chevron" aria-hidden="true">›</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>

        <aside class="dash-aside">
          <section class="dash-panel dash-quick">
            <div class="dash-panel-head">
              <h3>Quick actions</h3>
            </div>
            <ul class="dash-quick-list">
              <li><a href="<?php echo e(admin_url('properties/add.php')); ?>"><span>Add new property</span><span class="dash-chevron" aria-hidden="true">›</span></a></li>
              <?php if ($stats['new_enquiries'] > 0): ?>
                <li><a href="<?php echo e(admin_url('enquiries/index.php?status=New')); ?>" class="is-priority"><span>Review <?php echo $stats['new_enquiries']; ?> new <?php echo $stats['new_enquiries'] === 1 ? 'enquiry' : 'enquiries'; ?></span><span class="dash-chevron" aria-hidden="true">›</span></a></li>
              <?php endif; ?>
              <li><a href="<?php echo e(admin_url('pages/contact.php')); ?>"><span>Update contact details</span><span class="dash-chevron" aria-hidden="true">›</span></a></li>
            </ul>
          </section>

          <a href="<?php echo e(BASE_URL); ?>/home.php" target="_blank" rel="noopener" class="dash-panel dash-status dash-status-link">
            <span class="dash-status-icon" aria-hidden="true">✓</span>
            <div>
              <strong>All systems operational</strong>
              <p>Your website is live and running smoothly.</p>
              <span class="dash-status-action">Open live site ↗</span>
            </div>
          </a>
        </aside>
      </div>

      <section class="dash-panel dash-guide-panel">
        <header class="dash-guide-head">
          <h3 class="dash-guide-title">How to manage the website</h3>
          <p class="dash-guide-intro">A brief guide to the main tasks you will use day to day. All other tasks are in the sidebar.</p>
        </header>
        <div class="dash-guide-grid">
          <article class="dash-guide-card dash-guide-card--properties">
            <div class="dash-guide-card-head">
              <span class="dash-guide-icon dash-guide-icon--home" aria-hidden="true"></span>
              <h4>Properties</h4>
            </div>
            <p class="dash-guide-lead">Create and maintain your listings so they appear correctly on the public site.</p>
            <ul class="dash-guide-list">
              <li>Add a property with its title, price, location, and type.</li>
              <li>Set the status to <strong>Available</strong> when it should be visible to visitors.</li>
              <li>Upload photos and video from the property <strong>Media</strong> page.</li>
              <li>Changes are published as soon as you save.</li>
            </ul>
          </article>
          <article class="dash-guide-card dash-guide-card--enquiries">
            <div class="dash-guide-card-head">
              <span class="dash-guide-icon dash-guide-icon--mail" aria-hidden="true"></span>
              <h4>Enquiries</h4>
            </div>
            <p class="dash-guide-lead">Handle messages from the contact page and from individual property listings.</p>
            <ul class="dash-guide-list">
              <li>Open an enquiry to see the sender’s details and what they are interested in.</li>
              <li>Reply by phone, email, or WhatsApp using the contact information shown.</li>
              <li>Mark the enquiry as <strong>Contacted</strong> once you have responded.</li>
              <li>Mark it as <strong>Closed</strong> when the conversation is finished.</li>
            </ul>
          </article>
          <article class="dash-guide-card dash-guide-card--pages">
            <div class="dash-guide-card-head">
              <span class="dash-guide-icon dash-guide-icon--pages" aria-hidden="true"></span>
              <h4>Gallery and pages</h4>
            </div>
            <p class="dash-guide-lead">Control the images and written content visitors see across the site.</p>
            <ul class="dash-guide-list">
              <li>Choose which gallery slots are visible on the public gallery page.</li>
              <li>Edit the Home, About, and Contact pages from the sidebar menu.</li>
              <li>Use <strong>View live site</strong> in the top bar to check your changes.</li>
              <li>Contact details also appear in the site footer and enquiry forms.</li>
            </ul>
          </article>
        </div>
      </section>
    </div>
    <?php
});
