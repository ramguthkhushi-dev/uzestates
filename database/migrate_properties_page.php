<?php

declare(strict_types=1);

/**
 * Properties page hero settings — run once:
 * http://localhost/uz5/database/migrate_properties_page.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Properties page migration</title></head><body>';
    echo '<h1>Properties page migration</h1>';
}

try {
    $pdo = db();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS properties_page_settings (
          id              INT UNSIGNED NOT NULL PRIMARY KEY,
          hero_kicker     VARCHAR(255) NULL,
          hero_title      VARCHAR(255) NULL,
          hero_lead       TEXT         NULL,
          hero_image      TEXT         NULL,
          hero_image_alt  VARCHAR(255) NULL,
          updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
    $out('OK: properties_page_settings table ready');

    $pdo->exec(
        "INSERT INTO properties_page_settings (id, hero_kicker, hero_title, hero_lead, hero_image, hero_image_alt)
         VALUES (
           1,
           'Browse Listings',
           'Properties in Mauritius.',
           'Plots, villas and investment opportunities across Grand Baie and beyond.',
           'images/properties_page/hero.png',
           'Properties in Mauritius'
         )
         ON DUPLICATE KEY UPDATE id = id"
    );
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
