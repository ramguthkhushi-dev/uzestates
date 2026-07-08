<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

$slots = db()->query('SELECT slot_number, slot_name, file_path, media_type FROM gallery_slots ORDER BY slot_number')->fetchAll();
echo "gallery_slots: " . count($slots) . "\n";
foreach ($slots as $s) {
    $path = $s['file_path'] ?? '';
    $exists = $path !== '' && is_file(UPLOAD_PATH . '/' . ltrim($path, '/')) ? 'OK' : 'MISSING';
    echo "  #{$s['slot_number']} {$s['slot_name']} | {$path} | {$exists}\n";
}
