<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

redirect_if_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash_set('auth_error', 'Invalid session. Please try again.');
        header('Location: ' . BASE_URL . '/forgot-password.php');
        exit;
    }

    $result = attempt_forgot_password(trim($_POST['email'] ?? ''));

    if (!$result['success']) {
        $_SESSION['auth_old'] = ['email' => trim($_POST['email'] ?? '')];
        flash_set('auth_error', $result['error']);
        header('Location: ' . BASE_URL . '/forgot-password.php');
        exit;
    }

    flash_set('auth_success', $result['message']);
    if (!empty($result['dev_reset_url'])) {
        flash_set('dev_reset_url', $result['dev_reset_url']);
    }
    header('Location: ' . BASE_URL . '/forgot-password.php');
    exit;
}

$error   = flash_get('auth_error');
$success = flash_get('auth_success');
$devUrl  = flash_get('dev_reset_url');
$whatsapp = '23058154042';
$oldEmail = auth_form_data()['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password | <?php echo e(APP_NAME); ?></title>
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/style.css" />
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/header-nav.css" />
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/auth.css" />
</head>
<body class="auth-page">

<?php require __DIR__ . '/includes/site-header.php'; ?>

<div class="auth-standalone">
  <div class="auth-standalone-card">
    <h1>Forgot password?</h1>
    <p class="auth-standalone-lead">Enter your email address and we will send you a link to reset your password.</p>

    <?php if ($success): ?>
      <div class="auth-alert auth-alert-success"><?php echo e($success); ?></div>
      <?php if ($devUrl): ?>
        <div class="auth-alert auth-alert-info">
          <strong>Local development:</strong>
          <a href="<?php echo e($devUrl); ?>">Click here to reset your password</a>
          (email may not send on localhost).
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="auth-alert auth-alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
      <form method="post" class="auth-form" id="forgotForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />

        <label class="auth-field">
          <span>Email</span>
          <input type="email" name="email" required maxlength="150"
                 value="<?php echo e($oldEmail); ?>" data-validate="forgot-email" />
          <small class="auth-field-error" data-error-for="forgot-email"></small>
        </label>

        <button type="submit" class="auth-submit">Send reset link</button>
      </form>
    <?php endif; ?>

    <p class="auth-switch auth-standalone-switch">
      <a href="<?php echo e(admin_url('login.php')); ?>">Back to admin login</a>
    </p>
  </div>
</div>

<script src="<?php echo e(BASE_URL); ?>/js/script.js"></script>
<script src="<?php echo e(BASE_URL); ?>/js/auth-forms.js"></script>
</body>
</html>
