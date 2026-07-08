<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/gallery-page.php';
require_once __DIR__ . '/../../includes/homepage.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$error   = null;
$success = flash_get('success');
$galleryAdminTab = 'page';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session.';
    } else {
        try {
            $existing = settings_gallery_page();
            $current  = gallery_page_content($existing);
            $heroImage = $current['image'];

            if (!empty($_FILES['hero_image_file']['name'])) {
                $upload = handle_file_upload($_FILES['hero_image_file'], 'gallery-page', ['jpg', 'jpeg', 'png', 'webp']);
                if ($upload['error']) {
                    throw new RuntimeException($upload['error']);
                }
                if ($upload['path']) {
                    $heroImage = $upload['path'];
                }
            }

            settings_save_gallery_page([
                'hero_kicker'   => $_POST['hero_kicker'] ?? '',
                'hero_title'    => $_POST['hero_title'] ?? '',
                'hero_lead'     => $_POST['hero_lead'] ?? '',
                'hero_btn_text' => $_POST['hero_btn_text'] ?? '',
                'hero_btn_url'  => $_POST['hero_btn_url'] ?? '',
                'hero_image'    => $heroImage,
            ]);

            flash_set('success', 'Gallery page saved.');
            header('Location: ' . admin_url('gallery/page.php'));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$hero = gallery_page_content(settings_gallery_page());

admin_render('Gallery Page', 'gallery', static function () use ($hero, $error, $success): void {
    global $galleryAdminTab;
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <?php admin_view_site_link('gallery.php'); ?>

    <?php require __DIR__ . '/_tabs.php'; ?>

    <form method="post" enctype="multipart/form-data" class="admin-form homepage-admin">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />

      <section class="panel">
        <header class="hp-section-head">
          <h2>Hero</h2>
          <p class="panel-text">Hero banner at the top of the gallery page.</p>
        </header>
        <div class="hp-blocks">
          <div class="hp-block">
            <h3 class="hp-block-title">Copy</h3>
            <div class="form-grid">
              <label class="form-field"><span>Kicker</span><input type="text" name="hero_kicker" value="<?php echo e($hero['kicker']); ?>" /></label>
              <label class="form-field span-2"><span>Headline</span><input type="text" name="hero_title" value="<?php echo e($hero['title']); ?>" /></label>
              <label class="form-field span-2"><span>Lead text</span><textarea name="hero_lead" rows="3"><?php echo e($hero['lead']); ?></textarea></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">Button</h3>
            <div class="form-grid">
              <label class="form-field"><span>Button text</span><input type="text" name="hero_btn_text" value="<?php echo e($hero['btn_text']); ?>" /></label>
              <label class="form-field"><span>Button link</span><input type="text" name="hero_btn_url" value="<?php echo e($hero['btn_url']); ?>" placeholder="properties.php" /></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">Background</h3>
            <div class="hp-image-field">
              <label class="form-field">
                <span>Upload image</span>
                <input type="file" name="hero_image_file" accept="image/*" />
              </label>
              <?php if (!empty($hero['image'])): ?>
                <div class="hp-image-thumb hp-image-thumb--hero">
                  <img src="<?php echo e(gallery_page_asset_url($hero['image'])); ?>" alt="" />
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <div class="hp-form-footer">
        <button type="submit" class="btn btn-primary homepage-admin-save">Save gallery page</button>
      </div>
    </form>
    <?php
});
