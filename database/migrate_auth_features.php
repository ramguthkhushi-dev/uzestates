<?php

declare(strict_types=1);

/**
 * Auth feature migration: first/last name, password resets, remember tokens.
 * Run once: http://localhost/uz5/database/migrate_auth_features.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';

function mig(string $msg, bool $cli): void
{
    echo $cli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
}

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Auth Migration</title></head><body>';
    echo '<h1>Auth features migration</h1>';
}

try {
    $pdo = db();

    $cols = $pdo->query("SHOW COLUMNS FROM clients LIKE 'first_name'")->fetch();
    if (!$cols) {
        $hasFullName = (bool) $pdo->query("SHOW COLUMNS FROM clients LIKE 'full_name'")->fetch();

        $pdo->exec(
            'ALTER TABLE clients
             ADD COLUMN first_name VARCHAR(50) NOT NULL DEFAULT \'\' AFTER password_hash,
             ADD COLUMN last_name VARCHAR(50) NOT NULL DEFAULT \'\' AFTER first_name'
        );

        if ($hasFullName) {
            $pdo->exec(
                "UPDATE clients SET
                   first_name = TRIM(SUBSTRING_INDEX(full_name, ' ', 1)),
                   last_name  = TRIM(SUBSTRING(full_name, LENGTH(SUBSTRING_INDEX(full_name, ' ', 1)) + 2))
                 WHERE full_name <> ''"
            );
            $pdo->exec('ALTER TABLE clients DROP COLUMN full_name');
        }

        mig('Updated <strong>clients</strong> table with first_name and last_name.', $isCli);
    } else {
        mig('Clients name columns already exist.', $isCli);
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_resets (
          id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_type  ENUM(\'client\',\'admin\') NOT NULL,
          email      VARCHAR(150) NOT NULL,
          token_hash VARCHAR(255) NOT NULL,
          expires_at DATETIME     NOT NULL,
          created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_email (email),
          INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB'
    );
    mig('Table <strong>password_resets</strong> ready.', $isCli);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS remember_tokens (
          id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_type  ENUM(\'client\',\'admin\') NOT NULL,
          user_id    INT UNSIGNED NOT NULL,
          token_hash VARCHAR(255) NOT NULL,
          expires_at DATETIME     NOT NULL,
          created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_user (user_type, user_id),
          INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB'
    );
    mig('Table <strong>remember_tokens</strong> ready.', $isCli);
    mig('Migration complete.', $isCli);
} catch (Throwable $e) {
    mig('Failed: ' . $e->getMessage(), $isCli);
}

if (!$isCli) {
    echo '</body></html>';
}
