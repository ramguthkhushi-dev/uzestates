<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/settings.php';
require __DIR__ . '/../includes/homepage.php';

$h = settings_homepage();
echo 'hero_image: ' . ($h['hero_image'] ?? '(default)') . PHP_EOL;
echo 'hero_title: ' . ($h['hero_title'] ?? '') . PHP_EOL;

$c = settings_contact();
echo 'phone: ' . ($c['phone'] ?? '') . PHP_EOL;
echo 'email: ' . ($c['email'] ?? '') . PHP_EOL;

$slots = db()->query('SELECT slot_number, slot_name, file_path, title FROM gallery_slots ORDER BY slot_number')->fetchAll();
foreach ($slots as $s) {
    echo "gallery #{$s['slot_number']}: {$s['file_path']} | {$s['title']}\n";
}
