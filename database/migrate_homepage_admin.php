<?php

declare(strict_types=1);

/**
 * Homepage admin sections — run once:
 * http://localhost/uz5/database/migrate_homepage_admin.php
 * Or: php database/migrate_homepage_admin.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Homepage admin migration</title></head><body>';
    echo '<h1>Homepage admin migration</h1>';
}

try {
    $pdo = db();

    $columns = static function (string $table) use ($pdo): array {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        return array_column($stmt->fetchAll(), 'Field');
    };

    $addColumn = static function (string $sql) use ($pdo, $out): void {
        try {
            $pdo->exec($sql);
            $out('OK: ' . $sql);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate column')) {
                $out('Skip (exists): ' . $sql);
            } else {
                throw $e;
            }
        }
    };

    $homeCols = $columns('homepage_settings');

    foreach ([
        'hero_cta_url VARCHAR(500) NULL AFTER cta_text',
        'hero_secondary_text VARCHAR(255) NULL AFTER hero_cta_url',
        'hero_secondary_url VARCHAR(500) NULL AFTER hero_secondary_text',
        'match_json JSON NULL AFTER hero_secondary_url',
        'lifestyle_json JSON NULL AFTER match_json',
        'contact_json JSON NULL AFTER lifestyle_json',
    ] as $def) {
        [$name] = explode(' ', $def);
        if (!in_array($name, $homeCols, true)) {
            $addColumn("ALTER TABLE homepage_settings ADD COLUMN $def");
        } else {
            $out("Skip (exists): $name");
        }
    }

    $pdo->exec('INSERT INTO homepage_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id = id');
    $out('Done. Homepage settings row ready.');
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
