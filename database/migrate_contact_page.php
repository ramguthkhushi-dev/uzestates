<?php

declare(strict_types=1);

/**
 * Contact page settings — run once:
 * http://localhost/uz5/database/migrate_contact_page.php
 * Or: php database/migrate_contact_page.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Contact page migration</title></head><body>';
    echo '<h1>Contact page migration</h1>';
}

try {
    $pdo = db();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contact_page_settings (
          id               INT UNSIGNED NOT NULL PRIMARY KEY,
          hero_kicker      VARCHAR(255) NULL,
          hero_title       VARCHAR(255) NULL,
          hero_image       TEXT         NULL,
          hero_image_alt   VARCHAR(255) NULL,
          info_heading     VARCHAR(255) NULL,
          form_heading     VARCHAR(255) NULL,
          form_lead        TEXT         NULL,
          quick_chat_title VARCHAR(255) NULL,
          quick_chat_text  VARCHAR(255) NULL,
          updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
    $out('OK: contact_page_settings table ready');

    $pdo->exec('INSERT INTO contact_page_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id = id');
    $out('Done.');
} catch (Throwable $e) {
    $out('Error: ' . $e->getMessage());
    if (!$isCli) {
        echo '</body></html>';
    }
    exit(1);
}

if (!$isCli) {
    echo '</body></html>';
}
