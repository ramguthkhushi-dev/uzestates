<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/about-page.php';

$contactInfo  = settings_contact_public();
$phoneDisplay = $contactInfo['phone_display'];
$phoneTel     = $contactInfo['phone_tel'];
$whatsapp     = preg_replace('/\D/', '', $contactInfo['whatsapp'] ?? '') ?: '23058154042';
$email        = $contactInfo['email'];

try {
    $about = about_page_content(settings_about());
} catch (Throwable $e) {
    $about = about_page_content([]);
}

$hero     = $about['hero'];
$story    = $about['story'];
$values   = $about['values']['items'];
$approach = $about['approach'];
$process  = $about['process'];
$faq      = $about['faq']['items'];
$cta      = $about['cta'];

$heroImageUrl     = about_page_asset_url($hero['image']);
$approachImageUrl = about_page_asset_url($approach['image']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About | UZ Estates</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/header-nav.css" />
  <link rel="stylesheet" href="css/about.css?v=<?php echo (int) filemtime(__DIR__ . '/css/about.css'); ?>" />
  <link rel="stylesheet" href="css/page-animate.css?v=<?php echo (int) filemtime(__DIR__ . '/css/page-animate.css'); ?>" />
  <style>
    .about-hero-media { --about-hero-photo: url('<?php echo e($heroImageUrl); ?>'); }
    .about-approach-media { --about-approach-photo: url('<?php echo e($approachImageUrl); ?>'); }
  </style>
</head>
<body class="about-body">

<?php require __DIR__ . '/includes/site-header.php'; ?>

<main class="about-page">

  <!-- Hero -->
  <section class="about-hero" aria-labelledby="about-hero-heading">
    <div class="about-hero-copy">
      <p class="kicker"><?php echo e($hero['kicker']); ?></p>
      <h1 id="about-hero-heading">
        <?php echo e($hero['title']); ?><?php if ($hero['title_line2'] !== ''): ?><br><?php echo e($hero['title_line2']); ?><?php endif; ?>
      </h1>
      <p class="about-lead"><?php echo e($hero['lead']); ?></p>
      <a href="<?php echo e($hero['btn_url']); ?>" class="about-btn about-btn-primary">
        <?php echo e($hero['btn_text']); ?> <span class="about-btn-arrow" aria-hidden="true">→</span>
      </a>
    </div>
    <div class="about-hero-media" role="img" aria-label="<?php echo e($hero['image_alt']); ?>"></div>
  </section>

  <!-- Our Story -->
  <section class="about-story" id="our-story" aria-labelledby="about-story-heading">
    <div class="about-story-inner" data-reveal-group>
      <p class="section-kicker" data-reveal="fade-up"><?php echo e($story['kicker']); ?></p>
      <h2 id="about-story-heading" data-reveal="fade-up"><?php echo e($story['title']); ?></h2>
      <div class="about-story-divider" data-reveal="fade-up" aria-hidden="true"><span></span></div>
      <?php foreach ($story['paragraphs'] as $paragraph): ?>
        <p class="about-story-text" data-reveal="fade-up"><?php echo e($paragraph); ?></p>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Our Values -->
  <section class="about-values" aria-labelledby="about-values-heading">
    <p class="section-kicker about-values-kicker" data-reveal="fade-up"><?php echo e($about['values']['kicker']); ?></p>
    <h2 id="about-values-heading" class="visually-hidden"><?php echo e($about['values']['kicker']); ?></h2>
    <div class="about-values-grid" data-reveal-group>
      <?php foreach ($values as $value): ?>
        <article class="about-value-item" data-reveal="fade-up">
          <div class="about-value-icon" aria-hidden="true">
            <?php if ($value['icon'] === 'shield'): ?>
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M12 2l8 4v6c0 5-3.5 9.5-8 10-4.5-.5-8-5-8-10V6l8-4z"/></svg>
            <?php elseif ($value['icon'] === 'pin'): ?>
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
            <?php elseif ($value['icon'] === 'heart'): ?>
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
            <?php else: ?>
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="3"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <?php endif; ?>
          </div>
          <h3><?php echo e($value['title']); ?></h3>
          <p><?php echo e($value['text']); ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Our Approach -->
  <section class="about-approach" id="our-approach" aria-labelledby="about-approach-heading" data-approach-section>
    <div class="about-approach-content">
      <div class="about-approach-intro" data-reveal-group>
        <p class="section-kicker section-kicker-light" data-reveal="fade-up"><?php echo e($approach['kicker']); ?></p>
        <h2 id="about-approach-heading" data-reveal="fade-up"><?php echo e($approach['title']); ?></h2>
      </div>

      <div class="about-approach-tabs" role="tablist" aria-label="Our approach pillars" data-approach-tabs data-reveal="fade-up" data-reveal-delay="2">
        <span class="about-approach-tab-indicator" aria-hidden="true"></span>
        <?php foreach ($approach['items'] as $i => $item): ?>
          <button type="button"
                  class="about-approach-tab<?php echo $i === 0 ? ' is-active' : ''; ?>"
                  role="tab"
                  id="approach-tab-<?php echo e($item['key']); ?>"
                  aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                  aria-controls="approach-panel-<?php echo e($item['key']); ?>"
                  data-approach-index="<?php echo $i; ?>">
            <?php echo e($item['label']); ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="about-approach-panels" data-approach-panels data-reveal="fade-up" data-reveal-delay="3">
        <?php foreach ($approach['items'] as $i => $item): ?>
          <div class="about-approach-panel-content<?php echo $i === 0 ? ' is-active' : ''; ?>"
               role="tabpanel"
               id="approach-panel-<?php echo e($item['key']); ?>"
               aria-labelledby="approach-tab-<?php echo e($item['key']); ?>"
               <?php echo $i === 0 ? '' : 'hidden'; ?>>
            <p><?php echo e($item['text']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="about-approach-media" data-approach-media data-reveal="fade-right" data-reveal-delay="1" role="img" aria-label="<?php echo e($approach['image_alt']); ?>">
      <span class="about-approach-media-shine" aria-hidden="true"></span>
    </div>
  </section>

  <!-- Our Process -->
  <section class="about-process" id="our-process" aria-labelledby="about-process-heading">
    <div class="about-process-inner">
      <div data-reveal-group>
        <p class="section-kicker" data-reveal="fade-up"><?php echo e($process['kicker']); ?></p>
        <h2 id="about-process-heading" data-reveal="fade-up"><?php echo e($process['title']); ?></h2>
        <div class="about-process-divider" data-reveal="fade-up" aria-hidden="true"><span></span></div>
      </div>

      <ol class="about-process-steps" data-reveal-group>
        <?php foreach ($process['steps'] as $i => $step): ?>
          <li class="about-process-step" data-reveal="fade-up">
            <p class="about-process-index"><?php echo str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT); ?></p>
            <h3><?php echo e($step['title']); ?></h3>
            <p><?php echo e($step['text']); ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <!-- FAQ -->
  <section class="about-faq" aria-labelledby="about-faq-heading">
    <div class="about-faq-inner">
      <div data-reveal-group>
        <p class="section-kicker" data-reveal="fade-up"><?php echo e($about['faq']['kicker']); ?></p>
        <h2 id="about-faq-heading" data-reveal="fade-up"><?php echo e($about['faq']['title']); ?></h2>
        <div class="about-faq-divider" data-reveal="fade-up" aria-hidden="true"><span></span></div>
      </div>

      <div class="faq-accordion" data-faq-accordion data-reveal="scale-up" data-reveal-delay="2">
        <?php foreach ($faq as $i => $item): ?>
          <div class="faq-item">
            <button type="button" class="faq-question" aria-expanded="false"
                    aria-controls="faq-<?php echo $i + 1; ?>">
              <span class="faq-question-text"><?php echo e($item['q']); ?></span>
              <span class="faq-toggle" aria-hidden="true"></span>
            </button>
            <div class="faq-answer" id="faq-<?php echo $i + 1; ?>" role="region" hidden>
              <p><?php echo e($item['a']); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- CTA Bar -->
  <section class="about-cta-bar" aria-label="Contact us">
    <div class="about-cta-bar-inner" data-reveal="fade-up">
      <div class="about-cta-bar-copy">
        <svg class="about-cta-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
        <p><?php echo e($cta['text']); ?></p>
      </div>
      <a href="<?php echo e($cta['btn_url']); ?>" class="about-btn about-btn-tan about-cta-btn">
        <span class="about-cta-btn-label"><?php echo e($cta['btn_text']); ?></span>
        <span class="about-btn-arrow about-cta-btn-arrow" aria-hidden="true">→</span>
      </a>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/site-footer.php'; ?>

<script src="js/script.js"></script>
<script src="js/page-animate.js?v=<?php echo (int) filemtime(__DIR__ . '/js/page-animate.js'); ?>"></script>
<script src="js/about-approach.js?v=<?php echo (int) filemtime(__DIR__ . '/js/about-approach.js'); ?>"></script>
<script src="js/about-faq.js?v=<?php echo (int) filemtime(__DIR__ . '/js/about-faq.js'); ?>"></script>
</body>
</html>
