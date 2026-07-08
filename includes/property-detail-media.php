<?php

declare(strict_types=1);

/** @var list<string> $galleryImages */
/** @var list<string> $sitemapImages */
/** @var list<array{url: string, title: string}> $videoFiles */
/** @var string $mediaTitle */
/** @var bool $mediaHidden */
/** @var string $mediaMode gallery|showcase|all */

$mediaHidden = $mediaHidden ?? false;
$mediaMode   = $mediaMode ?? 'all';
$videoPoster = $galleryImages[0] ?? ($sitemapImages[0] ?? '');
$showGallery   = $mediaMode === 'all' || $mediaMode === 'gallery';
$showShowcase  = $mediaMode === 'all' || $mediaMode === 'showcase';
?>
<?php if ($showGallery && $galleryImages !== []): ?>
  <div
    class="detail-gallery"
    data-detail-gallery
    <?php if (!empty($isLotGallery)): ?>data-villa-gallery="lot"<?php endif; ?>
    data-reveal="fade-in"
    data-gallery-count="<?php echo count($galleryImages); ?>"
  >
    <div class="detail-gallery-stage" data-detail-gallery-stage tabindex="0" role="group" aria-label="<?php echo e($mediaTitle); ?> photo gallery">
      <?php foreach ($galleryImages as $index => $galleryUrl): ?>
        <img
          class="detail-gallery-slide<?php echo $index === 0 ? ' is-active' : ''; ?>"
          src="<?php echo e($galleryUrl); ?>"
          alt="<?php echo e($mediaTitle); ?>"
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

<?php if ($showShowcase && $videoFiles !== []): ?>
  <?php
    $singleVideo  = count($videoFiles) === 1;
    $videoBgStyle = $videoPoster !== '' ? '--showcase-bg: url(\'' . e($videoPoster) . '\')' : '';
  ?>
  <section
    class="detail-showcase detail-showcase--video<?php echo $singleVideo ? ' detail-showcase--single' : ''; ?>"
    <?php if (!$singleVideo): ?>data-detail-peek-carousel data-peek-type="video"<?php endif; ?>
    aria-labelledby="detail-videos-heading-<?php echo e(slugify($mediaTitle)); ?>"
    data-reveal="fade-up"
    <?php if ($videoBgStyle !== ''): ?>style="<?php echo $videoBgStyle; ?>"<?php endif; ?>
  >
    <div class="detail-showcase-bg" aria-hidden="true"></div>
    <div class="detail-showcase-inner">
      <h2 class="detail-showcase-title" id="detail-videos-heading-<?php echo e(slugify($mediaTitle)); ?>">Videos</h2>
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

<?php if ($showShowcase && $sitemapImages !== []): ?>
  <?php $sitemapCount = count($sitemapImages); ?>
  <section class="detail-plans" data-detail-plans aria-labelledby="detail-sitemaps-heading-<?php echo e(slugify($mediaTitle)); ?>" data-reveal="fade-up">
    <div class="detail-plans-head detail-shell">
      <p class="detail-plans-label">Site plans</p>
      <h2 class="detail-plans-title" id="detail-sitemaps-heading-<?php echo e(slugify($mediaTitle)); ?>">Sitemaps</h2>
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
                alt="<?php echo e($mediaTitle); ?> sitemap <?php echo (int) $index + 1; ?>"
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
