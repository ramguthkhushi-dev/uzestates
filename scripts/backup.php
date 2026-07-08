<?php

declare(strict_types=1);

/**
 * Database + uploads backup helper.
 * Run: php scripts/backup.php
 *
 * Optional environment variables:
 *   MYSQLDUMP_PATH — path to mysqldump binary (default: mysqldump)
 */

require __DIR__ . '/../config/config.php';

$backupDir = APP_ROOT . '/storage/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$stamp   = date('Y-m-d-His');
$sqlFile = $backupDir . '/db-' . DB_NAME . '-' . $stamp . '.sql';
$zipHint = $backupDir . '/uploads-' . $stamp . '.zip';

$mysqldump = getenv('MYSQLDUMP_PATH') ?: 'mysqldump';
$host      = DB_HOST;
$user      = DB_USER;
$pass      = DB_PASS;
$name      = DB_NAME;

$passArg = $pass !== '' ? ' -p' . escapeshellarg($pass) : '';
$cmd     = escapeshellcmd($mysqldump)
    . ' -h ' . escapeshellarg($host)
    . ' -u ' . escapeshellarg($user)
    . $passArg
    . ' --single-transaction --routines --triggers '
    . escapeshellarg($name)
    . ' > ' . escapeshellarg($sqlFile);

echo "Backing up database to {$sqlFile}\n";

$code = 0;
passthru($cmd, $code);

if ($code !== 0 || !is_file($sqlFile) || filesize($sqlFile) === 0) {
    fwrite(STDERR, "Database backup failed. Check MYSQLDUMP_PATH and database credentials.\n");
    exit(1);
}

echo 'Database backup OK (' . number_format((int) filesize($sqlFile)) . " bytes)\n";

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipHint, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $uploadRoot = realpath(UPLOAD_PATH);
        if ($uploadRoot !== false) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadRoot, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $path = $file->getPathname();
                $zip->addFile($path, 'uploads/' . substr($path, strlen($uploadRoot) + 1));
            }
        }
        $zip->close();
        echo 'Uploads archive OK: ' . $zipHint . "\n";
    } else {
        echo "Could not create uploads zip. Archive uploads/ manually.\n";
    }
} else {
    echo "ZipArchive not available. Archive uploads/ manually.\n";
}

echo "Done.\n";
