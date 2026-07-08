<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/properties.php';
require_once __DIR__ . '/includes/enquiries.php';
require_once __DIR__ . '/includes/settings.php';

start_session();

$contactInfo  = settings_contact_public();
$phoneTel     = $contactInfo['phone_tel'];
$phoneDisplay = $contactInfo['phone_display'];
$whatsappUrl  = $contactInfo['whatsapp_url'];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$property = ($id && $id > 0) ? property_find($id) : null;

if ($property && isset($property['is_visible']) && !(bool) $property['is_visible']) {
    $property = null;
}

$enquiryError   = null;
$enquirySuccess = flash_get('enquiry_success');
$formOld        = [];

if ($property && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $formOld = enquiry_form_old_from_request($_POST);

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $enquiryError = 'Invalid session. Please try again.';
    } else {
        $result = enquiry_save([
            'name'           => $_POST['name'] ?? '',
            'phone'          => $_POST['phone'] ?? '',
            'email'          => $_POST['email'] ?? '',
            'message'        => $_POST['message'] ?? '',
            'property_id'    => $id,
            'property_title' => $property['title'],
            'enquiry_type'   => 'Property Enquiry',
        ]);

        if ($result['success']) {
            flash_set('enquiry_success', 'Thank you. Your enquiry has been sent.');
            header('Location: ' . BASE_URL . '/property-details.php?id=' . $id . '#enquiry');
            exit;
        }

        $enquiryError = $result['error'];
    }
}

if (!$property) {
    http_response_code(404);
    $notFound = true;
} else {
    $notFound         = false;
    $features         = property_features($id);
    $lots             = property_lots($id);
    $displayLots      = property_lots_for_display($id, $property);
    $useVillaLots     = property_uses_villa_lots($property, $displayLots);
    $activeLotIndex   = isset($_GET['lot'])
        ? max(0, (int) $_GET['lot'])
        : -1;
    if ($useVillaLots && $activeLotIndex >= count($displayLots)) {
        $activeLotIndex = -1;
    }
    $cardTitle        = property_card_title($property);
    if ($useVillaLots && $displayLots !== []) {
        $galleryImages = property_villa_combined_gallery_images($displayLots, $property);
    } else {
        $galleryImages = property_detail_photo_images($property);
    }
    $sitemapImages    = property_detail_sitemap_images($property);
    $videos           = property_detail_videos($property);
    $videoFiles       = array_values(array_filter(
        $videos,
        static fn(array $video): bool => preg_match('/\.(mp4|webm)$/i', $video['url']) === 1
    ));
    $hasSitemapLightbox = $sitemapImages !== [];
    $specs            = property_detail_specs($property, $features);
    $similarListings  = property_similar_listings($property, 3);
    $mapEmbed         = property_map_embed_html($property);
    $hasMap           = property_has_map($property);
    $propertyWhatsapp = property_whatsapp_url_for($whatsappUrl, $cardTitle);
    $defaultMessage   = 'Hello, I am interested in [' . $cardTitle . ']';
    if ($useVillaLots && $displayLots !== [] && $activeLotIndex >= 0 && trim((string) ($formOld['message'] ?? '')) === '') {
        $defaultMessage = property_lot_enquiry_message($property, $displayLots[$activeLotIndex], $activeLotIndex);
    }
    $enquiryMessage   = trim((string) ($formOld['message'] ?? '')) ?: $defaultMessage;
    $statLine         = property_card_stat_for_property($property, $lots);
    $priceLine        = property_card_price_for_property($property, $lots);
    $typeLine         = property_card_type_label($property);

    $metaParts = array_filter([
        $typeLine !== '' ? $typeLine : null,
        !empty($property['listing_purpose']) ? 'For ' . ucfirst(strtolower((string) $property['listing_purpose'])) : null,
        !empty($property['status']) ? (string) $property['status'] : null,
        $statLine !== '' ? $statLine : null,
    ]);
}
?>
<!DOCTYPE html>
<html lang="en" data-base-url="<?php echo e(BASE_URL); ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $notFound ? 'Property not found' : e($cardTitle); ?> | UZ Estates</title>
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/style.css" />
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/header-nav.css" />
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/properties.css?v=<?php echo (int) filemtime(__DIR__ . '/css/properties.css'); ?>" />
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/page-animate.css?v=<?php echo (int) filemtime(__DIR__ . '/css/page-animate.css'); ?>" />
  <link rel="stylesheet" href="<?php echo e(BASE_URL); ?>/css/properties-animate.css?v=<?php echo (int) filemtime(__DIR__ . '/css/properties-animate.css'); ?>" />
  <?php enquiry_phone_stylesheet_tag(); ?>
</head>
<body class="detail-body">

<?php require __DIR__ . '/includes/site-header.php'; ?>

<main class="property-detail-page">
  <?php if ($notFound): ?>
    <section class="page-hero">
      <p class="kicker">Not found</p>
      <h1>Property not found.</h1>
      <p class="page-intro">This listing may have been removed or the link is incorrect.</p>
      <a href="<?php echo e(BASE_URL); ?>/properties.php" class="text-button">Back to properties</a>
    </section>
  <?php else: ?>
    <div class="detail-shell">
      <nav class="detail-breadcrumb" aria-label="Breadcrumb" data-reveal="fade-up">
        <a href="<?php echo e(BASE_URL); ?>/home.php">Home</a>
        <span aria-hidden="true">›</span>
        <a href="<?php echo e(BASE_URL); ?>/properties.php">Properties</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?php echo e($cardTitle); ?></span>
      </nav>

      <?php if ($galleryImages !== []): ?>
        <div
          class="detail-gallery<?php echo $useVillaLots ? ' detail-gallery--villa-combined' : ''; ?>"
          data-detail-gallery
          <?php if ($useVillaLots): ?>data-villa-gallery="combined"<?php endif; ?>
          data-reveal="fade-in"
          data-gallery-count="<?php echo count($galleryImages); ?>"
        >
          <div class="detail-gallery-stage" data-detail-gallery-stage tabindex="0" role="group" aria-label="<?php echo $useVillaLots ? 'Villa project photo gallery' : 'Property photo gallery'; ?>">
            <?php foreach ($galleryImages as $index => $galleryUrl): ?>
              <img
                class="detail-gallery-slide<?php echo $index === 0 ? ' is-active' : ''; ?>"
                src="<?php echo e($galleryUrl); ?>"
                alt="<?php echo e($cardTitle); ?>"
                loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                data-detail-gallery-open="<?php echo (int) $index; ?>"
              />
            <?php endforeach; ?>
            <?php if (count($galleryImages) > 1): ?>
              <button type="button" class="detail-gallery-nav detail-gallery-nav--prev" aria-label="Previous image">‹</button>
              <button type="button" class="detail-gallery-nav detail-gallery-nav--next" aria-label="Next image">›</button>
              <span class="detail-gallery-counter" data-detail-gallery-counter aria-live="polite">1 / <?php echo count($galleryImages); ?></span>
              <button type="button" class="detail-gallery-expand" data-detail-gallery-expand aria-label="View full screen">⤢</button>
            <?php endif; ?>
          </div>
          <?php if (count($galleryImages) > 1): ?>
            <div class="detail-gallery-thumbs" data-detail-thumbs>
              <?php foreach ($galleryImages as $index => $galleryUrl): ?>
                <button
                  type="button"
                  class="detail-gallery-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>"
                  data-detail-thumb="<?php echo (int) $index; ?>"
                  aria-label="View image <?php echo (int) $index + 1; ?>"
                >
                  <img src="<?php echo e($galleryUrl); ?>" alt="" loading="lazy" />
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="detail-layout">
        <header class="detail-intro" data-reveal="fade-up">
          <h1><?php echo e($cardTitle); ?></h1>
          <?php if ($priceLine !== ''): ?>
            <p class="detail-price"><?php echo e($priceLine); ?></p>
          <?php endif; ?>
          <?php if ($metaParts !== []): ?>
            <p class="detail-meta"><?php echo e(implode(' · ', $metaParts)); ?></p>
          <?php endif; ?>
          <?php if (!empty($property['location_name'])): ?>
            <p class="detail-location"><?php echo e($property['location_name']); ?></p>
          <?php endif; ?>
        </header>

        <div class="detail-main" data-reveal-group>
          <?php if (!empty($property['full_description'])): ?>
            <section class="detail-section detail-section--overview" data-reveal="fade-up">
              <h2><?php echo $useVillaLots ? 'Project overview' : 'Description'; ?></h2>
              <div class="detail-prose"><?php echo nl2br(e($property['full_description'])); ?></div>
            </section>
          <?php endif; ?>

          <?php if ($useVillaLots && $specs !== []): ?>
            <section class="detail-section detail-section--facts" data-reveal="fade-up">
              <h2>Key details</h2>
              <dl class="detail-specs" data-reveal-stagger>
                <?php foreach ($specs as $spec): ?>
                  <div class="detail-spec-row" data-reveal="fade-in">
                    <dt><?php echo e($spec['label']); ?></dt>
                    <dd><?php echo e($spec['value']); ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </section>
          <?php endif; ?>

          <?php if ($useVillaLots && $displayLots !== []): ?>
            <?php
              $savedGalleryImages = $galleryImages;
              require __DIR__ . '/includes/property-villa-lots.php';
              $galleryImages = $savedGalleryImages;
            ?>
          <?php elseif ($lots !== []): ?>
            <section class="detail-section detail-section--lots" data-reveal="fade-up">
              <h2>Available lots</h2>
              <p class="detail-lots-hint">Select a lot to include it in your enquiry message.</p>
              <ul class="detail-lots" data-reveal-stagger>
                <?php foreach ($lots as $index => $lot): ?>
                  <?php
                    $lotLabel   = trim((string) ($lot['label'] ?? '')) ?: 'Lot ' . ((int) $index + 1);
                    $lotSize    = (string) ($lot['size'] ?? '');
                    $lotPrice   = property_card_price_display((string) ($lot['price'] ?? ''));
                    $lotMessage = 'Hello, I am interested in [' . $cardTitle . '], ' . $lotLabel
                        . ($lotSize !== '' ? ' (' . $lotSize : '')
                        . ($lotPrice !== '' ? ($lotSize !== '' ? ', ' : ' (') . $lotPrice : '')
                        . ($lotSize !== '' || $lotPrice !== '' ? ')' : '');
                    $lotImages  = property_lot_images($property, $lot, (int) $index);
                  ?>
                  <li
                    class="detail-lot<?php echo $lotImages !== [] ? ' detail-lot--has-media' : ''; ?>"
                    data-reveal="fade-in"
                    data-lot-item
                    data-lot-message="<?php echo e($lotMessage); ?>"
                  >
                    <button type="button" class="detail-lot-main" data-lot-select aria-pressed="false">
                      <span class="detail-lot-label"><?php echo e($lotLabel); ?></span>
                      <span class="detail-lot-size"><?php echo e($lotSize); ?></span>
                      <span class="detail-lot-price"><?php echo e($lotPrice); ?></span>
                    </button>
                    <?php if ($lotImages !== []): ?>
                      <div class="detail-lot-media">
                        <?php foreach ($lotImages as $imageIndex => $imageUrl): ?>
                          <button
                            type="button"
                            class="detail-lot-photo"
                            data-detail-lightbox-open
                            data-detail-lightbox-src="<?php echo e($imageUrl); ?>"
                            data-detail-lightbox-caption="<?php echo e($lotLabel); ?>"
                            aria-label="View <?php echo e($lotLabel); ?> image <?php echo (int) $imageIndex + 1; ?>"
                          >
                            <img
                              src="<?php echo e($imageUrl); ?>"
                              alt=""
                              loading="lazy"
                            />
                          </button>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endif; ?>

          <?php if (!$useVillaLots && $specs !== []): ?>
            <section class="detail-section detail-section--facts" data-reveal="fade-up">
              <h2>Key details</h2>
              <dl class="detail-specs" data-reveal-stagger>
                <?php foreach ($specs as $spec): ?>
                  <div class="detail-spec-row" data-reveal="fade-in">
                    <dt><?php echo e($spec['label']); ?></dt>
                    <dd><?php echo e($spec['value']); ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            </section>
          <?php endif; ?>

          <?php if ($hasMap): ?>
            <section class="detail-section detail-section--map" data-reveal="fade-up">
              <div class="detail-map-header">
                <h2>Location</h2>
                <?php if (!empty($property['google_maps_link'])): ?>
                  <a href="<?php echo e($property['google_maps_link']); ?>" target="_blank" rel="noopener" class="detail-link">Open in Google Maps</a>
                <?php endif; ?>
              </div>
              <?php if ($mapEmbed !== ''): ?>
                <div class="detail-map"><?php echo $mapEmbed; ?></div>
              <?php endif; ?>
            </section>
          <?php endif; ?>

          <p class="detail-back" data-reveal="fade-up">
            <a href="<?php echo e(BASE_URL); ?>/properties.php">← Back to all properties</a>
          </p>
        </div>

        <aside class="detail-aside" id="enquiry" data-reveal="fade-left" data-reveal-delay="1">
          <div class="detail-aside-inner" data-detail-aside-inner>
            <h2 class="detail-aside-title">Enquire about this property</h2>
            <span class="detail-aside-rule" aria-hidden="true"></span>

            <?php if ($enquirySuccess): ?>
              <div class="contact-notice contact-notice-ok" role="status"><?php echo e($enquirySuccess); ?></div>
            <?php endif; ?>
            <?php if ($enquiryError): ?>
              <div class="contact-notice contact-notice-err" role="alert"><?php echo e($enquiryError); ?></div>
            <?php endif; ?>

            <a href="<?php echo e($propertyWhatsapp); ?>" target="_blank" rel="noopener" class="detail-wa-btn">
              <img src="<?php echo e(BASE_URL); ?>/images/icons/whatsapp-green.svg" width="18" height="18" alt="" />
              WhatsApp
            </a>

            <form class="detail-form" method="post" action="<?php echo e(BASE_URL); ?>/property-details.php?id=<?php echo (int) $id; ?>#enquiry" novalidate data-enquiry-api="<?php echo e(BASE_URL); ?>/api/enquiry.php">
              <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
              <input type="hidden" name="property_id" value="<?php echo (int) $id; ?>" />
              <?php enquiry_honeypot_field(); ?>
              <?php enquiry_recaptcha_fields(); ?>

              <label class="detail-field">
                <span>Full Name</span>
                <input type="text" name="name" required autocomplete="name" maxlength="150" value="<?php echo e($formOld['name'] ?? ''); ?>" />
              </label>
              <label class="detail-field">
                <span>Mobile number</span>
                <?php enquiry_phone_field($formOld['phone'] ?? '', 'Mobile number'); ?>
              </label>
              <label class="detail-field">
                <span>Email</span>
                <input type="email" name="email" required autocomplete="email" maxlength="150" value="<?php echo e($formOld['email'] ?? ''); ?>" />
              </label>
              <label class="detail-field">
                <span>Message</span>
                <textarea name="message" rows="4" required maxlength="5000"><?php echo e($enquiryMessage); ?></textarea>
              </label>
              <button type="submit" class="detail-submit">Send enquiry</button>
            </form>

            <p class="detail-aside-phone">
              Or call <a href="tel:<?php echo e($phoneTel); ?>"><?php echo e($phoneDisplay); ?></a>
            </p>
          </div>
        </aside>
      </div>
    </div>

    <?php if ($videoFiles !== []): ?>
      <?php
        $singleVideo   = count($videoFiles) === 1;
        $videoPoster   = $galleryImages[0] ?? ($sitemapImages[0] ?? '');
        $videoBgStyle  = $videoPoster !== '' ? '--showcase-bg: url(\'' . e($videoPoster) . '\')' : '';
      ?>
      <section
        class="detail-showcase detail-showcase--video<?php echo $singleVideo ? ' detail-showcase--single' : ''; ?>"
        <?php if (!$singleVideo): ?>data-detail-peek-carousel data-peek-type="video"<?php endif; ?>
        aria-labelledby="detail-videos-heading"
        data-reveal="fade-up"
        <?php if ($videoBgStyle !== ''): ?>style="<?php echo $videoBgStyle; ?>"<?php endif; ?>
      >
        <div class="detail-showcase-bg" aria-hidden="true"></div>
        <div class="detail-showcase-inner">
          <h2 class="detail-showcase-title" id="detail-videos-heading">Videos</h2>
          <?php if ($singleVideo): ?>
            <div class="detail-showcase-single">
              <video
                controls
                preload="metadata"
                playsinline
                <?php if ($videoPoster !== ''): ?>poster="<?php echo e($videoPoster); ?>"<?php endif; ?>
                src="<?php echo e($videoFiles[0]['url']); ?>"
              ></video>
            </div>
          <?php else: ?>
            <div class="detail-showcase-frame">
              <button type="button" class="detail-showcase-nav detail-showcase-nav--prev" aria-label="Previous video">‹</button>
              <div class="detail-showcase-viewport">
                <div class="detail-showcase-track">
                  <?php foreach ($videoFiles as $index => $video): ?>
                    <div class="detail-showcase-slide<?php echo $index === 0 ? ' is-active' : ''; ?>">
                      <video
                        controls
                        preload="metadata"
                        playsinline
                        <?php if ($videoPoster !== ''): ?>poster="<?php echo e($videoPoster); ?>"<?php endif; ?>
                        src="<?php echo e($video['url']); ?>"
                      ></video>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <button type="button" class="detail-showcase-nav detail-showcase-nav--next" aria-label="Next video">›</button>
            </div>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($sitemapImages !== []): ?>
      <?php $sitemapCount = count($sitemapImages); ?>
      <section class="detail-plans" data-detail-plans aria-labelledby="detail-sitemaps-heading" data-reveal="fade-up">
        <div class="detail-plans-head detail-shell">
          <p class="detail-plans-label">Site plans</p>
          <h2 class="detail-plans-title" id="detail-sitemaps-heading">Sitemaps</h2>
          <?php if ($sitemapCount > 1): ?>
            <p class="detail-plans-hint">Scroll to browse · Click any plan to enlarge</p>
          <?php endif; ?>
        </div>

        <div class="detail-plans-scroll" data-plans-scroll tabindex="0">
          <div class="detail-plans-track">
            <?php foreach ($sitemapImages as $index => $sitemapUrl): ?>
              <figure class="detail-plans-card">
                <button
                  type="button"
                  class="detail-plans-zoom"
                  data-plan-open="<?php echo (int) $index; ?>"
                  data-plan-src="<?php echo e($sitemapUrl); ?>"
                  aria-label="View sitemap <?php echo (int) $index + 1; ?> full size"
                >
                  <img
                    src="<?php echo e($sitemapUrl); ?>"
                    alt="<?php echo e($cardTitle); ?> sitemap <?php echo (int) $index + 1; ?>"
                    loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                  />
                </button>
                <figcaption>Plan <?php echo (int) $index + 1; ?></figcaption>
              </figure>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($sitemapCount > 1): ?>
          <div class="detail-plans-foot detail-shell">
            <div class="detail-plans-progress" aria-hidden="true">
              <span class="detail-plans-progress-fill" data-plans-progress></span>
            </div>
            <span class="detail-plans-index" data-plans-index>1 / <?php echo (int) $sitemapCount; ?></span>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <div class="detail-shell">
      <?php if ($similarListings !== []): ?>
        <section class="detail-similar" data-reveal-group>
          <h2 data-reveal="fade-up">Similar listings</h2>
          <div class="detail-similar-row">
            <?php foreach ($similarListings as $similarIndex => $similar): ?>
              <?php
                $similarUrl    = BASE_URL . '/property-details.php?id=' . (int) $similar['id'];
                $similarTitle  = property_card_title($similar);
                $similarImages = property_card_gallery_images($similar);
                $similarImage  = $similarImages[0] ?? null;
                $similarPrice  = property_card_price_display($similar['price'] ?? '');
              ?>
              <a href="<?php echo e($similarUrl); ?>" class="detail-similar-item" data-reveal="fade-up">
                <span class="detail-similar-img">
                  <?php if ($similarImage): ?>
                    <img src="<?php echo e($similarImage); ?>" alt="" loading="lazy" />
                  <?php endif; ?>
                </span>
                <span class="detail-similar-text">
                  <strong><?php echo e($similarTitle); ?></strong>
                  <?php if ($similarPrice !== ''): ?>
                    <em><?php echo e($similarPrice); ?></em>
                  <?php endif; ?>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!$notFound): ?>
    <div class="detail-media-lightbox" data-detail-media-lightbox hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Image viewer">
      <div class="detail-media-lightbox-backdrop" data-detail-media-close tabindex="-1"></div>
      <button type="button" class="detail-media-lightbox-close" data-detail-media-close aria-label="Close">×</button>
      <button type="button" class="detail-media-lightbox-nav detail-media-lightbox-nav--prev" data-detail-media-prev aria-label="Previous image">‹</button>
      <button type="button" class="detail-media-lightbox-nav detail-media-lightbox-nav--next" data-detail-media-next aria-label="Next image">›</button>
      <figure class="detail-media-lightbox-frame">
        <img class="detail-media-lightbox-img" data-detail-media-img src="" alt="" />
        <figcaption class="detail-media-lightbox-caption" data-detail-media-caption></figcaption>
      </figure>
    </div>

    <?php if ($hasSitemapLightbox): ?>
      <?php $sitemapLightboxCount = count($sitemapImages); ?>
      <div class="detail-plans-lightbox" data-plans-lightbox hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Sitemap viewer">
        <div class="detail-plans-lightbox-backdrop" data-plans-close tabindex="-1"></div>
        <button type="button" class="detail-plans-lightbox-close" data-plans-close aria-label="Close">×</button>
        <?php if ($sitemapLightboxCount > 1): ?>
          <button type="button" class="detail-plans-lightbox-nav detail-plans-lightbox-nav--prev" data-plans-prev aria-label="Previous sitemap">‹</button>
          <button type="button" class="detail-plans-lightbox-nav detail-plans-lightbox-nav--next" data-plans-next aria-label="Next sitemap">›</button>
        <?php endif; ?>
        <img class="detail-plans-lightbox-img" data-plans-lightbox-img src="" alt="" />
        <p class="detail-plans-lightbox-caption" data-plans-lightbox-caption></p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/site-footer.php'; ?>

<script src="<?php echo e(BASE_URL); ?>/js/script.js"></script>
<?php enquiry_phone_script_tags(); ?>
<?php enquiry_phone_script_tags(); ?>
<?php require_once __DIR__ . '/includes/recaptcha.php'; recaptcha_script_tag(); ?>
<script src="<?php echo e(BASE_URL); ?>/js/enquiry-form.js?v=<?php echo (int) filemtime(__DIR__ . '/js/enquiry-form.js'); ?>"></script>
<script src="<?php echo e(BASE_URL); ?>/js/property-details.js?v=<?php echo (int) filemtime(__DIR__ . '/js/property-details.js'); ?>"></script>
<script src="<?php echo e(BASE_URL); ?>/js/properties-animate.js?v=<?php echo (int) filemtime(__DIR__ . '/js/properties-animate.js'); ?>"></script>
<script src="<?php echo e(BASE_URL); ?>/js/page-animate.js?v=<?php echo (int) filemtime(__DIR__ . '/js/page-animate.js'); ?>"></script>
</body>
</html>
