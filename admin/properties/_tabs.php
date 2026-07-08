<?php

declare(strict_types=1);

/** @var string $propertiesAdminTab — 'list' or 'page' */
$propertiesAdminTab = $propertiesAdminTab ?? 'list';
?>
<nav class="admin-subnav" aria-label="Properties admin">
  <a href="<?php echo e(admin_url('properties/index.php')); ?>" class="admin-subnav-link<?php echo $propertiesAdminTab === 'list' ? ' is-active' : ''; ?>">Listings</a>
  <a href="<?php echo e(admin_url('properties/page.php')); ?>" class="admin-subnav-link<?php echo $propertiesAdminTab === 'page' ? ' is-active' : ''; ?>">Page hero</a>
</nav>
