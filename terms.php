<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/legal-page.php';

start_session();

$page = legal_page_content('terms');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo e($page['title']); ?> | <?php echo e(APP_NAME); ?></title>
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/style.css" />
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/header-nav.css" />
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/legal.css?v=<?php echo (int) @filemtime(__DIR__ . '/css/legal.css'); ?>" />
</head>
<body>

<?php require __DIR__ . '/includes/site-header.php'; ?>

<main class="legal-page">
  <div class="legal-shell">
    <h1><?php echo e($page['title']); ?></h1>
    <div class="legal-body">
      <?php legal_render_body($page['body']); ?>
    </div>
    <p class="legal-back"><a href="<?php echo e(BASE_URL); ?>/home.php">← Back to home</a></p>
  </div>
</main>

<?php require __DIR__ . '/includes/site-footer.php'; ?>
<script src="<?php echo e(BASE_URL); ?>/js/script.js"></script>
</body>
</html>
