<?php

declare(strict_types=1);

/**
 * @return array{name: string, phone: string, email: string, message: string, interested_property: string}
 */
function enquiry_form_old_from_request(array $source): array
{
    return [
        'name'                => trim($source['name'] ?? ''),
        'phone'               => trim($source['phone'] ?? ''),
        'email'               => trim($source['email'] ?? ''),
        'message'             => trim($source['message'] ?? ''),
        'interested_property' => trim($source['interested_property'] ?? ''),
    ];
}

function enquiry_normalize_phone(string $phone): string
{
    $phone = trim($phone);
    if ($phone === '') {
        return '';
    }

    if (str_starts_with($phone, '+')) {
        $digits = preg_replace('/\D/', '', substr($phone, 1)) ?? '';
        if ($digits !== '' && preg_match('/^[1-9]\d{6,14}$/', $digits)) {
            return '+' . $digits;
        }

        return '';
    }

    $digits = preg_replace('/\D/', '', $phone) ?? '';
    if ($digits === '') {
        return '';
    }

    // Mauritius local mobile without country code, e.g. 58154042
    if (strlen($digits) === 8 && str_starts_with($digits, '5')) {
        return '+230' . $digits;
    }

    // Full international number submitted without + prefix
    if (preg_match('/^[1-9]\d{6,14}$/', $digits)) {
        return '+' . $digits;
    }

    return '';
}

function enquiry_validate_phone(string $phone): ?string
{
    $phone = trim($phone);
    if ($phone === '') {
        return null;
    }

    $normalized = enquiry_normalize_phone($phone);
    if ($normalized === '' || !preg_match('/^\+[1-9]\d{6,14}$/', $normalized)) {
        return 'Please enter a valid phone number.';
    }

    return null;
}

function enquiry_phone_intl_version(): string
{
    return '25.3.1';
}

function enquiry_phone_stylesheet_tag(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $version = enquiry_phone_intl_version();
    $cdn     = 'https://cdn.jsdelivr.net/npm/intl-tel-input@' . $version;
    $local   = BASE_URL . '/css/enquiry-phone.css?v=' . (int) @filemtime(__DIR__ . '/../css/enquiry-phone.css');
    ?>
    <link rel="stylesheet" href="<?php echo e($cdn); ?>/build/css/intlTelInput.min.css" />
    <link rel="stylesheet" href="<?php echo e($local); ?>" />
    <?php
}

function enquiry_phone_script_tags(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $version = enquiry_phone_intl_version();
    $cdn     = 'https://cdn.jsdelivr.net/npm/intl-tel-input@' . $version;
    ?>
    <script src="<?php echo e($cdn); ?>/build/js/intlTelInput.min.js"></script>
    <?php
}

function enquiry_phone_field(string $value = '', string $label = 'Phone', bool $required = true): void
{
    $normalized = enquiry_normalize_phone($value);
    if ($normalized !== '') {
        $value = $normalized;
    }
    ?>
    <div class="enquiry-phone-field" data-enquiry-phone>
      <input
        type="tel"
        name="phone"
        class="enquiry-phone-input"
        autocomplete="tel"
        inputmode="tel"
        aria-label="<?php echo e($label); ?>"
        <?php echo $required ? 'required' : ''; ?>
        value="<?php echo e($value); ?>"
      />
    </div>
    <?php
}

function enquiry_honeypot_field(): void
{
    ?>
    <div class="enquiry-honeypot" aria-hidden="true">
      <label>
        Leave this blank
        <input type="text" name="website" tabindex="-1" autocomplete="off" value="" />
      </label>
    </div>
    <?php
}

function enquiry_recaptcha_fields(): void
{
    require_once __DIR__ . '/recaptcha.php';
    recaptcha_widget();
}
