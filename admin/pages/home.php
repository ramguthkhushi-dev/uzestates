<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/homepage.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$error   = null;
$success = flash_get('success');

/**
 * @param array<string, mixed> $file
 */
function homepage_admin_upload(string $field, string $subdir, ?string $existing): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return $existing;
    }

    $upload = handle_file_upload($_FILES[$field], $subdir, ['jpg', 'jpeg', 'png', 'webp']);
    if ($upload['error']) {
        throw new RuntimeException($upload['error']);
    }

    return $upload['path'] ?? $existing;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session.';
    } else {
        try {
            $existing = settings_homepage();
            $current  = homepage_content($existing);

            $heroImage = homepage_admin_upload('hero_image_file', 'homepage', $current['hero']['image']);

            $matchTabs = [];
            foreach ($current['match']['tabs'] as $index => $tab) {
                $posted = is_array($_POST['match_tabs'][$index] ?? null) ? $_POST['match_tabs'][$index] : [];
                $image  = trim((string) ($posted['image'] ?? '')) ?: $tab['image'];
                $image  = homepage_admin_upload('match_image_' . $index, 'homepage', $image);

                $matchTabs[] = [
                    'key'   => $tab['key'],
                    'label' => trim((string) ($posted['label'] ?? '')) ?: $tab['label'],
                    'title' => trim((string) ($posted['title'] ?? '')) ?: $tab['title'],
                    'text'  => trim((string) ($posted['text'] ?? '')) ?: $tab['text'],
                    'cta'   => trim((string) ($posted['cta'] ?? '')) ?: $tab['cta'],
                    'href'  => trim((string) ($posted['href'] ?? '')) ?: $tab['href'],
                    'image' => $image,
                ];
            }

            $matchJson = json_encode([
                'kicker'         => trim((string) ($_POST['match_kicker'] ?? '')),
                'title'          => trim((string) ($_POST['match_title'] ?? '')),
                'title_emphasis' => trim((string) ($_POST['match_title_emphasis'] ?? '')),
                'lead'           => trim((string) ($_POST['match_lead'] ?? '')),
                'tabs'           => homepage_normalize_match_tabs($matchTabs),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $lifestyleTiles = [];
            foreach ($current['lifestyle']['tiles'] as $index => $tile) {
                $posted = is_array($_POST['lifestyle_tiles'][$index] ?? null) ? $_POST['lifestyle_tiles'][$index] : [];
                $image  = trim((string) ($posted['image'] ?? '')) ?: $tile['image'];
                $image  = homepage_admin_upload('lifestyle_image_' . $index, 'homepage', $image);

                $lifestyleTiles[] = [
                    'label'    => trim((string) ($posted['label'] ?? '')) ?: $tile['label'],
                    'subtitle' => trim((string) ($posted['subtitle'] ?? '')) ?: $tile['subtitle'],
                    'image'    => $image,
                ];
            }

            $lifestyleJson = json_encode([
                'kicker'   => trim((string) ($_POST['lifestyle_kicker'] ?? '')),
                'title'    => trim((string) ($_POST['lifestyle_title'] ?? '')),
                'cta_text' => trim((string) ($_POST['lifestyle_cta_text'] ?? '')),
                'cta_url'  => trim((string) ($_POST['lifestyle_cta_url'] ?? '')),
                'tiles'    => homepage_normalize_lifestyle_tiles($lifestyleTiles),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $contactImage = trim((string) ($_POST['contact_whatsapp_image'] ?? '')) ?: $current['contact']['whatsapp_image'];
            $contactImage = homepage_admin_upload('contact_whatsapp_image_file', 'homepage', $contactImage);

            $contactJson = json_encode([
                'kicker'         => trim((string) ($_POST['contact_kicker'] ?? '')),
                'title'          => trim((string) ($_POST['contact_title'] ?? '')),
                'lead'           => trim((string) ($_POST['contact_lead'] ?? '')),
                'whatsapp_image' => $contactImage,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            settings_save_homepage([
                'hero_kicker'         => $_POST['hero_kicker'] ?? '',
                'hero_title'          => $_POST['hero_title'] ?? '',
                'hero_subtitle'       => $_POST['hero_subtitle'] ?? '',
                'hero_image'          => $heroImage,
                'cta_text'            => $_POST['cta_text'] ?? '',
                'hero_cta_url'        => $_POST['hero_cta_url'] ?? '',
                'hero_secondary_text' => $_POST['hero_secondary_text'] ?? '',
                'hero_secondary_url'  => $_POST['hero_secondary_url'] ?? '',
                'match_json'          => $matchJson,
                'lifestyle_json'      => $lifestyleJson,
                'contact_json'        => $contactJson,
            ]);

            flash_set('success', 'Homepage saved.');
            header('Location: ' . admin_url('pages/home.php'));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$content = homepage_content(settings_homepage());
$hero    = $content['hero'];
$match   = $content['match'];
$life    = $content['lifestyle'];
$contact = $content['contact'];

/**
 * Compact image upload row with small thumbnail preview.
 */
function homepage_admin_image_row(string $inputName, ?string $imagePath, string $label, bool $hero = false): void
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
          <img src="<?php echo e(homepage_asset_url($imagePath)); ?>" alt="" />
        </div>
      <?php endif; ?>
    </div>
    <?php
}

admin_render('Home Page', 'pages/home.php', static function () use ($hero, $match, $life, $contact, $error, $success): void {
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <?php admin_view_site_link('home.php'); ?>

    <form method="post" enctype="multipart/form-data" class="admin-form homepage-admin">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />

      <section class="panel">
        <header class="hp-section-head">
          <h2>Hero</h2>
          <p class="panel-text">Headline, buttons and background image.</p>
        </header>
        <div class="hp-blocks">
          <div class="hp-block">
            <h3 class="hp-block-title">Copy</h3>
            <div class="form-grid">
              <label class="form-field"><span>Kicker</span><input type="text" name="hero_kicker" value="<?php echo e($hero['kicker']); ?>" /></label>
              <label class="form-field span-2"><span>Headline</span><input type="text" name="hero_title" value="<?php echo e($hero['title']); ?>" /></label>
              <label class="form-field span-2"><span>Subtitle</span><textarea name="hero_subtitle" rows="2"><?php echo e($hero['subtitle']); ?></textarea></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">Buttons</h3>
            <div class="form-grid">
              <label class="form-field"><span>Primary button text</span><input type="text" name="cta_text" value="<?php echo e($hero['cta_text']); ?>" /></label>
              <label class="form-field"><span>Primary button link</span><input type="text" name="hero_cta_url" value="<?php echo e($hero['cta_url']); ?>" placeholder="properties.php" /></label>
              <label class="form-field"><span>Secondary button text</span><input type="text" name="hero_secondary_text" value="<?php echo e($hero['secondary_text']); ?>" /></label>
              <label class="form-field"><span>Secondary button link</span><input type="text" name="hero_secondary_url" value="<?php echo e($hero['secondary_url']); ?>" placeholder="about.php" /></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">Background</h3>
            <?php homepage_admin_image_row('hero_image_file', $hero['image'], 'Upload image', true); ?>
          </div>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Find your perfect match</h2>
          <p class="panel-text">Section intro and four property paths.</p>
        </header>
        <div class="hp-block">
          <h3 class="hp-block-title">Section intro</h3>
          <div class="form-grid">
            <label class="form-field"><span>Kicker</span><input type="text" name="match_kicker" value="<?php echo e($match['kicker']); ?>" /></label>
            <label class="form-field"><span>Title line 1</span><input type="text" name="match_title" value="<?php echo e($match['title']); ?>" /></label>
            <label class="form-field"><span>Title line 2</span><input type="text" name="match_title_emphasis" value="<?php echo e($match['title_emphasis']); ?>" /></label>
            <label class="form-field span-2"><span>Lead text</span><textarea name="match_lead" rows="2"><?php echo e($match['lead']); ?></textarea></label>
          </div>
        </div>
        <div class="hp-cards">
          <?php foreach ($match['tabs'] as $index => $tab): ?>
            <details class="hp-card">
              <summary><?php echo e($tab['label']); ?> <span class="hp-card-meta">(<?php echo e($tab['key']); ?>)</span></summary>
              <div class="hp-card-body">
                <input type="hidden" name="match_tabs[<?php echo $index; ?>][key]" value="<?php echo e($tab['key']); ?>" />
                <input type="hidden" name="match_tabs[<?php echo $index; ?>][image]" value="<?php echo e($tab['image']); ?>" />
                <div class="form-grid">
                  <label class="form-field"><span>Tab label</span><input type="text" name="match_tabs[<?php echo $index; ?>][label]" value="<?php echo e($tab['label']); ?>" /></label>
                  <label class="form-field"><span>Panel title</span><input type="text" name="match_tabs[<?php echo $index; ?>][title]" value="<?php echo e($tab['title']); ?>" /></label>
                  <label class="form-field span-2"><span>Panel text</span><textarea name="match_tabs[<?php echo $index; ?>][text]" rows="2"><?php echo e($tab['text']); ?></textarea></label>
                  <label class="form-field"><span>Button text</span><input type="text" name="match_tabs[<?php echo $index; ?>][cta]" value="<?php echo e($tab['cta']); ?>" /></label>
                  <label class="form-field"><span>Button link</span><input type="text" name="match_tabs[<?php echo $index; ?>][href]" value="<?php echo e($tab['href']); ?>" /></label>
                  <div class="form-field span-2">
                    <?php homepage_admin_image_row('match_image_' . $index, $tab['image'], 'Background image'); ?>
                  </div>
                </div>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Lifestyle</h2>
          <p class="panel-text">Green section with four hover tiles.</p>
        </header>
        <div class="hp-block">
          <h3 class="hp-block-title">Section intro</h3>
          <div class="form-grid">
            <label class="form-field"><span>Kicker</span><input type="text" name="lifestyle_kicker" value="<?php echo e($life['kicker']); ?>" /></label>
            <label class="form-field"><span>Button text</span><input type="text" name="lifestyle_cta_text" value="<?php echo e($life['cta_text']); ?>" /></label>
            <label class="form-field span-2"><span>Headline</span><input type="text" name="lifestyle_title" value="<?php echo e($life['title']); ?>" /></label>
            <label class="form-field span-2"><span>Button link</span><input type="text" name="lifestyle_cta_url" value="<?php echo e($life['cta_url']); ?>" placeholder="gallery.php" /></label>
          </div>
        </div>
        <div class="hp-cards">
          <?php foreach ($life['tiles'] as $index => $tile): ?>
            <details class="hp-card">
              <summary><?php echo e($tile['label']); ?></summary>
              <div class="hp-card-body">
                <input type="hidden" name="lifestyle_tiles[<?php echo $index; ?>][image]" value="<?php echo e($tile['image']); ?>" />
                <div class="form-grid">
                  <label class="form-field"><span>Title</span><input type="text" name="lifestyle_tiles[<?php echo $index; ?>][label]" value="<?php echo e($tile['label']); ?>" /></label>
                  <label class="form-field"><span>Hover subtitle</span><input type="text" name="lifestyle_tiles[<?php echo $index; ?>][subtitle]" value="<?php echo e($tile['subtitle']); ?>" /></label>
                  <div class="form-field span-2">
                    <?php homepage_admin_image_row('lifestyle_image_' . $index, $tile['image'], 'Tile image'); ?>
                  </div>
                </div>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Contact teaser</h2>
          <p class="panel-text">Intro copy and WhatsApp image. Phone and email live under Contact.</p>
        </header>
        <div class="hp-blocks">
          <div class="hp-block">
            <h3 class="hp-block-title">Copy</h3>
            <div class="form-grid">
              <label class="form-field"><span>Kicker</span><input type="text" name="contact_kicker" value="<?php echo e($contact['kicker']); ?>" /></label>
              <label class="form-field span-2"><span>Headline</span><input type="text" name="contact_title" value="<?php echo e($contact['title']); ?>" /></label>
              <label class="form-field span-2"><span>Lead text</span><textarea name="contact_lead" rows="2"><?php echo e($contact['lead']); ?></textarea></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">WhatsApp panel</h3>
            <input type="hidden" name="contact_whatsapp_image" value="<?php echo e($contact['whatsapp_image']); ?>" />
            <?php homepage_admin_image_row('contact_whatsapp_image_file', $contact['whatsapp_image'], 'Panel image'); ?>
          </div>
        </div>
      </section>

      <div class="hp-form-footer">
        <button type="submit" class="btn btn-primary homepage-admin-save">Save homepage</button>
      </div>
    </form>
    <?php
});
