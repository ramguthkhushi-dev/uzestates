<?php

declare(strict_types=1);

/**
 * Properties module migration — run once:
 * http://localhost/uz5/database/migrate_properties_module.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Properties Migration</title></head><body>';
    echo '<h1>Properties module migration</h1>';
}

try {
    $pdo = db();

    foreach (['property_sitemaps', 'property_videos', 'property_images', 'property_media', 'property_features'] as $table) {
        $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('DROP TABLE IF EXISTS `properties`');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $pdo->exec(
        'CREATE TABLE properties (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          title VARCHAR(255) NOT NULL,
          property_type VARCHAR(100) NULL,
          listing_purpose VARCHAR(100) NULL,
          status VARCHAR(100) NULL,
          price VARCHAR(100) NULL,
          size VARCHAR(100) NULL,
          location_name VARCHAR(255) NULL,
          short_description TEXT NULL,
          full_description TEXT NULL,
          is_featured TINYINT(1) NOT NULL DEFAULT 0,
          show_on_home TINYINT(1) NOT NULL DEFAULT 0,
          display_order INT NOT NULL DEFAULT 0,
          google_maps_link TEXT NULL,
          google_maps_embed TEXT NULL,
          latitude VARCHAR(100) NULL,
          longitude VARCHAR(100) NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          INDEX idx_featured (is_featured),
          INDEX idx_show_home (show_on_home),
          INDEX idx_display (display_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE property_features (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          property_id INT UNSIGNED NOT NULL,
          feature_label VARCHAR(255) NOT NULL,
          feature_value TEXT NULL,
          display_order INT NOT NULL DEFAULT 0,
          FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
          INDEX idx_property (property_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE property_media (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          property_id INT UNSIGNED NOT NULL,
          media_type VARCHAR(50) NOT NULL,
          media_category VARCHAR(100) NULL,
          file_path TEXT NULL,
          external_url TEXT NULL,
          title VARCHAR(255) NULL,
          alt_text VARCHAR(255) NULL,
          is_main TINYINT(1) NOT NULL DEFAULT 0,
          display_order INT NOT NULL DEFAULT 0,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
          INDEX idx_property (property_id),
          INDEX idx_type (media_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $out('Tables <strong>properties</strong>, <strong>property_features</strong>, <strong>property_media</strong> created.');

    $samples = require __DIR__ . '/seeds/sample_properties.php';
    $insertProperty = $pdo->prepare(
        'INSERT INTO properties (title, property_type, listing_purpose, status, price, size, location_name,
         short_description, full_description, is_featured, show_on_home, display_order)
         VALUES (:title, :property_type, :listing_purpose, :status, :price, :size, :location_name,
         :short_description, :full_description, :is_featured, :show_on_home, :display_order)'
    );
    $insertFeature = $pdo->prepare(
        'INSERT INTO property_features (property_id, feature_label, feature_value, display_order)
         VALUES (:property_id, :feature_label, :feature_value, :display_order)'
    );

    foreach ($samples as $i => $sample) {
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
        ]);
        $propertyId = (int) $pdo->lastInsertId();
        $order = 0;
        foreach ($sample['features'] as $label => $value) {
            $insertFeature->execute([
                'property_id'    => $propertyId,
                'feature_label'  => $label,
                'feature_value'  => $value,
                'display_order'  => ++$order,
            ]);
        }
    }

    $dirs = [
        UPLOAD_PATH . '/properties',
        UPLOAD_PATH . '/sitemaps',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    $out('Inserted ' . count($samples) . ' sample properties.');
    $out('Migration complete.');
} catch (Throwable $e) {
    $out('Failed: ' . $e->getMessage());
}

if (!$isCli) {
    echo '</body></html>';
}
