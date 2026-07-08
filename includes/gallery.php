<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function gallery_media_types(): array
{
    return [
        'image' => 'Image',
        'video' => 'Video',
        'text'  => 'Text card',
    ];
}

function gallery_slot_sizes(): array
{
    return [
        'small'             => 'Small',
        'large_horizontal'  => 'Large horizontal',
        'large_vertical'    => 'Large vertical',
        'text_card'         => 'Text card',
    ];
}

function gallery_icon_options(): array
{
    return [
        ''       => 'None',
        'home'   => 'Home',
        'pin'    => 'Location pin',
        'layers' => 'Layers',
    ];
}

function gallery_card_styles(): array
{
    return [
        'light' => 'Light',
        'dark'  => 'Dark',
    ];
}

function gallery_slot_size_class(string $slotSize): string
{
    return match ($slotSize) {
        'large_horizontal' => 'gal-wide',
        'large_vertical'   => 'gal-tall',
        default            => 'gal-square',
    };
}

function gallery_slot_size_label(string $size): string
{
    return gallery_slot_sizes()[$size] ?? ucfirst(str_replace('_', ' ', $size));
}

function gallery_media_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    $path = ltrim($path, '/');
    if (str_starts_with($path, 'uploads/')) {
        return BASE_URL . '/' . $path;
    }

    return UPLOAD_URL . '/' . $path;
}

function gallery_slots_all(): array
{
    $stmt = db()->query(
        'SELECT * FROM gallery_slots ORDER BY slot_number ASC'
    );

    return $stmt->fetchAll();
}

function gallery_slot_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM gallery_slots WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function gallery_slot_by_number(int $number): ?array
{
    $stmt = db()->prepare('SELECT * FROM gallery_slots WHERE slot_number = :n LIMIT 1');
    $stmt->execute(['n' => $number]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function gallery_slot_is_text(array $slot): bool
{
    return ($slot['slot_size'] ?? '') === 'text_card' || ($slot['media_type'] ?? '') === 'text';
}

function gallery_slot_allowed_media_types(array $slot): array
{
    if (($slot['slot_size'] ?? '') === 'text_card') {
        return ['text' => 'Text card'];
    }

    return [
        'image' => 'Image',
        'video' => 'Video',
    ];
}

/**
 * @return array{type: string, url: string}|null
 */
function gallery_video_playback(array $slot): ?array
{
    $external = trim($slot['external_url'] ?? '');
    if ($external !== '') {
        $embed = gallery_external_video_embed($external);
        if ($embed !== null) {
            return ['type' => 'embed', 'url' => $embed];
        }
    }

    $fileUrl = gallery_media_url($slot['file_path'] ?? null);
    if ($fileUrl !== null) {
        return ['type' => 'file', 'url' => $fileUrl];
    }

    return null;
}

function gallery_external_video_embed(string $url): ?string
{
    $url = trim($url);

    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&rel=0';
    }

    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
    }

    return null;
}

function gallery_slot_poster_url(array $slot): ?string
{
    $thumb = gallery_media_url($slot['thumbnail_path'] ?? null);
    if ($thumb !== null) {
        return $thumb;
    }

    if (($slot['media_type'] ?? '') === 'image') {
        return gallery_media_url($slot['file_path'] ?? null);
    }

    return null;
}

function gallery_slot_has_media(array $slot): bool
{
    if (gallery_slot_is_text($slot)) {
        return trim($slot['title'] ?? '') !== '';
    }

    if (($slot['media_type'] ?? '') === 'image') {
        return gallery_media_url($slot['file_path'] ?? null) !== null;
    }

    if (($slot['media_type'] ?? '') === 'video') {
        return gallery_video_playback($slot) !== null;
    }

    return false;
}

function gallery_icon_svg(string $icon): string
{
    return match ($icon) {
        'pin' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
        'layers' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
        default => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 10.5L12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>',
    };
}

function gallery_normalise_link(?string $link): string
{
    $link = trim($link ?? '');
    if ($link === '') {
        return '#';
    }

    if (str_starts_with($link, 'http://') || str_starts_with($link, 'https://') || str_starts_with($link, '/')) {
        return $link;
    }

    return BASE_URL . '/' . ltrim($link, '/');
}

function gallery_type_label(string $type): string
{
    return gallery_media_types()[$type] ?? ucfirst($type);
}

/**
 * @param array<string, mixed> $data
 * @param array<string, mixed>|null $file
 * @param array<string, mixed>|null $thumbnail
 * @return array{success: bool, error?: string, id?: int}
 */
function gallery_slot_save(array $data, ?array $file = null, ?array $thumbnail = null): array
{
    require_once __DIR__ . '/functions.php';

    $id          = (int) ($data['id'] ?? 0);
    $title       = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $mediaType   = trim($data['media_type'] ?? 'image');
    $externalUrl = trim($data['external_url'] ?? '');
    $buttonText  = trim($data['button_text'] ?? '');
    $buttonLink  = trim($data['button_link'] ?? '');
    $icon        = trim($data['icon'] ?? '');
    $cardStyle   = trim($data['card_style'] ?? 'light');
    $isVisible   = !empty($data['is_visible']) ? 1 : 0;

    $slot = $id > 0 ? gallery_slot_find($id) : null;
    if (!$slot) {
        return ['success' => false, 'error' => 'Gallery slot not found.'];
    }

    $allowedTypes = gallery_slot_allowed_media_types($slot);
    if (!isset($allowedTypes[$mediaType])) {
        return ['success' => false, 'error' => 'Invalid media type for this slot.'];
    }

    if (gallery_slot_is_text($slot) || $mediaType === 'text') {
        if ($title === '') {
            return ['success' => false, 'error' => 'Title is required for text cards.'];
        }
        if ($buttonText === '') {
            return ['success' => false, 'error' => 'Button text is required for text cards.'];
        }
        $mediaType = 'text';
    }

    $filePath      = $slot['file_path'] ?? null;
    $thumbnailPath = $slot['thumbnail_path'] ?? null;

    if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $ext = $mediaType === 'video'
            ? ['mp4', 'webm']
            : ['jpg', 'jpeg', 'png', 'webp'];
        $upload = handle_file_upload($file, 'gallery', $ext);
        if ($upload['error']) {
            return ['success' => false, 'error' => $upload['error']];
        }
        if ($upload['path']) {
            if ($filePath) {
                unlink_upload($filePath);
            }
            $filePath = $upload['path'];
        }
    }

    if ($thumbnail !== null && ($thumbnail['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = handle_file_upload($thumbnail, 'gallery', ['jpg', 'jpeg', 'png', 'webp']);
        if ($upload['error']) {
            return ['success' => false, 'error' => $upload['error']];
        }
        if ($upload['path']) {
            if ($thumbnailPath) {
                unlink_upload($thumbnailPath);
            }
            $thumbnailPath = $upload['path'];
        }
    }

    if ($mediaType === 'image' && !$filePath) {
        // Allow saving metadata without file (placeholder on public page)
    }

    if ($mediaType === 'video' && !$filePath && $externalUrl === '') {
        // Allow empty video slot — placeholder on public page
    }

    if ($mediaType === 'text') {
        $filePath = null;
        $thumbnailPath = null;
        $externalUrl = '';
    }

    if ($mediaType === 'video' && $externalUrl !== '' && gallery_external_video_embed($externalUrl) === null && !$filePath) {
        return ['success' => false, 'error' => 'External URL must be a valid YouTube or Vimeo link, or upload a video file.'];
    }

    db()->prepare(
        'UPDATE gallery_slots SET
            media_type = :media_type,
            title = :title,
            description = :description,
            file_path = :file_path,
            thumbnail_path = :thumbnail_path,
            external_url = :external_url,
            button_text = :button_text,
            button_link = :button_link,
            icon = :icon,
            card_style = :card_style,
            is_visible = :is_visible,
            updated_at = NOW()
         WHERE id = :id'
    )->execute([
        'id'             => $id,
        'media_type'     => $mediaType,
        'title'          => $title !== '' ? $title : null,
        'description'    => $description !== '' ? $description : null,
        'file_path'      => $filePath,
        'thumbnail_path' => $thumbnailPath,
        'external_url'   => $externalUrl !== '' ? $externalUrl : null,
        'button_text'    => $buttonText !== '' ? $buttonText : null,
        'button_link'    => $buttonLink !== '' ? $buttonLink : null,
        'icon'           => $icon !== '' ? $icon : null,
        'card_style'     => in_array($cardStyle, ['light', 'dark'], true) ? $cardStyle : 'light',
        'is_visible'     => $isVisible,
    ]);

    return ['success' => true, 'id' => $id];
}

function gallery_slots_visible_count(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM gallery_slots WHERE is_visible = 1')->fetchColumn();
}

/**
 * Build lightbox item list from fixed slots (slot order).
 *
 * @return array{items: array<int, array<string, mixed>>, indexBySlotId: array<int, int>}
 */
function gallery_lightbox_payload(array $slots): array
{
    $items = [];
    $indexBySlotId = [];

    foreach ($slots as $slot) {
        if (empty($slot['is_visible'])) {
            continue;
        }

        $slotId = (int) ($slot['id'] ?? 0);
        $mediaType = $slot['media_type'] ?? 'image';

        if ($mediaType === 'image') {
            $url = gallery_media_url($slot['file_path'] ?? null);
            if ($url === null) {
                continue;
            }
            $indexBySlotId[$slotId] = count($items);
            $items[] = [
                'type'  => 'image',
                'title' => trim($slot['title'] ?? ''),
                'url'   => $url,
                'embed' => false,
            ];
            continue;
        }

        if ($mediaType === 'video') {
            $playback = gallery_video_playback($slot);
            if ($playback === null) {
                continue;
            }
            $indexBySlotId[$slotId] = count($items);
            $items[] = [
                'type'  => 'video',
                'title' => trim($slot['title'] ?? ''),
                'url'   => $playback['url'],
                'embed' => ($playback['type'] ?? '') === 'embed',
            ];
        }
    }

    return ['items' => $items, 'indexBySlotId' => $indexBySlotId];
}
