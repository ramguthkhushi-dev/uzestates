<?php

declare(strict_types=1);

/**
 * About page admin sections — run once:
 * http://localhost/uz5/database/migrate_about_page.php
 * Or: php database/migrate_about_page.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>About page migration</title></head><body>';
    echo '<h1>About page migration</h1>';
}

try {
    $pdo = db();

    $columns = static function (string $table) use ($pdo): array {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        return array_column($stmt->fetchAll(), 'Field');
    };

    $addColumn = static function (string $sql) use ($pdo, $out): void {
        try {
            $pdo->exec($sql);
            $out('OK: ' . $sql);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate column')) {
                $out('Skip (exists): ' . $sql);
            } else {
                throw $e;
            }
        }
    };

    $aboutCols = $columns('about_settings');

    foreach ([
        'hero_title VARCHAR(255) NULL',
        'hero_text TEXT NULL',
        'hero_image TEXT NULL',
        'hero_kicker VARCHAR(255) NULL',
        'hero_title_line2 VARCHAR(255) NULL',
        'hero_btn_text VARCHAR(255) NULL',
        'hero_btn_url VARCHAR(500) NULL',
        'hero_image_alt VARCHAR(255) NULL',
        'approach_image TEXT NULL',
        'approach_image_alt VARCHAR(255) NULL',
        'story_json JSON NULL',
        'values_json JSON NULL',
        'approach_json JSON NULL',
        'process_json JSON NULL',
        'faq_json JSON NULL',
        'cta_json JSON NULL',
    ] as $def) {
        [$name] = explode(' ', $def);
        if (!in_array($name, $aboutCols, true)) {
            $addColumn("ALTER TABLE about_settings ADD COLUMN $def");
        } else {
            $out("Skip (exists): $name");
        }
    }

    $pdo->exec('INSERT INTO about_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id = id');
    $out('Done. About settings row ready.');
} catch (Throwable $e) {
    $out('Error: ' . $e->getMessage());
    if (!$isCli) {
        echo '</body></html>';
    }
    exit(1);
}

if (!$isCli) {
    echo '</body></html>';
}
