<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/homepage.php';

$headerVariant = 'hero';

$contactInfo  = settings_contact_public();
$whatsappUrl  = $contactInfo['whatsapp_url'];
$phoneDisplay = $contactInfo['phone_display'];
$phoneTel     = $contactInfo['phone_tel'];
$email        = $contactInfo['email'];

try {
    $home = homepage_content(settings_homepage());
} catch (Throwable $e) {
    $home = homepage_content([]);
}

$hero          = $home['hero'];
$matchSection  = $home['match'];
$matchOptions  = $matchSection['tabs'];
$lifestyle     = $home['lifestyle'];
$contactBlock  = $home['contact'];

$heroImageUrl = homepage_asset_url($hero['image']);
$contactWaUrl = homepage_asset_url($contactBlock['whatsapp_image']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UZ Estates | Real Estate in Mauritius</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/header-nav.css" />
  <link rel="stylesheet" href="css/home.css?v=<?php echo (int) filemtime(__DIR__ . '/css/home.css'); ?>" />
  <link rel="stylesheet" href="css/page-animate.css?v=<?php echo (int) filemtime(__DIR__ . '/css/page-animate.css'); ?>" />
  <style>
    .home-hero-bg { background-image: url('<?php echo e($heroImageUrl); ?>'); }
    <?php foreach ($matchOptions as $option): ?>
    .match-bg-<?php echo e($option['key']); ?> {
      <?php echo homepage_match_bg_style($option['key'], $option['image']); ?>
    }
    <?php endforeach; ?>
    <?php foreach ($lifestyle['tiles'] as $index => $tile): ?>
    .lifestyle-tile-<?php echo $index + 1; ?> .lifestyle-tile-bg {
      background-image: url('<?php echo e(homepage_asset_url($tile['image'])); ?>');
    }
    <?php endforeach; ?>
    .contact-card-visual {
      background-image: url('<?php echo e($contactWaUrl); ?>');
    }
  </style>
</head>
<body class="page-home">

<?php require __DIR__ . '/includes/site-header.php'; ?>

<main class="home-main">

  <section class="home-hero">
    <div class="home-hero-bg" role="img" aria-label="Luxury property in Mauritius"></div>
    <div class="home-hero-overlay" aria-hidden="true"></div>

    <div class="home-hero-inner">
      <div class="home-hero-copy hero-copy-block">
        <p class="kicker kicker-light hero-kicker"><?php echo e($hero['kicker']); ?></p>
        <h1><?php echo e($hero['title']); ?></h1>
        <div class="hero-title-divider hero-title-divider--on-dark" aria-hidden="true"></div>
        <?php if ($hero['subtitle'] !== ''): ?>
        <p class="hero-lead"><?php echo e($hero['subtitle']); ?></p>
        <?php else: ?>
        <p class="hero-lead"></p>
        <?php endif; ?>
        <div class="hero-actions">
          <a href="<?php echo e($hero['cta_url']); ?>" class="btn btn-solid hero-primary-btn"><?php echo e($hero['cta_text']); ?></a>
          <a href="<?php echo e($hero['secondary_url']); ?>" class="btn btn-ghost hero-gallery-btn">
            <span class="hero-play" aria-hidden="true">
              <svg width="10" height="12" viewBox="0 0 10 12" fill="currentColor"><path d="M0 0v12l10-6z"/></svg>
            </span>
            <?php echo e($hero['secondary_text']); ?>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="home-match" id="properties">
    <div class="match-header-wrap">
      <header class="match-header" data-reveal-group>
        <p class="kicker" data-reveal="fade-up"><?php echo e($matchSection['kicker']); ?></p>
        <h2 data-reveal="fade-up"><?php echo e($matchSection['title']); ?><br /><span class="match-title-line"><?php echo e($matchSection['title_emphasis']); ?></span></h2>
        <p class="match-lead" data-reveal="fade-up"><?php echo e($matchSection['lead']); ?></p>
        <div class="match-divider" data-reveal="fade-up" aria-hidden="true"><span></span></div>
      </header>
    </div>

    <div class="match-stage-wrap" data-reveal="scale-up" data-reveal-delay="2">
    <div class="match-stage" data-match-stage>
      <div class="match-bg" aria-hidden="true">
        <?php foreach ($matchOptions as $index => $option): ?>
          <div
            class="match-bg-layer match-bg-<?php echo e($option['key']); ?><?php echo $index === 0 ? ' is-active' : ''; ?>"
            data-match-bg="<?php echo e($option['key']); ?>"
          ></div>
        <?php endforeach; ?>
      </div>
      <div class="match-scrim" aria-hidden="true"></div>

      <div class="match-layout">
        <div class="match-choices" role="tablist" aria-label="What are you looking for?" data-match-tabs>
          <?php foreach ($matchOptions as $index => $option): ?>
            <button
              type="button"
              class="match-choice<?php echo $index === 0 ? ' is-active' : ''; ?>"
              role="tab"
              id="match-tab-<?php echo e($option['key']); ?>"
              aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
              aria-controls="match-panel-<?php echo e($option['key']); ?>"
              data-match-key="<?php echo e($option['key']); ?>"
            ><?php echo e($option['label']); ?></button>
          <?php endforeach; ?>
        </div>

        <div class="match-panels">
          <?php foreach ($matchOptions as $index => $option): ?>
            <article
              class="match-panel<?php echo $index === 0 ? ' is-active' : ''; ?>"
              id="match-panel-<?php echo e($option['key']); ?>"
              role="tabpanel"
              aria-labelledby="match-tab-<?php echo e($option['key']); ?>"
              <?php echo $index === 0 ? '' : 'hidden'; ?>
            >
              <p class="match-panel-index"><?php echo str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); ?></p>
              <h3 class="match-panel-title"><?php echo e($option['title']); ?></h3>
              <p class="match-panel-text"><?php echo e($option['text']); ?></p>
              <a href="<?php echo e($option['href']); ?>" class="btn btn-solid match-panel-cta"><?php echo e($option['cta']); ?></a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    </div>
  </section>

  <section class="home-lifestyle">
    <div class="lifestyle-shell">
      <div class="lifestyle-copy" data-reveal-group>
        <p class="kicker kicker-light" data-reveal="fade-up"><?php echo e($lifestyle['kicker']); ?></p>
        <h2 data-reveal="fade-up"><?php echo e($lifestyle['title']); ?></h2>
        <div class="lifestyle-divider" data-reveal="fade-up" aria-hidden="true"><span></span></div>
        <a href="<?php echo e($lifestyle['cta_url']); ?>" class="btn btn-ghost btn-sm lifestyle-cta" data-reveal="fade-up"><?php echo e($lifestyle['cta_text']); ?></a>
      </div>
      <div class="lifestyle-tiles" data-reveal-group>
        <?php foreach ($lifestyle['tiles'] as $index => $tile): ?>
        <article class="lifestyle-tile lifestyle-tile-<?php echo $index + 1; ?>" data-reveal="fade-up">
          <span class="lifestyle-tile-bg" aria-hidden="true"></span>
          <span class="lifestyle-tile-overlay" aria-hidden="true"></span>
          <div class="lifestyle-tile-caption">
            <div class="lifestyle-tile-copy">
              <span class="lifestyle-tile-label"><?php echo e($tile['label']); ?></span>
              <span class="lifestyle-tile-sub"><?php echo e($tile['subtitle']); ?></span>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="home-contact" id="contact">
    <div class="contact-shell">
      <div class="contact-intro" data-reveal-group>
        <p class="kicker" data-reveal="fade-up"><?php echo e($contactBlock['kicker']); ?></p>
        <h2 data-reveal="fade-up"><?php echo e($contactBlock['title']); ?></h2>
        <div class="contact-divider" data-reveal="fade-up" aria-hidden="true"><span></span></div>
        <p class="contact-lead" data-reveal="fade-up"><?php echo e($contactBlock['lead']); ?></p>
      </div>

      <div class="contact-card" data-reveal="scale-up" data-reveal-delay="2">
        <div class="contact-card-info">
          <ul class="contact-rows">
            <li class="contact-row">
              <span class="contact-row-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              </span>
              <div class="contact-row-text">
                <span class="contact-label">Phone</span>
                <span class="contact-value contact-value-phone"><?php echo e($phoneDisplay); ?></span>
              </div>
            </li>
            <li class="contact-row">
              <span class="contact-row-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
              </span>
              <div class="contact-row-text">
                <span class="contact-label">WhatsApp</span>
                <span class="contact-value contact-value-phone"><?php echo e($phoneDisplay); ?></span>
              </div>
            </li>
            <li class="contact-row">
              <span class="contact-row-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg>
              </span>
              <div class="contact-row-text">
                <span class="contact-label">Email</span>
                <span class="contact-value"><?php echo e($email); ?></span>
              </div>
            </li>
          </ul>
        </div>

        <div class="contact-card-visual">
          <div class="contact-visual-overlay" aria-hidden="true"></div>
          <a href="<?php echo e($whatsappUrl); ?>" target="_blank" rel="noopener" class="contact-wa-btn" data-reveal="fade-up" data-reveal-delay="3">
            <span class="contact-wa-pulse" aria-hidden="true"></span>
            <span class="contact-wa-content">
              <span class="contact-wa-icon-wrap" aria-hidden="true">
                <svg class="contact-wa-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.126 1.532 5.855L0 24l6.335-1.662A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.006-1.37l-.358-.213-3.756.984.984-3.663-.233-.375A9.818 9.818 0 1 1 12 21.818z"/></svg>
              </span>
              <span class="contact-wa-label">Message on WhatsApp</span>
            </span>
          </a>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/site-footer.php'; ?>

<script src="js/script.js"></script>
<script src="js/page-animate.js?v=<?php echo (int) filemtime(__DIR__ . '/js/page-animate.js'); ?>"></script>
<script src="js/home.js?v=<?php echo (int) filemtime(__DIR__ . '/js/home.js'); ?>"></script>
<script src="js/home-match.js?v=<?php echo (int) filemtime(__DIR__ . '/js/home-match.js'); ?>"></script>
</body>
</html>
