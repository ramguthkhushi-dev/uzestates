<?php

declare(strict_types=1);

/**
 * Restore gallery slots and property media from the bundled uz5.zip backup.
 * Run: php database/restore_media_backup.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Restore Media</title></head><body><h1>Restore media backup</h1>';
}

$zipPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uz5.zip';
if (!is_file($zipPath)) {
    $out('ERROR: uz5.zip not found at ' . $zipPath);
    exit(1);
}

$out('Found backup: ' . $zipPath);

// ---------------------------------------------------------------------------
// 1. Restore Almaris Villas folder from zip (original: root photos + option2)
// ---------------------------------------------------------------------------
$almarisDir = APP_ROOT . '/images/properties_page/Almaris Villas';
$removeDirs = ['lot_1', 'lot_2', 'lot1', 'lot2', 'option1'];

foreach ($removeDirs as $subdir) {
    $path = $almarisDir . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($path)) {
        continue;
    }
    remove_tree($path);
    $out('Removed non-original folder: Almaris Villas/' . $subdir);
}

$zipPrefix = 'uz5/images/properties_page/Almaris Villas/';
$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    $out('ERROR: Could not open uz5.zip');
    exit(1);
}

$restoredFiles = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if ($name === false || !str_starts_with($name, $zipPrefix) || str_ends_with($name, '/')) {
        continue;
    }

    $relative = substr($name, strlen($zipPrefix));
    if ($relative === '' || str_contains($relative, '..')) {
        continue;
    }

    $target = $almarisDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $targetDir = dirname($target);
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $contents = $zip->getFromIndex($i);
    if ($contents === false) {
        continue;
    }

    file_put_contents($target, $contents);
    $restoredFiles++;
}

$zip->close();
$out("Restored {$restoredFiles} Almaris Villas media file(s) from backup.");

// ---------------------------------------------------------------------------
// 2. Restore homepage gallery slots (videos on slots 1 & 8)
// ---------------------------------------------------------------------------
$pdo = db();

$galleryDefaults = [
    1 => [
        'media_type'     => 'video',
        'file_path'      => 'gallery/up_6a411acc1388d4.79025401.mp4',
        'thumbnail_path' => 'gallery/up_6a411acc144ec1.34528305.png',
    ],
    2 => [
        'media_type'     => 'image',
        'file_path'      => 'gallery/up_6a412bfbc57dd2.42470603.png',
        'thumbnail_path' => null,
    ],
    3 => [
        'media_type'     => 'text',
        'file_path'      => null,
        'thumbnail_path' => null,
        'title'          => 'Modern Villas',
        'description'    => 'Contemporary living spaces designed for comfort.',
        'button_text'    => 'View Collection',
        'button_link'    => 'properties.php?category=villas',
        'icon'           => 'home',
        'card_style'     => 'light',
    ],
    4 => [
        'media_type'     => 'image',
        'file_path'      => 'gallery/up_6a412d688ac632.65439732.png',
        'thumbnail_path' => null,
    ],
    5 => [
        'media_type'     => 'image',
        'file_path'      => 'gallery/up_6a4125a2db50d4.10008345.png',
        'thumbnail_path' => null,
    ],
    6 => [
        'media_type'     => 'image',
        'file_path'      => 'gallery/up_6a4128a9670e93.45907298.png',
        'thumbnail_path' => null,
    ],
    7 => [
        'media_type'     => 'text',
        'file_path'      => null,
        'thumbnail_path' => null,
        'title'          => 'Grand Baie',
        'description'    => "Villas and plots in the heart of Mauritius' north coast.",
        'button_text'    => 'View Location',
        'button_link'    => 'properties.php?keyword=Grand+Baie',
        'icon'           => 'pin',
        'card_style'     => 'light',
    ],
    8 => [
        'media_type'     => 'video',
        'file_path'      => 'gallery/up_6a41209ec748b5.29671819.mp4',
        'thumbnail_path' => 'gallery/up_6a41209ec818f2.97801736.png',
    ],
    9 => [
        'media_type'     => 'image',
        'file_path'      => 'gallery/up_6a41234d8e4848.08619337.png',
        'thumbnail_path' => null,
    ],
    10 => [
        'media_type'     => 'text',
        'file_path'      => null,
        'thumbnail_path' => null,
        'title'          => 'Residential Plots',
        'description'    => 'Land and villa listings across prime north-coast locations.',
        'button_text'    => 'Explore Now',
        'button_link'    => 'properties.php?category=plots',
        'icon'           => 'layers',
        'card_style'     => 'dark',
    ],
    11 => [
        'media_type'     => 'image',
        'file_path'      => 'gallery/up_6a41293f033378.77223372.png',
        'thumbnail_path' => null,
    ],
];

$update = $pdo->prepare(
    'UPDATE gallery_slots SET
      media_type = :media_type,
      file_path = :file_path,
      thumbnail_path = :thumbnail_path,
      title = COALESCE(:title, title),
      description = COALESCE(:description, description),
      button_text = COALESCE(:button_text, button_text),
      button_link = COALESCE(:button_link, button_link),
      icon = COALESCE(:icon, icon),
      card_style = COALESCE(:card_style, card_style),
      is_visible = 1
     WHERE slot_number = :slot_number'
);

foreach ($galleryDefaults as $slotNumber => $data) {
    $update->execute([
        'slot_number'     => $slotNumber,
        'media_type'      => $data['media_type'],
        'file_path'       => $data['file_path'],
        'thumbnail_path'  => $data['thumbnail_path'],
        'title'           => $data['title'] ?? null,
        'description'     => $data['description'] ?? null,
        'button_text'     => $data['button_text'] ?? null,
        'button_link'     => $data['button_link'] ?? null,
        'icon'            => $data['icon'] ?? null,
        'card_style'      => $data['card_style'] ?? null,
    ]);
    $out('Gallery slot #' . $slotNumber . ' restored (' . $data['media_type'] . ').');
}

$out('Media restore complete. Hard-refresh gallery.php and property pages (Ctrl+F5).');

if (!$isCli) {
    echo '</body></html>';
}

function remove_tree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            remove_tree($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}
