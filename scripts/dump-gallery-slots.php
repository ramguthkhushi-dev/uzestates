<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/gallery.php';

foreach (db()->query('SELECT * FROM gallery_slots ORDER BY slot_number')->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo sprintf(
        "#%d %s | type=%s size=%s | file=%s | thumb=%s | ext=%s | title=%s | visible=%s\n",
        $row['slot_number'],
        $row['slot_name'],
        $row['media_type'],
        $row['slot_size'],
        $row['file_path'] ?? '',
        $row['thumbnail_path'] ?? '',
        $row['external_url'] ?? '',
        $row['title'] ?? '',
        $row['is_visible']
    );
}
