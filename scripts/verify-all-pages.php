<?php

declare(strict_types=1);

/**
 * Full site smoke test — public pages, assets, property media.
 * Run: php scripts/verify-all-pages.php
 */

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/properties.php';
require __DIR__ . '/../includes/gallery.php';
require __DIR__ . '/../includes/homepage.php';
require __DIR__ . '/../includes/about-page.php';
require __DIR__ . '/../includes/contact-page.php';
require __DIR__ . '/../includes/gallery-page.php';
require __DIR__ . '/../includes/properties-page.php';
require __DIR__ . '/../includes/settings.php';

$passed = 0;
$failed = 0;

function page_check(string $label, bool $ok): void
{
    global $passed, $failed;
    echo ($ok ? 'PASS' : 'FAIL') . "  {$label}\n";
    if ($ok) {
        $passed++;
    } else {
        $failed++;
    }
}

function asset_exists(string $relativePath): bool
{
    $relativePath = ltrim($relativePath, '/');
    if (str_starts_with($relativePath, 'uploads/')) {
        return is_file(UPLOAD_PATH . '/' . substr($relativePath, strlen('uploads/')));
    }

    return is_file(dirname(__DIR__) . '/' . $relativePath);
}

$publicPages = [
    'index.php',
    'home.php',
    'properties.php',
    'gallery.php',
    'about.php',
    'contact.php',
    'privacy.php',
    'terms.php',
];

foreach ($publicPages as $page) {
    $path = dirname(__DIR__) . '/' . $page;
    page_check("public file {$page}", is_file($path));
}

$properties = property_list_all();
page_check('properties in database', count($properties) >= 4);

foreach ($properties as $property) {
    $id    = (int) $property['id'];
    $title = (string) ($property['title'] ?? '');
    $detailFile = dirname(__DIR__) . '/property-details.php';
    page_check("property detail file for #{$id}", is_file($detailFile));

    $photos = property_detail_photo_images($property);
    $maps   = property_detail_sitemap_images($property);
    $displayLots = property_lots_for_display($id, $property);

    page_check("#{$id} {$title} — card image", property_card_gallery_images($property) !== []);
    page_check("#{$id} {$title} — detail media", $photos !== [] || $maps !== [] || property_detail_videos($property) !== []);

    if (property_is_villa($property) && $displayLots !== []) {
        foreach ($displayLots as $i => $lot) {
            $label = property_lot_label($lot, $i);
            page_check("#{$id} {$title} — {$label} photos", ($lot['photos'] ?? []) !== []);
        }
    }
}

$homepage = homepage_content();
foreach (['hero'] as $section) {
    $image = $homepage[$section]['image'] ?? '';
    if ($image !== '') {
        page_check("homepage {$section} image", asset_exists($image));
    }
}

foreach ($homepage['match']['tabs'] ?? [] as $tab) {
    $image = $tab['image'] ?? '';
    if ($image !== '') {
        page_check('homepage match tab: ' . ($tab['key'] ?? ''), asset_exists($image));
    }
}

foreach ($homepage['lifestyle']['tiles'] ?? [] as $tile) {
    $image = $tile['image'] ?? '';
    if ($image !== '') {
        page_check('homepage lifestyle: ' . ($tile['label'] ?? ''), asset_exists($image));
    }
}

page_check('logo.png', asset_exists('images/logo.png'));

$pageHeroes = [
    'about'      => about_page_content()['hero']['image'] ?? '',
    'contact'    => contact_page_content()['hero']['image'] ?? '',
    'gallery'    => gallery_page_content()['image'] ?? '',
    'properties' => properties_page_content()['image'] ?? '',
];

foreach ($pageHeroes as $pageKey => $hero) {
    if ($hero !== '') {
        page_check("{$pageKey} page hero", asset_exists($hero));
    }
}

$slots = gallery_slots_all();
$mediaSlots = 0;
$missingMedia = 0;

foreach ($slots as $slot) {
    if (gallery_slot_is_text($slot)) {
        continue;
    }
    $mediaSlots++;
    if (!gallery_slot_has_media($slot)) {
        $missingMedia++;
        page_check('gallery slot #' . ($slot['slot_number'] ?? '?') . ' media', false);
    }
}

page_check('gallery image/video slots present', $mediaSlots >= 8);
page_check('gallery media slots complete', $missingMedia === 0);

page_check('contact settings phone', trim((string) (settings_contact()['phone'] ?? '')) !== '');
page_check('admin user exists', (bool) db()->query('SELECT id FROM admins WHERE is_active = 1 LIMIT 1')->fetch());

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
