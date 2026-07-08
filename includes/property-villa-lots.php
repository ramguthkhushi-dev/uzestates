<?php

declare(strict_types=1);

/** @var array $property */
/** @var list<array<string, mixed>> $displayLots */
/** @var int $activeLotIndex */

$activeLotIndex = max(-1, min($activeLotIndex, count($displayLots) - 1));
?>
<section class="villa-units" data-villa-units aria-labelledby="villa-units-heading">
  <div class="villa-units-head">
    <p class="kicker">Available units</p>
    <h2 id="villa-units-heading">Lots</h2>
    <p class="villa-units-lead">The overview above applies to the whole villa project. The gallery at the top shows photos from all available lots. Select a lot below to view its details and photos.</p>
  </div>

  <ul class="villa-lots" data-villa-lots>
    <?php foreach ($displayLots as $index => $lot): ?>
      <?php
        $lotLabel         = property_lot_label($lot, $index);
        $lotStats         = $lot['stats'] ?? [];
        $lotMessage       = property_lot_enquiry_message($property, $lot, $index);
        $isOpen           = $index === $activeLotIndex;
        $lotGalleryImages = $lot['photos'] ?? [];
        $lotThumb         = $lotGalleryImages[0] ?? '';
        $lotStatus        = trim((string) ($lot['status'] ?? ''));
        $lotStatusClass   = property_lot_status_class($lotStatus);
        $lotSize          = trim((string) ($lot['size'] ?? ''));
        $lotPrice         = trim((string) ($lot['price_display'] ?? ''));
      ?>
      <li
        class="villa-lot<?php echo $isOpen ? ' is-open' : ''; ?>"
        data-villa-lot-item
        data-villa-lot="<?php echo (int) $index; ?>"
        data-lot-message="<?php echo e($lotMessage); ?>"
      >
        <button
          type="button"
          class="villa-lot-trigger"
          data-villa-lot-toggle
          aria-expanded="<?php echo $isOpen ? 'true' : 'false'; ?>"
          aria-controls="villa-lot-body-<?php echo (int) $index; ?>"
          id="villa-lot-trigger-<?php echo (int) $index; ?>"
        >
          <?php if ($lotThumb !== ''): ?>
            <span class="villa-lot-trigger-media">
              <img src="<?php echo e($lotThumb); ?>" alt="" loading="lazy" />
            </span>
          <?php else: ?>
            <span class="villa-lot-trigger-media villa-lot-trigger-media--empty" aria-hidden="true">
              <?php echo property_lot_icon_svg('size'); ?>
            </span>
          <?php endif; ?>

          <span class="villa-lot-trigger-copy">
            <span class="villa-lot-trigger-eyebrow">Unit <?php echo (int) $index + 1; ?></span>
            <span class="villa-lot-trigger-label"><?php echo e($lotLabel); ?></span>
            <span class="villa-lot-trigger-meta">
              <?php if ($lotSize !== ''): ?>
                <span class="villa-lot-meta-chip">
                  <span class="villa-lot-meta-icon"><?php echo property_lot_icon_svg('land'); ?></span>
                  <?php echo e($lotSize); ?>
                </span>
              <?php endif; ?>
              <?php if ($lotPrice !== ''): ?>
                <span class="villa-lot-meta-chip villa-lot-meta-chip--price">
                  <span class="villa-lot-meta-icon"><?php echo property_lot_icon_svg('price'); ?></span>
                  <?php echo e($lotPrice); ?>
                </span>
              <?php endif; ?>
            </span>
          </span>

          <?php if ($lotStatus !== ''): ?>
            <span class="villa-lot-status<?php echo $lotStatusClass !== '' ? ' ' . e($lotStatusClass) : ''; ?>">
              <?php echo e($lotStatus); ?>
            </span>
          <?php endif; ?>

          <span class="villa-lot-trigger-toggle" aria-hidden="true">
            <?php echo property_lot_icon_svg('expand'); ?>
          </span>
        </button>

        <div
          class="villa-lot-body"
          id="villa-lot-body-<?php echo (int) $index; ?>"
          role="region"
          aria-labelledby="villa-lot-trigger-<?php echo (int) $index; ?>"
          <?php echo $isOpen ? '' : 'hidden'; ?>
        >
          <?php if ($lotStats !== []): ?>
            <ul class="villa-lot-specs">
              <?php foreach ($lotStats as $stat): ?>
                <?php $iconKey = property_lot_stat_icon_key((string) $stat['label']); ?>
                <li class="villa-lot-spec">
                  <span class="villa-lot-spec-icon"><?php echo property_lot_icon_svg($iconKey); ?></span>
                  <span class="villa-lot-spec-copy">
                    <span class="villa-lot-spec-label"><?php echo e($stat['label']); ?></span>
                    <strong class="villa-lot-spec-value"><?php echo e($stat['value']); ?></strong>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if (!empty($lot['description'])): ?>
            <div class="villa-lot-description detail-prose">
              <?php echo nl2br(e((string) $lot['description'])); ?>
            </div>
          <?php endif; ?>

          <?php if ($lotGalleryImages !== []): ?>
            <div class="villa-lot-media">
              <?php
                $galleryImages = $lotGalleryImages;
                $mediaTitle    = $lotLabel;
                $mediaMode     = 'gallery';
                $isLotGallery  = true;
                require __DIR__ . '/property-detail-media.php';
              ?>
            </div>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
