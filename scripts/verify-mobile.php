<?php

declare(strict_types=1);

/**
 * Responsive / mobile readiness audit (code-level).
 * Run: php scripts/verify-mobile.php
 */

require __DIR__ . '/../config/config.php';

$passed = 0;
$failed = 0;
$notes  = [];

function check(string $label, bool $ok): void
{
    global $passed, $failed;
    if ($ok) {
        echo "PASS  {$label}\n";
        $passed++;
    } else {
        echo "FAIL  {$label}\n";
        $failed++;
    }
}

function note(string $message): void
{
    global $notes;
    $notes[] = $message;
}

$pages = [
    'home.php',
    'properties.php',
    'contact.php',
    'about.php',
    'gallery.php',
    'admin/login.php',
    'admin/forgot-password.php',
    'forgot-password.php',
    'reset-password.php',
];

$cssFiles = [
    'css/header-nav.css',
    'css/home.css',
    'css/contact.css',
    'css/properties.css',
    'css/about.css',
    'css/gallery.css',
    'css/auth.css',
    'admin/assets/admin.css',
    'admin/assets/admin-auth.css',
];

foreach ($pages as $page) {
    $path = APP_ROOT . '/' . $page;
    check("page exists: {$page}", is_file($path));
    if (!is_file($path)) {
        continue;
    }
    $html = (string) file_get_contents($path);
    check("viewport meta: {$page}", str_contains($html, 'name="viewport"'));
}

foreach ($cssFiles as $css) {
    $path = APP_ROOT . '/' . $css;
    check("stylesheet exists: {$css}", is_file($path));
    if (!is_file($path)) {
        continue;
    }
    $content = (string) file_get_contents($path);
    check("mobile breakpoints in {$css}", preg_match('/@media\s*\(max-width:/', $content) === 1);
}

$headerNav = (string) file_get_contents(APP_ROOT . '/css/header-nav.css');
check('mobile nav menu at 760px', str_contains($headerNav, 'max-width: 760px') && str_contains($headerNav, '.menu-button'));

$contactCss = (string) file_get_contents(APP_ROOT . '/css/contact.css');
check('contact form stacks at 640px', str_contains($contactCss, '.contact-form-pair') && str_contains($contactCss, 'max-width: 640px'));

$propertiesCss = (string) file_get_contents(APP_ROOT . '/css/properties.css');
check('property detail stacks at 960px', str_contains($propertiesCss, '.detail-layout') && str_contains($propertiesCss, 'max-width: 960px'));

$adminCss = (string) file_get_contents(APP_ROOT . '/admin/assets/admin.css');
check('admin sidebar drawer at 960px', str_contains($adminCss, '.sidebar-toggle') && str_contains($adminCss, 'max-width: 960px'));
check('dashboard stats stack at 640px', str_contains($adminCss, '.dash-stats') && str_contains($adminCss, 'grid-template-columns: 1fr'));

if (str_contains($propertiesCss, '100vw')) {
    note('properties.css uses 100vw in places — can cause slight horizontal scroll on some phones (usually acceptable).');
}

if (str_contains($adminCss, 'min-width: 520px')) {
    note('Admin enquiry tables scroll sideways on narrow screens — by design, not broken.');
}

if (str_contains($headerNav, 'max-width: 760px')) {
    note('Public nav switches to hamburger menu below 760px width.');
}

echo "\n";
foreach ($notes as $n) {
    echo "NOTE  {$n}\n";
}

echo "\n{$passed} passed, {$failed} failed\n";
echo "This is a code audit, not a visual test. Your eyeball check still matters.\n";
exit($failed > 0 ? 1 : 0);
