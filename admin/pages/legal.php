<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/settings.php';

require_admin();

$error   = null;
$success = flash_get('success');
$legal   = settings_legal();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session. Please try again.';
    } else {
        try {
            settings_save_legal($_POST);
            flash_set('success', 'Legal pages updated.');
            header('Location: ' . admin_url('pages/legal.php'));
            exit;
        } catch (Throwable $e) {
            $error = 'Could not save. Run database/migrate_optional.php if this is the first time.';
        }
    }
}

admin_render('Legal pages', 'pages/legal.php', static function () use ($legal, $error, $success): void {
    admin_back_link('Dashboard', 'dashboard.php');
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

    <div class="panel">
      <div class="panel-toolbar">
        <h2>Privacy &amp; terms</h2>
        <span class="panel-meta">
          <a href="<?php echo e(BASE_URL); ?>/privacy.php" target="_blank" rel="noopener">View privacy ↗</a>
          ·
          <a href="<?php echo e(BASE_URL); ?>/terms.php" target="_blank" rel="noopener">View terms ↗</a>
        </span>
      </div>

      <form method="post" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />

        <label class="form-field">
          <span>Privacy page title</span>
          <input type="text" name="privacy_title" maxlength="255" value="<?php echo e($legal['privacy_title'] ?? 'Privacy Policy'); ?>" />
        </label>
        <label class="form-field">
          <span>Privacy content</span>
          <textarea name="privacy_body" rows="10"><?php echo e($legal['privacy_body'] ?? ''); ?></textarea>
        </label>

        <label class="form-field">
          <span>Terms page title</span>
          <input type="text" name="terms_title" maxlength="255" value="<?php echo e($legal['terms_title'] ?? 'Terms & Conditions'); ?>" />
        </label>
        <label class="form-field">
          <span>Terms content</span>
          <textarea name="terms_body" rows="10"><?php echo e($legal['terms_body'] ?? ''); ?></textarea>
        </label>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
    <?php
});
