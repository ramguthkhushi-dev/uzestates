<?php

declare(strict_types=1);

require_once __DIR__ . '/settings.php';

/**
 * @return array{title: string, body: string}
 */
function legal_page_content(string $type): array
{
    $legal = settings_legal();

    if ($type === 'terms') {
        return [
            'title' => $legal['terms_title'] ?? 'Terms & Conditions',
            'body'  => $legal['terms_body'] ?? '',
        ];
    }

    return [
        'title' => $legal['privacy_title'] ?? 'Privacy Policy',
        'body'  => $legal['privacy_body'] ?? '',
    ];
}

function legal_render_body(string $body): void
{
    if (trim($body) === '') {
        echo '<p>Content coming soon.</p>';
        return;
    }

    $paragraphs = preg_split("/\r\n|\n|\r/", $body) ?: [];
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }
        echo '<p>' . e($paragraph) . '</p>';
    }
}
