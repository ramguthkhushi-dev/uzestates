<?php

declare(strict_types=1);

/** @var array $villaLots */
/** @var array<int, list<array<string, mixed>>> $lotMediaByLotId */
/** @var bool $isEdit */
/** @var bool $isAdd */

$lotMediaByLotId = $lotMediaByLotId ?? [];
$villaLots       = $villaLots ?? [];
$isAdd           = $isAdd ?? empty($isEdit);
$statusOptions   = property_lot_status_options();

$hasVillaUnits = array_filter($villaLots, static fn(array $l): bool => trim((string) ($l['size'] ?? '')) !== '' && trim((string) ($l['price'] ?? '')) !== '') !== [];
$villaFoldOpen = !empty($isVillaProperty) && (!$isAdd || $hasVillaUnits);
?>
<details class="prop-fold villa-lots-panel" id="villaLotsPanel"<?php echo !empty($isVillaProperty) ? ($villaFoldOpen ? ' open' : '') : ' hidden'; ?>>
  <summary class="prop-fold-head">
    <span class="prop-fold-step">+</span>
    <span class="prop-fold-copy">
      <strong class="prop-fold-title">Villa units</strong>
      <span class="prop-fold-sub">One card per lot (required for villas)</span>
    </span>
  </summary>
  <div class="prop-fold-body panel">
    <div class="prop-fold-actions">
      <button type="button" class="btn btn-outline btn-sm" id="add-villa-lot-row">+ Add unit</button>
    </div>

    <div id="villa-lot-cards" class="villa-lot-cards">
      <?php foreach ($villaLots as $i => $lot): ?>
        <?php
          $lotId    = (int) ($lot['id'] ?? 0);
          $lotMedia = $lotMediaByLotId[$lotId] ?? [];
        ?>
        <article class="villa-lot-card" data-villa-lot-card>
          <header class="villa-lot-card-head">
            <h3>Unit <?php echo (int) $i + 1; ?></h3>
            <button type="button" class="btn btn-outline btn-sm row-remove" aria-label="Remove unit">Remove</button>
          </header>

          <input type="hidden" name="lot_id[]" value="<?php echo $lotId > 0 ? (int) $lotId : 0; ?>" />
          <input type="hidden" name="lot_order[]" value="<?php echo (int) ($lot['display_order'] ?? $i); ?>" data-order-field />

          <div class="form-grid">
            <label class="form-field">
              <span>Label</span>
              <input type="text" name="lot_label[]" placeholder="Lot 1"
                     value="<?php echo e($lot['label'] ?? ''); ?>" />
            </label>

            <label class="form-field">
              <span>Status</span>
              <select name="lot_status[]">
                <?php foreach ($statusOptions as $option): ?>
                  <option value="<?php echo e($option); ?>"<?php echo ($lot['status'] ?? 'Available') === $option ? ' selected' : ''; ?>>
                    <?php echo e($option); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="form-field">
              <span>Land size *</span>
              <input type="text" name="lot_size[]" placeholder="7 perches" required
                     value="<?php echo e($lot['size'] ?? ''); ?>" />
            </label>

            <label class="form-field">
              <span>Price *</span>
              <input type="text" name="lot_price[]" placeholder="Rs 9,700,000" required
                     value="<?php echo e($lot['price'] ?? ''); ?>" />
            </label>

            <label class="form-field">
              <span>Villa size</span>
              <input type="text" name="lot_villa_size[]" placeholder="1,250 sq ft"
                     value="<?php echo e($lot['villa_size'] ?? ''); ?>" />
            </label>

            <label class="form-field">
              <span>Bedrooms</span>
              <input type="text" name="lot_bedrooms[]" placeholder="3"
                     value="<?php echo e($lot['bedrooms'] ?? ''); ?>" />
            </label>

            <label class="form-field">
              <span>Bathrooms</span>
              <input type="text" name="lot_bathrooms[]" placeholder="2"
                     value="<?php echo e($lot['bathrooms'] ?? ''); ?>" />
            </label>

            <label class="form-field span-2">
              <span>Unit description</span>
              <textarea name="lot_description[]" rows="4" placeholder="Layout, finishes, outdoor areas…"><?php echo e($lot['description'] ?? ''); ?></textarea>
            </label>
          </div>

          <?php if ($lotMedia !== []): ?>
            <div class="villa-lot-media-existing">
              <h4>Current photos</h4>
              <div class="villa-lot-media-grid">
                <?php foreach ($lotMedia as $item): ?>
                  <?php $previewUrl = property_media_url($item['file_path']); ?>
                  <label class="villa-lot-media-item">
                    <input type="checkbox" name="lot_media_delete[]" value="<?php echo (int) $item['id']; ?>" />
                    <span class="villa-lot-media-thumb">
                      <?php if ($previewUrl && ($item['media_type'] ?? '') === 'image'): ?>
                        <img src="<?php echo e($previewUrl); ?>" alt="" />
                      <?php else: ?>
                        <span class="no-preview"><?php echo e($item['media_type'] ?? 'file'); ?></span>
                      <?php endif; ?>
                    </span>
                    <span class="villa-lot-media-meta">Delete</span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <label class="form-field span-2">
            <span>Unit photos</span>
            <input type="file" name="lot_upload[<?php echo (int) $i; ?>][]" multiple
                   accept="image/jpeg,image/png,image/webp" />
          </label>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="prop-empty-hint" id="villaLotsEmpty"<?php echo $hasVillaUnits ? ' hidden' : ''; ?>>Add at least one unit with land size and price.</p>
  </div>
</details>

<template id="villa-lot-card-template">
  <article class="villa-lot-card" data-villa-lot-card>
    <header class="villa-lot-card-head">
      <h3>Unit</h3>
      <button type="button" class="btn btn-outline btn-sm row-remove" aria-label="Remove unit">Remove</button>
    </header>

    <input type="hidden" name="lot_id[]" value="0" />
    <input type="hidden" name="lot_order[]" value="0" data-order-field />

    <div class="form-grid">
      <label class="form-field">
        <span>Label</span>
        <input type="text" name="lot_label[]" placeholder="Lot 1" value="" />
      </label>

      <label class="form-field">
        <span>Status</span>
        <select name="lot_status[]">
          <?php foreach ($statusOptions as $option): ?>
            <option value="<?php echo e($option); ?>"><?php echo e($option); ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="form-field">
        <span>Land size *</span>
        <input type="text" name="lot_size[]" placeholder="7 perches" required value="" />
      </label>

      <label class="form-field">
        <span>Price *</span>
        <input type="text" name="lot_price[]" placeholder="Rs 9,700,000" required value="" />
      </label>

      <label class="form-field">
        <span>Villa size</span>
        <input type="text" name="lot_villa_size[]" placeholder="1,250 sq ft" value="" />
      </label>

      <label class="form-field">
        <span>Bedrooms</span>
        <input type="text" name="lot_bedrooms[]" placeholder="3" value="" />
      </label>

      <label class="form-field">
        <span>Bathrooms</span>
        <input type="text" name="lot_bathrooms[]" placeholder="2" value="" />
      </label>

      <label class="form-field span-2">
        <span>Unit description</span>
        <textarea name="lot_description[]" rows="4" placeholder="Describe this unit…"></textarea>
      </label>
    </div>

    <label class="form-field span-2">
      <span>Unit photos</span>
      <input type="file" data-lot-upload-input multiple accept="image/jpeg,image/png,image/webp" />
    </label>
  </article>
</template>
