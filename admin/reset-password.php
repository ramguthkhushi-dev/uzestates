<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

redirect_if_logged_in();

$token = trim($_GET['token'] ?? '');
$error = flash_get('auth_error');
$success = flash_get('auth_success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('auth_error', 'Invalid session. Please try again.');
        header('Location: ' . admin_url('reset-password.php?token=' . urlencode($_POST['token'] ?? '')));
        exit;
    }

    $result = attempt_reset_password(
        trim($_POST['token'] ?? ''),
        $_POST['password'] ?? '',
        $_POST['confirm_password'] ?? ''
    );

    if (!$result['success']) {
        flash_set('auth_error', $result['error']);
        header('Location: ' . admin_url('reset-password.php?token=' . urlencode($_POST['token'] ?? '')));
        exit;
    }

    flash_set('auth_success', 'Your password has been updated. Please log in with your new password.');
    header('Location: ' . admin_url('login.php'));
    exit;
}

$tokenValid = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset password | <?php echo e(APP_NAME); ?> Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?php echo e(admin_auth_stylesheet_url()); ?>" />
</head>
<body class="admin-auth-body">

<div class="admin-auth-shell">
  <?php
  $authAsideTagline = 'Choose a new password for your admin account.';
  require __DIR__ . '/includes/auth-aside.php';
  ?>

  <main class="admin-auth-main">
    <div class="admin-auth-panel">
      <header class="admin-auth-head">
        <h2>Set new password</h2>
        <p>Your reset link is valid for 1 hour.</p>
      </header>

      <?php if ($success): ?>
        <div class="alert alert-success" role="status"><?php echo e($success); ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
      <?php endif; ?>

      <?php if (!$tokenValid): ?>
        <div class="alert alert-error" role="alert">Invalid or missing reset link. Please request a new one.</div>
        <footer class="admin-auth-foot">
          <a href="<?php echo e(admin_url('forgot-password.php')); ?>">Request reset link</a>
        </footer>
      <?php else: ?>
        <form method="post" class="admin-auth-form" id="adminResetForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
          <input type="hidden" name="token" value="<?php echo e($token); ?>" />
          <?php
            $label = 'New password';
            $name = 'password';
            $errorKey = 'reset-password';
            $inputAttrs = [
                'id'            => 'reset_password',
                'required'      => true,
                'minlength'     => 8,
                'maxlength'     => 128,
                'autocomplete'  => 'new-password',
                'data-validate' => 'reset-password',
            ];
            require __DIR__ . '/includes/auth-password-field.php';

            $label = 'Confirm new password';
            $name = 'confirm_password';
            $errorKey = 'reset-confirm';
            $inputAttrs = [
                'id'            => 'reset_confirm_password',
                'required'      => true,
                'minlength'     => 8,
                'maxlength'     => 128,
                'autocomplete'  => 'new-password',
                'data-validate' => 'reset-confirm',
            ];
            require __DIR__ . '/includes/auth-password-field.php';
          ?>
          <button type="submit" class="btn btn-primary btn-block admin-auth-submit">Update password</button>
        </form>
        <footer class="admin-auth-foot">
          <a href="<?php echo e(admin_url('login.php')); ?>">← Back to login</a>
        </footer>
      <?php endif; ?>
    </div>
  </main>
</div>

<script src="<?php echo e(admin_auth_script_url()); ?>" defer></script>
</body>
</html>
