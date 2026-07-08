<?php

declare(strict_types=1);

/** @var array<string, mixed> $slot */
/** @var string $layoutClass */
/** @var array<int, int> $lightboxIndexBySlotId */

$slotId    = (int) ($slot['id'] ?? 0);
$slotSize  = $slot['slot_size'] ?? 'small';
$layoutClass = gallery_slot_size_class($slotSize);
$visible   = !empty($slot['is_visible']);
$hasMedia  = gallery_slot_has_media($slot);
$mediaType = $slot['media_type'] ?? 'image';
$tone      = ((int) ($slot['slot_number'] ?? 1) - 1) % 6 + 1;

if (gallery_slot_is_text($slot)):
    if (!$visible || !$hasMedia):
?>
  <div class="gal-item gal-slot-empty gal-feature gal-card <?php echo e($layoutClass); ?>" aria-hidden="true">
    <div class="gal-placeholder gal-tone-<?php echo (int) $tone; ?>"></div>
  </div>
<?php
    else:
        $isDark = ($slot['card_style'] ?? 'light') === 'dark';
        $href   = gallery_normalise_link($slot['button_link'] ?? '');
?>
  <a href="<?php echo e($href); ?>"
     class="gal-feature gal-card <?php echo e($layoutClass); ?><?php echo $isDark ? ' is-dark' : ''; ?>">
    <div class="gal-feature-shine" aria-hidden="true"></div>
    <div class="gal-feature-body">
      <?php if (!empty($slot['icon'])): ?>
        <div class="gal-feature-icon" aria-hidden="true">
          <?php echo gallery_icon_svg((string) $slot['icon']); ?>
        </div>
      <?php endif; ?>
      <h2><?php echo e($slot['title']); ?></h2>
      <?php if (!empty($slot['description'])): ?>
        <p><?php echo e($slot['description']); ?></p>
      <?php endif; ?>
      <?php if (!empty($slot['button_text'])): ?>
        <span class="gal-feature-cta">
          <?php echo e($slot['button_text']); ?>
          <span class="gal-feature-cta-arrow" aria-hidden="true">→</span>
        </span>
      <?php endif; ?>
    </div>
  </a>
<?php
    endif;
elseif ($mediaType === 'video'):
    $playback  = gallery_video_playback($slot);
    $posterUrl = gallery_slot_poster_url($slot);
    $lbIndex   = $lightboxIndexBySlotId[$slotId] ?? null;
    if ($visible && $playback !== null && $lbIndex !== null):
?>
  <figure class="gal-item gal-media gal-video <?php echo e($layoutClass); ?>"
          data-gallery-item
          data-gallery-index="<?php echo (int) $lbIndex; ?>"
          data-media-type="video"
          tabindex="0"
          role="button"
          aria-label="<?php echo e(trim($slot['title'] ?? '') !== '' ? 'Play ' . $slot['title'] : 'Play video'); ?>">
    <?php if ($posterUrl): ?>
      <img src="<?php echo e($posterUrl); ?>" alt="<?php echo e($slot['title'] ?? ''); ?>" loading="lazy" />
    <?php else: ?>
      <div class="gal-video-fallback" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="gal-video-overlay" aria-hidden="true"></div>
    <span class="gal-video-play" aria-hidden="true">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
    </span>
    <?php if (trim($slot['title'] ?? '') !== ''): ?>
      <figcaption class="gal-media-caption"><span><?php echo e($slot['title']); ?></span></figcaption>
    <?php endif; ?>
  </figure>
<?php
    else:
?>
  <div class="gal-item gal-slot-empty gal-media <?php echo e($layoutClass); ?>" aria-hidden="true">
    <div class="gal-placeholder gal-tone-<?php echo (int) $tone; ?>"></div>
  </div>
<?php
    endif;
else:
    $imageUrl = gallery_media_url($slot['file_path'] ?? null);
    $lbIndex  = $lightboxIndexBySlotId[$slotId] ?? null;
    if ($visible && $imageUrl !== null && $lbIndex !== null):
?>
  <figure class="gal-item gal-media <?php echo e($layoutClass); ?>"
          data-gallery-item
          data-gallery-index="<?php echo (int) $lbIndex; ?>"
          data-media-type="image"
          tabindex="0"
          role="button"
          aria-label="<?php echo e(trim($slot['title'] ?? '') !== '' ? 'View ' . $slot['title'] : 'View image'); ?>">
    <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($slot['title'] ?? ''); ?>" loading="lazy" />
    <?php if (trim($slot['title'] ?? '') !== ''): ?>
      <figcaption class="gal-media-caption"><span><?php echo e($slot['title']); ?></span></figcaption>
    <?php endif; ?>
  </figure>
<?php
    else:
?>
  <div class="gal-item gal-slot-empty gal-media <?php echo e($layoutClass); ?>" aria-hidden="true">
    <div class="gal-placeholder gal-tone-<?php echo (int) $tone; ?>"></div>
  </div>
<?php
    endif;
endif;
