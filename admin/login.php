<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$error = flash_get('auth_error');
$success = flash_get('auth_success');
$oldEmail = $_SESSION['auth_old']['email'] ?? '';
unset($_SESSION['auth_old']);

if (admin_logged_in()) {
    header('Location: ' . admin_url('dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $result = attempt_login(
            trim($_POST['email'] ?? ''),
            $_POST['password'] ?? '',
            !empty($_POST['remember_me'])
        );

        if ($result['success']) {
            header('Location: ' . admin_url('dashboard.php'));
            exit;
        }

        $error = $result['error'];
        $oldEmail = trim($_POST['email'] ?? '');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | <?php echo e(APP_NAME); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?php echo e(admin_auth_stylesheet_url()); ?>" />
</head>
<body class="admin-auth-body">

<div class="admin-auth-shell">
  <?php
  $authAsideTagline = 'Manage properties, content, and enquiries from one place.';
  require __DIR__ . '/includes/auth-aside.php';
  ?>

  <main class="admin-auth-main">
    <div class="admin-auth-panel">
      <header class="admin-auth-head">
        <h2>Sign in</h2>
        <p>Enter your credentials to access the dashboard.</p>
      </header>

      <?php if ($success): ?>
        <div class="alert alert-success" role="status"><?php echo e($success); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
      <?php endif; ?>

      <form method="post" class="admin-auth-form" id="adminLoginForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />

        <label class="form-field" for="login_email">
          <span>Username or email</span>
          <input type="text" name="email" id="login_email" required autocomplete="username" placeholder="admin or you@example.com" value="<?php echo e($oldEmail); ?>" data-validate="login-email" />
          <small class="admin-field-error" data-error-for="login-email"></small>
        </label>

        <?php
          $label = 'Password';
          $name = 'password';
          $errorKey = 'login-password';
          $inputAttrs = [
              'id'               => 'login_password',
              'required'         => true,
              'autocomplete'     => 'current-password',
              'placeholder'      => 'Your password',
              'data-validate'    => 'login-password',
          ];
          require __DIR__ . '/includes/auth-password-field.php';
        ?>

        <div class="admin-auth-options">
          <label class="form-check">
            <input type="checkbox" name="remember_me" value="1" />
            <span>Remember me</span>
          </label>
          <a class="admin-auth-link" href="<?php echo e(admin_url('forgot-password.php')); ?>">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-block admin-auth-submit">Log in</button>
      </form>

      <footer class="admin-auth-foot">
        <a href="<?php echo e(BASE_URL); ?>/home.php">← Back to website</a>
      </footer>
    </div>
  </main>
</div>

<script src="<?php echo e(admin_auth_script_url()); ?>" defer></script>
</body>
</html>
