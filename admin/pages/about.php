<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/about-page.php';
require_once __DIR__ . '/../../includes/functions.php';

require_admin();

$error   = null;
$success = flash_get('success');

/**
 * @param array<string, mixed> $file
 */
function about_admin_upload(string $field, ?string $existing): ?string
{
    if (empty($_FILES[$field]['name'])) {
        return $existing;
    }

    $upload = handle_file_upload($_FILES[$field], 'about', ['jpg', 'jpeg', 'png', 'webp']);
    if ($upload['error']) {
        throw new RuntimeException($upload['error']);
    }

    return $upload['path'] ?? $existing;
}

function about_admin_image_row(string $inputName, ?string $imagePath, string $label, bool $hero = false): void
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
          <img src="<?php echo e(about_page_asset_url($imagePath)); ?>" alt="" />
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
            $existing = settings_about();
            $current  = about_page_content($existing);

            $heroImage = about_admin_upload('hero_image_file', $current['hero']['image']);
            $approachImage = about_admin_upload('approach_image_file', $current['approach']['image']);

            $storyJson = json_encode(about_page_normalize_story([
                'kicker'     => $_POST['story_kicker'] ?? '',
                'title'      => $_POST['story_title'] ?? '',
                'paragraphs' => $_POST['story_paragraphs'] ?? [],
            ]), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $valueItems = [];
            foreach ($current['values']['items'] as $index => $item) {
                $posted = is_array($_POST['values_items'][$index] ?? null) ? $_POST['values_items'][$index] : [];
                $valueItems[] = [
                    'title' => trim((string) ($posted['title'] ?? '')) ?: $item['title'],
                    'text'  => trim((string) ($posted['text'] ?? '')) ?: $item['text'],
                    'icon'  => trim((string) ($posted['icon'] ?? '')) ?: $item['icon'],
                ];
            }

            $valuesJson = json_encode([
                'kicker' => trim((string) ($_POST['values_kicker'] ?? '')),
                'items'  => about_page_normalize_values($valueItems),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $approachItems = [];
            foreach ($current['approach']['items'] as $index => $item) {
                $posted = is_array($_POST['approach_items'][$index] ?? null) ? $_POST['approach_items'][$index] : [];
                $approachItems[] = [
                    'key'   => $item['key'],
                    'label' => trim((string) ($posted['label'] ?? '')) ?: $item['label'],
                    'text'  => trim((string) ($posted['text'] ?? '')) ?: $item['text'],
                ];
            }

            $approachJson = json_encode([
                'kicker' => trim((string) ($_POST['approach_kicker'] ?? '')),
                'title'  => trim((string) ($_POST['approach_title'] ?? '')),
                'items'  => about_page_normalize_approach_items($approachItems),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $processSteps = [];
            foreach ($current['process']['steps'] as $index => $step) {
                $posted = is_array($_POST['process_steps'][$index] ?? null) ? $_POST['process_steps'][$index] : [];
                $processSteps[] = [
                    'title' => trim((string) ($posted['title'] ?? '')) ?: $step['title'],
                    'text'  => trim((string) ($posted['text'] ?? '')) ?: $step['text'],
                ];
            }

            $processJson = json_encode([
                'kicker' => trim((string) ($_POST['process_kicker'] ?? '')),
                'title'  => trim((string) ($_POST['process_title'] ?? '')),
                'steps'  => about_page_normalize_process_steps($processSteps),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $faqItems = [];
            foreach ($current['faq']['items'] as $index => $item) {
                $posted = is_array($_POST['faq_items'][$index] ?? null) ? $_POST['faq_items'][$index] : [];
                $faqItems[] = [
                    'q' => trim((string) ($posted['q'] ?? '')) ?: $item['q'],
                    'a' => trim((string) ($posted['a'] ?? '')) ?: $item['a'],
                ];
            }

            $faqJson = json_encode([
                'kicker' => trim((string) ($_POST['faq_kicker'] ?? '')),
                'title'  => trim((string) ($_POST['faq_title'] ?? '')),
                'items'  => about_page_normalize_faq_items($faqItems),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $ctaJson = json_encode([
                'text'     => trim((string) ($_POST['cta_text'] ?? '')),
                'btn_text' => trim((string) ($_POST['cta_btn_text'] ?? '')),
                'btn_url'  => trim((string) ($_POST['cta_btn_url'] ?? '')),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            settings_save_about([
                'hero_kicker'        => $_POST['hero_kicker'] ?? '',
                'hero_title'         => $_POST['hero_title'] ?? '',
                'hero_title_line2'   => $_POST['hero_title_line2'] ?? '',
                'hero_text'          => $_POST['hero_text'] ?? '',
                'hero_btn_text'      => $_POST['hero_btn_text'] ?? '',
                'hero_btn_url'       => $_POST['hero_btn_url'] ?? '',
                'hero_image'         => $heroImage,
                'hero_image_alt'     => $_POST['hero_image_alt'] ?? '',
                'approach_image'     => $approachImage,
                'approach_image_alt' => $_POST['approach_image_alt'] ?? '',
                'story_json'         => $storyJson,
                'values_json'        => $valuesJson,
                'approach_json'      => $approachJson,
                'process_json'       => $processJson,
                'faq_json'           => $faqJson,
                'cta_json'           => $ctaJson,
            ]);

            flash_set('success', 'About page saved.');
            header('Location: ' . admin_url('pages/about.php'));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$content = about_page_content(settings_about());
$hero    = $content['hero'];
$story   = $content['story'];
$values  = $content['values'];
$approach = $content['approach'];
$process = $content['process'];
$faq     = $content['faq'];
$cta     = $content['cta'];

$valueIcons = [
    'shield' => 'Shield',
    'person' => 'People',
    'pin'    => 'Location pin',
    'heart'  => 'Heart',
];

admin_render('About Page', 'pages/about.php', static function () use (
    $hero,
    $story,
    $values,
    $approach,
    $process,
    $faq,
    $cta,
    $valueIcons,
    $error,
    $success
): void {
    ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
    <?php admin_view_site_link('about.php'); ?>

    <form method="post" enctype="multipart/form-data" class="admin-form homepage-admin">
      <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />

      <section class="panel">
        <header class="hp-section-head">
          <h2>Hero</h2>
          <p class="panel-text">Copy on the left, photo on the right with a white scrim.</p>
        </header>
        <div class="hp-blocks">
          <div class="hp-block">
            <h3 class="hp-block-title">Copy</h3>
            <div class="form-grid">
              <label class="form-field"><span>Kicker</span><input type="text" name="hero_kicker" value="<?php echo e($hero['kicker']); ?>" /></label>
              <label class="form-field"><span>Headline line 1</span><input type="text" name="hero_title" value="<?php echo e($hero['title']); ?>" /></label>
              <label class="form-field"><span>Headline line 2</span><input type="text" name="hero_title_line2" value="<?php echo e($hero['title_line2']); ?>" /></label>
              <label class="form-field span-2"><span>Lead text</span><textarea name="hero_text" rows="3"><?php echo e($hero['lead']); ?></textarea></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">Button</h3>
            <div class="form-grid">
              <label class="form-field"><span>Button text</span><input type="text" name="hero_btn_text" value="<?php echo e($hero['btn_text']); ?>" /></label>
              <label class="form-field"><span>Button link</span><input type="text" name="hero_btn_url" value="<?php echo e($hero['btn_url']); ?>" placeholder="#our-story" /></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">Photo</h3>
            <div class="form-grid">
              <label class="form-field span-2"><span>Image alt text</span><input type="text" name="hero_image_alt" value="<?php echo e($hero['image_alt']); ?>" /></label>
              <div class="form-field span-2">
                <?php about_admin_image_row('hero_image_file', $hero['image'], 'Upload hero image', true); ?>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Our Story</h2>
          <p class="panel-text">Centred intro below the hero.</p>
        </header>
        <div class="form-grid">
          <label class="form-field"><span>Kicker</span><input type="text" name="story_kicker" value="<?php echo e($story['kicker']); ?>" /></label>
          <label class="form-field span-2"><span>Heading</span><input type="text" name="story_title" value="<?php echo e($story['title']); ?>" /></label>
          <?php foreach ($story['paragraphs'] as $index => $paragraph): ?>
            <label class="form-field span-2"><span>Paragraph <?php echo $index + 1; ?></span><textarea name="story_paragraphs[<?php echo $index; ?>]" rows="3"><?php echo e($paragraph); ?></textarea></label>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Our Values</h2>
          <p class="panel-text">Four value cards with icons.</p>
        </header>
        <div class="hp-block">
          <label class="form-field"><span>Section kicker</span><input type="text" name="values_kicker" value="<?php echo e($values['kicker']); ?>" /></label>
        </div>
        <div class="hp-cards">
          <?php foreach ($values['items'] as $index => $item): ?>
            <details class="hp-card">
              <summary><?php echo e($item['title']); ?></summary>
              <div class="hp-card-body">
                <div class="form-grid">
                  <label class="form-field"><span>Title</span><input type="text" name="values_items[<?php echo $index; ?>][title]" value="<?php echo e($item['title']); ?>" /></label>
                  <label class="form-field"><span>Icon</span>
                    <select name="values_items[<?php echo $index; ?>][icon]">
                      <?php foreach ($valueIcons as $iconKey => $iconLabel): ?>
                        <option value="<?php echo e($iconKey); ?>"<?php echo $item['icon'] === $iconKey ? ' selected' : ''; ?>><?php echo e($iconLabel); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label class="form-field span-2"><span>Text</span><textarea name="values_items[<?php echo $index; ?>][text]" rows="2"><?php echo e($item['text']); ?></textarea></label>
                </div>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Our Approach</h2>
          <p class="panel-text">Dark green section with three tabs and a side image.</p>
        </header>
        <div class="hp-blocks">
          <div class="hp-block">
            <h3 class="hp-block-title">Section intro</h3>
            <div class="form-grid">
              <label class="form-field"><span>Kicker</span><input type="text" name="approach_kicker" value="<?php echo e($approach['kicker']); ?>" /></label>
              <label class="form-field span-2"><span>Heading</span><input type="text" name="approach_title" value="<?php echo e($approach['title']); ?>" /></label>
            </div>
          </div>
          <div class="hp-block">
            <h3 class="hp-block-title">Side image</h3>
            <div class="form-grid">
              <label class="form-field span-2"><span>Image alt text</span><input type="text" name="approach_image_alt" value="<?php echo e($approach['image_alt']); ?>" /></label>
              <div class="form-field span-2">
                <?php about_admin_image_row('approach_image_file', $approach['image'], 'Upload approach image'); ?>
              </div>
            </div>
          </div>
        </div>
        <div class="hp-cards">
          <?php foreach ($approach['items'] as $index => $item): ?>
            <details class="hp-card">
              <summary><?php echo e($item['label']); ?> <span class="hp-card-meta">(<?php echo e($item['key']); ?>)</span></summary>
              <div class="hp-card-body">
                <div class="form-grid">
                  <label class="form-field"><span>Tab label</span><input type="text" name="approach_items[<?php echo $index; ?>][label]" value="<?php echo e($item['label']); ?>" /></label>
                  <label class="form-field span-2"><span>Panel text</span><textarea name="approach_items[<?php echo $index; ?>][text]" rows="3"><?php echo e($item['text']); ?></textarea></label>
                </div>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Our Process</h2>
          <p class="panel-text">Four numbered steps.</p>
        </header>
        <div class="hp-block">
          <div class="form-grid">
            <label class="form-field"><span>Kicker</span><input type="text" name="process_kicker" value="<?php echo e($process['kicker']); ?>" /></label>
            <label class="form-field span-2"><span>Heading</span><input type="text" name="process_title" value="<?php echo e($process['title']); ?>" /></label>
          </div>
        </div>
        <div class="hp-cards">
          <?php foreach ($process['steps'] as $index => $step): ?>
            <details class="hp-card">
              <summary>Step <?php echo str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); ?> · <?php echo e($step['title']); ?></summary>
              <div class="hp-card-body">
                <div class="form-grid">
                  <label class="form-field"><span>Title</span><input type="text" name="process_steps[<?php echo $index; ?>][title]" value="<?php echo e($step['title']); ?>" /></label>
                  <label class="form-field span-2"><span>Text</span><textarea name="process_steps[<?php echo $index; ?>][text]" rows="2"><?php echo e($step['text']); ?></textarea></label>
                </div>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>FAQ</h2>
          <p class="panel-text">Expandable questions and answers.</p>
        </header>
        <div class="hp-block">
          <div class="form-grid">
            <label class="form-field"><span>Kicker</span><input type="text" name="faq_kicker" value="<?php echo e($faq['kicker']); ?>" /></label>
            <label class="form-field span-2"><span>Heading</span><input type="text" name="faq_title" value="<?php echo e($faq['title']); ?>" /></label>
          </div>
        </div>
        <div class="hp-cards">
          <?php foreach ($faq['items'] as $index => $item): ?>
            <details class="hp-card">
              <summary>Q<?php echo $index + 1; ?>: <?php echo e($item['q']); ?></summary>
              <div class="hp-card-body">
                <div class="form-grid">
                  <label class="form-field span-2"><span>Question</span><input type="text" name="faq_items[<?php echo $index; ?>][q]" value="<?php echo e($item['q']); ?>" /></label>
                  <label class="form-field span-2"><span>Answer</span><textarea name="faq_items[<?php echo $index; ?>][a]" rows="3"><?php echo e($item['a']); ?></textarea></label>
                </div>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="panel">
        <header class="hp-section-head">
          <h2>Contact bar</h2>
          <p class="panel-text">Green call to action at the foot of the page.</p>
        </header>
        <div class="form-grid">
          <label class="form-field span-2"><span>Message</span><textarea name="cta_text" rows="2"><?php echo e($cta['text']); ?></textarea></label>
          <label class="form-field"><span>Button text</span><input type="text" name="cta_btn_text" value="<?php echo e($cta['btn_text']); ?>" /></label>
          <label class="form-field"><span>Button link</span><input type="text" name="cta_btn_url" value="<?php echo e($cta['btn_url']); ?>" placeholder="contact.php" /></label>
        </div>
      </section>

      <div class="hp-form-footer">
        <button type="submit" class="btn btn-primary homepage-admin-save">Save about page</button>
      </div>
    </form>
    <?php
});
