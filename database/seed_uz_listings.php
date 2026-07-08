<?php

declare(strict_types=1);

/**
 * Refresh UZ Estates sample listings (Vale Plot, Grand Baie Villa, etc.).
 * http://localhost/uz5/database/seed_uz_listings.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/properties.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Seed Listings</title></head><body>';
    echo '<h1>Seed UZ listings</h1>';
}

try {
    $pdo = db();
    $samples = require __DIR__ . '/seeds/sample_properties.php';

    $pdo->exec('DELETE FROM property_media');
    $pdo->exec('DELETE FROM property_features');
    $pdo->exec('DELETE FROM property_lots');
    $pdo->exec('DELETE FROM properties');

    $insertProperty = $pdo->prepare(
        'INSERT INTO properties (
          title, property_type, listing_purpose, status, price, size, location_name,
          short_description, full_description, is_featured, show_on_home, display_order,
          google_maps_link, google_maps_embed, latitude, longitude
        ) VALUES (
          :title, :property_type, :listing_purpose, :status, :price, :size, :location_name,
          :short_description, :full_description, :is_featured, :show_on_home, :display_order,
          :google_maps_link, :google_maps_embed, :latitude, :longitude
        )'
    );

    $insertFeature = $pdo->prepare(
        'INSERT INTO property_features (property_id, feature_label, feature_value, display_order)
         VALUES (:property_id, :feature_label, :feature_value, :display_order)'
    );

    $insertLot = $pdo->prepare(
        'INSERT INTO property_lots (
          property_id, label, size, price, description, bedrooms, bathrooms, villa_size, status, display_order
        ) VALUES (
          :property_id, :label, :size, :price, :description, :bedrooms, :bathrooms, :villa_size, :status, :display_order
        )'
    );

    foreach ($samples as $i => $sample) {
        $lat = trim($sample['latitude'] ?? '');
        $lng = trim($sample['longitude'] ?? '');
        $embed = ($lat !== '' && $lng !== '')
            ? property_map_embed_html([
                'latitude'          => $lat,
                'longitude'         => $lng,
                'property_type'     => $sample['property_type'] ?? '',
                'google_maps_embed' => '',
            ])
            : null;

        $insertProperty->execute([
            'title'             => $sample['title'],
            'property_type'     => $sample['property_type'],
            'listing_purpose'   => $sample['listing_purpose'],
            'status'            => $sample['status'],
            'price'             => $sample['price'],
            'size'              => $sample['size'],
            'location_name'     => $sample['location_name'],
            'short_description' => $sample['short_description'] ?? '',
            'full_description'  => $sample['full_description'] ?? '',
            'is_featured'       => $sample['is_featured'] ?? 1,
            'show_on_home'      => $sample['show_on_home'] ?? 1,
            'display_order'     => $i + 1,
            'google_maps_link'  => $sample['google_maps_link'] ?? null,
            'google_maps_embed' => $embed,
            'latitude'          => $lat !== '' ? $lat : null,
            'longitude'         => $lng !== '' ? $lng : null,
        ]);

        $propertyId = (int) $pdo->lastInsertId();
        $order = 0;
        foreach ($sample['features'] as $label => $value) {
            $insertFeature->execute([
                'property_id'   => $propertyId,
                'feature_label' => $label,
                'feature_value' => $value,
                'display_order' => ++$order,
            ]);
        }

        $lotOrder = 0;
        foreach ($sample['lots'] ?? [] as $lot) {
            $insertLot->execute([
                'property_id'   => $propertyId,
                'label'         => $lot['label'] ?? null,
                'size'          => $lot['size'] ?? '',
                'price'         => $lot['price'] ?? '',
                'description'   => $lot['description'] ?? null,
                'bedrooms'      => $lot['bedrooms'] ?? null,
                'bathrooms'     => $lot['bathrooms'] ?? null,
                'villa_size'    => $lot['villa_size'] ?? null,
                'status'        => $lot['status'] ?? 'Available',
                'display_order' => ++$lotOrder,
            ]);
        }

        property_sync_price_numeric($propertyId);
    }

    $out('Seeded ' . count($samples) . ' listings.');
} catch (Throwable $e) {
    $out('Failed: ' . $e->getMessage());
}

if (!$isCli) {
    echo '</body></html>';
}
