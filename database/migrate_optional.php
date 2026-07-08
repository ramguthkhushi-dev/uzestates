<?php

declare(strict_types=1);

/**
 * Optional features migration — legal pages settings.
 * Run once: php database/migrate_optional.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Optional migration</title></head><body>';
    echo '<h1>Optional features migration</h1>';
}

try {
    db()->exec(
        'CREATE TABLE IF NOT EXISTS legal_settings (
          id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
          privacy_title VARCHAR(255) NOT NULL DEFAULT \'Privacy Policy\',
          privacy_body TEXT NULL,
          terms_title VARCHAR(255) NOT NULL DEFAULT \'Terms & Conditions\',
          terms_body TEXT NULL,
          updated_at DATETIME NULL
        ) ENGINE=InnoDB'
    );
    $out('OK: legal_settings table ready');

    db()->exec(
        "INSERT INTO legal_settings (id, privacy_title, privacy_body, terms_title, terms_body)
         VALUES (
           1,
           'Privacy Policy',
           'UZ Estates respects your privacy. We collect information you submit through enquiry forms (name, contact details, and message) to respond to your request. We do not sell your data to third parties.\n\nFor questions about your data, contact us using the details on our Contact page.',
           'Terms & Conditions',
           'Information on this website is provided for general guidance about properties in Mauritius. Listings, prices, and availability may change without notice. Enquiries through this site do not constitute a contract until confirmed directly with UZ Estates.'
         )
         ON DUPLICATE KEY UPDATE id = id"
    );
    $out('OK: default legal content seeded');
    $out('Migration complete.');
} catch (Throwable $e) {
    $out('ERROR: ' . $e->getMessage());
    exit(1);
}

if (!$isCli) {
    echo '</body></html>';
}
