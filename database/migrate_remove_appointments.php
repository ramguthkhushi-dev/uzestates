<?php

declare(strict_types=1);

/**
 * Remove appointments system — run once:
 * http://localhost/uz5/database/migrate_remove_appointments.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Remove Appointments</title></head><body>';
    echo '<h1>Remove appointments migration</h1>';
}

try {
    $pdo = db();
    $cols = array_column($pdo->query('SHOW COLUMNS FROM enquiries')->fetchAll(), 'Field');

    if (!in_array('property_title', $cols, true)) {
        $pdo->exec('ALTER TABLE enquiries ADD COLUMN property_title VARCHAR(255) NULL AFTER property_id');
        $out('Added enquiries.property_title');
    }

    $pdo->exec("UPDATE enquiries SET status = 'Contacted' WHERE status IN ('Read', 'Replied', 'read', 'replied')");
    $pdo->exec("UPDATE enquiries SET status = 'New' WHERE status IN ('new')");
    $pdo->exec("UPDATE enquiries SET status = 'Closed' WHERE status IN ('Closed', 'closed', 'archived', 'Archived')");

    $pdo->exec(
        'UPDATE enquiries e
         INNER JOIN properties p ON p.id = e.property_id
         SET e.property_title = p.title
         WHERE e.property_title IS NULL OR e.property_title = \'\''
    );

    try {
        $pdo->exec('DROP TABLE IF EXISTS appointments');
        $out('Dropped appointments table');
    } catch (PDOException $e) {
        $out('Skip drop appointments: ' . $e->getMessage());
    }

    $out('Migration complete.');
} catch (Throwable $e) {
    if ($isCli) {
        fwrite(STDERR, 'Failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    echo '<p style="color:red">' . htmlspecialchars($e->getMessage()) . '</p>';
}

if (!$isCli) {
    echo '</body></html>';
}
