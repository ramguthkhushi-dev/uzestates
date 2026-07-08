<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/properties-page.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$error   = null;
$success = flash_get('success');
$propertiesAdminTab = 'page';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session.';
    } else {
        try {
            $existing  = settings_properties_page();
            $current   = properties_page_content($existing);
            $heroImage = $current['image'];

            if (!empty($_FILES['hero_image_file']['name'])) {
                $upload = handle_file_upload($_FILES['hero_image_file'], 'properties-page', ['jpg', 'jpeg', 'png', 'webp']);
                if ($upload['error']) {
                    throw new RuntimeException($upload['error']);
                }
                if ($upload['path']) {
                    $heroImage = $upload['path'];
                }
            }

            settings_save_properties_page([
                'hero_kicker'    => $_POST['hero_kicker'] ?? '',
                'hero_title'     => $_POST['hero_title'] ?? '',
                'hero_lead'      => $_POST['hero_lead'] ?? '',
                'hero_image'     => $heroImage,
                'hero_image_alt' => $_POST['hero_image_alt'] ?? '',
            ]);

            flash_set('success', 'Properties page hero saved.');
            header('Location: ' . admin_url('properties/page.php'));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

try {
    $hero = properties_page_content(settings_properties_page());
} catch (Throwable $e) {
    $hero = properties_page_content([]);
}

admin_render('Properties Page Hero', 'properties', static function () use ($hero, $error, $success): void {
    global $propertiesAdminTab;
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <?php admin_view_site_link('properties.php'); ?>

    <div class="properties-admin-page">
    <?php require __DIR__ . '/_tabs.php'; ?>

    <form method="post" enctype="multipart/form-data" class="admin-form homepage-admin">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />

      <section class="panel">
        <header class="hp-section-head">
          <h2>Hero</h2>
          <p class="panel-text">
            Banner at the top of the public
            <a href="<?php echo e(BASE_URL); ?>/properties.php" target="_blank" rel="noopener">properties page</a>.
          </p>
        </header>
        <div class="hp-blocks">
          <div class="hp-block">
            <h3 class="hp-block-title">Copy</h3>
            <div class="form-grid">
              <label class="form-field"><span>Kicker</span><input type="text" name="hero_kicker" value="<?php echo e($hero['kicker']); ?>" /></label>
              <label class="form-field span-2"><span>Headline</span><input type="text" name="hero_title" value="<?php echo e($hero['title']); ?>" /></label>
              <label class="form-field span-2"><span>Lead text</span><textarea name="hero_lead" rows="3"><?php echo e($hero['lead']); ?></textarea></label>
              <label class="form-field span-2"><span>Background image alt text</span><input type="text" name="hero_image_alt" value="<?php echo e($hero['image_alt']); ?>" /></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">Background</h3>
            <div class="hp-image-field">
              <label class="form-field">
                <span>Upload image</span>
                <input type="file" name="hero_image_file" accept="image/*" />
              </label>
              <?php if ($hero['image'] !== ''): ?>
                <div class="hp-image-thumb hp-image-thumb--hero">
                  <img src="<?php echo e(properties_page_asset_url($hero['image'])); ?>" alt="" />
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <div class="hp-form-footer">
        <button type="submit" class="btn btn-primary homepage-admin-save">Save page hero</button>
      </div>
    </form>
    </div>
    <?php
});
