<?php

declare(strict_types=1);

/**
 * Sync property descriptions from seed file into the database.
 * php database/sync_property_descriptions.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$samples = require __DIR__ . '/seeds/sample_properties.php';
$stmt = db()->prepare(
    'UPDATE properties
     SET location_name = :location_name,
         short_description = :short_description,
         full_description = :full_description
     WHERE title = :title'
);

foreach ($samples as $sample) {
    $stmt->execute([
        'title'             => $sample['title'],
        'location_name'     => $sample['location_name'] ?? '',
        'short_description' => $sample['short_description'] ?? '',
        'full_description'  => $sample['full_description'] ?? '',
    ]);
}

echo 'Updated listing copy for ' . count($samples) . ' properties.' . PHP_EOL;
