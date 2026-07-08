<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$error   = null;
$message = null;
$devUrl  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $result = attempt_forgot_password(trim($_POST['email'] ?? ''));
        if ($result['success']) {
            $message = $result['message'];
            $devUrl  = $result['dev_reset_url'];
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot password | <?php echo e(APP_NAME); ?> Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?php echo e(admin_auth_stylesheet_url()); ?>" />
</head>
<body class="admin-auth-body">

<div class="admin-auth-shell">
  <?php
  $authAsideTagline = 'We will send a secure link to reset your password.';
  require __DIR__ . '/includes/auth-aside.php';
  ?>

  <main class="admin-auth-main">
    <div class="admin-auth-panel">
      <header class="admin-auth-head">
        <h2>Reset password</h2>
        <p>Enter your admin email and we will send you a reset link.</p>
      </header>

      <?php if ($message): ?>
        <div class="alert alert-success" role="status">
          <?php echo e($message); ?>
          <?php if ($devUrl): ?><br /><a href="<?php echo e($devUrl); ?>">Dev reset link</a><?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
      <?php endif; ?>

      <form method="post" class="admin-auth-form" id="adminForgotForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <label class="form-field" for="forgot_email">
          <span>Email</span>
          <input type="email" name="email" id="forgot_email" required autocomplete="username" placeholder="you@example.com" data-validate="forgot-email" maxlength="150" />
          <small class="admin-field-error" data-error-for="forgot-email"></small>
        </label>
        <button type="submit" class="btn btn-primary btn-block admin-auth-submit">Send reset link</button>
      </form>

      <footer class="admin-auth-foot">
        <a href="<?php echo e(admin_url('login.php')); ?>">← Back to login</a>
      </footer>
    </div>
  </main>
</div>

<script src="<?php echo e(admin_auth_script_url()); ?>" defer></script>
</body>
</html>
