<?php

declare(strict_types=1);

/**
 * CLI smoke tests for admin page rendering and assets.
 * Run: php scripts/verify-admin-ui.php
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/properties.php';
require __DIR__ . '/../includes/enquiries.php';
require __DIR__ . '/../includes/gallery.php';
require __DIR__ . '/../includes/admin-nav.php';

$passed = 0;
$failed = 0;

function ui_check(string $label, bool $ok): void
{
    global $passed, $failed;
    echo ($ok ? 'PASS' : 'FAIL') . "  {$label}\n";
    if ($ok) {
        $passed++;
    } else {
        $failed++;
    }
}

$admin = db()->query('SELECT id, username, email, full_name FROM admins WHERE is_active = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!is_array($admin) || empty($admin['id'])) {
    echo "FAIL  active admin user exists\n";
    exit(1);
}

login_admin($admin, false);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET                      = [];
$_POST                     = [];

$adminPages = [
    'dashboard.php'        => ['dash-hero', 'dash-guide-panel', 'admin-sidebar', 'topbar-view-site'],
    'properties/index.php' => ['properties-header', 'admin-sidebar'],
    'properties/add.php'   => ['properties-admin-page', 'admin-sidebar'],
    'enquiries/index.php'  => ['admin-sidebar', 'data-table'],
    'gallery/index.php'    => ['admin-sidebar'],
    'pages/home.php'       => ['admin-sidebar'],
    'pages/about.php'      => ['admin-sidebar'],
    'pages/contact.php'    => ['admin-sidebar'],
];

foreach ($adminPages as $pageFile => $needles) {
    ob_start();
    try {
        include APP_ROOT . '/admin/' . $pageFile;
        $html = ob_get_clean();
        $ok   = $html !== '' && !str_contains($html, 'Fatal error') && !str_contains($html, 'Parse error');
        foreach ($needles as $needle) {
            $ok = $ok && str_contains($html, $needle);
        }
        ui_check("render {$pageFile}", $ok);
    } catch (Throwable $e) {
        ob_end_clean();
        ui_check("render {$pageFile}", false);
        echo "       {$e->getMessage()}\n";
    }
}

foreach (admin_nav_items() as $item) {
    $path = parse_url($item['url'], PHP_URL_PATH) ?: '';
    $rel  = preg_replace('#^.*/admin/#', '', $path) ?? '';
    ui_check("nav {$item['label']} -> {$rel}", is_file(APP_ROOT . '/admin/' . $rel));
}

foreach (['admin/assets/admin.css', 'admin/assets/admin.js', 'images/logo.png'] as $asset) {
    ui_check("asset {$asset}", is_file(APP_ROOT . '/' . $asset));
}

$css = file_get_contents(APP_ROOT . '/admin/assets/admin.css') ?: '';
ui_check('dashboard hero styles present', str_contains($css, '.dash-hero::after'));
ui_check('sidebar footer pinned styles', str_contains($css, '.sidebar-footer') && str_contains($css, 'margin-top: auto'));
ui_check('interactive view site button styles', str_contains($css, '.topbar-view-site:hover'));
ui_check('interactive status card styles', str_contains($css, '.dash-status-link:hover'));

$js = file_get_contents(APP_ROOT . '/admin/assets/admin.js') ?: '';
ui_check('dashboard table row navigation', str_contains($js, 'dash-table-row[data-href]'));
ui_check('page reveal animations', str_contains($js, 'admin-reveal') && str_contains($js, 'admin-ready'));

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
