<?php

declare(strict_types=1);

/** @var array<string, mixed> $slot */
$slot = $slot ?? [];
$mediaType = $slot['media_type'] ?? 'image';
$allowedTypes = gallery_slot_allowed_media_types($slot);
$isTextSlot = ($slot['slot_size'] ?? '') === 'text_card';
?>
<div class="form-grid">
  <label class="form-field"><span>Slot</span>
    <input type="text" readonly value="Slot <?php echo (int) ($slot['slot_number'] ?? 0); ?> · <?php echo e($slot['slot_name'] ?? ''); ?>" />
  </label>

  <label class="form-field"><span>Fixed size</span>
    <input type="text" readonly value="<?php echo e(gallery_slot_size_label($slot['slot_size'] ?? 'small')); ?>" />
  </label>

  <label class="form-field"><span>Media type *</span>
    <select name="media_type" id="galleryMediaType">
      <?php foreach ($allowedTypes as $key => $label): ?>
        <option value="<?php echo e($key); ?>"<?php echo $mediaType === $key ? ' selected' : ''; ?>><?php echo e($label); ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="form-field"><span>Title<?php echo $isTextSlot ? ' *' : ''; ?></span>
    <input type="text" name="title"<?php echo $isTextSlot ? ' required' : ''; ?> value="<?php echo e($slot['title'] ?? ''); ?>" />
  </label>

  <label class="form-field span-2 gallery-field-text gallery-field-media"><span>Description</span>
    <textarea name="description" rows="3"><?php echo e($slot['description'] ?? ''); ?></textarea>
  </label>

  <label class="form-field gallery-field-image gallery-field-video"><span id="galleryFileLabel">File upload</span>
    <input type="file" name="file" id="galleryFile" />
  </label>

  <label class="form-field span-2 gallery-field-video"><span>External video URL (YouTube / Vimeo)</span>
    <input type="url" name="external_url" placeholder="https://www.youtube.com/watch?v=…" value="<?php echo e($slot['external_url'] ?? ''); ?>" />
  </label>

  <label class="form-field gallery-field-video"><span>Video poster / thumbnail</span>
    <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" />
  </label>

  <?php if (!empty($slot['thumbnail_path'])): ?>
    <p class="panel-text gallery-field-video">Current poster: <?php echo e($slot['thumbnail_path']); ?></p>
  <?php endif; ?>

  <?php if (!empty($slot['file_path']) && in_array($mediaType, ['image', 'video'], true)): ?>
    <p class="panel-text gallery-field-image gallery-field-video">Current file: <?php echo e($slot['file_path']); ?></p>
  <?php endif; ?>

  <label class="form-field gallery-field-text"><span>Button text<?php echo $isTextSlot ? ' *' : ''; ?></span>
    <input type="text" name="button_text"<?php echo $isTextSlot ? ' required' : ''; ?> value="<?php echo e($slot['button_text'] ?? ''); ?>" />
  </label>

  <label class="form-field gallery-field-text"><span>Button link</span>
    <input type="text" name="button_link" placeholder="properties.php or full URL" value="<?php echo e($slot['button_link'] ?? ''); ?>" />
  </label>

  <label class="form-field gallery-field-text"><span>Icon</span>
    <select name="icon">
      <?php foreach (gallery_icon_options() as $key => $label): ?>
        <option value="<?php echo e($key); ?>"<?php echo ($slot['icon'] ?? '') === $key ? ' selected' : ''; ?>><?php echo e($label); ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="form-field gallery-field-text"><span>Card style</span>
    <select name="card_style">
      <?php foreach (gallery_card_styles() as $key => $label): ?>
        <option value="<?php echo e($key); ?>"<?php echo ($slot['card_style'] ?? 'light') === $key ? ' selected' : ''; ?>><?php echo e($label); ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="form-check span-2"><input type="checkbox" name="is_visible" value="1" <?php echo !isset($slot['is_visible']) || !empty($slot['is_visible']) ? 'checked' : ''; ?> /><span>Visible on website</span></label>
</div>

<script>
(function () {
  var select = document.getElementById('galleryMediaType');
  if (!select) return;
  var isTextSlot = <?php echo $isTextSlot ? 'true' : 'false'; ?>;

  function toggleFields() {
    var type = select.value;
    var fileInput = document.getElementById('galleryFile');
    var fileLabel = document.getElementById('galleryFileLabel');

    document.querySelectorAll('.gallery-field-image, .gallery-field-video, .gallery-field-text, .gallery-field-media').forEach(function (el) {
      el.style.display = 'none';
    });

    if (type === 'text' || isTextSlot) {
      document.querySelectorAll('.gallery-field-text').forEach(function (el) { el.style.display = ''; });
      return;
    }

    document.querySelectorAll('.gallery-field-media').forEach(function (el) { el.style.display = ''; });

    if (type === 'image') {
      document.querySelectorAll('.gallery-field-image').forEach(function (el) { el.style.display = ''; });
      if (fileInput && fileLabel) {
        fileInput.accept = 'image/jpeg,image/png,image/webp';
        fileLabel.textContent = 'Image upload';
      }
    }

    if (type === 'video') {
      document.querySelectorAll('.gallery-field-video').forEach(function (el) { el.style.display = ''; });
      if (fileInput && fileLabel) {
        fileInput.accept = 'video/mp4,video/webm';
        fileLabel.textContent = 'Video upload (MP4 / WebM)';
      }
    }
  }

  select.addEventListener('change', toggleFields);
  toggleFields();
})();
</script>
