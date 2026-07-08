<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** Public URL for a homepage image (upload path or static images/ path). */
function homepage_asset_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    if (str_starts_with($path, 'images/')) {
        return rtrim(BASE_URL, '/') . '/' . $path;
    }

    $uploaded = upload_url($path);

    return $uploaded ?? rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/** @return array<string, string> */
function homepage_match_gradients(): array
{
    return [
        'land'       => 'linear-gradient(135deg, rgba(49, 72, 63, 0.55) 0%, rgba(107, 85, 64, 0.45) 100%)',
        'villa'      => 'linear-gradient(120deg, rgba(20, 35, 30, 0.35) 0%, rgba(20, 35, 30, 0.15) 100%)',
        'investment' => 'linear-gradient(160deg, rgba(20, 30, 26, 0.62) 0%, rgba(49, 72, 63, 0.35) 100%)',
        'explore'    => 'linear-gradient(to bottom, rgba(25, 45, 55, 0.45) 0%, rgba(49, 72, 63, 0.65) 100%)',
    ];
}

function homepage_match_bg_style(string $key, string $imagePath): string
{
    $gradients = homepage_match_gradients();
    $gradient  = $gradients[$key] ?? 'linear-gradient(to bottom, rgba(20, 35, 30, 0.5) 0%, rgba(20, 35, 30, 0.25) 100%)';
    $url       = homepage_asset_url($imagePath);

    return "background: {$gradient}, url('" . str_replace("'", "\\'", $url) . "') center / cover no-repeat;";
}

function homepage_defaults(): array
{
    return [
        'hero' => [
            'kicker'          => 'Mauritius Real Estate',
            'title'           => 'Find your next property.',
            'subtitle'        => '',
            'cta_text'        => 'Explore Properties',
            'cta_url'         => 'properties.php',
            'secondary_text'  => 'Who we are',
            'secondary_url'   => 'about.php',
            'image'           => 'images/home_page/hero.png',
        ],
        'match' => [
            'kicker'          => 'Choose your next step',
            'title'           => 'Find your',
            'title_emphasis'  => 'perfect match.',
            'lead'            => 'Pick a path below and explore the listings that match your goals.',
            'tabs'            => [
                [
                    'key'   => 'land',
                    'label' => 'Land',
                    'title' => 'Build your future.',
                    'text'  => 'Looking for the perfect plot to build your home? Browse available land across Mauritius.',
                    'cta'   => 'Browse available land',
                    'href'  => 'properties.php?category=plots',
                    'image' => 'images/home_page/land.png',
                ],
                [
                    'key'   => 'villa',
                    'label' => 'Villa',
                    'title' => 'Move in and settle.',
                    'text'  => 'Ready to move into your next home? Explore villas chosen for location, comfort and long-term value.',
                    'cta'   => 'Explore available villas',
                    'href'  => 'properties.php?category=villas',
                    'image' => 'images/home_page/villa.png',
                ],
                [
                    'key'   => 'investment',
                    'label' => 'Investment',
                    'title' => 'Think long term.',
                    'text'  => 'Discover property opportunities with lasting potential, from off-plan projects to carefully selected listings.',
                    'cta'   => 'View investment listings',
                    'href'  => 'properties.php?category=off-plan',
                    'image' => 'images/home_page/investment.png',
                ],
                [
                    'key'   => 'explore',
                    'label' => 'Not sure yet',
                    'title' => 'Start exploring.',
                    'text'  => 'Not sure where to begin? View every available property and find what feels right for you.',
                    'cta'   => 'View all properties',
                    'href'  => 'properties.php',
                    'image' => 'images/home_page/not_sure_yet.png',
                ],
            ],
        ],
        'lifestyle' => [
            'kicker'    => 'The Mauritius Lifestyle',
            'title'     => 'Explore the best of island living.',
            'cta_text'  => 'Discover more',
            'cta_url'   => 'gallery.php',
            'tiles'     => [
                ['label' => 'Location', 'subtitle' => 'Prime Locations', 'image' => 'images/home_page/location.png'],
                ['label' => 'Lifestyle', 'subtitle' => 'Authentic Island Living', 'image' => 'images/home_page/lifestyle.png'],
                ['label' => 'Value', 'subtitle' => 'Quality You Can Trust', 'image' => 'images/home_page/value.png'],
                ['label' => 'Opportunity', 'subtitle' => 'Exceptional Land Opportunities', 'image' => 'images/home_page/opportunity.png'],
            ],
        ],
        'contact' => [
            'kicker'          => 'Get in Touch',
            'title'           => "Let's find the right property for you.",
            'lead'            => 'Send an enquiry and UZ Estates will contact you directly, or reach us by phone or WhatsApp.',
            'whatsapp_image'  => 'images/home_page/whatsapp.png',
        ],
    ];
}

/**
 * @param array<string, mixed> $defaults
 * @param array<string, mixed> $stored
 * @return array<string, mixed>
 */
function homepage_merge_section(array $defaults, array $stored): array
{
    $merged = array_replace_recursive($defaults, $stored);

    if (isset($defaults['tabs'], $stored['tabs']) && is_array($defaults['tabs'])) {
        $merged['tabs'] = [];
        foreach ($defaults['tabs'] as $index => $defaultTab) {
            $storedTab = is_array($stored['tabs'][$index] ?? null) ? $stored['tabs'][$index] : [];
            if (isset($stored['tabs']) && is_array($stored['tabs'])) {
                foreach ($stored['tabs'] as $candidate) {
                    if (is_array($candidate) && ($candidate['key'] ?? '') === ($defaultTab['key'] ?? '')) {
                        $storedTab = $candidate;
                        break;
                    }
                }
            }
            $merged['tabs'][$index] = array_replace($defaultTab, $storedTab);
            $merged['tabs'][$index]['key'] = $defaultTab['key'];
        }
    }

    if (isset($defaults['tiles'], $stored['tiles']) && is_array($defaults['tiles'])) {
        $merged['tiles'] = [];
        foreach ($defaults['tiles'] as $index => $defaultTile) {
            $storedTile = is_array($stored['tiles'][$index] ?? null) ? $stored['tiles'][$index] : [];
            $merged['tiles'][$index] = array_replace($defaultTile, $storedTile);
        }
    }

    return $merged;
}

/** @return array<string, mixed> */
function homepage_content(?array $row = null): array
{
    $defaults = homepage_defaults();
    $row      = $row ?? [];

    $matchStored = homepage_decode_json($row['match_json'] ?? null);
    $lifeStored  = homepage_decode_json($row['lifestyle_json'] ?? null);
    $contactStored = homepage_decode_json($row['contact_json'] ?? null);

    return [
        'hero' => [
            'kicker'         => trim((string) ($row['hero_kicker'] ?? '')) ?: $defaults['hero']['kicker'],
            'title'          => trim((string) ($row['hero_title'] ?? '')) ?: $defaults['hero']['title'],
            'subtitle'       => trim((string) ($row['hero_subtitle'] ?? '')),
            'cta_text'       => trim((string) ($row['cta_text'] ?? '')) ?: $defaults['hero']['cta_text'],
            'cta_url'        => trim((string) ($row['hero_cta_url'] ?? '')) ?: $defaults['hero']['cta_url'],
            'secondary_text' => trim((string) ($row['hero_secondary_text'] ?? '')) ?: $defaults['hero']['secondary_text'],
            'secondary_url'  => trim((string) ($row['hero_secondary_url'] ?? '')) ?: $defaults['hero']['secondary_url'],
            'image'          => !empty($row['hero_image']) ? (string) $row['hero_image'] : $defaults['hero']['image'],
        ],
        'match'      => homepage_merge_section($defaults['match'], $matchStored),
        'lifestyle'  => homepage_merge_section($defaults['lifestyle'], $lifeStored),
        'contact'    => homepage_merge_section($defaults['contact'], $contactStored),
    ];
}

/** @return array<string, mixed> */
function homepage_decode_json(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }

    $decoded = json_decode($json, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * @param array<int, array<string, string>> $tabs
 * @return array<int, array<string, string>>
 */
function homepage_normalize_match_tabs(array $tabs): array
{
    $defaults = homepage_defaults()['match']['tabs'];
    $normalized = [];

    foreach ($defaults as $index => $defaultTab) {
        $tab = is_array($tabs[$index] ?? null) ? $tabs[$index] : [];
        $normalized[] = [
            'key'   => $defaultTab['key'],
            'label' => trim((string) ($tab['label'] ?? '')) ?: $defaultTab['label'],
            'title' => trim((string) ($tab['title'] ?? '')) ?: $defaultTab['title'],
            'text'  => trim((string) ($tab['text'] ?? '')) ?: $defaultTab['text'],
            'cta'   => trim((string) ($tab['cta'] ?? '')) ?: $defaultTab['cta'],
            'href'  => trim((string) ($tab['href'] ?? '')) ?: $defaultTab['href'],
            'image' => trim((string) ($tab['image'] ?? '')) ?: $defaultTab['image'],
        ];
    }

    return $normalized;
}

/**
 * @param array<int, array<string, string>> $tiles
 * @return array<int, array<string, string>>
 */
function homepage_normalize_lifestyle_tiles(array $tiles): array
{
    $defaults = homepage_defaults()['lifestyle']['tiles'];
    $normalized = [];

    foreach ($defaults as $index => $defaultTile) {
        $tile = is_array($tiles[$index] ?? null) ? $tiles[$index] : [];
        $normalized[] = [
            'label'    => trim((string) ($tile['label'] ?? '')) ?: $defaultTile['label'],
            'subtitle' => trim((string) ($tile['subtitle'] ?? '')) ?: $defaultTile['subtitle'],
            'image'    => trim((string) ($tile['image'] ?? '')) ?: $defaultTile['image'],
        ];
    }

    return $normalized;
}
