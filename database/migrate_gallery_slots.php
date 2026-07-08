<?php

declare(strict_types=1);

/**
 * Fixed gallery slots — run once: php database/migrate_gallery_slots.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$out = static function (string $msg): void {
    echo $msg . PHP_EOL;
};

$fixedSlots = [
    ['slot_number' => 1,  'slot_name' => 'Wide banner top',      'slot_size' => 'large_horizontal'],
    ['slot_number' => 2,  'slot_name' => 'Square top right',     'slot_size' => 'small'],
    ['slot_number' => 3,  'slot_name' => 'Modern Villas card',   'slot_size' => 'text_card'],
    ['slot_number' => 4,  'slot_name' => 'Square mid left',      'slot_size' => 'small'],
    ['slot_number' => 5,  'slot_name' => 'Square mid centre',    'slot_size' => 'small'],
    ['slot_number' => 6,  'slot_name' => 'Wide banner mid',      'slot_size' => 'large_horizontal'],
    ['slot_number' => 7,  'slot_name' => 'Grand Baie card',      'slot_size' => 'text_card'],
    ['slot_number' => 8,  'slot_name' => 'Tall feature',         'slot_size' => 'large_vertical'],
    ['slot_number' => 9,  'slot_name' => 'Wide banner lower',    'slot_size' => 'large_horizontal'],
    ['slot_number' => 10, 'slot_name' => 'Residential Plots card', 'slot_size' => 'text_card'],
    ['slot_number' => 11, 'slot_name' => 'Square bottom',        'slot_size' => 'small'],
];

$textDefaults = [
    3 => [
        'title'       => 'Modern Villas',
        'description' => 'Contemporary living spaces designed for comfort.',
        'button_text' => 'View Collection',
        'button_link' => 'properties.php?category=villas',
        'icon'        => 'home',
        'card_style'  => 'light',
    ],
    7 => [
        'title'       => 'Grand Baie',
        'description' => "Villas and plots in the heart of Mauritius' north coast.",
        'button_text' => 'View Location',
        'button_link' => 'properties.php?keyword=Grand+Baie',
        'icon'        => 'pin',
        'card_style'  => 'light',
    ],
    10 => [
        'title'       => 'Residential Plots',
        'description' => 'Land and villa listings across prime north-coast locations.',
        'button_text' => 'Explore Now',
        'button_link' => 'properties.php?category=plots',
        'icon'        => 'layers',
        'card_style'  => 'dark',
    ],
];

try {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS gallery_slots (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slot_number INT UNSIGNED NOT NULL,
            slot_name VARCHAR(100) NOT NULL,
            slot_size VARCHAR(32) NOT NULL,
            media_type VARCHAR(20) NOT NULL DEFAULT \'image\',
            title VARCHAR(255) NULL,
            description TEXT NULL,
            file_path VARCHAR(500) NULL,
            thumbnail_path VARCHAR(500) NULL,
            external_url TEXT NULL,
            button_text VARCHAR(255) NULL,
            button_link VARCHAR(500) NULL,
            icon VARCHAR(50) NULL,
            card_style VARCHAR(20) NOT NULL DEFAULT \'light\',
            is_visible TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_slot_number (slot_number),
            INDEX idx_visible (is_visible)
        ) ENGINE=InnoDB'
    );
    $out('gallery_slots table ready.');

    $count = (int) $pdo->query('SELECT COUNT(*) FROM gallery_slots')->fetchColumn();
    if ($count > 0) {
        $out("gallery_slots already has {$count} row(s) - checking bundled media.");
        backfill_gallery_slot_media($pdo);
        $out('Gallery media backfill complete.');
        exit(0);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO gallery_slots (
            slot_number, slot_name, slot_size, media_type, title, description,
            button_text, button_link, icon, card_style, is_visible
        ) VALUES (
            :slot_number, :slot_name, :slot_size, :media_type, :title, :description,
            :button_text, :button_link, :icon, :card_style, 1
        )'
    );

    foreach ($fixedSlots as $slot) {
        $num = (int) $slot['slot_number'];
        $isText = $slot['slot_size'] === 'text_card';
        $defaults = $textDefaults[$num] ?? [];

        $stmt->execute([
            'slot_number' => $num,
            'slot_name'   => $slot['slot_name'],
            'slot_size'   => $slot['slot_size'],
            'media_type'  => $isText ? 'text' : 'image',
            'title'       => $defaults['title'] ?? null,
            'description' => $defaults['description'] ?? null,
            'button_text' => $defaults['button_text'] ?? null,
            'button_link' => $defaults['button_link'] ?? null,
            'icon'        => $defaults['icon'] ?? null,
            'card_style'  => $defaults['card_style'] ?? 'light',
        ]);
    }

    $out('Seeded 11 fixed gallery slots.');
    backfill_gallery_slot_media($pdo);
    $out('Gallery slots migration complete.');
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

function backfill_gallery_slot_media(PDO $pdo): void
{
    $galleryFiles = [];
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
        foreach (glob(APP_ROOT . '/uploads/gallery/*.' . $extension) ?: [] as $file) {
            $galleryFiles[] = $file;
        }
    }

    sort($galleryFiles, SORT_NATURAL);

    $stmt = $pdo->prepare(
        'UPDATE gallery_slots
         SET media_type = \'image\', file_path = :file_path
         WHERE slot_number = :slot_number
           AND (file_path IS NULL OR file_path = \'\')'
    );

    foreach ([1, 2, 4, 5, 6, 8, 9, 11] as $index => $slotNumber) {
        if (!isset($galleryFiles[$index])) {
            break;
        }

        $stmt->execute([
            'slot_number' => $slotNumber,
            'file_path' => 'gallery/' . basename($galleryFiles[$index]),
        ]);
    }
}
