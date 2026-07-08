<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/settings.php';

header('Content-Type: text/plain; charset=utf-8');

$row = settings_contact();
$phoneLocal    = phone_tel_local((string) ($row['phone'] ?? ''));
$whatsappIntl  = phone_whatsapp_digits((string) ($row['whatsapp'] ?? ''));

if ($phoneLocal === '' && $whatsappIntl === '') {
    echo "No contact phone row to normalize.\n";
    exit;
}

db()->prepare(
    'UPDATE contact_settings SET phone = :phone, whatsapp = :whatsapp, updated_at = NOW() WHERE id = 1'
)->execute([
    'phone'    => $phoneLocal !== '' ? $phoneLocal : null,
    'whatsapp' => $whatsappIntl !== '' ? $whatsappIntl : null,
]);

echo "Normalized contact phone storage.\n";
print_r(settings_contact_public());
