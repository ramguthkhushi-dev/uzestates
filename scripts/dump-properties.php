<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';

foreach (db()->query('SELECT id, title, short_description, updated_at FROM properties ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "#{$row['id']} {$row['title']}\n";
    echo '  short: ' . substr((string) $row['short_description'], 0, 100) . "\n";
    echo '  updated: ' . ($row['updated_at'] ?? 'NULL') . "\n\n";
}
