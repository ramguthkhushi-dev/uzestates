<?php

declare(strict_types=1);

/**
 * Password field with show/hide toggle for admin auth & account forms.
 *
 * @var string $label
 * @var string $name
 * @var string|null $errorKey
 * @var array<string, scalar|null> $inputAttrs
 */
$label      = $label ?? 'Password';
$name       = $name ?? 'password';
$errorKey   = $errorKey ?? null;
$inputAttrs = $inputAttrs ?? [];
$inputId    = (string) ($inputAttrs['id'] ?? $name . '_field');
unset($inputAttrs['id']);

$attrParts = [];
foreach ($inputAttrs as $attrName => $attrValue) {
    if ($attrValue === null || $attrValue === false) {
        continue;
    }
    if ($attrValue === true) {
        $attrParts[] = e((string) $attrName);
        continue;
    }
    $attrParts[] = e((string) $attrName) . '="' . e((string) $attrValue) . '"';
}
$attrsHtml = $attrParts !== [] ? ' ' . implode(' ', $attrParts) : '';
?>
<label class="form-field" for="<?php echo e($inputId); ?>">
  <span><?php echo e($label); ?></span>
  <div class="admin-password-wrap">
    <input
      type="password"
      name="<?php echo e($name); ?>"
      id="<?php echo e($inputId); ?>"
      <?php echo $attrsHtml; ?>
    />
    <button type="button" class="admin-password-toggle" data-toggle-password aria-label="Show password">
      <svg class="admin-eye-show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/>
        <circle cx="12" cy="12" r="3"/>
      </svg>
      <svg class="admin-eye-hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-8-10-8a18.45 18.45 0 0 1 5.06-5.94"/>
        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19"/>
        <line x1="1" y1="1" x2="23" y2="23"/>
      </svg>
    </button>
  </div>
  <?php if ($errorKey !== null && $errorKey !== ''): ?>
    <small class="admin-field-error" data-error-for="<?php echo e($errorKey); ?>"></small>
  <?php endif; ?>
</label>
