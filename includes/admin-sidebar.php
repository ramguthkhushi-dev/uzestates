<?php

declare(strict_types=1);

/** @var string $adminNavKey */

require_once __DIR__ . '/admin-nav.php';

$adminNavKey = $adminNavKey ?? 'dashboard.php';
$admin       = current_admin();
$navItems    = admin_nav_items();
?>
<aside class="admin-sidebar" id="admin-sidebar">
  <div class="sidebar-brand">
    <a href="<?php echo e(admin_url('dashboard.php')); ?>" class="sidebar-brand-link">
      <img
        src="<?php echo e(BASE_URL); ?>/images/logo.png"
        alt="<?php echo e(APP_NAME); ?>"
        class="sidebar-brand-logo"
        width="168"
        height="auto"
      />
    </a>
    <span class="sidebar-brand-tag">Administration</span>
  </div>

  <nav class="sidebar-nav" aria-label="Admin navigation">
    <?php foreach ($navItems as $item): ?>
      <a href="<?php echo e($item['url']); ?>"
         class="sidebar-link<?php echo admin_nav_is_active($adminNavKey, $item['key']) ? ' is-active' : ''; ?>">
        <span class="sidebar-icon sidebar-icon--<?php echo e($item['icon_key'] ?? 'default'); ?>" aria-hidden="true"></span>
        <span class="sidebar-label"><?php echo e($item['label']); ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <?php if ($admin): ?>
      <a href="<?php echo e(admin_url('account.php')); ?>" class="sidebar-user sidebar-user-link">
        <span class="sidebar-avatar" aria-hidden="true"><?php echo e(mb_strtoupper(mb_substr($admin['name'] ?: $admin['username'], 0, 1))); ?></span>
        <div class="sidebar-user-meta">
          <strong><?php echo e($admin['name'] ?: $admin['username']); ?></strong>
          <span><?php echo e($admin['email']); ?></span>
        </div>
      </a>
    <?php endif; ?>
    <a href="<?php echo e(admin_url('logout.php')); ?>" class="sidebar-logout">
      <span class="sidebar-logout-icon" aria-hidden="true"></span>
      Sign out
    </a>
  </div>
</aside>
