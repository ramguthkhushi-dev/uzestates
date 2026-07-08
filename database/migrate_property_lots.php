<?php

declare(strict_types=1);

/**
 * Add property_lots table for multi-lot villa listings.
 * Run once: php database/migrate_property_lots.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Property lots migration</title></head><body>';
    echo '<h1>Property lots migration</h1>';
}

try {
    db()->exec(
        'CREATE TABLE IF NOT EXISTS property_lots (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          property_id INT UNSIGNED NOT NULL,
          label VARCHAR(100) NULL,
          size VARCHAR(100) NOT NULL,
          price VARCHAR(100) NOT NULL,
          display_order INT NOT NULL DEFAULT 0,
          FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
          INDEX idx_property (property_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $out('Table <strong>property_lots</strong> is ready.');
} catch (Throwable $e) {
    $out('Failed: ' . $e->getMessage());
}

if (!$isCli) {
    echo '</body></html>';
}
