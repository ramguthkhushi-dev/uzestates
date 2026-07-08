<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

require_admin();

$admins  = admin_list_all();
$success = flash_get('success');
$error   = flash_get('error');

admin_render('Admin users', 'account', static function () use ($admins, $success, $error): void {
    admin_back_link('My account', 'account.php');
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

    <div class="panel">
      <div class="panel-toolbar">
        <h2>Admin users</h2>
        <a href="<?php echo e(admin_url('admins/add.php')); ?>" class="btn btn-primary btn-sm">+ Add admin</a>
      </div>
      <p class="panel-text">Each admin can sign in with their username or email and manage the full site.</p>
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Last login</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($admins as $row): ?>
              <tr>
                <td><?php echo e($row['full_name'] ?: '—'); ?></td>
                <td><?php echo e($row['username']); ?></td>
                <td><?php echo e($row['email']); ?></td>
                <td><?php echo !empty($row['last_login_at']) ? e($row['last_login_at']) : '—'; ?></td>
                <td><?php echo !empty($row['is_active']) ? 'Active' : 'Inactive'; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
});
