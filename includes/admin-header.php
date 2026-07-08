<?php

declare(strict_types=1);

/** @var string $adminPageTitle */
/** @var string $adminNavKey */

$adminPageTitle = $adminPageTitle ?? 'Admin';
$adminNavKey    = $adminNavKey ?? 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo e($adminPageTitle); ?> | <?php echo e(APP_NAME); ?> Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/admin/assets/admin.css?v=<?php echo (int) @filemtime(__DIR__ . '/../admin/assets/admin.css'); ?>" />
</head>
<body class="admin-body">

<div class="admin-layout">
  <?php require __DIR__ . '/admin-sidebar.php'; ?>

  <div class="admin-main">
    <header class="admin-topbar">
      <div class="topbar-start">
        <button type="button" class="sidebar-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="admin-sidebar">
          <span class="sidebar-toggle-bar"></span>
          <span class="sidebar-toggle-bar"></span>
          <span class="sidebar-toggle-bar"></span>
        </button>
        <h1><?php echo e($adminPageTitle); ?></h1>
      </div>
      <a href="<?php echo e(BASE_URL); ?>/home.php" target="_blank" rel="noopener" class="btn btn-outline btn-sm topbar-view-site">
        <span class="topbar-view-site-label">View live site</span>
        <span class="topbar-view-site-icon" aria-hidden="true">↗</span>
      </a>
    </header>

    <main class="admin-content">
