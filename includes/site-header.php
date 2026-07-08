<?php

declare(strict_types=1);

$currentPath = $_SERVER['PHP_SELF'] ?? '';
$onHome        = str_contains($currentPath, '/home.php') || str_contains($currentPath, '/index.php');
$onProperties  = str_contains($currentPath, '/properties.php') || str_contains($currentPath, '/property-details.php');
$onGallery     = str_contains($currentPath, '/gallery.php');
$onAbout       = str_contains($currentPath, '/about.php');
$onContact     = str_contains($currentPath, '/contact.php');
$headerVariant = $headerVariant ?? 'default';
?>
<header class="site-header<?php echo $headerVariant === 'hero' ? ' site-header-hero' : ''; ?>" data-site-header>
  <div class="header-inner">
    <a href="<?php echo e(BASE_URL); ?>/home.php" class="brand">
      <span class="brand-shine" aria-hidden="true"></span>
      <span class="brand-name" data-brand-name>
        <span class="brand-part brand-part-uz">UZ</span>
        <span class="brand-part brand-part-estates">Estates</span>
      </span>
    </a>

    <nav class="nav-links" id="navLinks" aria-label="Main">
      <a href="<?php echo e(BASE_URL); ?>/home.php" class="nav-link<?php echo $onHome ? ' is-active' : ''; ?>"><span>Home</span></a>
      <a href="<?php echo e(BASE_URL); ?>/properties.php" class="nav-link<?php echo $onProperties ? ' is-active' : ''; ?>"><span>Properties</span></a>
      <a href="<?php echo e(BASE_URL); ?>/gallery.php" class="nav-link<?php echo $onGallery ? ' is-active' : ''; ?>"><span>Gallery</span></a>
      <a href="<?php echo e(BASE_URL); ?>/about.php" class="nav-link<?php echo $onAbout ? ' is-active' : ''; ?>"><span>About</span></a>
      <a href="<?php echo e(BASE_URL); ?>/contact.php" class="nav-link<?php echo $onContact ? ' is-active' : ''; ?>"><span>Contact</span></a>
    </nav>

    <div class="header-actions"></div>

    <button type="button" class="menu-button" id="menuButton" aria-label="Open menu" aria-expanded="false" aria-controls="navLinks">
      <span class="menu-icon" aria-hidden="true"><span></span><span></span></span>
      Menu
    </button>
  </div>
</header>
