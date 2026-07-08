<?php

declare(strict_types=1);

/** @var string $galleryAdminTab — 'page' or 'slots' */
$galleryAdminTab = $galleryAdminTab ?? 'slots';
?>
<nav class="admin-subnav" aria-label="Gallery admin">
  <a href="<?php echo e(admin_url('gallery/page.php')); ?>" class="admin-subnav-link<?php echo $galleryAdminTab === 'page' ? ' is-active' : ''; ?>">Page hero</a>
  <a href="<?php echo e(admin_url('gallery/index.php')); ?>" class="admin-subnav-link<?php echo $galleryAdminTab === 'slots' ? ' is-active' : ''; ?>">Gallery slots</a>
</nav>
