<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_admin();

$admin = current_admin();
if (!$admin) {
    header('Location: ' . admin_url('login.php'));
    exit;
}

$error   = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $action = $_POST['action'] ?? 'profile';

        if ($action === 'password') {
            $result = admin_change_password(
                (int) $admin['id'],
                $_POST['current_password'] ?? '',
                $_POST['new_password'] ?? '',
                $_POST['confirm_password'] ?? ''
            );
        } else {
            $result = admin_update_profile(
                (int) $admin['id'],
                $_POST['full_name'] ?? '',
                $_POST['email'] ?? '',
                $_POST['username'] ?? ''
            );
        }

        if ($result['success']) {
            $success = $action === 'password'
                ? 'Password updated.'
                : 'Account details saved.';
            $admin = current_admin();
        } else {
            $error = $result['error'];
        }
    }
}

$adminExtraScripts = [admin_auth_script_url()];

admin_render('My account', 'account', static function () use ($admin, $error, $success): void {
    admin_back_link('Dashboard', 'dashboard.php');
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

    <div class="panel">
      <div class="panel-toolbar"><h2>Profile</h2></div>
      <form method="post" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <input type="hidden" name="action" value="profile" />
        <label class="form-field">
          <span>Full name</span>
          <input type="text" name="full_name" required maxlength="100" value="<?php echo e($admin['name'] ?? ''); ?>" />
        </label>
        <label class="form-field">
          <span>Username</span>
          <input type="text" name="username" required maxlength="50" value="<?php echo e($admin['username'] ?? ''); ?>" />
        </label>
        <label class="form-field">
          <span>Email</span>
          <input type="email" name="email" required maxlength="150" value="<?php echo e($admin['email'] ?? ''); ?>" />
        </label>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save profile</button>
        </div>
      </form>
    </div>

    <div class="panel">
      <div class="panel-toolbar">
        <h2>Admin users</h2>
        <a href="<?php echo e(admin_url('admins/add.php')); ?>" class="btn btn-outline btn-sm">+ Add admin</a>
      </div>
      <p class="panel-text">Manage who can access this dashboard. <a href="<?php echo e(admin_url('admins/index.php')); ?>">View all admin accounts</a>.</p>
    </div>

    <div class="panel">
      <div class="panel-toolbar"><h2>Change password</h2></div>
      <form method="post" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <input type="hidden" name="action" value="password" />
        <?php
          $label = 'Current password';
          $name = 'current_password';
          $errorKey = null;
          $inputAttrs = [
              'required'     => true,
              'autocomplete' => 'current-password',
          ];
          require __DIR__ . '/includes/auth-password-field.php';

          $label = 'New password';
          $name = 'new_password';
          $errorKey = null;
          $inputAttrs = [
              'required'     => true,
              'minlength'    => 8,
              'maxlength'    => 128,
              'autocomplete' => 'new-password',
          ];
          require __DIR__ . '/includes/auth-password-field.php';

          $label = 'Confirm new password';
          $name = 'confirm_password';
          $errorKey = null;
          $inputAttrs = [
              'required'     => true,
              'minlength'    => 8,
              'maxlength'    => 128,
              'autocomplete' => 'new-password',
          ];
          require __DIR__ . '/includes/auth-password-field.php';
        ?>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Update password</button>
        </div>
      </form>
    </div>
    <?php
});
