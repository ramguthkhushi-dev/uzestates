<?php

declare(strict_types=1);

/**
 * Villa lots v2 — description, room details, per-lot media.
 * Run once: php database/migrate_property_lots_v2.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Lots v2 migration</title></head><body>';
    echo '<h1>Property lots v2 migration</h1>';
}

try {
    $pdo = db();

    $lotCols = $pdo->query('SHOW COLUMNS FROM property_lots')->fetchAll(PDO::FETCH_COLUMN);
    $addLotColumn = static function (string $sql) use ($pdo, $lotCols, $out): void {
        global $lotCols;
        preg_match('/ADD COLUMN\s+(\w+)/i', $sql, $match);
        $col = $match[1] ?? '';
        if ($col !== '' && in_array($col, $lotCols, true)) {
            $out('Skip lot column: ' . $col);

            return;
        }
        $pdo->exec($sql);
        if ($col !== '') {
            $lotCols[] = $col;
        }
        $out('OK: ' . $col);
    };

    $addLotColumn('ALTER TABLE property_lots ADD COLUMN description TEXT NULL AFTER price');
    $addLotColumn('ALTER TABLE property_lots ADD COLUMN bedrooms VARCHAR(20) NULL AFTER description');
    $addLotColumn('ALTER TABLE property_lots ADD COLUMN bathrooms VARCHAR(20) NULL AFTER bedrooms');
    $addLotColumn('ALTER TABLE property_lots ADD COLUMN villa_size VARCHAR(100) NULL AFTER bathrooms');
    $addLotColumn("ALTER TABLE property_lots ADD COLUMN status VARCHAR(50) NULL DEFAULT 'Available' AFTER villa_size");

    $mediaCols = $pdo->query('SHOW COLUMNS FROM property_media')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('lot_id', $mediaCols, true)) {
        $pdo->exec(
            'ALTER TABLE property_media
             ADD COLUMN lot_id INT UNSIGNED NULL AFTER property_id,
             ADD INDEX idx_lot (lot_id),
             ADD CONSTRAINT fk_property_media_lot
               FOREIGN KEY (lot_id) REFERENCES property_lots(id) ON DELETE CASCADE'
        );
        $out('OK: property_media.lot_id');
    } else {
        $out('Skip: property_media.lot_id');
    }

    $out('Lots v2 migration complete.');
} catch (Throwable $e) {
    $out('Failed: ' . $e->getMessage());
}

if (!$isCli) {
    echo '</body></html>';
}
