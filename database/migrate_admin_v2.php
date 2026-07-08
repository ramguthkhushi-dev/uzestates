<?php

declare(strict_types=1);

/**
 * Admin panel v2 migration — run once:
 * http://localhost/uz5/database/migrate_admin_v2.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$isCli = PHP_SAPI === 'cli';
$out = static function (string $msg) use ($isCli): void {
    echo $isCli ? strip_tags($msg) . PHP_EOL : '<p>' . htmlspecialchars($msg) . '</p>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admin v2 Migration</title></head><body>';
    echo '<h1>Admin panel v2 migration</h1>';
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

    // Properties
    $propCols = $columns('properties');
    if (!in_array('slug', $propCols, true)) {
        $addColumn('ALTER TABLE properties ADD COLUMN slug VARCHAR(255) NULL AFTER title');
    }
    if (!in_array('is_visible', $propCols, true)) {
        $addColumn('ALTER TABLE properties ADD COLUMN is_visible TINYINT(1) NOT NULL DEFAULT 1 AFTER show_on_home');
    }

    // Appointments — guest viewing requests (no client login)
    $hasAppointmentsTable = false;
    try {
        $pdo->query("SELECT 1 FROM `appointments` LIMIT 1");
        $hasAppointmentsTable = true;
    } catch (PDOException $e) {
        $hasAppointmentsTable = false;
    }

    if ($hasAppointmentsTable) {
        $apptCols = $columns('appointments');
        if (!in_array('name', $apptCols, true)) {
            $addColumn('ALTER TABLE appointments ADD COLUMN name VARCHAR(150) NULL AFTER id');
        }
        if (!in_array('phone', $apptCols, true)) {
            $addColumn('ALTER TABLE appointments ADD COLUMN phone VARCHAR(100) NULL AFTER name');
        }
        if (!in_array('email', $apptCols, true)) {
            $addColumn('ALTER TABLE appointments ADD COLUMN email VARCHAR(150) NULL AFTER phone');
        }
        if (!in_array('admin_note', $apptCols, true)) {
            $addColumn('ALTER TABLE appointments ADD COLUMN admin_note TEXT NULL AFTER message');
        }

        try {
            $pdo->exec('ALTER TABLE appointments MODIFY client_id INT UNSIGNED NULL');
            $out('OK: appointments.client_id nullable');
        } catch (PDOException $e) {
            $out('Skip: appointments.client_id — ' . $e->getMessage());
        }

        try {
            $pdo->exec("ALTER TABLE appointments MODIFY status VARCHAR(100) NOT NULL DEFAULT 'Pending'");
            $out('OK: appointments.status VARCHAR');
        } catch (PDOException $e) {
            $out('Skip: appointments.status — ' . $e->getMessage());
        }
    } else {
        $out('Skip appointments: Table appointments does not exist.');
    }

    // Enquiries
    $enqCols = $columns('enquiries');
    if (!in_array('enquiry_type', $enqCols, true)) {
        $addColumn("ALTER TABLE enquiries ADD COLUMN enquiry_type VARCHAR(100) NOT NULL DEFAULT 'General' AFTER message");
    }
    if (!in_array('admin_note', $enqCols, true)) {
        $addColumn('ALTER TABLE enquiries ADD COLUMN admin_note TEXT NULL AFTER status');
    }
    if (!in_array('updated_at', $enqCols, true)) {
        $addColumn('ALTER TABLE enquiries ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
    }

    try {
        $pdo->exec("ALTER TABLE enquiries MODIFY status VARCHAR(100) NOT NULL DEFAULT 'New'");
        $pdo->exec("UPDATE enquiries SET status = 'New' WHERE LOWER(status) = 'new'");
        $pdo->exec("UPDATE enquiries SET status = 'Read' WHERE LOWER(status) = 'read'");
        $pdo->exec("UPDATE enquiries SET status = 'Replied' WHERE LOWER(status) = 'replied'");
        $pdo->exec("UPDATE enquiries SET status = 'Closed' WHERE status IN ('archived', 'Archived')");
        $pdo->exec("UPDATE appointments SET status = 'Pending' WHERE LOWER(status) = 'pending'");
        $pdo->exec("UPDATE appointments SET status = 'Confirmed' WHERE LOWER(status) = 'confirmed'");
        $pdo->exec("UPDATE appointments SET status = 'Declined' WHERE LOWER(status) IN ('cancelled', 'declined')");
        $pdo->exec("UPDATE appointments SET status = 'Completed' WHERE LOWER(status) = 'completed'");
        $out('OK: normalized status values');
    } catch (PDOException $e) {
        $out('Skip: status normalization — ' . $e->getMessage());
    }

    // Homepage settings
    $homeCols = $columns('homepage_settings');
    if (!in_array('hero_image', $homeCols, true)) {
        $addColumn('ALTER TABLE homepage_settings ADD COLUMN hero_image TEXT NULL AFTER hero_subtitle');
    }
    if (!in_array('cta_text', $homeCols, true)) {
        $addColumn('ALTER TABLE homepage_settings ADD COLUMN cta_text VARCHAR(255) NULL AFTER hero_image');
    }

    // About settings
    $aboutCols = $columns('about_settings');
    foreach ([
        'hero_title VARCHAR(255) NULL',
        'hero_text TEXT NULL',
        'hero_image TEXT NULL',
        'intro_title VARCHAR(255) NULL',
        'intro_text TEXT NULL',
        'image_1 TEXT NULL',
    ] as $def) {
        [$name] = explode(' ', $def);
        if (!in_array($name, $aboutCols, true)) {
            $addColumn("ALTER TABLE about_settings ADD COLUMN $def");
        }
    }

    // Contact settings
    $contactCols = $columns('contact_settings');
    foreach ([
        'business_hours VARCHAR(255) NULL',
        'facebook_link TEXT NULL',
        'tiktok_link TEXT NULL',
        'instagram_link TEXT NULL',
    ] as $def) {
        [$name] = explode(' ', $def);
        if (!in_array($name, $contactCols, true)) {
            $addColumn("ALTER TABLE contact_settings ADD COLUMN $def");
        }
    }

    // Gallery — add description + external_url if missing
    $hasGalleryTable = false;
    try {
        $pdo->query("SELECT 1 FROM `gallery` LIMIT 1");
        $hasGalleryTable = true;
    } catch (PDOException $e) {
        $hasGalleryTable = false;
    }

    if ($hasGalleryTable) {
        $galCols = $columns('gallery');
        if (!in_array('description', $galCols, true)) {
            $addColumn('ALTER TABLE gallery ADD COLUMN description TEXT NULL AFTER title');
        }
        if (!in_array('external_url', $galCols, true)) {
            $addColumn('ALTER TABLE gallery ADD COLUMN external_url TEXT NULL AFTER file_path');
        }
        if (!in_array('media_category', $galCols, true)) {
            $addColumn("ALTER TABLE gallery ADD COLUMN media_category VARCHAR(100) NULL AFTER media_type");
        }
    } else {
        $out('Skip gallery columns: Table gallery does not exist.');
    }

    // Admins role column
    $adminCols = $columns('admins');
    if (!in_array('role', $adminCols, true)) {
        $addColumn("ALTER TABLE admins ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'admin' AFTER full_name");
    }

    // Seed contact defaults (no office address in public use)
    $pdo->exec("UPDATE contact_settings SET
        phone = COALESCE(NULLIF(phone, ''), '58154042'),
        whatsapp = COALESCE(NULLIF(whatsapp, ''), '23058154042'),
        email = COALESCE(NULLIF(email, ''), 'Sheikhuzayr8@gmail.com'),
        business_hours = COALESCE(business_hours, 'Mon – Sun, 8:00 AM – 5:00 PM'),
        facebook_link = COALESCE(facebook_link, '" . addslashes(FACEBOOK_URL) . "')
        WHERE id = 1");

    $out('Migration complete.');
} catch (Throwable $e) {
    if ($isCli) {
        fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    echo '<p style="color:red">Migration failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

if (!$isCli) {
    echo '</body></html>';
}
