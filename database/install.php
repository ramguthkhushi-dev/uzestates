<?php

declare(strict_types=1);

/**
 * Database installer — run once via browser or CLI:
 *   http://localhost/uz5/database/install.php
 *   php database/install.php
 */

require_once __DIR__ . '/../config/config.php';

$isCli = PHP_SAPI === 'cli';

function output(string $message, bool $isCli): void
{
    if ($isCli) {
        echo strip_tags($message) . PHP_EOL;
        return;
    }

    echo '<p>' . $message . '</p>';
}

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>UZ Estates Install</title>';
    echo '<style>body{font-family:Arial,sans-serif;max-width:640px;margin:40px auto;padding:0 20px;line-height:1.6}';
    echo '.ok{color:#2d6a4f}.err{color:#c1121f}h1{color:#31483f}</style></head><body>';
    echo '<h1>UZ Estates — Database Install</h1>';
}

try {
    $dsn = sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    output('Connected to MySQL.', $isCli);

    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', DB_NAME)
    ));

    output('Database <strong>' . e(DB_NAME) . '</strong> ready.', $isCli);

    $pdo->exec('USE `' . str_replace('`', '``', DB_NAME) . '`');

    $schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Could not read schema.sql');
    }

    // Remove CREATE DATABASE and USE lines — handled above
    $schema = preg_replace('/CREATE DATABASE.*?;/is', '', $schema);
    $schema = preg_replace('/USE\s+\w+\s*;/i', '', $schema);

    $statements = array_filter(
        array_map('trim', preg_split('/;\s*\n/', $schema)),
        fn(string $s) => $s !== ''
    );

    foreach ($statements as $statement) {
        // Strip line comments so blocks after header comments are not skipped
        $statement = preg_replace('/^--.*$/m', '', $statement);
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }

    output('Tables and default data created successfully.', $isCli);

    seed_gallery_slots($pdo, $isCli);

    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE admins SET password_hash = :hash WHERE username = :username');
    $stmt->execute(['hash' => $passwordHash, 'username' => 'admin']);

    output('Default admin: username <strong>admin</strong>, password <strong>admin123</strong>', $isCli);
    output('Change this password after your first login.', $isCli);

    $dirs = [
        UPLOAD_PATH . '/properties/images',
        UPLOAD_PATH . '/properties/videos',
        UPLOAD_PATH . '/properties/sitemaps',
        UPLOAD_PATH . '/gallery',
        UPLOAD_PATH . '/gallery-page',
        UPLOAD_PATH . '/homepage',
        UPLOAD_PATH . '/about',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    output('Upload directories created.', $isCli);
    output('Install complete. <a href="' . BASE_URL . '/admin/login.php">Go to admin login</a>', $isCli);
} catch (Throwable $e) {
    if ($isCli) {
        fwrite(STDERR, 'Install failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }

    echo '<p class="err">Install failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Make sure XAMPP MySQL is running and credentials in config/config.php are correct.</p>';
}

if (!$isCli) {
    echo '</body></html>';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function seed_gallery_slots(PDO $pdo, bool $isCli): void
{
    $fixedSlots = [
        ['slot_number' => 1,  'slot_name' => 'Wide banner top',        'slot_size' => 'large_horizontal'],
        ['slot_number' => 2,  'slot_name' => 'Square top right',       'slot_size' => 'small'],
        ['slot_number' => 3,  'slot_name' => 'Modern Villas card',     'slot_size' => 'text_card'],
        ['slot_number' => 4,  'slot_name' => 'Square mid left',        'slot_size' => 'small'],
        ['slot_number' => 5,  'slot_name' => 'Square mid centre',      'slot_size' => 'small'],
        ['slot_number' => 6,  'slot_name' => 'Wide banner mid',        'slot_size' => 'large_horizontal'],
        ['slot_number' => 7,  'slot_name' => 'Grand Baie card',        'slot_size' => 'text_card'],
        ['slot_number' => 8,  'slot_name' => 'Tall feature',           'slot_size' => 'large_vertical'],
        ['slot_number' => 9,  'slot_name' => 'Wide banner lower',      'slot_size' => 'large_horizontal'],
        ['slot_number' => 10, 'slot_name' => 'Residential Plots card', 'slot_size' => 'text_card'],
        ['slot_number' => 11, 'slot_name' => 'Square bottom',          'slot_size' => 'small'],
    ];

    $textDefaults = [
        3 => [
            'title' => 'Modern Villas',
            'description' => 'Contemporary living spaces designed for comfort.',
            'button_text' => 'View Collection',
            'button_link' => 'properties.php?category=villas',
            'icon' => 'home',
            'card_style' => 'light',
        ],
        7 => [
            'title' => 'Grand Baie',
            'description' => "Villas and plots in the heart of Mauritius' north coast.",
            'button_text' => 'View Location',
            'button_link' => 'properties.php?keyword=Grand+Baie',
            'icon' => 'pin',
            'card_style' => 'light',
        ],
        10 => [
            'title' => 'Residential Plots',
            'description' => 'Land and villa listings across prime north-coast locations.',
            'button_text' => 'Explore Now',
            'button_link' => 'properties.php?category=plots',
            'icon' => 'layers',
            'card_style' => 'dark',
        ],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO gallery_slots (
            slot_number, slot_name, slot_size, media_type, title, description,
            button_text, button_link, icon, card_style, is_visible
        ) VALUES (
            :slot_number, :slot_name, :slot_size, :media_type, :title, :description,
            :button_text, :button_link, :icon, :card_style, 1
        )
        ON DUPLICATE KEY UPDATE
            slot_name = VALUES(slot_name),
            slot_size = VALUES(slot_size),
            title = COALESCE(title, VALUES(title)),
            description = COALESCE(description, VALUES(description)),
            button_text = COALESCE(button_text, VALUES(button_text)),
            button_link = COALESCE(button_link, VALUES(button_link)),
            icon = COALESCE(icon, VALUES(icon)),
            card_style = COALESCE(card_style, VALUES(card_style))'
    );

    foreach ($fixedSlots as $slot) {
        $number = (int) $slot['slot_number'];
        $defaults = $textDefaults[$number] ?? [];
        $isText = $slot['slot_size'] === 'text_card';

        $stmt->execute([
            'slot_number' => $number,
            'slot_name' => $slot['slot_name'],
            'slot_size' => $slot['slot_size'],
            'media_type' => $isText ? 'text' : 'image',
            'title' => $defaults['title'] ?? null,
            'description' => $defaults['description'] ?? null,
            'button_text' => $defaults['button_text'] ?? null,
            'button_link' => $defaults['button_link'] ?? null,
            'icon' => $defaults['icon'] ?? null,
            'card_style' => $defaults['card_style'] ?? 'light',
        ]);
    }

    $galleryFiles = [];
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
        foreach (glob(APP_ROOT . '/uploads/gallery/*.' . $extension) ?: [] as $file) {
            $galleryFiles[] = $file;
        }
    }

    sort($galleryFiles, SORT_NATURAL);

    $imageSlotNumbers = [1, 2, 4, 5, 6, 8, 9, 11];
    $assign = $pdo->prepare(
        'UPDATE gallery_slots
         SET media_type = \'image\', file_path = :file_path
         WHERE slot_number = :slot_number
           AND (file_path IS NULL OR file_path = \'\')'
    );

    foreach ($imageSlotNumbers as $index => $slotNumber) {
        if (!isset($galleryFiles[$index])) {
            break;
        }

        $assign->execute([
            'slot_number' => $slotNumber,
            'file_path' => 'gallery/' . basename($galleryFiles[$index]),
        ]);
    }

    output('Gallery slots and bundled gallery images ready.', $isCli);
}
