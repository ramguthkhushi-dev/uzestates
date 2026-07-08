<?php

declare(strict_types=1);

/**
 * Gallery page hero settings — run once:
 * http://localhost/uz5/database/migrate_gallery_page.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Gallery page migration</title></head><body>';
    echo '<h1>Gallery page migration</h1>';
}

try {
    $pdo = db();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS gallery_page_settings (
          id            INT UNSIGNED NOT NULL PRIMARY KEY,
          hero_kicker   VARCHAR(255) NULL,
          hero_title    VARCHAR(255) NULL,
          hero_lead     TEXT         NULL,
          hero_btn_text VARCHAR(255) NULL,
          hero_btn_url  VARCHAR(500) NULL,
          hero_image    TEXT         NULL,
          updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
    $out('OK: gallery_page_settings table ready');

    $pdo->exec('INSERT INTO gallery_page_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id = id');
    $out('Done.');
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
