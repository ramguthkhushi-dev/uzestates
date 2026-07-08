<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/properties.php';

$samples = require __DIR__ . '/seeds/sample_properties.php';
$villa   = null;

foreach ($samples as $sample) {
    if (($sample['title'] ?? '') === 'Almaris Villas') {
        $villa = $sample;
        break;
    }
}

if ($villa === null) {
    fwrite(STDERR, "Almaris Villas not found.\n");
    exit(1);
}

$stmt = db()->prepare('SELECT id FROM properties WHERE title = :title LIMIT 1');
$stmt->execute(['title' => 'Almaris Villas']);
$row = $stmt->fetch();

if (!$row) {
    fwrite(STDERR, "Almaris Villas not in database.\n");
    exit(1);
}

$propertyId = (int) $row['id'];

db()->prepare(
    'UPDATE properties SET
      status = :status,
      size = :size,
      short_description = :short_description,
      full_description = :full_description
     WHERE id = :id'
)->execute([
    'status'             => $villa['status'] ?? 'Off-Plan',
    'size'               => $villa['size'],
    'short_description'  => $villa['short_description'],
    'full_description'   => $villa['full_description'],
    'id'                 => $propertyId,
]);

$existingLots = property_lots($propertyId);
$existingByLabel = [];

foreach ($existingLots as $existingLot) {
    $key = strtolower(trim((string) ($existingLot['label'] ?? '')));
    if ($key !== '') {
        $existingByLabel[$key] = $existingLot;
    }
}

$lotsData = [];

foreach ($villa['lots'] ?? [] as $index => $lot) {
    $key = strtolower(trim((string) ($lot['label'] ?? '')));
    $lotsData[] = array_merge($lot, [
        'id'            => (int) ($existingByLabel[$key]['id'] ?? 0),
        'display_order' => $index,
    ]);
}

$lotIds = property_sync_lots($propertyId, $lotsData);

echo 'Synced Almaris Villas with ' . count($lotIds) . ' units.' . PHP_EOL;
