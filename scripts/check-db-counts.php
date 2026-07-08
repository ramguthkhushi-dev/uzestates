<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

try {
    $props = (int) db()->query('SELECT COUNT(*) FROM properties')->fetchColumn();
    $media = (int) db()->query('SELECT COUNT(*) FROM property_media')->fetchColumn();
    $enq   = (int) db()->query('SELECT COUNT(*) FROM enquiries')->fetchColumn();
    echo "properties: {$props}\n";
    echo "property_media: {$media}\n";
    echo "enquiries: {$enq}\n";
    if ($props > 0) {
        foreach (db()->query('SELECT id, title FROM properties ORDER BY id')->fetchAll() as $r) {
            echo "  #{$r['id']} {$r['title']}\n";
        }
    }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
