<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** @return array<string, mixed> */
function gallery_page_defaults(): array
{
    return [
        'kicker'    => 'Gallery',
        'title'     => 'Properties in focus.',
        'lead'      => 'A curated collection of our properties, locations and the lifestyle that makes Mauritius exceptional.',
        'btn_text'  => 'Explore Properties',
        'btn_url'   => 'properties.php',
        'image'     => 'images/gallery_page/hero.png',
    ];
}

/** @return array<string, mixed> */
function gallery_page_content(?array $row = null): array
{
    $defaults = gallery_page_defaults();
    $row      = $row ?? [];

    return [
        'kicker'   => trim((string) ($row['hero_kicker'] ?? '')) ?: $defaults['kicker'],
        'title'    => trim((string) ($row['hero_title'] ?? '')) ?: $defaults['title'],
        'lead'     => trim((string) ($row['hero_lead'] ?? '')) ?: $defaults['lead'],
        'btn_text' => trim((string) ($row['hero_btn_text'] ?? '')) ?: $defaults['btn_text'],
        'btn_url'  => trim((string) ($row['hero_btn_url'] ?? '')) ?: $defaults['btn_url'],
        'image'    => trim((string) ($row['hero_image'] ?? '')) ?: $defaults['image'],
    ];
}

function gallery_page_asset_url(string $path): string
{
    require_once __DIR__ . '/homepage.php';

    return homepage_asset_url($path);
}
