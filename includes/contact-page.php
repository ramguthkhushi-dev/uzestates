<?php

declare(strict_types=1);

require_once __DIR__ . '/homepage.php';

/** @return array<string, mixed> */
function contact_page_defaults(): array
{
    return [
        'hero' => [
            'kicker'    => 'Contact Us',
            'title'     => "We're here to help.",
            'image'     => 'images/contact_page/hero.png',
            'image_alt' => 'Modern interior living space',
        ],
        'info' => [
            'heading' => 'Contact information',
        ],
        'form' => [
            'heading' => 'Send an enquiry',
            'lead'    => 'Send an enquiry and UZ Estates will contact you directly by phone, WhatsApp or email.',
        ],
        'quick_chat' => [
            'title' => 'Prefer a quick chat?',
            'text'  => 'Call or WhatsApp us directly.',
        ],
    ];
}

/** @return array<string, mixed> */
function contact_page_content(?array $row = null): array
{
    $defaults = contact_page_defaults();
    $row      = $row ?? [];

    return [
        'hero' => [
            'kicker'    => trim((string) ($row['hero_kicker'] ?? '')) ?: $defaults['hero']['kicker'],
            'title'     => trim((string) ($row['hero_title'] ?? '')) ?: $defaults['hero']['title'],
            'image'     => trim((string) ($row['hero_image'] ?? '')) ?: $defaults['hero']['image'],
            'image_alt' => trim((string) ($row['hero_image_alt'] ?? '')) ?: $defaults['hero']['image_alt'],
        ],
        'info' => [
            'heading' => trim((string) ($row['info_heading'] ?? '')) ?: $defaults['info']['heading'],
        ],
        'form' => [
            'heading' => trim((string) ($row['form_heading'] ?? '')) ?: $defaults['form']['heading'],
            'lead'    => trim((string) ($row['form_lead'] ?? '')) ?: $defaults['form']['lead'],
        ],
        'quick_chat' => [
            'title' => trim((string) ($row['quick_chat_title'] ?? '')) ?: $defaults['quick_chat']['title'],
            'text'  => trim((string) ($row['quick_chat_text'] ?? '')) ?: $defaults['quick_chat']['text'],
        ],
    ];
}

function contact_page_asset_url(string $path): string
{
    return homepage_asset_url($path);
}
