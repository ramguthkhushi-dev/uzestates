<?php

declare(strict_types=1);

/** @var array $formData */
/** @var array $features */
/** @var array $mediaItems */
/** @var array $lots */
/** @var bool $isEdit */
/** @var int|null $propertyId */
/** @var string $formMode */

$formData    = $formData ?? [];
$features    = $features ?? [];
$mediaItems  = $mediaItems ?? [];
$lots        = $lots ?? [];
$isEdit      = $isEdit ?? false;
$propertyId  = $propertyId ?? null;
$formMode    = $formMode ?? 'full';
$isMediaOnly = $formMode === 'media';
$isAdd       = !$isEdit;

$defaults = [
    'title'             => '',
    'property_type'     => '',
    'listing_purpose'   => '',
    'status'            => '',
    'price'             => '',
    'size'              => '',
    'location_name'     => '',
    'short_description' => '',
    'full_description'  => '',
    'is_visible'        => 1,
    'is_featured'       => 0,
    'show_on_home'      => 0,
    'display_order'     => 0,
    'google_maps_link'  => '',
    'google_maps_embed' => '',
    'latitude'          => '',
    'longitude'         => '',
    'price_numeric'     => null,
];

$formData = array_merge($defaults, $formData);

$typeOptions    = property_admin_select_options(property_admin_type_options(), (string) $formData['property_type']);
$purposeOptions = property_admin_select_options(property_admin_purpose_options(), (string) $formData['listing_purpose']);
$statusOptions  = property_admin_select_options(property_admin_status_options(), (string) $formData['status']);
$isVillaProperty = stripos((string) ($formData['property_type'] ?? ''), 'villa') !== false;
$usesSimpleLots  = property_admin_uses_simple_lots((string) ($formData['property_type'] ?? ''));
$typeHint        = property_admin_type_hint((string) ($formData['property_type'] ?? ''));
$simpleLots      = $isVillaProperty ? [] : $lots;
$villaLots       = $isVillaProperty ? $lots : [];

$hasFeatures = array_filter($features, static fn(array $f): bool => trim((string) ($f['feature_label'] ?? '')) !== '' || trim((string) ($f['feature_value'] ?? '')) !== '') !== [];
$hasLots     = array_filter($simpleLots, static fn(array $l): bool => trim((string) ($l['size'] ?? '')) !== '' || trim((string) ($l['price'] ?? '')) !== '') !== [];
$hasMap      = trim((string) ($formData['google_maps_link'] ?? '')) !== ''
    || trim((string) ($formData['google_maps_embed'] ?? '')) !== '';
$hasPublishingExtras = !empty($formData['is_featured']) || !empty($formData['show_on_home'])
    || (int) ($formData['display_order'] ?? 0) > 0;

$mainMediaId = 0;
foreach ($mediaItems as $item) {
    if (!empty($item['is_main'])) {
        $mainMediaId = (int) $item['id'];
        break;
    }
}

$foldOpen = static fn(bool $condition): string => ($isAdd && !$condition) ? '' : ' open';
?>

<?php if ($isEdit && $propertyId): ?>
  <div class="property-form-toolbar panel">
    <p class="form-hint">
      Public listing:
      <a href="<?php echo e(BASE_URL); ?>/property-details.php?id=<?php echo (int) $propertyId; ?>" target="_blank" rel="noopener">
        View on site ↗
      </a>
      ·
      <a href="<?php echo e(BASE_URL); ?>/properties.php" target="_blank" rel="noopener">Properties page ↗</a>
    </p>
  </div>
<?php endif; ?>

<form class="property-form property-form--simple" method="post" enctype="multipart/form-data" action="">
  <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
  <?php if ($isEdit && $propertyId): ?>
    <input type="hidden" name="id" value="<?php echo (int) $propertyId; ?>" />
  <?php endif; ?>

  <?php if (!$isMediaOnly): ?>

  <details class="prop-fold"<?php echo $foldOpen(true); ?>>
    <summary class="prop-fold-head">
      <span class="prop-fold-step">1</span>
      <span class="prop-fold-copy">
        <strong class="prop-fold-title">Essentials</strong>
        <span class="prop-fold-sub">Title, type, price, location</span>
      </span>
    </summary>
    <div class="prop-fold-body panel">
      <div class="form-grid">
        <label class="form-field span-2">
          <span>Title *</span>
          <input type="text" name="title" required value="<?php echo e($formData['title']); ?>" placeholder="e.g. Almaris Villas" />
        </label>

        <label class="form-field">
          <span>Type *</span>
          <select name="property_type" required>
            <option value="">Select…</option>
            <?php foreach ($typeOptions as $option): ?>
              <option value="<?php echo e($option); ?>"<?php echo $formData['property_type'] === $option ? ' selected' : ''; ?>>
                <?php echo e($option); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="form-field">
          <span>Purpose</span>
          <select name="listing_purpose">
            <option value="">Select…</option>
            <?php foreach ($purposeOptions as $option): ?>
              <option value="<?php echo e($option); ?>"<?php echo $formData['listing_purpose'] === $option ? ' selected' : ''; ?>>
                <?php echo e($option); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="form-field">
          <span>Status</span>
          <select name="status">
            <option value="">Select…</option>
            <?php foreach ($statusOptions as $option): ?>
              <option value="<?php echo e($option); ?>"<?php echo $formData['status'] === $option ? ' selected' : ''; ?>>
                <?php echo e($option); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="form-field">
          <span>Price</span>
          <input type="text" name="price" placeholder="Rs 9.7M" value="<?php echo e($formData['price']); ?>" />
        </label>

        <label class="form-field">
          <span>Size</span>
          <input type="text" name="size" placeholder="12 perches" value="<?php echo e($formData['size']); ?>" />
        </label>

        <label class="form-field span-2">
          <span>Location</span>
          <input type="text" name="location_name" placeholder="Grand Baie, The Vale…"
                 value="<?php echo e($formData['location_name']); ?>" />
        </label>
      </div>

      <p class="prop-type-hint" id="propertyTypeHint"><?php echo e($typeHint); ?></p>

      <label class="form-field checkbox-field prop-visible-toggle">
        <input type="checkbox" name="is_visible" value="1" <?php echo !isset($formData['is_visible']) || !empty($formData['is_visible']) ? 'checked' : ''; ?> />
        <span>Visible on website</span>
      </label>
    </div>
  </details>

  <details class="prop-fold"<?php echo $foldOpen(trim((string) ($formData['full_description'] ?? '')) !== ''); ?>>
    <summary class="prop-fold-head">
      <span class="prop-fold-step">2</span>
      <span class="prop-fold-copy">
        <strong class="prop-fold-title">Description</strong>
        <span class="prop-fold-sub">Main text on the detail page</span>
      </span>
    </summary>
    <div class="prop-fold-body panel">
      <label class="form-field span-full">
        <span><?php echo $isVillaProperty ? 'Project overview' : 'Description'; ?></span>
        <textarea name="full_description" rows="8" placeholder="Describe the property…"><?php echo e($formData['full_description']); ?></textarea>
      </label>

      <details class="prop-inline-fold">
        <summary>Search snippet <span class="prop-optional">optional</span></summary>
        <label class="form-field">
          <span>Short description</span>
          <textarea name="short_description" rows="2" placeholder="Brief line used in search results"><?php echo e($formData['short_description']); ?></textarea>
        </label>
      </details>
    </div>
  </details>

  <details class="prop-fold" id="simpleLotsPanel"<?php echo ($usesSimpleLots && !$isVillaProperty) ? $foldOpen($hasLots) : ''; ?><?php echo ($usesSimpleLots && !$isVillaProperty) ? '' : ' hidden'; ?>>
    <summary class="prop-fold-head">
      <span class="prop-fold-step">+</span>
      <span class="prop-fold-copy">
        <strong class="prop-fold-title">Multiple lots</strong>
        <span class="prop-fold-sub">Only when a plot/land has several parcels</span>
      </span>
    </summary>
    <div class="prop-fold-body panel">
      <div class="prop-fold-actions">
        <button type="button" class="btn btn-outline btn-sm" id="add-lot-row">+ Add lot</button>
      </div>
      <div id="lot-rows" class="dynamic-rows dynamic-rows--simple">
        <?php foreach ($simpleLots as $i => $lot): ?>
          <?php if (trim((string) ($lot['size'] ?? '')) === '' && trim((string) ($lot['price'] ?? '')) === '' && $isAdd): continue; endif; ?>
          <div class="dynamic-row lot-row lot-row--simple">
            <input type="hidden" name="lot_id[]" value="<?php echo (int) ($lot['id'] ?? 0); ?>" />
            <input type="hidden" name="lot_order[]" value="<?php echo (int) ($lot['display_order'] ?? $i); ?>" data-order-field />
            <input type="hidden" name="lot_description[]" value="" />
            <input type="hidden" name="lot_bedrooms[]" value="" />
            <input type="hidden" name="lot_bathrooms[]" value="" />
            <input type="hidden" name="lot_villa_size[]" value="" />
            <input type="hidden" name="lot_status[]" value="Available" />
            <label class="form-field">
              <span>Label</span>
              <input type="text" name="lot_label[]" placeholder="Lot 3" value="<?php echo e($lot['label'] ?? ''); ?>" />
            </label>
            <label class="form-field">
              <span>Size</span>
              <input type="text" name="lot_size[]" placeholder="22 perches" value="<?php echo e($lot['size'] ?? ''); ?>" />
            </label>
            <label class="form-field">
              <span>Price</span>
              <input type="text" name="lot_price[]" placeholder="Rs 11M" value="<?php echo e($lot['price'] ?? ''); ?>" />
            </label>
            <button type="button" class="btn btn-outline btn-sm row-remove" aria-label="Remove lot">Remove</button>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="prop-empty-hint" id="lotRowsEmpty"<?php echo $hasLots ? ' hidden' : ''; ?>>No lots yet. Skip this if the listing is a single plot.</p>
    </div>
  </details>

  <?php require __DIR__ . '/villa-lot-form.php'; ?>

  <details class="prop-fold"<?php echo $foldOpen($hasFeatures); ?>>
    <summary class="prop-fold-head">
      <span class="prop-fold-step">+</span>
      <span class="prop-fold-copy">
        <strong class="prop-fold-title">Key details</strong>
        <span class="prop-fold-sub">Extra specs shown on the detail page</span>
      </span>
    </summary>
    <div class="prop-fold-body panel">
      <div class="prop-fold-actions">
        <button type="button" class="btn btn-outline btn-sm" id="add-feature-row">+ Add detail</button>
      </div>
      <div id="feature-rows" class="dynamic-rows dynamic-rows--simple">
        <?php foreach ($features as $i => $feature): ?>
          <?php
            $label = trim((string) ($feature['feature_label'] ?? ''));
            $value = trim((string) ($feature['feature_value'] ?? ''));
            if ($label === '' && $value === '' && $isAdd) {
                continue;
            }
          ?>
          <div class="dynamic-row feature-row feature-row--simple">
            <input type="hidden" name="feature_order[]" value="<?php echo (int) ($feature['display_order'] ?? $i); ?>" data-order-field />
            <label class="form-field">
              <span>Label</span>
              <input type="text" name="feature_label[]" placeholder="Bedrooms" value="<?php echo e($feature['feature_label'] ?? ''); ?>" />
            </label>
            <label class="form-field">
              <span>Value</span>
              <input type="text" name="feature_value[]" placeholder="3" value="<?php echo e($feature['feature_value'] ?? ''); ?>" />
            </label>
            <button type="button" class="btn btn-outline btn-sm row-remove" aria-label="Remove">Remove</button>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="prop-empty-hint" id="featureRowsEmpty"<?php echo $hasFeatures ? ' hidden' : ''; ?>>e.g. Plus code, Road frontage, Permits</p>
    </div>
  </details>

  <details class="prop-fold"<?php echo $foldOpen($hasMap); ?>>
    <summary class="prop-fold-head">
      <span class="prop-fold-step">+</span>
      <span class="prop-fold-copy">
        <strong class="prop-fold-title">Map &amp; location</strong>
        <span class="prop-fold-sub">Google Maps link or embed</span>
      </span>
    </summary>
    <div class="prop-fold-body panel">
      <div class="form-grid">
        <label class="form-field span-2">
          <span>Google Maps link</span>
          <input type="url" name="google_maps_link" placeholder="https://maps.google.com/…"
                 value="<?php echo e($formData['google_maps_link']); ?>" />
        </label>
        <label class="form-field span-2">
          <span>Map embed code</span>
          <textarea name="google_maps_embed" rows="3" placeholder="Paste iframe code from Google Maps"><?php echo e($formData['google_maps_embed']); ?></textarea>
        </label>
      </div>
      <details class="prop-inline-fold">
        <summary>Coordinates <span class="prop-optional">optional</span></summary>
        <div class="form-grid">
          <label class="form-field">
            <span>Latitude</span>
            <input type="text" name="latitude" value="<?php echo e($formData['latitude']); ?>" />
          </label>
          <label class="form-field">
            <span>Longitude</span>
            <input type="text" name="longitude" value="<?php echo e($formData['longitude']); ?>" />
          </label>
        </div>
      </details>
    </div>
  </details>

  <details class="prop-fold"<?php echo $foldOpen($hasPublishingExtras); ?>>
    <summary class="prop-fold-head">
      <span class="prop-fold-step">+</span>
      <span class="prop-fold-copy">
        <strong class="prop-fold-title">Featured &amp; sorting</strong>
        <span class="prop-fold-sub">Home page, featured badge, list order</span>
      </span>
    </summary>
    <div class="prop-fold-body panel">
      <div class="prop-check-grid">
        <label class="form-field checkbox-field">
          <input type="checkbox" name="is_featured" value="1" <?php echo !empty($formData['is_featured']) ? 'checked' : ''; ?> />
          <span>Featured listing</span>
        </label>
        <label class="form-field checkbox-field">
          <input type="checkbox" name="show_on_home" value="1" <?php echo !empty($formData['show_on_home']) ? 'checked' : ''; ?> />
          <span>Show on home page</span>
        </label>
      </div>
      <label class="form-field prop-sort-field">
        <span>List sort priority</span>
        <input type="number" name="display_order" min="0" value="<?php echo (int) $formData['display_order']; ?>" />
        <small class="field-note">Higher numbers appear first. Leave at 0 for default date order.</small>
      </label>
    </div>
  </details>

  <?php endif; ?>

  <details class="prop-fold"<?php echo $isMediaOnly || !$isAdd ? ' open' : ''; ?>>
    <summary class="prop-fold-head">
      <span class="prop-fold-step"><?php echo $isMediaOnly ? '1' : '3'; ?></span>
      <span class="prop-fold-copy">
        <strong class="prop-fold-title">Photos &amp; media</strong>
        <span class="prop-fold-sub">Gallery, videos, sitemaps<?php echo $isVillaProperty ? ' (videos/sitemaps for villas)' : ''; ?></span>
      </span>
    </summary>
    <div class="prop-fold-body panel">
      <p class="prop-media-note">
        Add photos for the gallery<?php echo $isVillaProperty ? '. For villas, unit photos go on each unit above; use this section for project videos and sitemaps.' : ', plus videos and site plans if you have them.'; ?>
        Large videos: upload one or two per save.
      </p>

      <?php if ($isEdit && $mediaItems !== []): ?>
        <div class="existing-media existing-media--compact">
          <h3 class="prop-subheading">Current files</h3>
          <div class="media-compact-grid">
            <?php foreach ($mediaItems as $i => $item): ?>
              <?php
                $previewUrl = property_media_url($item['file_path']);
                $isVideo    = ($item['media_type'] ?? '') === 'video';
                $isImage    = ($item['media_type'] ?? '') === 'image';
              ?>
              <div class="media-compact-card">
                <input type="hidden" name="existing_media_id[]" value="<?php echo (int) $item['id']; ?>" />
                <input type="hidden" name="existing_media_title[]" value="<?php echo e($item['title'] ?? ''); ?>" />
                <input type="hidden" name="existing_alt_text[]" value="<?php echo e($item['alt_text'] ?? ''); ?>" />
                <input type="hidden" name="existing_display_order[]" value="<?php echo (int) ($item['display_order'] ?? $i); ?>" data-order-field />

                <div class="media-compact-preview">
                  <?php if ($previewUrl && $isImage): ?>
                    <img src="<?php echo e($previewUrl); ?>" alt="" />
                  <?php elseif ($previewUrl && $isVideo): ?>
                    <video src="<?php echo e($previewUrl); ?>" muted></video>
                  <?php else: ?>
                    <span class="no-preview"><?php echo e($item['media_type'] ?? 'file'); ?></span>
                  <?php endif; ?>
                </div>

                <span class="media-compact-badge"><?php echo e($item['media_type'] ?? 'file'); ?></span>

                <?php if ($isImage): ?>
                  <label class="media-compact-main">
                    <input type="radio" name="main_media_id" value="<?php echo (int) $item['id']; ?>"
                           <?php echo ((int) $item['id'] === $mainMediaId) ? 'checked' : ''; ?> />
                    Main photo
                  </label>
                <?php endif; ?>

                <label class="media-compact-delete">
                  <input type="checkbox" name="delete_media[]" value="<?php echo (int) $item['id']; ?>" />
                  Delete
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="prop-media-quick">
        <span class="prop-media-quick-label">Add new:</span>
        <button type="button" class="btn btn-outline btn-sm" data-add-media="image">+ Photo</button>
        <button type="button" class="btn btn-outline btn-sm" data-add-media="video">+ Video</button>
        <button type="button" class="btn btn-outline btn-sm" data-add-media="sitemap">+ Sitemap</button>
      </div>

      <div id="media-rows" class="dynamic-rows dynamic-rows--media"></div>
      <p class="prop-empty-hint" id="mediaRowsEmpty"<?php echo ($isEdit && $mediaItems !== []) ? ' hidden' : ''; ?>>Use the buttons above to attach files.</p>
    </div>
  </details>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      <?php
        if ($isMediaOnly) {
            echo 'Save media';
        } elseif ($isEdit) {
            echo 'Save changes';
        } else {
            echo 'Create property';
        }
      ?>
    </button>
    <a href="<?php echo e(admin_url('properties/index.php')); ?>" class="btn btn-outline">Cancel</a>
    <?php if ($isMediaOnly && $propertyId): ?>
      <a href="<?php echo e(admin_url('properties/edit.php?id=' . (int) $propertyId)); ?>" class="btn btn-outline">Edit property</a>
    <?php endif; ?>
  </div>
</form>

<?php if (!$isMediaOnly): ?>
<template id="lot-row-template">
  <div class="dynamic-row lot-row lot-row--simple">
    <input type="hidden" name="lot_id[]" value="0" />
    <input type="hidden" name="lot_order[]" value="0" data-order-field />
    <input type="hidden" name="lot_description[]" value="" />
    <input type="hidden" name="lot_bedrooms[]" value="" />
    <input type="hidden" name="lot_bathrooms[]" value="" />
    <input type="hidden" name="lot_villa_size[]" value="" />
    <input type="hidden" name="lot_status[]" value="Available" />
    <label class="form-field">
      <span>Label</span>
      <input type="text" name="lot_label[]" placeholder="Lot 3" value="" />
    </label>
    <label class="form-field">
      <span>Size</span>
      <input type="text" name="lot_size[]" placeholder="22 perches" value="" />
    </label>
    <label class="form-field">
      <span>Price</span>
      <input type="text" name="lot_price[]" placeholder="Rs 11M" value="" />
    </label>
    <button type="button" class="btn btn-outline btn-sm row-remove" aria-label="Remove lot">Remove</button>
  </div>
</template>

<template id="feature-row-template">
  <div class="dynamic-row feature-row feature-row--simple">
    <input type="hidden" name="feature_order[]" value="0" data-order-field />
    <label class="form-field">
      <span>Label</span>
      <input type="text" name="feature_label[]" placeholder="Bedrooms" value="" />
    </label>
    <label class="form-field">
      <span>Value</span>
      <input type="text" name="feature_value[]" placeholder="3" value="" />
    </label>
    <button type="button" class="btn btn-outline btn-sm row-remove" aria-label="Remove">Remove</button>
  </div>
</template>
<?php endif; ?>

<template id="media-row-template">
  <div class="dynamic-row media-row media-row--simple">
    <input type="hidden" name="new_media_type[]" value="image" data-media-type />
    <input type="hidden" name="new_media_category[]" value="actual" data-media-category />
    <input type="hidden" name="new_display_order[]" value="0" data-order-field />
    <input type="hidden" name="new_media_title[]" value="" />
    <input type="hidden" name="new_alt_text[]" value="" />
    <input type="hidden" name="new_external_url[]" value="" />

    <span class="media-row-badge" data-media-badge>Photo</span>

    <label class="form-field media-row-file">
      <span>Choose file</span>
      <input type="file" name="new_media_file[]" />
    </label>

    <label class="form-field checkbox-field media-row-main">
      <input type="checkbox" class="new-main-checkbox" name="new_is_main[]" value="__INDEX__" />
      <span>Main photo</span>
    </label>

    <button type="button" class="btn btn-outline btn-sm row-remove" aria-label="Remove">Remove</button>
  </div>
</template>

<script>
  window.propertyAdminTypeHints = <?php echo json_encode(property_admin_type_hints(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="<?php echo e(BASE_URL); ?>/admin/assets/properties.js?v=<?php echo (int) filemtime(__DIR__ . '/../assets/properties.js'); ?>"></script>
