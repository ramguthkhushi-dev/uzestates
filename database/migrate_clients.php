<?php

declare(strict_types=1);

/**
 * Adds clients + appointments tables to an existing install.
 * Run once: http://localhost/uz5/database/migrate_clients.php
 */

require_once __DIR__ . '/../config/config.php';

$isCli = PHP_SAPI === 'cli';

function mig_out(string $msg, bool $cli): void
{
    echo $cli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
}

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migrate Clients</title></head><body>';
    echo '<h1>Client tables migration</h1>';
}

try {
    require_once __DIR__ . '/../config/database.php';

    db()->exec(
        'CREATE TABLE IF NOT EXISTS clients (
          id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          email         VARCHAR(150) NOT NULL UNIQUE,
          password_hash VARCHAR(255) NOT NULL,
          full_name     VARCHAR(100) NOT NULL,
          phone         VARCHAR(30)  NULL,
          is_active     TINYINT(1)   NOT NULL DEFAULT 1,
          last_login_at DATETIME     NULL,
          created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
    mig_out('Table <strong>clients</strong> ready.', $isCli);

    db()->exec(
        'CREATE TABLE IF NOT EXISTS appointments (
          id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          client_id      INT UNSIGNED NOT NULL,
          property_id    INT UNSIGNED NULL,
          property_title VARCHAR(255) NULL,
          preferred_date DATE         NOT NULL,
          preferred_time TIME         NULL,
          message        TEXT         NULL,
          status         ENUM(\'pending\',\'confirmed\',\'cancelled\',\'completed\') NOT NULL DEFAULT \'pending\',
          created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
          FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
          INDEX idx_client (client_id),
          INDEX idx_status (status),
          INDEX idx_date (preferred_date)
        ) ENGINE=InnoDB'
    );
    mig_out('Table <strong>appointments</strong> ready.', $isCli);
    mig_out('Migration complete.', $isCli);
} catch (Throwable $e) {
    mig_out('Failed: ' . $e->getMessage(), $isCli);
}

if (!$isCli) {
    echo '</body></html>';
}
