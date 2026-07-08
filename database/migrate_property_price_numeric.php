<?php

declare(strict_types=1);

/**
 * Add price_numeric for reliable property search filtering.
 * php database/migrate_property_price_numeric.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/properties.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Price numeric migration</title></head><body>';
    echo '<h1>Property price_numeric migration</h1>';
}

try {
    $pdo = db();

    try {
        $pdo->exec('ALTER TABLE properties ADD COLUMN price_numeric INT UNSIGNED NULL AFTER price');
        $out('Added column <strong>price_numeric</strong>.');
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            $out('Column <strong>price_numeric</strong> already exists.');
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec('CREATE INDEX idx_price_numeric ON properties (price_numeric)');
        $out('Index <strong>idx_price_numeric</strong> ready.');
    } catch (Throwable) {
        $out('Index idx_price_numeric already exists or could not be created.');
    }

    $count = property_sync_all_price_numeric();
    $out('Synced price_numeric for ' . $count . ' properties.');
} catch (Throwable $e) {
    $out('Failed: ' . $e->getMessage());
}

if (!$isCli) {
    echo '</body></html>';
}
