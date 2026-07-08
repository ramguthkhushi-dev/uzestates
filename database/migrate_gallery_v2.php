<?php

declare(strict_types=1);

/**
 * Gallery v2 — dynamic layout types, image/video/text cards, display_order.
 * Run once: php database/migrate_gallery_v2.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$out = static function (string $msg): void {
    echo $msg . PHP_EOL;
};

    $hasGalleryTable = false;
    try {
        $pdo->query("SELECT 1 FROM `gallery` LIMIT 1");
        $hasGalleryTable = true;
    } catch (PDOException $e) {
        $hasGalleryTable = false;
    }

    if (!$hasGalleryTable) {
        $out('Skip gallery v2: Table gallery does not exist.');
        exit(0);
    }

    try {
        $cols = array_column($pdo->query('SHOW COLUMNS FROM gallery')->fetchAll(PDO::FETCH_ASSOC), 'Field');

    $addColumn = static function (string $sql) use ($pdo, $out): void {
        $pdo->exec($sql);
        $out('  + ' . $sql);
    };

    if (!in_array('description', $cols, true)) {
        $addColumn('ALTER TABLE gallery ADD COLUMN description TEXT NULL AFTER title');
    }

    if (!in_array('layout_type', $cols, true)) {
        $addColumn("ALTER TABLE gallery ADD COLUMN layout_type VARCHAR(32) NOT NULL DEFAULT 'small' AFTER media_type");
    }

    if (!in_array('thumbnail', $cols, true)) {
        $addColumn('ALTER TABLE gallery ADD COLUMN thumbnail VARCHAR(500) NULL AFTER file_path');
    }

    if (!in_array('external_url', $cols, true)) {
        $addColumn('ALTER TABLE gallery ADD COLUMN external_url TEXT NULL AFTER thumbnail');
    }

    if (!in_array('button_text', $cols, true)) {
        $addColumn('ALTER TABLE gallery ADD COLUMN button_text VARCHAR(255) NULL AFTER external_url');
    }

    if (!in_array('button_link', $cols, true)) {
        $addColumn('ALTER TABLE gallery ADD COLUMN button_link VARCHAR(500) NULL AFTER button_text');
    }

    if (!in_array('icon', $cols, true)) {
        $addColumn('ALTER TABLE gallery ADD COLUMN icon VARCHAR(50) NULL AFTER button_link');
    }

    if (!in_array('card_style', $cols, true)) {
        $addColumn("ALTER TABLE gallery ADD COLUMN card_style VARCHAR(20) NOT NULL DEFAULT 'light' AFTER icon");
    }

    if (!in_array('display_order', $cols, true)) {
        if (in_array('sort_order', $cols, true)) {
            $addColumn('ALTER TABLE gallery CHANGE sort_order display_order INT NOT NULL DEFAULT 0');
        } else {
            $addColumn('ALTER TABLE gallery ADD COLUMN display_order INT NOT NULL DEFAULT 0');
        }
    }

    if (!in_array('is_visible', $cols, true)) {
        if (in_array('is_active', $cols, true)) {
            $addColumn('ALTER TABLE gallery CHANGE is_active is_visible TINYINT(1) NOT NULL DEFAULT 1');
        } else {
            $addColumn('ALTER TABLE gallery ADD COLUMN is_visible TINYINT(1) NOT NULL DEFAULT 1');
        }
    }

    // Normalise media_type values: photo -> image, allow text
    $pdo->exec("UPDATE gallery SET media_type = 'image' WHERE media_type IN ('photo', 'Photo')");
    $pdo->exec("UPDATE gallery SET layout_type = 'small' WHERE layout_type IS NULL OR layout_type = ''");

    // Make file_path nullable for text cards
    $pdo->exec('ALTER TABLE gallery MODIFY file_path VARCHAR(500) NULL');
    $pdo->exec("ALTER TABLE gallery MODIFY media_type VARCHAR(20) NOT NULL DEFAULT 'image'");

    $count = (int) $pdo->query('SELECT COUNT(*) FROM gallery')->fetchColumn();
    if ($count === 0) {
        $out('Seeding default text cards…');
        $seed = [
            [
                'title'         => 'Modern Villas',
                'description'   => 'Contemporary living spaces designed for comfort.',
                'media_type'    => 'text',
                'layout_type'   => 'small',
                'button_text'   => 'View Collection',
                'button_link'   => 'properties.php?category=villas',
                'icon'          => 'home',
                'display_order' => 30,
            ],
            [
                'title'         => 'Grand Baie',
                'description'   => "Villas and plots in the heart of Mauritius' north coast.",
                'media_type'    => 'text',
                'layout_type'   => 'small',
                'button_text'   => 'View Location',
                'button_link'   => 'properties.php?keyword=Grand+Baie',
                'icon'          => 'pin',
                'display_order' => 70,
            ],
            [
                'title'         => 'Residential Plots',
                'description'   => 'Land and villa listings across prime north-coast locations.',
                'media_type'    => 'text',
                'layout_type'   => 'small',
                'button_text'   => 'Explore Now',
                'button_link'   => 'properties.php?category=plots',
                'icon'          => 'layers',
                'card_style'    => 'dark',
                'display_order' => 100,
            ],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO gallery (title, description, media_type, layout_type, button_text, button_link, icon, card_style, display_order, is_visible, file_path)
             VALUES (:title, :description, :media_type, :layout_type, :button_text, :button_link, :icon, :card_style, :display_order, 1, NULL)'
        );

        foreach ($seed as $row) {
            $stmt->execute([
                'title'         => $row['title'],
                'description'   => $row['description'],
                'media_type'    => $row['media_type'],
                'layout_type'   => $row['layout_type'],
                'button_text'   => $row['button_text'],
                'button_link'   => $row['button_link'],
                'icon'          => $row['icon'],
                'card_style'    => $row['card_style'] ?? 'light',
                'display_order' => $row['display_order'],
            ]);
        }
    }

    $out('Gallery v2 migration complete.');
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
