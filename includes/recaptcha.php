<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function recaptcha_enabled(): bool
{
    return defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== ''
        && defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY !== '';
}

function recaptcha_verify_token(?string $token): ?string
{
    if (!recaptcha_enabled()) {
        return null;
    }

    $token = trim($token ?? '');
    if ($token === '') {
        return 'Please complete the security check.';
    }

    $payload = http_build_query([
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    $response = false;

    if (function_exists('curl_init')) {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);
        $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    }

    if ($response === false || $response === '') {
        return 'Security check could not be completed. Please try again.';
    }

    $data = json_decode((string) $response, true);
    if (!is_array($data) || empty($data['success'])) {
        return 'Security check failed. Please try again.';
    }

    return null;
}

function recaptcha_script_tag(): void
{
    if (!recaptcha_enabled()) {
        return;
    }

    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php
}

function recaptcha_widget(): void
{
    if (!recaptcha_enabled()) {
        return;
    }
    ?>
    <div class="enquiry-recaptcha">
      <div class="g-recaptcha" data-sitekey="<?php echo e(RECAPTCHA_SITE_KEY); ?>"></div>
    </div>
    <?php
}
