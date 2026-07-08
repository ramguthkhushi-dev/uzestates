<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/gallery.php';
require_once __DIR__ . '/includes/gallery-page.php';
require_once __DIR__ . '/includes/settings.php';

$contactInfo  = settings_contact_public();
$phoneDisplay = $contactInfo['phone_display'];
$phoneTel     = $contactInfo['phone_tel'];
$whatsapp     = preg_replace('/\D/', '', $contactInfo['whatsapp'] ?? '') ?: '23058154042';
$email        = $contactInfo['email'];

try {
    $galHero = gallery_page_content(settings_gallery_page());
} catch (Throwable $e) {
    $galHero = gallery_page_content([]);
}

$heroImageUrl = gallery_page_asset_url($galHero['image']);

$slots = gallery_slots_all();
$lightboxPayload = gallery_lightbox_payload($slots);
$lightboxItems = $lightboxPayload['items'];
$lightboxIndexBySlotId = $lightboxPayload['indexBySlotId'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gallery | UZ Estates</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/header-nav.css" />
  <link rel="stylesheet" href="css/gallery.css?v=<?php echo (int) filemtime(__DIR__ . '/css/gallery.css'); ?>" />
  <link rel="stylesheet" href="css/page-animate.css?v=<?php echo (int) filemtime(__DIR__ . '/css/page-animate.css'); ?>" />
  <style>.gal-hero-bg { background-image: url('<?php echo e($heroImageUrl); ?>'); }</style>
</head>
<body class="gallery-body">

<?php require __DIR__ . '/includes/site-header.php'; ?>

<main class="gallery-page">

  <section class="gal-hero">
    <div class="gal-hero-bg" aria-hidden="true"></div>
    <div class="gal-hero-overlay" aria-hidden="true"></div>
    <div class="gal-hero-inner">
      <p class="kicker"><?php echo e($galHero['kicker']); ?></p>
      <h1><?php echo e($galHero['title']); ?></h1>
      <p class="gal-hero-lead"><?php echo e($galHero['lead']); ?></p>
      <a href="<?php echo e($galHero['btn_url']); ?>" class="gal-hero-btn">
        <span class="gal-hero-btn-label"><?php echo e($galHero['btn_text']); ?></span>
        <span class="gal-hero-btn-arrow" aria-hidden="true">→</span>
      </a>
    </div>
  </section>

  <section class="gal-content">
    <div class="gal-grid" data-reveal-group>
      <?php if ($slots === []): ?>
        <div class="gal-empty">
          <p>Gallery slots are not configured yet.</p>
        </div>
      <?php else: ?>
        <?php foreach ($slots as $slot): ?>
          <?php require __DIR__ . '/includes/gallery-slot-render.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

</main>

<div class="gallery-lightbox" id="galleryLightbox" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Gallery viewer">
  <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
  <button type="button" class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Previous">&lsaquo;</button>
  <button type="button" class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Next">&rsaquo;</button>
  <div class="lightbox-stage" id="lightboxStage"></div>
  <p class="lightbox-caption" id="lightboxCaption" hidden></p>
</div>

<script>
  window.galleryLightboxItems = <?php echo json_encode($lightboxItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<?php require __DIR__ . '/includes/site-footer.php'; ?>

<script src="js/script.js"></script>
<script src="js/page-animate.js?v=<?php echo (int) filemtime(__DIR__ . '/js/page-animate.js'); ?>"></script>
<script src="js/gallery.js?v=<?php echo (int) filemtime(__DIR__ . '/js/gallery.js'); ?>"></script>
</body>
</html>
