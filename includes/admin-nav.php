<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function admin_nav_items(): array
{
    return [
        ['label' => 'Dashboard',  'icon_key' => 'dashboard',  'url' => admin_url('dashboard.php'),          'key' => 'dashboard.php'],
        ['label' => 'Properties', 'icon_key' => 'properties', 'url' => admin_url('properties/index.php'), 'key' => 'properties'],
        ['label' => 'Enquiries',  'icon_key' => 'enquiries',  'url' => admin_url('enquiries/index.php'),  'key' => 'enquiries'],
        ['label' => 'Gallery',    'icon_key' => 'gallery',    'url' => admin_url('gallery/index.php'),    'key' => 'gallery'],
        ['label' => 'Home Page',  'icon_key' => 'pages',      'url' => admin_url('pages/home.php'),       'key' => 'pages/home.php'],
        ['label' => 'About Page', 'icon_key' => 'about',      'url' => admin_url('pages/about.php'),      'key' => 'pages/about.php'],
        ['label' => 'Contact',    'icon_key' => 'contact',    'url' => admin_url('pages/contact.php'),    'key' => 'pages/contact.php'],
        ['label' => 'Legal',      'icon_key' => 'pages',      'url' => admin_url('pages/legal.php'),      'key' => 'pages/legal.php'],
    ];
}

function admin_nav_is_active(string $currentKey, string $itemKey): bool
{
    if ($currentKey === $itemKey) {
        return true;
    }

    if (str_ends_with($currentKey, '.php') && str_ends_with($itemKey, '.php')) {
        return basename($currentKey) === basename($itemKey);
    }

    return str_starts_with($currentKey, $itemKey);
}
