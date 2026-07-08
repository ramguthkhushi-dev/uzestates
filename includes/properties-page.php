<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** @return array<string, string> */
function properties_page_defaults(): array
{
    return [
        'kicker'     => 'Browse Listings',
        'title'      => 'Properties in Mauritius.',
        'lead'       => 'Plots, villas and investment opportunities across Grand Baie and beyond.',
        'image'      => 'images/properties_page/hero.png',
        'image_alt'  => 'Properties in Mauritius',
    ];
}

/** @return array<string, string> */
function properties_page_content(?array $row = null): array
{
    $defaults = properties_page_defaults();
    $row      = $row ?? [];

    return [
        'kicker'    => trim((string) ($row['hero_kicker'] ?? '')) ?: $defaults['kicker'],
        'title'     => trim((string) ($row['hero_title'] ?? '')) ?: $defaults['title'],
        'lead'      => trim((string) ($row['hero_lead'] ?? '')) ?: $defaults['lead'],
        'image'     => trim((string) ($row['hero_image'] ?? '')) ?: $defaults['image'],
        'image_alt' => trim((string) ($row['hero_image_alt'] ?? '')) ?: $defaults['image_alt'],
    ];
}

function properties_page_asset_url(string $path): string
{
    require_once __DIR__ . '/homepage.php';

    return homepage_asset_url($path);
}
