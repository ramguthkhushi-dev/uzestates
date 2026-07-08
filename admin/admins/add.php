<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

require_admin();

$error = null;
$old   = [
    'full_name' => '',
    'username'  => '',
    'email'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $old = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username'  => trim($_POST['username'] ?? ''),
            'email'     => trim($_POST['email'] ?? ''),
        ];

        $result = admin_create_user(
            $old['full_name'],
            $old['email'],
            $old['username'],
            $_POST['password'] ?? '',
            $_POST['confirm_password'] ?? ''
        );

        if ($result['success']) {
            flash_set('success', 'Admin account created for ' . $old['full_name'] . '.');
            header('Location: ' . admin_url('admins/index.php'));
            exit;
        }

        $error = $result['error'];
    }
}

$adminExtraScripts = [admin_auth_script_url()];

admin_render('Add admin', 'account', static function () use ($error, $old): void {
    admin_back_link('Admin users', 'admins/index.php');
    ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

    <div class="panel">
      <div class="panel-toolbar"><h2>New admin account</h2></div>
      <p class="panel-text">Create a login for your colleague. They can change their password later under My account.</p>
      <form method="post" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
        <div class="form-grid">
          <label class="form-field span-2">
            <span>Full name *</span>
            <input type="text" name="full_name" required maxlength="100" value="<?php echo e($old['full_name']); ?>" />
          </label>
          <label class="form-field">
            <span>Username *</span>
            <input type="text" name="username" required maxlength="50" pattern="[A-Za-z0-9._-]+" autocomplete="username" value="<?php echo e($old['username']); ?>" />
          </label>
          <label class="form-field">
            <span>Email *</span>
            <input type="email" name="email" required maxlength="150" autocomplete="email" value="<?php echo e($old['email']); ?>" />
          </label>
          <?php
            $label = 'Password *';
            $name = 'password';
            $errorKey = null;
            $inputAttrs = [
                'required'     => true,
                'minlength'    => 8,
                'maxlength'    => 128,
                'autocomplete' => 'new-password',
            ];
            require __DIR__ . '/../includes/auth-password-field.php';

            $label = 'Confirm password *';
            $name = 'confirm_password';
            $errorKey = null;
            $inputAttrs = [
                'required'     => true,
                'minlength'    => 8,
                'maxlength'    => 128,
                'autocomplete' => 'new-password',
            ];
            require __DIR__ . '/../includes/auth-password-field.php';
          ?>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Create admin</button>
          <a href="<?php echo e(admin_url('admins/index.php')); ?>" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
    <?php
});
