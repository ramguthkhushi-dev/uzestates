<?php

declare(strict_types=1);

require_once __DIR__ . '/homepage.php';

/** @return array<string, mixed> */
function about_page_defaults(): array
{
    return [
        'hero' => [
            'kicker'       => 'About UZ Estates',
            'title'        => 'Built on trust.',
            'title_line2'  => 'Driven by people.',
            'lead'         => "Whether you're looking for land, a villa or simply exploring your options, we're here to provide clear guidance and support you can rely on.",
            'btn_text'     => 'Our Story',
            'btn_url'      => '#our-story',
            'image'        => 'images/about_page/hero.png',
            'image_alt'    => 'Luxury villa terrace with pool and ocean view',
        ],
        'story' => [
            'kicker'     => 'Our Story',
            'title'      => 'A fresh approach to real estate in Mauritius.',
            'paragraphs' => [
                'UZ Estates was founded with a simple idea: finding the right property should feel clear, straightforward and personal. We know that every property decision is important, whether you\'re buying your first plot, searching for a family home or exploring your next investment.',
                'As a growing real estate agency in Mauritius, we\'re focused on building lasting relationships through honest communication, local understanding and genuine support. Every conversation matters, and every client deserves the time and attention to make confident decisions.',
            ],
        ],
        'values' => [
            'kicker' => 'Our Values',
            'items'  => [
                ['title' => 'Transparency', 'text' => 'Clear information and open communication at every stage of your property journey.', 'icon' => 'shield'],
                ['title' => 'Integrity', 'text' => 'Honest advice you can rely on, with your interests at the centre of every decision.', 'icon' => 'person'],
                ['title' => 'Local Knowledge', 'text' => 'Every location offers something different. We\'ll help you find the one that\'s right for you.', 'icon' => 'pin'],
                ['title' => 'Personal Approach', 'text' => 'Taking the time to understand what matters most to you.', 'icon' => 'heart'],
            ],
        ],
        'approach' => [
            'kicker' => 'Our Approach',
            'title'  => 'Simple. Transparent. Personal.',
            'image'  => 'images/about_page/approach.png',
            'image_alt' => 'Tropical landscape in Mauritius',
            'items'  => [
                ['key' => 'simple', 'label' => 'Simple', 'text' => 'We keep the process clear from first enquiry to viewing. Accurate information and direct communication without unnecessary complexity.'],
                ['key' => 'transparent', 'label' => 'Transparent', 'text' => 'Honest guidance at every stage. You always know where things stand, with clear answers and straightforward advice you can trust.'],
                ['key' => 'personal', 'label' => 'Personal', 'text' => 'Whether you are exploring land, a villa or a commercial opportunity, we focus on understanding your needs and guiding you with care.'],
            ],
        ],
        'process' => [
            'kicker' => 'Our Process',
            'title'  => 'What working with UZ Estates feels like.',
            'steps'  => [
                ['title' => 'Listen', 'text' => 'We start by understanding what you\'re looking for.'],
                ['title' => 'Recommend', 'text' => 'We suggest properties that match your goals and budget.'],
                ['title' => 'Visit', 'text' => 'Arrange a viewing at a time that suits you.'],
                ['title' => 'Decide', 'text' => 'We\'re here to answer questions and guide you through the next steps.'],
            ],
        ],
        'faq' => [
            'kicker' => 'Frequently Asked Questions',
            'title'  => 'Quick answers to common questions.',
            'items'  => [
                ['q' => 'What happens after I send an enquiry?', 'a' => 'We\'ll get in touch to understand what you\'re looking for, answer your questions and discuss the next steps.'],
                ['q' => 'Can I arrange a viewing before making a decision?', 'a' => 'Of course. Viewing a property is an important part of the process, and we\'ll arrange a suitable time that works for you.'],
                ['q' => 'Do you only work in the north of Mauritius?', 'a' => 'No. We list properties in different regions across Mauritius and continue to expand our portfolio.'],
                ['q' => 'Are all properties shown on the website?', 'a' => 'Our website features a selection of available properties. If you don\'t find what you\'re looking for, contact us and we\'ll do our best to help.'],
                ['q' => 'Can I contact you through WhatsApp?', 'a' => 'Yes. You can reach us by WhatsApp, phone or by submitting an enquiry through our website.'],
            ],
        ],
        'cta' => [
            'text'     => 'Have a question or want to talk? We\'d love to hear from you.',
            'btn_text' => 'Contact Us',
            'btn_url'  => 'contact.php',
        ],
    ];
}

/** @return array<string, mixed> */
function about_page_decode_json(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }
    $decoded = json_decode($json, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * @param array<string, mixed> $defaults
 * @param array<string, mixed> $stored
 * @return array<string, mixed>
 */
function about_page_merge_section(array $defaults, array $stored): array
{
    $merged = array_replace_recursive($defaults, $stored);

    foreach (['items', 'steps', 'paragraphs'] as $listKey) {
        if (!isset($defaults[$listKey]) || !is_array($defaults[$listKey])) {
            continue;
        }
        $merged[$listKey] = [];
        foreach ($defaults[$listKey] as $index => $defaultItem) {
            $storedItem = is_array($stored[$listKey][$index] ?? null) ? $stored[$listKey][$index] : [];
            if (isset($stored[$listKey]) && is_array($stored[$listKey])) {
                foreach ($stored[$listKey] as $candidate) {
                    if (!is_array($candidate)) {
                        continue;
                    }
                    if (($listKey === 'items' && isset($defaultItem['key'])) && ($candidate['key'] ?? '') === ($defaultItem['key'] ?? '')) {
                        $storedItem = $candidate;
                        break;
                    }
                }
            }
            $merged[$listKey][$index] = is_array($defaultItem)
                ? array_replace($defaultItem, $storedItem)
                : ($storedItem ?: $defaultItem);
            if (isset($defaultItem['key'])) {
                $merged[$listKey][$index]['key'] = $defaultItem['key'];
            }
        }
    }

    return $merged;
}

/** @return array<string, mixed> */
function about_page_content(?array $row = null): array
{
    $defaults = about_page_defaults();
    $row      = $row ?? [];

    return [
        'hero' => [
            'kicker'      => trim((string) ($row['hero_kicker'] ?? '')) ?: $defaults['hero']['kicker'],
            'title'       => trim((string) ($row['hero_title'] ?? '')) ?: $defaults['hero']['title'],
            'title_line2' => trim((string) ($row['hero_title_line2'] ?? '')) ?: $defaults['hero']['title_line2'],
            'lead'        => trim((string) ($row['hero_text'] ?? '')) ?: $defaults['hero']['lead'],
            'btn_text'    => trim((string) ($row['hero_btn_text'] ?? '')) ?: $defaults['hero']['btn_text'],
            'btn_url'     => trim((string) ($row['hero_btn_url'] ?? '')) ?: $defaults['hero']['btn_url'],
            'image'       => trim((string) ($row['hero_image'] ?? '')) ?: $defaults['hero']['image'],
            'image_alt'   => trim((string) ($row['hero_image_alt'] ?? '')) ?: $defaults['hero']['image_alt'],
        ],
        'story'    => about_page_merge_section($defaults['story'], about_page_decode_json($row['story_json'] ?? null)),
        'values'   => about_page_merge_section($defaults['values'], about_page_decode_json($row['values_json'] ?? null)),
        'approach' => about_page_merge_section(
            $defaults['approach'],
            array_replace(about_page_decode_json($row['approach_json'] ?? null), [
                'image'     => trim((string) ($row['approach_image'] ?? '')) ?: $defaults['approach']['image'],
                'image_alt' => trim((string) ($row['approach_image_alt'] ?? '')) ?: $defaults['approach']['image_alt'],
            ])
        ),
        'process'  => about_page_merge_section($defaults['process'], about_page_decode_json($row['process_json'] ?? null)),
        'faq'      => about_page_merge_section($defaults['faq'], about_page_decode_json($row['faq_json'] ?? null)),
        'cta'      => array_replace($defaults['cta'], about_page_decode_json($row['cta_json'] ?? null)),
    ];
}

// Fix approach image fallback after merge
function about_page_content_fixed(?array $row = null): array
{
    $content = about_page_content($row);
    $defaults = about_page_defaults();
    if (empty(trim((string) ($row['approach_image'] ?? '')))) {
        $content['approach']['image'] = $defaults['approach']['image'];
    } else {
        $content['approach']['image'] = trim((string) $row['approach_image']);
    }
    if (empty(trim((string) ($row['approach_image_alt'] ?? '')))) {
        $content['approach']['image_alt'] = $defaults['approach']['image_alt'];
    }

    return $content;
}

function about_page_asset_url(string $path): string
{
    return homepage_asset_url($path);
}

/** @param array<string, mixed> $data */
function about_page_normalize_story(array $data): array
{
    $defaults = about_page_defaults()['story'];
    $paragraphs = [];

    foreach (is_array($data['paragraphs'] ?? null) ? $data['paragraphs'] : [] as $paragraph) {
        $paragraph = trim((string) $paragraph);
        if ($paragraph !== '') {
            $paragraphs[] = $paragraph;
        }
    }

    if ($paragraphs === []) {
        $paragraphs = $defaults['paragraphs'];
    }

    return [
        'kicker'     => trim((string) ($data['kicker'] ?? '')) ?: $defaults['kicker'],
        'title'      => trim((string) ($data['title'] ?? '')) ?: $defaults['title'],
        'paragraphs' => $paragraphs,
    ];
}

/** @param array<int, array<string, string>> $items */
function about_page_normalize_values(array $items): array
{
    $defaults = about_page_defaults()['values']['items'];
    $icons    = ['shield', 'person', 'pin', 'heart'];
    $normalized = [];
    foreach ($defaults as $index => $default) {
        $item = is_array($items[$index] ?? null) ? $items[$index] : [];
        $icon = trim((string) ($item['icon'] ?? ''));
        if (!in_array($icon, $icons, true)) {
            $icon = $default['icon'];
        }
        $normalized[] = [
            'title' => trim((string) ($item['title'] ?? '')) ?: $default['title'],
            'text'  => trim((string) ($item['text'] ?? '')) ?: $default['text'],
            'icon'  => $icon,
        ];
    }

    return $normalized;
}

/** @param array<int, array<string, string>> $items */
function about_page_normalize_approach_items(array $items): array
{
    $defaults = about_page_defaults()['approach']['items'];
    $normalized = [];
    foreach ($defaults as $index => $default) {
        $item = is_array($items[$index] ?? null) ? $items[$index] : [];
        $normalized[] = [
            'key'   => $default['key'],
            'label' => trim((string) ($item['label'] ?? '')) ?: $default['label'],
            'text'  => trim((string) ($item['text'] ?? '')) ?: $default['text'],
        ];
    }

    return $normalized;
}

/** @param array<int, array<string, string>> $steps */
function about_page_normalize_process_steps(array $steps): array
{
    $defaults = about_page_defaults()['process']['steps'];
    $normalized = [];
    foreach ($defaults as $index => $default) {
        $step = is_array($steps[$index] ?? null) ? $steps[$index] : [];
        $normalized[] = [
            'title' => trim((string) ($step['title'] ?? '')) ?: $default['title'],
            'text'  => trim((string) ($step['text'] ?? '')) ?: $default['text'],
        ];
    }

    return $normalized;
}

/** @param array<int, array<string, string>> $items */
function about_page_normalize_faq_items(array $items): array
{
    $defaults = about_page_defaults()['faq']['items'];
    $normalized = [];
    foreach ($defaults as $index => $default) {
        $item = is_array($items[$index] ?? null) ? $items[$index] : [];
        $normalized[] = [
            'q' => trim((string) ($item['q'] ?? '')) ?: $default['q'],
            'a' => trim((string) ($item['a'] ?? '')) ?: $default['a'],
        ];
    }

    return $normalized;
}

function about_page_hero_scrim_gradient(): string
{
    return 'linear-gradient(90deg, var(--white) 0%, rgba(255, 255, 255, 0.82) 8%, rgba(255, 255, 255, 0.48) 16%, rgba(255, 255, 255, 0.18) 24%, transparent 34%)';
}
