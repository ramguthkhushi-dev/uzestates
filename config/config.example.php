<?php

declare(strict_types=1);

/**
 * Copy to config/config.php and fill in on production hosting.
 * Local XAMPP default: DB_USER root, DB_PASS empty — no changes needed.
 */

define('APP_NAME', 'UZ Estates');
define('APP_ROOT', dirname(__DIR__));

// On live site set via server env, e.g. APP_BASE_URL=https://yourdomain.com
$configuredBaseUrl = getenv('APP_BASE_URL');
define('BASE_URL', $configuredBaseUrl !== false && trim($configuredBaseUrl) !== ''
    ? rtrim(trim($configuredBaseUrl), '/')
    : '/' . basename(APP_ROOT));

define('DB_HOST', 'localhost');
define('DB_NAME', 'uz_estates');
define('DB_USER', 'root');           // Production: use the user from your hosting panel
define('DB_PASS', '');               // Production: use the password from your hosting panel
define('DB_CHARSET', 'utf8mb4');
