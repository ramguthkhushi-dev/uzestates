<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/contact-page.php';
require_once __DIR__ . '/../../includes/homepage.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$error   = null;
$success = flash_get('success');

/**
 * @param array<string, mixed> $file
 */
function contact_admin_upload(string $field, ?string $existing): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return $existing;
    }

    $upload = handle_file_upload($_FILES[$field], 'contact-page', ['jpg', 'jpeg', 'png', 'webp']);
    if ($upload['error']) {
        throw new RuntimeException($upload['error']);
    }

    return $upload['path'] ?? $existing;
}

function contact_admin_image_row(string $inputName, ?string $imagePath, string $label, bool $hero = false): void
{
    $thumbClass = $hero ? 'hp-image-thumb hp-image-thumb--hero' : 'hp-image-thumb';
    ?>
    <div class="hp-image-field">
      <label class="form-field">
        <span><?php echo e($label); ?></span>
        <input type="file" name="<?php echo e($inputName); ?>" accept="image/*" />
      </label>
      <?php if (!empty($imagePath)): ?>
        <div class="<?php echo e($thumbClass); ?>">
          <img src="<?php echo e(contact_page_asset_url($imagePath)); ?>" alt="" />
        </div>
      <?php endif; ?>
    </div>
    <?php
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session.';
    } else {
        try {
            settings_save_contact($_POST);

            $existing = settings_contact_page();
            $current  = contact_page_content($existing);
            $heroImage = contact_admin_upload('hero_image_file', $current['hero']['image']);

            settings_save_contact_page([
                'hero_kicker'      => $_POST['hero_kicker'] ?? '',
                'hero_title'       => $_POST['hero_title'] ?? '',
                'hero_image'       => $heroImage,
                'hero_image_alt'   => $_POST['hero_image_alt'] ?? '',
                'info_heading'     => $_POST['info_heading'] ?? '',
                'form_heading'     => $_POST['form_heading'] ?? '',
                'form_lead'        => $_POST['form_lead'] ?? '',
                'quick_chat_title' => $_POST['quick_chat_title'] ?? '',
                'quick_chat_text'  => $_POST['quick_chat_text'] ?? '',
            ]);

            flash_set('success', 'Contact settings saved.');
            header('Location: ' . admin_url('pages/contact.php'));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$c    = settings_contact();
$c['phone']    = phone_tel_local((string) ($c['phone'] ?? ''));
$c['whatsapp'] = phone_whatsapp_digits((string) ($c['whatsapp'] ?? ''));
$page = contact_page_content(settings_contact_page());
$hero = $page['hero'];

admin_render('Contact', 'pages/contact.php', static function () use ($c, $page, $hero, $error, $success): void {
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <?php admin_view_site_link('contact.php'); ?>

    <form method="post" enctype="multipart/form-data" class="admin-form homepage-admin">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />

      <section class="panel">
        <header class="hp-section-head">
          <h2>Site contact details</h2>
          <p class="panel-text">Phone, email and social links for the footer, contact page, and enquiries.</p>
        </header>
        <div class="form-grid">
          <label class="form-field"><span>Phone</span><input type="text" name="phone" value="<?php echo e($c['phone'] ?? ''); ?>" placeholder="58154042" /><small class="field-hint">Local or full number (58154042 or 23058154042). Displays on site as <?php echo e(format_phone_display((string) ($c['phone'] ?? ''))); ?>.</small></label>
          <label class="form-field"><span>WhatsApp</span><input type="text" name="whatsapp" value="<?php echo e($c['whatsapp'] ?? ''); ?>" placeholder="23058154042" /><small class="field-hint">Full international number with 230 prefix for WhatsApp links.</small></label>
          <label class="form-field span-2"><span>Email</span><input type="email" name="email" value="<?php echo e($c['email'] ?? ''); ?>" /></label>
          <label class="form-field span-2"><span>Business hours</span><input type="text" name="business_hours" value="<?php echo e($c['business_hours'] ?? ''); ?>" /></label>
          <label class="form-field span-2"><span>Facebook link</span><input type="url" name="facebook_link" value="<?php echo e($c['facebook_link'] ?? FACEBOOK_URL); ?>" /></label>
          <label class="form-field span-2"><span>TikTok link</span><input type="url" name="tiktok_link" value="<?php echo e($c['tiktok_link'] ?? ''); ?>" /></label>
          <label class="form-field span-2"><span>Instagram link</span><input type="url" name="instagram_link" value="<?php echo e($c['instagram_link'] ?? ''); ?>" /></label>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Page hero</h2>
          <p class="panel-text">Hero banner at the top of the page.</p>
        </header>
        <div class="hp-blocks">
          <div class="hp-block">
            <h3 class="hp-block-title">Copy</h3>
            <div class="form-grid">
              <label class="form-field"><span>Kicker</span><input type="text" name="hero_kicker" value="<?php echo e($hero['kicker']); ?>" /></label>
              <label class="form-field span-2"><span>Headline</span><input type="text" name="hero_title" value="<?php echo e($hero['title']); ?>" /></label>
              <label class="form-field span-2"><span>Image alt text</span><input type="text" name="hero_image_alt" value="<?php echo e($hero['image_alt']); ?>" /></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">Photo</h3>
            <?php contact_admin_image_row('hero_image_file', $hero['image'], 'Upload hero image', true); ?>
          </div>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Contact information block</h2>
          <p class="panel-text">Left column. Phone, WhatsApp, email and hours sync from site contact details above.</p>
        </header>
        <label class="form-field"><span>Section heading</span><input type="text" name="info_heading" value="<?php echo e($page['info']['heading']); ?>" /></label>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Enquiry form</h2>
          <p class="panel-text">Enquiry form in the right column.</p>
        </header>
        <div class="form-grid">
          <label class="form-field span-2"><span>Heading</span><input type="text" name="form_heading" value="<?php echo e($page['form']['heading']); ?>" /></label>
          <label class="form-field span-2"><span>Lead text</span><textarea name="form_lead" rows="2"><?php echo e($page['form']['lead']); ?></textarea></label>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Quick chat bar</h2>
          <p class="panel-text">Bottom strip. Phone syncs from site contact details.</p>
        </header>
        <div class="form-grid">
          <label class="form-field"><span>Title</span><input type="text" name="quick_chat_title" value="<?php echo e($page['quick_chat']['title']); ?>" /></label>
          <label class="form-field"><span>Supporting text</span><input type="text" name="quick_chat_text" value="<?php echo e($page['quick_chat']['text']); ?>" /></label>
        </div>
      </section>

      <div class="hp-form-footer">
        <button type="submit" class="btn btn-primary homepage-admin-save">Save contact settings</button>
      </div>
    </form>
    <?php
});
