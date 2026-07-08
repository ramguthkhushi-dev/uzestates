<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

const PROPERTY_MAX_IMAGE_BYTES  = 20971520;   // 20MB — photos, sitemaps, PDFs
const PROPERTY_MAX_VIDEO_BYTES  = 536870912;  // 512MB — property tour clips
/** @deprecated Use property_max_upload_bytes() */
const PROPERTY_MAX_BYTES        = PROPERTY_MAX_IMAGE_BYTES;

function property_parse_ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $num  = (float) $value;

    return (int) match ($unit) {
        'g'     => $num * 1024 * 1024 * 1024,
        'm'     => $num * 1024 * 1024,
        'k'     => $num * 1024,
        default => $num,
    };
}

function property_php_ini_size_label(string $directive): string
{
    $raw = trim((string) ini_get($directive));
    if ($raw === '') {
        return 'unknown';
    }

    return $raw;
}

function property_max_upload_bytes(string $mediaType): int
{
    $appLimit = $mediaType === 'video'
        ? PROPERTY_MAX_VIDEO_BYTES
        : PROPERTY_MAX_IMAGE_BYTES;

    $phpFileLimit = property_parse_ini_bytes((string) ini_get('upload_max_filesize'));
    if ($phpFileLimit > 0) {
        $appLimit = min($appLimit, $phpFileLimit);
    }

    return $appLimit;
}

function property_max_upload_label(string $mediaType): string
{
    $bytes = property_max_upload_bytes($mediaType);

    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 1) . 'GB';
    }

    if ($bytes >= 1048576) {
        return round($bytes / 1048576) . 'MB';
    }

    return round($bytes / 1024) . 'KB';
}

function property_upload_error_message(int $errorCode): string
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds the server upload limit ('
            . property_php_ini_size_label('upload_max_filesize') . ' per file, '
            . property_php_ini_size_label('post_max_size') . ' per save). '
            . 'Upload large videos one at a time, or raise upload_max_filesize and post_max_size in php.ini.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Try again with a stable connection.',
        UPLOAD_ERR_NO_FILE => '',
        default            => 'File upload failed.',
    };
}

function property_media_types(): array
{
    return ['image', 'video', 'sitemap'];
}

function property_media_categories(): array
{
    return ['actual', 'ai', 'sitemap', 'video', 'thumbnail'];
}

/** @return list<string> */
function property_admin_type_options(): array
{
    return ['Plot', 'Villa', 'Apartment', 'Commercial', 'Land'];
}

/** @return list<string> */
function property_admin_purpose_options(): array
{
    return ['Sale', 'Rent'];
}

/** @return list<string> */
function property_admin_status_options(): array
{
    return ['Available', 'Off-Plan', 'Sold', 'Under Offer', 'Reserved'];
}

function property_admin_uses_simple_lots(string $propertyType): bool
{
    $type = strtolower(trim($propertyType));

    return str_contains($type, 'plot') || $type === 'land';
}

/** @return array<string, string> */
function property_admin_type_hints(): array
{
    return [
        'Plot'       => 'Single plot or multiple subdivided lots. Fill price and size above, or use Available lots for each lot.',
        'Villa'      => 'Use Villa units for each lot. Full description is the project overview. Videos and sitemaps go in Media.',
        'Apartment'  => 'Single unit listing: set price, size, photos, key details, and map. Lots section is not used.',
        'Commercial' => 'Single commercial listing: set price, size, photos, key details, and map. Lots section is not used.',
        'Land'       => 'Land for sale: same as a plot. Use Available lots only when the land is split into multiple parcels.',
    ];
}

function property_admin_type_hint(string $propertyType): string
{
    $hints = property_admin_type_hints();
    $type  = trim($propertyType);

    return $hints[$type] ?? 'Choose a type to see what to fill in. Title, price, size, description, media, and map work for every listing.';
}

/**
 * @param list<string> $options
 */
function property_admin_select_options(array $options, string $current): array
{
    $current = trim($current);
    if ($current !== '' && !in_array($current, $options, true)) {
        $options = array_merge([$current], $options);
    }

    return $options;
}

function property_allowed_extensions(string $mediaType): array
{
    return match ($mediaType) {
        'video'   => ['mp4', 'webm'],
        'sitemap' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        default   => ['jpg', 'jpeg', 'png', 'webp'],
    };
}

function property_upload_subdir(string $mediaType): string
{
    return $mediaType === 'sitemap' ? 'sitemaps' : 'properties';
}

function property_list_all(): array
{
    return db()->query(
        'SELECT * FROM properties ORDER BY display_order ASC, title ASC'
    )->fetchAll();
}

function property_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM properties WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function property_features(int $propertyId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM property_features WHERE property_id = :id ORDER BY display_order ASC, id ASC'
    );
    $stmt->execute(['id' => $propertyId]);

    return $stmt->fetchAll();
}

/** @return list<array<string, mixed>> */
function property_lots(int $propertyId): array
{
    static $cache = [];

    if (isset($cache[$propertyId])) {
        return $cache[$propertyId];
    }

    try {
        $stmt = db()->prepare(
            'SELECT * FROM property_lots WHERE property_id = :id ORDER BY display_order ASC, id ASC'
        );
        $stmt->execute(['id' => $propertyId]);
        $cache[$propertyId] = $stmt->fetchAll() ?: [];
    } catch (Throwable) {
        $cache[$propertyId] = [];
    }

    return $cache[$propertyId];
}

/** @param list<int> $propertyIds @return array<int, list<array<string, mixed>>> */
function property_lots_by_property_ids(array $propertyIds): array
{
    $propertyIds = array_values(array_unique(array_filter(array_map('intval', $propertyIds))));
    if ($propertyIds === []) {
        return [];
    }

    try {
        $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
        $stmt = db()->prepare(
            "SELECT * FROM property_lots WHERE property_id IN ({$placeholders})
             ORDER BY property_id ASC, display_order ASC, id ASC"
        );
        $stmt->execute($propertyIds);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['property_id']][] = $row;
        }

        return $grouped;
    } catch (Throwable) {
        return [];
    }
}

function property_price_amount(?string $price): ?int
{
    $price = trim((string) $price);
    if ($price === '') {
        return null;
    }

    if (preg_match('/Rs\s*([\d,\.]+)\s*M\b/i', $price, $match)) {
        return (int) round((float) str_replace(',', '', $match[1]) * 1000000);
    }

    if (preg_match('/Rs\s*([\d,]+)/i', $price, $match)) {
        return (int) str_replace(',', '', $match[1]);
    }

    return null;
}

/** Parse filter tokens such as 4M, 9M, or Rs 9,700,000. */
function property_filter_price_amount(?string $token): ?int
{
    $token = strtoupper(trim((string) $token));
    if ($token === '') {
        return null;
    }

    if (preg_match('/^(\d+(?:\.\d+)?)M$/', $token, $match)) {
        return (int) round((float) $match[1] * 1000000);
    }

    return property_price_amount($token);
}

/** @param list<array<string, mixed>> $lots */
function property_compute_price_numeric(array $property, array $lots = []): ?int
{
    if ($lots === [] && !empty($property['id'])) {
        $lots = property_lots((int) $property['id']);
    }

    if ($lots !== []) {
        $amounts = array_values(array_filter(array_map(
            static fn(array $lot): ?int => property_price_amount($lot['price'] ?? null),
            $lots
        )));

        if ($amounts !== []) {
            return min($amounts);
        }
    }

    return property_price_amount($property['price'] ?? null);
}

function property_sync_price_numeric(int $propertyId): void
{
    $property = property_find($propertyId);
    if (!$property) {
        return;
    }

    $numeric = property_compute_price_numeric($property, property_lots($propertyId));

    db()->prepare('UPDATE properties SET price_numeric = :price_numeric WHERE id = :id')
        ->execute([
            'price_numeric' => $numeric,
            'id'            => $propertyId,
        ]);
}

function property_sync_all_price_numeric(): int
{
    $ids = db()->query('SELECT id FROM properties ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        property_sync_price_numeric((int) $id);
    }

    return count($ids);
}

/** @return list<array{value: string, label: string}> */
function property_price_filter_options(string $kind): array
{
    $options = match ($kind) {
        'min' => [
            ['4M', 'Rs 4M+'],
            ['6M', 'Rs 6M+'],
            ['9M', 'Rs 9M+'],
            ['11M', 'Rs 11M+'],
            ['15M', 'Rs 15M+'],
            ['25M', 'Rs 25M+'],
        ],
        'max' => [
            ['5M', 'Up to Rs 5M'],
            ['7M', 'Up to Rs 7M'],
            ['10M', 'Up to Rs 10M'],
            ['12M', 'Up to Rs 12M'],
            ['20M', 'Up to Rs 20M'],
            ['40M', 'Up to Rs 40M'],
            ['50M', 'Up to Rs 50M+'],
        ],
        default => [],
    };

    return array_map(
        static fn(array $option): array => ['value' => $option[0], 'label' => $option[1]],
        $options
    );
}

/** @return list<string> */
function property_status_filter_options(): array
{
    $statuses = property_distinct_values('status');
    $normalized = array_map(
        static fn(string $status): string => strtolower(str_replace(' ', '-', trim($status))),
        $statuses
    );

    if (!in_array('off-plan', $normalized, true)) {
        $rows = db()->query(
            'SELECT property_type, status FROM properties WHERE COALESCE(is_visible, 1) = 1'
        )->fetchAll();

        foreach ($rows as $row) {
            if (property_listing_is_off_plan($row)) {
                $statuses[] = 'Off-Plan';
                break;
            }
        }
    }

    sort($statuses, SORT_NATURAL | SORT_FLAG_CASE);

    return array_values(array_unique($statuses));
}

function property_sql_off_plan_condition(string $alias = 'p'): string
{
    return "(
        LOWER(REPLACE(TRIM({$alias}.status), ' ', '-')) IN ('off-plan', 'offplan')
        OR LOWER(COALESCE({$alias}.property_type, '')) LIKE '%villa%'
        OR EXISTS (
            SELECT 1 FROM property_features pf
            WHERE pf.property_id = {$alias}.id
              AND (
                LOWER(pf.feature_value) LIKE '%off%plan%'
                OR LOWER(pf.feature_label) LIKE '%sale format%'
              )
        )
    )";
}

function property_matches_off_plan_status(array $property): bool
{
    return property_listing_is_off_plan($property);
}

function property_search(array $filters, string $category = 'all', string $sort = 'newest', bool $applyCategory = true): array
{
    $where  = ['COALESCE(p.is_visible, 1) = 1'];
    $params = [];

    if (!empty($filters['keyword'])) {
        $keyword = '%' . trim($filters['keyword']) . '%';
        $where[] = '(
            p.title LIKE :keyword_a OR p.short_description LIKE :keyword_b OR p.full_description LIKE :keyword_c
            OR p.location_name LIKE :keyword_d OR p.price LIKE :keyword_e OR p.property_type LIKE :keyword_f
            OR p.listing_purpose LIKE :keyword_g OR p.status LIKE :keyword_h OR p.size LIKE :keyword_i
            OR EXISTS (
                SELECT 1 FROM property_features pf
                WHERE pf.property_id = p.id
                  AND (pf.feature_label LIKE :keyword_j OR pf.feature_value LIKE :keyword_k)
            )
            OR EXISTS (
                SELECT 1 FROM property_lots pl
                WHERE pl.property_id = p.id
                  AND (pl.label LIKE :keyword_l OR pl.size LIKE :keyword_m OR pl.price LIKE :keyword_n)
            )
        )';
        foreach ([
            'keyword_a', 'keyword_b', 'keyword_c', 'keyword_d', 'keyword_e', 'keyword_f',
            'keyword_g', 'keyword_h', 'keyword_i', 'keyword_j', 'keyword_k', 'keyword_l', 'keyword_m', 'keyword_n',
        ] as $key) {
            $params[$key] = $keyword;
        }
    }

    if (!empty($filters['location'])) {
        $where[] = 'p.location_name = :location';
        $params['location'] = trim($filters['location']);
    }

    if (!empty($filters['property_type'])) {
        $where[] = 'p.property_type = :property_type';
        $params['property_type'] = trim($filters['property_type']);
    }

    if (!empty($filters['listing_purpose'])) {
        $where[] = 'p.listing_purpose = :listing_purpose';
        $params['listing_purpose'] = trim($filters['listing_purpose']);
    }

    if (!empty($filters['status'])) {
        $status = trim($filters['status']);
        if (preg_match('/^off[\s-]?plan$/i', $status)) {
            $where[] = property_sql_off_plan_condition('p');
        } else {
            $where[] = 'p.status = :status';
            $params['status'] = $status;
        }
    }

    if (!empty($filters['min_price'])) {
        $minAmount = property_filter_price_amount($filters['min_price']);
        if ($minAmount !== null) {
            $where[] = 'p.price_numeric IS NOT NULL AND p.price_numeric >= :min_price_numeric';
            $params['min_price_numeric'] = $minAmount;
        }
    }

    if (!empty($filters['max_price'])) {
        $maxAmount = property_filter_price_amount($filters['max_price']);
        if ($maxAmount !== null) {
            $where[] = 'p.price_numeric IS NOT NULL AND p.price_numeric <= :max_price_numeric';
            $params['max_price_numeric'] = $maxAmount;
        }
    }

    if ($applyCategory) {
        if ($category === 'plots') {
            $where[] = "LOWER(COALESCE(p.property_type, '')) LIKE '%plot%'";
        } elseif ($category === 'villas') {
            $where[] = "LOWER(COALESCE(p.property_type, '')) LIKE '%villa%'";
        } elseif ($category === 'off-plan') {
            $where[] = property_sql_off_plan_condition('p');
        }
    }

    $orderBy = match ($sort) {
        'oldest' => 'p.created_at ASC, p.display_order ASC, p.title ASC',
        'title'  => 'p.title ASC, p.display_order ASC',
        'price_asc'  => 'p.price_numeric IS NULL ASC, p.price_numeric ASC, p.title ASC',
        'price_desc' => 'p.price_numeric IS NULL ASC, p.price_numeric DESC, p.title ASC',
        default  => 'p.created_at DESC, p.display_order ASC, p.title ASC',
    };

    $sql = 'SELECT p.*,
        COALESCE(
            (SELECT pm.file_path FROM property_media pm
             WHERE pm.property_id = p.id AND pm.is_main = 1 AND pm.media_type = \'image\'
             ORDER BY pm.display_order ASC, pm.id ASC LIMIT 1),
            (SELECT pm.file_path FROM property_media pm
             WHERE pm.property_id = p.id AND pm.media_type = \'image\'
             ORDER BY pm.display_order ASC, pm.id ASC LIMIT 1)
        ) AS card_image
        FROM properties p
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ' . $orderBy;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function property_card_price_display(?string $price): string
{
    $price = trim((string) $price);
    if ($price === '') {
        return '';
    }

    $amount = property_price_amount($price);
    if ($amount !== null) {
        return 'Rs ' . number_format($amount);
    }

    return $price;
}

/** @param list<array<string, mixed>> $lots */
function property_card_price_for_property(array $property, array $lots = []): string
{
    if ($lots !== []) {
        $amounts = array_values(array_filter(array_map(
            static fn(array $lot): ?int => property_price_amount($lot['price'] ?? null),
            $lots
        )));

        if ($amounts !== []) {
            $min = min($amounts);
            $formatted = 'Rs ' . number_format($min);

            return count($amounts) > 1 ? 'From ' . $formatted : $formatted;
        }
    }

    return property_card_price_display($property['price'] ?? '');
}

/** @param list<array<string, mixed>> $lots */
function property_card_stat_for_property(array $property, array $lots = []): string
{
    if ($lots !== []) {
        $sizes = array_values(array_filter(array_map(
            static fn(array $lot): string => trim((string) ($lot['size'] ?? '')),
            $lots
        )));

        if (count($sizes) === 1) {
            return $sizes[0];
        }

        if (count($sizes) > 1) {
            return count($sizes) . ' lots';
        }
    }

    return property_card_stat_line($property);
}

function property_home_card_stats(array $features, ?string $size): array
{
    $stats = ['beds' => null, 'baths' => null, 'area' => $size ?: null];

    foreach ($features as $feature) {
        $label = strtolower($feature['feature_label']);
        if (str_contains($label, 'bed')) {
            $stats['beds'] = $feature['feature_value'];
        }
        if (str_contains($label, 'bath')) {
            $stats['baths'] = $feature['feature_value'];
        }
    }

    return $stats;
}

/** @return list<array{label: string, kind: string, variant: string}> */
function property_card_badges(array $property): array
{
    $badges = [];

    if (!empty($property['listing_purpose'])) {
        $purpose = strtolower(trim((string) $property['listing_purpose']));
        $label = match ($purpose) {
            'sale'  => 'FOR SALE',
            'rent'  => 'FOR RENT',
            default => strtoupper((string) $property['listing_purpose']),
        };
        $badges[] = [
            'label'   => $label,
            'kind'    => 'purpose',
            'variant' => property_card_badge_slug($label),
        ];
    }

    if (!empty($property['status'])) {
        $label = strtoupper(str_replace(' ', '-', (string) $property['status']));
        $badges[] = [
            'label'   => $label,
            'kind'    => 'status',
            'variant' => property_card_badge_slug($label),
        ];
    }

    if (property_listing_is_off_plan($property) && !property_card_badges_has_variant($badges, 'off-plan')) {
        $badges[] = [
            'label'   => 'OFF-PLAN',
            'kind'    => 'status',
            'variant' => 'off-plan',
        ];
    }

    return $badges;
}

/** @param list<array{label: string, kind: string, variant: string}> $badges */
function property_card_badges_has_variant(array $badges, string $variant): bool
{
    foreach ($badges as $badge) {
        if (($badge['variant'] ?? '') === $variant) {
            return true;
        }
    }

    return false;
}

function property_listing_is_off_plan(array $property): bool
{
    $status = strtolower(trim((string) ($property['status'] ?? '')));
    if ($status === 'off-plan' || str_contains($status, 'off plan')) {
        return true;
    }

    return property_is_villa($property);
}

function property_card_badge_slug(string $label): string
{
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $label) ?? '', '-'));

    return $slug !== '' ? $slug : 'default';
}

function property_is_villa(array $property): bool
{
    return stripos((string) ($property['property_type'] ?? ''), 'villa') !== false;
}

function property_card_title(array $property): string
{
    $title = trim((string) ($property['title'] ?? ''));
    if ($title !== '') {
        return $title;
    }

    $size = trim(explode('·', (string) ($property['size'] ?? ''))[0]);
    $area = property_card_area_label($property);

    if ($size !== '' && $area !== '') {
        return $size . ' ' . $area;
    }

    return $size !== '' ? $size : $area;
}

/** @return list<string> */
function property_legacy_media_folder_names(array $property): array
{
    $names = [];
    $title = trim((string) ($property['title'] ?? ''));
    if ($title !== '') {
        $names[] = $title;
    }

    $size = trim(explode('·', (string) ($property['size'] ?? ''))[0]);
    $area = property_card_area_label($property);
    if ($size !== '' && $area !== '') {
        $legacy = $size . ' ' . $area;
        if (!in_array($legacy, $names, true)) {
            $names[] = $legacy;
        }
    } elseif ($size !== '' && !in_array($size, $names, true)) {
        $names[] = $size;
    }

    return $names;
}

function property_resolved_media_folder_name(array $property): string
{
    $dir = property_card_gallery_dir($property);
    if ($dir !== '' && is_dir($dir)) {
        return basename(str_replace('\\', '/', $dir));
    }

    $names = property_legacy_media_folder_names($property);

    return $names[0] ?? property_card_title($property);
}

function property_card_type_label(array $property): string
{
    return trim((string) ($property['property_type'] ?? 'Property'));
}

function property_card_stat_line(array $property): string
{
    $size = trim((string) ($property['size'] ?? ''));
    if ($size === '') {
        return '';
    }

    if (str_contains($size, '·')) {
        foreach (array_map('trim', explode('·', $size)) as $part) {
            if (stripos($part, 'm²') !== false || stripos($part, 'm2') !== false) {
                return $part;
            }
        }
    }

    return trim(explode('·', $size)[0]);
}

/** @return list<string> */
function property_lot_subdir_name_variants(string $num): array
{
    return [
        'lot_' . $num,
        'lot-' . $num,
        'lot' . $num,
        'option_' . $num,
        'option-' . $num,
        'option' . $num,
    ];
}

function property_resolve_lot_subdir(string $galleryDir, string $num): ?string
{
    foreach (property_lot_subdir_name_variants($num) as $subdir) {
        if (is_dir($galleryDir . DIRECTORY_SEPARATOR . $subdir)) {
            return $subdir;
        }
    }

    return null;
}

function property_lot_subdir_name(array $property, array $lot, int $index): ?string
{
    $galleryDir = property_card_gallery_dir($property);
    $label      = strtolower(trim((string) ($lot['label'] ?? '')));

    if (preg_match('/(?:option|lot)[\s_-]*(\d+)/i', $label, $match)) {
        return property_resolve_lot_subdir($galleryDir, $match[1]);
    }

    if ($index >= 0) {
        $num = (string) ($index + 1);

        return property_resolve_lot_subdir($galleryDir, $num);
    }

    return null;
}

/** @return array{photos: list<string>, sitemaps: list<string>, videos: list<array{url: string, title: string}>} */
function property_lot_folder_media_urls(array $property, ?string $subdir): array
{
    $empty = ['photos' => [], 'sitemaps' => [], 'videos' => []];
    $dir   = property_card_gallery_dir($property);

    if ($subdir !== null) {
        $dir = $dir . DIRECTORY_SEPARATOR . $subdir;
    }

    if (!is_dir($dir)) {
        return $empty;
    }

    $extensions = ['jpeg', 'jpg', 'png', 'webp', 'gif'];
    $photos     = [];
    $sitemaps   = [];
    $videos     = [];
    $folder     = property_resolved_media_folder_name($property);
    $prefix     = $subdir !== null ? $subdir . '/' : '';

    foreach (scandir($dir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (in_array($ext, ['mp4', 'webm'], true)) {
            $path = 'images/properties_page/' . $folder . '/' . $prefix . $file;
            $videos[] = [
                'url'   => property_static_image_url($path),
                'title' => pathinfo($file, PATHINFO_FILENAME),
            ];
            continue;
        }

        if (!in_array($ext, $extensions, true)) {
            continue;
        }

        if (preg_match('/^photo/i', $file) === 1) {
            $photos[] = $file;
        } elseif (preg_match('/^sitemap/i', $file) === 1) {
            $sitemaps[] = $file;
        } elseif ($subdir !== null) {
            $photos[] = $file;
        }
    }

    natsort($photos);
    $sitemaps = property_card_sort_sitemap_files(array_values($sitemaps));

    $buildImages = static function (array $files) use ($dir, $folder, $prefix): array {
        $urls = [];
        foreach ($files as $file) {
            $path  = 'images/properties_page/' . $folder . '/' . $prefix . $file;
            $url   = property_static_image_url($path);
            $mtime = @filemtime($dir . DIRECTORY_SEPARATOR . $file);
            if ($mtime) {
                $url .= '?v=' . $mtime;
            }
            $urls[] = $url;
        }

        return $urls;
    };

    return [
        'photos'   => $buildImages(array_values($photos)),
        'sitemaps' => $buildImages($sitemaps),
        'videos'   => $videos,
    ];
}

/** @return list<string> */
function property_lot_detail_photos(array $property, array $lot, int $index): array
{
    $propertyId = (int) ($property['id'] ?? 0);
    $lotId      = (int) ($lot['id'] ?? 0);
    $urls       = [];

    if ($lotId > 0 && $propertyId > 0) {
        foreach (property_media($propertyId, $lotId) as $row) {
            if (($row['media_type'] ?? '') !== 'image') {
                continue;
            }
            $url = property_media_public_url($row['file_path'] ?? null)
                ?: trim((string) ($row['external_url'] ?? ''));
            if ($url !== '') {
                $urls[] = $url;
            }
        }
    }

    $subdir   = property_lot_subdir_name($property, $lot, $index);
    $folder   = property_lot_folder_media_urls($property, $subdir);
    $specific = $folder['photos'] !== [] ? $folder['photos'] : property_lot_images($property, $lot, $index);
    $shared   = property_detail_photo_images($property);

    foreach (array_merge($specific, $shared) as $url) {
        if (!in_array($url, $urls, true)) {
            $urls[] = $url;
        }
    }

    return $urls;
}

/** @return list<array{url: string, title: string}> */
function property_lot_detail_videos(array $property, array $lot, int $index): array
{
    $propertyId = (int) ($property['id'] ?? 0);
    $lotId      = (int) ($lot['id'] ?? 0);
    $videos     = [];

    if ($lotId > 0 && $propertyId > 0) {
        foreach (property_media($propertyId, $lotId) as $row) {
            if (($row['media_type'] ?? '') !== 'video') {
                continue;
            }
            $url = property_media_public_url($row['file_path'] ?? null)
                ?: trim((string) ($row['external_url'] ?? ''));
            if ($url !== '') {
                $videos[] = ['url' => $url, 'title' => trim((string) ($row['title'] ?? 'Video'))];
            }
        }
    }

    $subdir = property_lot_subdir_name($property, $lot, $index);
    $folder = property_lot_folder_media_urls($property, $subdir);
    if ($folder['videos'] !== []) {
        foreach ($folder['videos'] as $video) {
            $videos[] = $video;
        }
    }

    if ($videos === []) {
        return property_detail_videos($property);
    }

    $seen = [];
    $unique = [];
    foreach ($videos as $video) {
        if (isset($seen[$video['url']])) {
            continue;
        }
        $seen[$video['url']] = true;
        $unique[] = $video;
    }

    foreach (property_detail_videos($property) as $video) {
        if (!isset($seen[$video['url']])) {
            $seen[$video['url']] = true;
            $unique[] = $video;
        }
    }

    return $unique;
}

/** @return list<string> */
function property_lot_detail_sitemaps(array $property, array $lot, int $index): array
{
    $propertyId = (int) ($property['id'] ?? 0);
    $lotId      = (int) ($lot['id'] ?? 0);
    $urls       = [];

    if ($lotId > 0 && $propertyId > 0) {
        foreach (property_media($propertyId, $lotId) as $row) {
            if (!in_array($row['media_type'] ?? '', ['sitemap', 'image'], true)
                && ($row['media_category'] ?? '') !== 'sitemap') {
                continue;
            }
            $url = property_media_public_url($row['file_path'] ?? null);
            if ($url !== null && $url !== '' && !preg_match('/\.pdf$/i', $url)) {
                $urls[] = $url;
            }
        }
    }

    $subdir = property_lot_subdir_name($property, $lot, $index);
    $folder = property_lot_folder_media_urls($property, $subdir);
    foreach ($folder['sitemaps'] as $url) {
        if (!in_array($url, $urls, true)) {
            $urls[] = $url;
        }
    }

    if ($urls === []) {
        return property_detail_sitemap_images($property);
    }

    foreach (property_detail_sitemap_images($property) as $url) {
        if (!in_array($url, $urls, true)) {
            $urls[] = $url;
        }
    }

    return $urls;
}

/** @return list<string> */
function property_lot_images(array $property, array $lot, int $index = 0): array
{
    $subdir = property_lot_subdir_name($property, $lot, $index);
    if ($subdir === null) {
        return [];
    }

    return property_lot_folder_media_urls($property, $subdir)['photos'];
}

function property_card_gallery_dir(array $property): string
{
    $base = dirname(__DIR__) . '/images/properties_page/';

    foreach (property_legacy_media_folder_names($property) as $folder) {
        $dir = $base . $folder;
        if (is_dir($dir)) {
            return $dir;
        }
    }

    $names = property_legacy_media_folder_names($property);

    return $base . ($names[0] ?? '');
}

/** @return list<string> */
function property_card_gallery_images(array $property): array
{
    $propertyId = (int) ($property['id'] ?? 0);

    if ($propertyId > 0 && property_is_villa($property)) {
        $lots = property_lots($propertyId);
        if ($lots !== []) {
            $displayLots = property_lots_for_display($propertyId, $property);
            $combined    = property_villa_combined_gallery_images($displayLots, $property);
            if ($combined !== []) {
                return $combined;
            }
        }
    }

    $dir = property_card_gallery_dir($property);
    if (!is_dir($dir)) {
        return [];
    }

    $extensions = ['jpeg', 'jpg', 'png', 'webp', 'gif'];
    $photos     = [];
    $details    = [];
    $sitemaps   = [];

    foreach (scandir($dir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $extensions, true)) {
            continue;
        }

        if (preg_match('/^photo/i', $file) === 1) {
            $photos[] = $file;
        } elseif (preg_match('/^details\./i', $file) === 1) {
            $details[] = $file;
        } elseif (preg_match('/^sitemap/i', $file) === 1) {
            $sitemaps[] = $file;
        }
    }

    natsort($photos);
    natsort($details);
    $sitemaps = property_card_sort_sitemap_files(array_values($sitemaps));

    $files = $photos !== [] ? array_values($photos) : ($sitemaps !== [] ? $sitemaps : array_values($details));
    if ($files === []) {
        return [];
    }

    $folder = property_resolved_media_folder_name($property);
    $urls   = [];

    foreach ($files as $file) {
        $path = 'images/properties_page/' . $folder . '/' . $file;
        $url  = property_static_image_url($path);
        $mtime = @filemtime($dir . DIRECTORY_SEPARATOR . $file);
        if ($mtime) {
            $url .= '?v=' . $mtime;
        }
        $urls[] = $url;
    }

    return $urls;
}

/** @param list<string> $files */
function property_card_sort_sitemap_files(array $files): array
{
    usort($files, static function (string $a, string $b): int {
        $rank = static function (string $file): int {
            $base = strtolower(pathinfo($file, PATHINFO_FILENAME));
            if ($base === 'sitemap') {
                return 0;
            }
            if (preg_match('/^sitemap(\d+)$/i', $base) === 1) {
                return 1;
            }

            return 2;
        };

        $rankA = $rank($a);
        $rankB = $rank($b);
        if ($rankA !== $rankB) {
            return $rankA <=> $rankB;
        }

        return strnatcasecmp($a, $b);
    });

    return $files;
}

function property_static_image_url(string $relativePath): string
{
    $segments = explode('/', str_replace('\\', '/', ltrim($relativePath, '/')));

    return rtrim(BASE_URL, '/') . '/' . implode('/', array_map('rawurlencode', $segments));
}

/** @return array{photos: list<string>, sitemaps: list<string>} */
function property_detail_folder_media_urls(array $property): array
{
    $dir = property_card_gallery_dir($property);
    $empty = ['photos' => [], 'sitemaps' => []];

    if (!is_dir($dir)) {
        return $empty;
    }

    $extensions = ['jpeg', 'jpg', 'png', 'webp', 'gif'];
    $photos     = [];
    $sitemaps   = [];

    foreach (scandir($dir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $extensions, true)) {
            continue;
        }

        if (preg_match('/^photo/i', $file) === 1) {
            $photos[] = $file;
        } elseif (preg_match('/^sitemap/i', $file) === 1) {
            $sitemaps[] = $file;
        }
    }

    natsort($photos);
    $sitemaps = property_card_sort_sitemap_files(array_values($sitemaps));
    $folder   = property_resolved_media_folder_name($property);

    $build = static function (array $files) use ($dir, $folder): array {
        $urls = [];
        foreach ($files as $file) {
            $path  = 'images/properties_page/' . $folder . '/' . $file;
            $url   = property_static_image_url($path);
            $mtime = @filemtime($dir . DIRECTORY_SEPARATOR . $file);
            if ($mtime) {
                $url .= '?v=' . $mtime;
            }
            $urls[] = $url;
        }

        return $urls;
    };

    return [
        'photos'   => $build(array_values($photos)),
        'sitemaps' => $build($sitemaps),
    ];
}

/** @return list<string> */
function property_db_photo_urls(int $propertyId): array
{
    $urls = [];

    foreach (property_media_by_category($propertyId, 'image', 'actual') as $media) {
        $url = property_media_public_url($media['file_path'] ?? null);
        if ($url !== null && $url !== '' && !in_array($url, $urls, true)) {
            $urls[] = $url;
        }
    }

    foreach (property_media_by_category($propertyId, 'image', 'ai') as $media) {
        $url = property_media_public_url($media['file_path'] ?? null)
            ?: trim((string) ($media['external_url'] ?? ''));
        if ($url !== '' && !in_array($url, $urls, true)) {
            $urls[] = $url;
        }
    }

    return $urls;
}

/** @return list<string> */
function property_db_sitemap_urls(int $propertyId): array
{
    $urls = [];

    foreach (property_sitemaps($propertyId) as $sitemap) {
        $url = property_media_public_url($sitemap['file_path'] ?? null);
        if ($url !== null && $url !== '' && !preg_match('/\.pdf$/i', $url) && !in_array($url, $urls, true)) {
            $urls[] = $url;
        }
    }

    return $urls;
}

/** @return list<array{url: string, title: string}> */
function property_db_video_items(int $propertyId): array
{
    $videos = [];

    foreach (property_media_by_category($propertyId, 'video') as $video) {
        $url = property_media_public_url($video['file_path'] ?? null)
            ?: trim((string) ($video['external_url'] ?? ''));
        if ($url === '') {
            continue;
        }
        $videos[] = [
            'url'   => $url,
            'title' => trim((string) ($video['title'] ?? 'Video')),
        ];
    }

    return $videos;
}

/** @return list<string> */
function property_detail_photo_images(array $property): array
{
    $id = (int) ($property['id'] ?? 0);

    if ($id > 0) {
        $adminPhotos = property_db_photo_urls($id);
        if ($adminPhotos !== []) {
            return $adminPhotos;
        }
    }

    $urls = property_detail_folder_media_urls($property)['photos'];

    if ($urls === [] && $id > 0) {
        $fallback = property_media_public_url($property['card_image'] ?? null);
        if ($fallback) {
            $urls[] = $fallback;
        }
    }

    return $urls;
}

/** @return list<string> */
function property_detail_sitemap_images(array $property): array
{
    $id = (int) ($property['id'] ?? 0);

    if ($id > 0) {
        $adminSitemaps = property_db_sitemap_urls($id);
        if ($adminSitemaps !== []) {
            return $adminSitemaps;
        }
    }

    return property_detail_folder_media_urls($property)['sitemaps'];
}

/** @return list<string> */
function property_detail_gallery_images(array $property): array
{
    return property_detail_photo_images($property);
}

/** @return list<string> @deprecated use property_detail_photo_images */
function property_detail_folder_images(array $property): array
{
    $media = property_detail_folder_media_urls($property);

    return array_merge($media['photos'], $media['sitemaps']);
}

/** @return list<array{url: string, title: string}> */
function property_detail_videos(array $property): array
{
    $id = (int) ($property['id'] ?? 0);

    if ($id > 0) {
        $adminVideos = property_db_video_items($id);
        if ($adminVideos !== []) {
            return $adminVideos;
        }
    }

    $videos = [];
    $dir    = property_card_gallery_dir($property);

    if (is_dir($dir)) {
        $folder = property_resolved_media_folder_name($property);
        foreach (scandir($dir) ?: [] as $file) {
            if (!preg_match('/\.(mp4|webm)$/i', $file)) {
                continue;
            }
            $path = 'images/properties_page/' . $folder . '/' . $file;
            $url  = property_static_image_url($path);
            $videos[] = ['url' => $url, 'title' => pathinfo($file, PATHINFO_FILENAME)];
        }
    }

    return $videos;
}

/** @return list<array{label: string, value: string}> */
function property_detail_address_rows(array $property): array
{
    $rows = [];

    if (!empty($property['location_name'])) {
        $rows[] = ['label' => 'Address', 'value' => (string) $property['location_name']];
    }

    if (!empty($property['location_area'])) {
        $rows[] = ['label' => 'Area', 'value' => (string) $property['location_area']];
    }

    $rows[] = ['label' => 'Country', 'value' => 'Mauritius'];

    return $rows;
}

/** @return list<array{label: string, value: string}> */
function property_detail_specs(array $property, array $features = []): array
{
    $specs = [];

    $skipLabels = [
        'price',
        'villa price',
        'land size',
        'property size',
        'land area',
        'plot size',
        'sale format',
        'listing type',
        'property type',
        'property status',
        'area',
        'address',
        'address reference',
        'location',
    ];

    $heroValues = array_filter([
        property_detail_normalize_spec_value(property_card_price_display($property['price'] ?? '')),
        property_detail_normalize_spec_value(property_card_stat_line($property)),
        property_detail_normalize_spec_value(property_card_type_label($property)),
        property_detail_normalize_spec_value((string) ($property['location_name'] ?? '')),
        property_detail_normalize_spec_value((string) ($property['status'] ?? '')),
        property_detail_normalize_spec_value(
            !empty($property['listing_purpose'])
                ? 'for ' . strtolower((string) $property['listing_purpose'])
                : ''
        ),
    ]);

    $size = trim((string) ($property['size'] ?? ''));
    if ($size !== '') {
        foreach (array_map('trim', explode('·', $size)) as $part) {
            if ($part !== '') {
                $heroValues[] = property_detail_normalize_spec_value($part);
            }
        }
    }

    $statusLine = implode(' · ', array_filter([
        !empty($property['listing_purpose']) ? 'for ' . strtolower((string) $property['listing_purpose']) : '',
        !empty($property['status']) ? strtolower((string) $property['status']) : '',
    ]));
    if ($statusLine !== '') {
        $heroValues[] = property_detail_normalize_spec_value($statusLine);
    }

    $heroValues = array_values(array_unique(array_filter($heroValues)));

    foreach ($features as $feature) {
        $label = trim((string) ($feature['feature_label'] ?? ''));
        $value = trim((string) ($feature['feature_value'] ?? ''));
        if ($label === '' || $value === '') {
            continue;
        }

        $key = strtolower($label);
        if (in_array($key, $skipLabels, true)) {
            continue;
        }

        $normalizedValue = property_detail_normalize_spec_value($value);
        if ($normalizedValue === '' || in_array($normalizedValue, $heroValues, true)) {
            continue;
        }

        $specs[] = ['label' => $label, 'value' => $value];
    }

    return $specs;
}

function property_detail_normalize_spec_value(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;
    $value = str_replace(['perches', 'perch'], 'perch', $value);

    return $value;
}

/** @return list<array<string, mixed>> */
function property_similar_listings(array $property, int $limit = 4): array
{
    $id   = (int) ($property['id'] ?? 0);
    $type = strtolower((string) ($property['property_type'] ?? ''));

    $list = array_values(array_filter(
        property_search([]),
        static fn(array $item): bool => (int) ($item['id'] ?? 0) !== $id
    ));

    usort($list, static function (array $a, array $b) use ($type): int {
        $aMatch = stripos((string) ($a['property_type'] ?? ''), $type) !== false ? 0 : 1;
        $bMatch = stripos((string) ($b['property_type'] ?? ''), $type) !== false ? 0 : 1;

        return $aMatch <=> $bMatch;
    });

    return array_slice($list, 0, max(1, $limit));
}

function property_whatsapp_url_for(string $baseUrl, string $propertyTitle): string
{
    $text = 'Hello, I am interested in [' . $propertyTitle . ']';
    $sep  = str_contains($baseUrl, '?') ? '&' : '?';

    return $baseUrl . $sep . 'text=' . rawurlencode($text);
}

/** @return list<array{icon: string, label: string}> */
function property_card_features(array $property, array $features = []): array
{
    $items = [];

    if (!empty($property['size'])) {
        $size = trim(explode('·', (string) $property['size'])[0]);
        if ($size !== '') {
            $label = strtolower($size);
            if (preg_match('/^\d+\s+perch$/i', $size)) {
                $label = preg_replace('/perch$/i', 'perches', $size);
            }
            $items[] = ['icon' => 'size', 'label' => $label];
        }
    }

    foreach ($features as $feature) {
        $name = strtolower((string) ($feature['feature_label'] ?? ''));
        $value = trim((string) ($feature['feature_value'] ?? ''));
        if ($value === '') {
            continue;
        }
        if (str_contains($name, 'bed')) {
            $items[] = ['icon' => 'bed', 'label' => $value];
        } elseif (str_contains($name, 'bath')) {
            $items[] = ['icon' => 'bath', 'label' => $value];
        }
    }

    return $items;
}

function property_card_price_short(?string $price): string
{
    $price = trim((string) $price);
    if ($price === '') {
        return '';
    }

    if (preg_match('/Rs\s*([\d,]+)/i', $price, $match)) {
        $amount = (int) str_replace(',', '', $match[1]);
        if ($amount >= 1000000) {
            $millions = $amount / 1000000;
            $formatted = fmod($millions, 1.0) === 0.0
                ? (string) (int) $millions
                : rtrim(rtrim(number_format($millions, 1, '.', ''), '0'), '.');

            return 'Rs ' . $formatted . 'M';
        }
    }

    return $price;
}

function property_card_area_label(array $property): string
{
    $location = trim((string) ($property['location_name'] ?? ''));
    if ($location === '') {
        return '';
    }

    $parts = array_map('trim', explode(',', $location));

    return $parts[0];
}

/** @return list<array{key: string, value: string}> */
function property_card_stats(array $property): array
{
    $stats = [];

    if (!empty($property['size'])) {
        $size = (string) $property['size'];
        $shortSize = trim(explode('·', $size)[0]);
        $stats[] = ['key' => 'size', 'value' => $shortSize !== '' ? $shortSize : $size];
    }

    $type = strtolower((string) ($property['property_type'] ?? ''));
    if (str_contains($type, 'villa')) {
        $stats[] = ['key' => 'villa', 'value' => 'Off-plan'];
    }

    return $stats;
}

function property_card_kind(array $property): string
{
    $type    = trim((string) ($property['property_type'] ?? 'Property'));
    $purpose = trim((string) ($property['listing_purpose'] ?? 'Sale'));

    return $type . ' · ' . $purpose;
}

function property_card_tag(array $property): string
{
    if (property_listing_is_off_plan($property)) {
        return 'Off-Plan';
    }

    $type = trim((string) ($property['property_type'] ?? ''));

    return $type !== '' ? $type : 'Property';
}

function property_card_meta_line(array $property): string
{
    $parts = [];

    if (!empty($property['size'])) {
        $size = trim(explode('·', (string) $property['size'])[0]);
        if ($size !== '') {
            $parts[] = $size;
        }
    }

    $type = strtolower((string) ($property['property_type'] ?? ''));
    if (str_contains($type, 'villa')) {
        $parts[] = 'Off-plan sale';
    }

    return implode(' · ', $parts);
}

function property_map_embed_html(array $property): string
{
    if (!empty($property['google_maps_embed'])) {
        return (string) $property['google_maps_embed'];
    }

    $lat = trim((string) ($property['latitude'] ?? ''));
    $lng = trim((string) ($property['longitude'] ?? ''));

    if ($lat === '' || $lng === '') {
        return '';
    }

    $type = strtolower((string) ($property['property_type'] ?? ''));
    $zoom = str_contains($type, 'plot') ? 18 : 17;
    $query = rawurlencode($lat . ',' . $lng);

    return '<iframe src="https://maps.google.com/maps?q=' . $query . '&amp;z=' . $zoom . '&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>';
}

function property_has_map(array $property): bool
{
    return property_map_embed_html($property) !== ''
        || !empty($property['google_maps_link']);
}

function property_media(int $propertyId, ?int $lotId = null): array
{
    $where  = ['property_id = :property_id'];
    $params = ['property_id' => $propertyId];

    if ($lotId !== null) {
        $where[] = 'lot_id = :lot_id';
        $params['lot_id'] = $lotId;
    } else {
        $where[] = 'lot_id IS NULL';
    }

    $sql = 'SELECT * FROM property_media WHERE ' . implode(' AND ', $where)
        . ' ORDER BY display_order ASC, id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function property_parse_basic(array $post): array
{
    return [
        'title'             => trim($post['title'] ?? ''),
        'property_type'     => trim($post['property_type'] ?? ''),
        'listing_purpose'   => trim($post['listing_purpose'] ?? ''),
        'status'            => trim($post['status'] ?? ''),
        'price'             => trim($post['price'] ?? ''),
        'size'              => trim($post['size'] ?? ''),
        'location_name'     => trim($post['location_name'] ?? ''),
        'short_description' => trim($post['short_description'] ?? ''),
        'full_description'  => trim($post['full_description'] ?? ''),
        'is_visible'        => !isset($post['is_visible']) || !empty($post['is_visible']) ? 1 : 0,
        'is_featured'         => !empty($post['is_featured']) ? 1 : 0,
        'show_on_home'        => !empty($post['show_on_home']) ? 1 : 0,
        'display_order'     => (int) ($post['display_order'] ?? 0),
        'google_maps_link'  => trim($post['google_maps_link'] ?? ''),
        'google_maps_embed' => trim($post['google_maps_embed'] ?? ''),
        'latitude'          => trim($post['latitude'] ?? ''),
        'longitude'         => trim($post['longitude'] ?? ''),
    ];
}

function property_validate_basic(array $data): ?string
{
    if ($data['title'] === '') {
        return 'Property title is required.';
    }

    if (trim((string) ($data['property_type'] ?? '')) === '') {
        return 'Property type is required.';
    }

    return null;
}

/** @param list<array<string, mixed>> $lotsData */
function property_validate_lots(array $data, array $lotsData): ?string
{
    if (stripos((string) ($data['property_type'] ?? ''), 'villa') === false) {
        return null;
    }

    foreach ($lotsData as $lot) {
        $size  = trim((string) ($lot['size'] ?? ''));
        $price = trim((string) ($lot['price'] ?? ''));
        if ($size !== '' && $price !== '') {
            return null;
        }
    }

    return 'Villa listings need at least one unit with land size and price.';
}

function property_save_basic(?int $id, array $data): int
{
    if ($id) {
        db()->prepare(
            'UPDATE properties SET
              title = :title, property_type = :property_type, listing_purpose = :listing_purpose,
              status = :status, price = :price, size = :size, location_name = :location_name,
              short_description = :short_description, full_description = :full_description,
              is_visible = :is_visible, is_featured = :is_featured, show_on_home = :show_on_home,
              display_order = :display_order,
              google_maps_link = :google_maps_link, google_maps_embed = :google_maps_embed,
              latitude = :latitude, longitude = :longitude, updated_at = NOW()
             WHERE id = :id'
        )->execute(array_merge($data, ['id' => $id]));

        return $id;
    }

    db()->prepare(
        'INSERT INTO properties (
          title, property_type, listing_purpose, status, price, size, location_name,
          short_description, full_description, is_visible, is_featured, show_on_home, display_order,
          google_maps_link, google_maps_embed, latitude, longitude
        ) VALUES (
          :title, :property_type, :listing_purpose, :status, :price, :size, :location_name,
          :short_description, :full_description, :is_visible, :is_featured, :show_on_home, :display_order,
          :google_maps_link, :google_maps_embed, :latitude, :longitude
        )'
    )->execute($data);

    return (int) db()->lastInsertId();
}

function property_sync_features(int $propertyId, array $labels, array $values, array $orders): void
{
    db()->prepare('DELETE FROM property_features WHERE property_id = :id')->execute(['id' => $propertyId]);

    $insert = db()->prepare(
        'INSERT INTO property_features (property_id, feature_label, feature_value, display_order)
         VALUES (:property_id, :feature_label, :feature_value, :display_order)'
    );

    foreach ($labels as $i => $label) {
        $label = trim($label);
        $value = trim($values[$i] ?? '');
        if ($label === '' && $value === '') {
            continue;
        }
        $insert->execute([
            'property_id'   => $propertyId,
            'feature_label' => $label,
            'feature_value' => $value,
            'display_order' => (int) ($orders[$i] ?? $i),
        ]);
    }
}

/** @return array<int, int> Form row index => saved lot id */
function property_sync_lots(int $propertyId, array $lotsData): array
{
    $existing = db()->prepare('SELECT id FROM property_lots WHERE property_id = :id');
    $existing->execute(['id' => $propertyId]);
    $existingIds = array_map('intval', $existing->fetchAll(PDO::FETCH_COLUMN));

    $keptIds = [];

    $update = db()->prepare(
        'UPDATE property_lots SET
          label = :label, size = :size, price = :price, description = :description,
          bedrooms = :bedrooms, bathrooms = :bathrooms, villa_size = :villa_size,
          status = :status, display_order = :display_order
         WHERE id = :id AND property_id = :property_id'
    );

    $insert = db()->prepare(
        'INSERT INTO property_lots (
          property_id, label, size, price, description, bedrooms, bathrooms, villa_size, status, display_order
        ) VALUES (
          :property_id, :label, :size, :price, :description, :bedrooms, :bathrooms, :villa_size, :status, :display_order
        )'
    );

    foreach ($lotsData as $i => $lot) {
        $size  = trim((string) ($lot['size'] ?? ''));
        $price = trim((string) ($lot['price'] ?? ''));
        $label = trim((string) ($lot['label'] ?? ''));

        if ($size === '' && $price === '' && $label === '') {
            continue;
        }

        if ($size === '' || $price === '') {
            continue;
        }

        $payload = [
            'label'         => $label !== '' ? $label : null,
            'size'          => $size,
            'price'         => $price,
            'description'   => trim((string) ($lot['description'] ?? '')) ?: null,
            'bedrooms'      => trim((string) ($lot['bedrooms'] ?? '')) ?: null,
            'bathrooms'     => trim((string) ($lot['bathrooms'] ?? '')) ?: null,
            'villa_size'    => trim((string) ($lot['villa_size'] ?? '')) ?: null,
            'status'        => trim((string) ($lot['status'] ?? 'Available')) ?: 'Available',
            'display_order' => (int) ($lot['display_order'] ?? $i),
        ];

        $lotId = (int) ($lot['id'] ?? 0);
        if ($lotId > 0 && in_array($lotId, $existingIds, true)) {
            $update->execute(array_merge($payload, [
                'id'          => $lotId,
                'property_id' => $propertyId,
            ]));
            $keptIds[$i] = $lotId;
        } else {
            $insert->execute(array_merge($payload, ['property_id' => $propertyId]));
            $keptIds[$i] = (int) db()->lastInsertId();
        }
    }

    $removeIds = array_diff($existingIds, array_values($keptIds));
    if ($removeIds !== []) {
        $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
        db()->prepare("DELETE FROM property_lots WHERE id IN ($placeholders) AND property_id = ?")
            ->execute([...array_values($removeIds), $propertyId]);
    }

    return $keptIds;
}

/** @return list<array<string, mixed>> */
function property_lots_from_post(array $post): array
{
    $ids          = $post['lot_id'] ?? [];
    $labels       = $post['lot_label'] ?? [];
    $sizes        = $post['lot_size'] ?? [];
    $prices       = $post['lot_price'] ?? [];
    $orders       = $post['lot_order'] ?? [];
    $descriptions = $post['lot_description'] ?? [];
    $bedrooms     = $post['lot_bedrooms'] ?? [];
    $bathrooms    = $post['lot_bathrooms'] ?? [];
    $villaSizes   = $post['lot_villa_size'] ?? [];
    $statuses     = $post['lot_status'] ?? [];
    $lots         = [];

    foreach ($sizes as $i => $size) {
        $lots[] = [
            'id'            => (int) ($ids[$i] ?? 0),
            'label'         => trim((string) ($labels[$i] ?? '')),
            'size'          => trim((string) $size),
            'price'         => trim((string) ($prices[$i] ?? '')),
            'description'   => trim((string) ($descriptions[$i] ?? '')),
            'bedrooms'      => trim((string) ($bedrooms[$i] ?? '')),
            'bathrooms'     => trim((string) ($bathrooms[$i] ?? '')),
            'villa_size'    => trim((string) ($villaSizes[$i] ?? '')),
            'status'        => trim((string) ($statuses[$i] ?? 'Available')),
            'display_order' => (int) ($orders[$i] ?? $i),
        ];
    }

    return $lots;
}

/** @return list<string> */
function property_lot_status_options(): array
{
    return ['Available', 'Sold', 'Reserved', 'Under Offer'];
}

function property_uses_villa_lots(array $property, array $lots = []): bool
{
    if (!property_is_villa($property)) {
        return false;
    }

    if ($lots === [] && !empty($property['id'])) {
        $lots = property_lots((int) $property['id']);
    }

    return $lots !== [];
}

/** @return list<array<string, mixed>> */
function property_lots_for_display(int $propertyId, array $property = []): array
{
    $lots = property_lots_sorted_for_display(property_lots($propertyId));
    if ($lots === []) {
        return [];
    }

    if ($property === []) {
        $property = property_find($propertyId) ?? [];
    }

    foreach ($lots as $index => &$lot) {
        $lot['photos']        = property_lot_detail_photos($property, $lot, $index);
        $lot['videos']        = property_lot_detail_videos($property, $lot, $index);
        $lot['sitemaps']      = property_lot_detail_sitemaps($property, $lot, $index);
        $lot['stats']         = property_lot_stats($lot);
        $lot['price_display'] = property_card_price_display((string) ($lot['price'] ?? ''));
    }
    unset($lot);

    return $lots;
}

/** @return list<array<string, mixed>> */
function property_lots_sorted_for_display(array $lots): array
{
    if ($lots === []) {
        return [];
    }

    usort($lots, static function (array $a, array $b): int {
        $keyA = property_lot_sort_number($a);
        $keyB = property_lot_sort_number($b);

        if ($keyA !== $keyB) {
            return $keyA <=> $keyB;
        }

        $orderA = (int) ($a['display_order'] ?? 0);
        $orderB = (int) ($b['display_order'] ?? 0);
        if ($orderA !== $orderB) {
            return $orderA <=> $orderB;
        }

        return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
    });

    return array_values($lots);
}

function property_lot_sort_number(array $lot): int
{
    if (preg_match('/(?:option|lot)\s*(\d+)/i', (string) ($lot['label'] ?? ''), $match)) {
        return (int) $match[1];
    }

    return 1000 + (int) ($lot['display_order'] ?? 0);
}

/** @param list<array<string, mixed>> $displayLots @return list<string> */
function property_villa_combined_gallery_images(array $displayLots, array $property): array
{
    $urls = [];

    foreach ($displayLots as $index => $lot) {
        foreach ($lot['photos'] ?? [] as $url) {
            if ($url !== '' && !in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        if (($lot['photos'] ?? []) === []) {
            $subdir = property_lot_subdir_name($property, $lot, $index);
            if ($subdir !== null) {
                foreach (property_lot_folder_media_urls($property, $subdir)['photos'] as $url) {
                    if ($url !== '' && !in_array($url, $urls, true)) {
                        $urls[] = $url;
                    }
                }
            }
        }
    }

    if ($urls !== []) {
        return $urls;
    }

    return property_detail_photo_images($property);
}

/** @return array<int, array{photos: list<string>, videos: list<array{url: string, title: string}>, sitemaps: list<string>}> */
function property_lot_media_grouped(int $propertyId): array
{
    try {
        $stmt = db()->prepare(
            'SELECT * FROM property_media
             WHERE property_id = :property_id AND lot_id IS NOT NULL
             ORDER BY lot_id ASC, display_order ASC, id ASC'
        );
        $stmt->execute(['property_id' => $propertyId]);
        $rows = $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }

    $grouped = [];

    foreach ($rows as $row) {
        $lotId = (int) $row['lot_id'];
        if (!isset($grouped[$lotId])) {
            $grouped[$lotId] = ['photos' => [], 'videos' => [], 'sitemaps' => []];
        }

        $mediaType = strtolower((string) ($row['media_type'] ?? 'image'));
        $category  = strtolower((string) ($row['media_category'] ?? ''));
        $url       = property_media_public_url($row['file_path'] ?? null)
            ?: trim((string) ($row['external_url'] ?? ''));

        if ($url === '') {
            continue;
        }

        if ($mediaType === 'video' || $category === 'video') {
            $grouped[$lotId]['videos'][] = [
                'url'   => $url,
                'title' => trim((string) ($row['title'] ?? 'Video')),
            ];
        } elseif ($mediaType === 'sitemap' || $category === 'sitemap' || preg_match('/\.pdf$/i', $url)) {
            if (!preg_match('/\.pdf$/i', $url)) {
                $grouped[$lotId]['sitemaps'][] = $url;
            }
        } else {
            $grouped[$lotId]['photos'][] = $url;
        }
    }

    return $grouped;
}

/** @return list<array{label: string, value: string}> */
function property_lot_stats(array $lot): array
{
    $stats = [];

    if (!empty($lot['villa_size'])) {
        $stats[] = ['label' => 'Villa size', 'value' => (string) $lot['villa_size']];
    }
    if (!empty($lot['size'])) {
        $stats[] = ['label' => 'Land', 'value' => (string) $lot['size']];
    }
    if (!empty($lot['bedrooms'])) {
        $stats[] = ['label' => 'Bedrooms', 'value' => (string) $lot['bedrooms']];
    }
    if (!empty($lot['bathrooms'])) {
        $stats[] = ['label' => 'Bathrooms', 'value' => (string) $lot['bathrooms']];
    }
    if (!empty($lot['status'])) {
        $stats[] = ['label' => 'Status', 'value' => (string) $lot['status']];
    }

    return $stats;
}

function property_lot_stat_icon_key(string $label): string
{
    return match (strtolower(trim($label))) {
        'villa size' => 'size',
        'land'       => 'land',
        'bedrooms'   => 'bed',
        'bathrooms'  => 'bath',
        'status'     => 'status',
        default      => 'info',
    };
}

function property_lot_icon_svg(string $key): string
{
    $stroke = 'currentColor';

    return match ($key) {
        'bed' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" stroke-width="1.5" aria-hidden="true"><path d="M3 10V19M21 10V19M3 14h18M5 10V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3M7 19v2M17 19v2"/></svg>',
        'bath' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" stroke-width="1.5" aria-hidden="true"><path d="M4 12h16a2 2 0 0 1 2 2v3H2v-3a2 2 0 0 1 2-2zM6 12V7a3 3 0 0 1 3-3h1"/><path d="M8 19v2M16 19v2"/></svg>',
        'land' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" stroke-width="1.5" aria-hidden="true"><path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10z"/><circle cx="12" cy="11" r="2.5"/></svg>',
        'size' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" stroke-width="1.5" aria-hidden="true"><rect x="4" y="8" width="16" height="12" rx="1.5"/><path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M4 14h16"/></svg>',
        'status' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>',
        'price' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" stroke-width="1.5" aria-hidden="true"><path d="M7 7h10l-1 10H8L7 7z"/><path d="M9 7V5a3 3 0 0 1 3-3"/><circle cx="10" cy="11" r="1"/><circle cx="14" cy="11" r="1"/></svg>',
        'expand' => '<svg viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" stroke-width="1.75" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>',
        default => '<svg viewBox="0 0 24 24" fill="none" stroke="' . $stroke . '" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 10v5M12 7h.01"/></svg>',
    };
}

function property_lot_status_class(string $status): string
{
    $normalized = strtolower(trim($status));

    return match ($normalized) {
        'available'   => 'is-available',
        'sold'        => 'is-sold',
        'reserved', 'under offer' => 'is-reserved',
        default       => '',
    };
}

function property_lot_label(array $lot, int $index = 0): string
{
    $label = trim((string) ($lot['label'] ?? ''));

    return $label !== '' ? $label : 'Lot ' . ($index + 1);
}

function property_lot_enquiry_message(array $property, array $lot, int $index = 0): string
{
    $title = property_card_title($property);
    $label = property_lot_label($lot, $index);
    $parts = [$title, $label];

    if (!empty($lot['size'])) {
        $parts[] = (string) $lot['size'];
    }

    $price = property_card_price_display((string) ($lot['price'] ?? ''));
    if ($price !== '') {
        $parts[] = $price;
    }

    return 'Hello, I am interested in [' . implode(' — ', $parts) . ']';
}

function property_process_lot_uploads(int $propertyId, array $lotIdsByIndex, array $files): ?string
{
    $bucket = $files['lot_upload'] ?? null;
    if (!is_array($bucket)) {
        return null;
    }

    foreach ($lotIdsByIndex as $index => $lotId) {
        if ($lotId <= 0 || !isset($bucket['name'][$index])) {
            continue;
        }

        $names = $bucket['name'][$index];
        if (!is_array($names)) {
            $names = [$names];
        }

        $count = count($names);
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name'     => $bucket['name'][$index][$i] ?? '',
                'type'     => $bucket['type'][$index][$i] ?? '',
                'tmp_name' => $bucket['tmp_name'][$index][$i] ?? '',
                'error'    => $bucket['error'][$index][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $bucket['size'][$index][$i] ?? 0,
            ];

            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4', 'webm'], true)) {
                $mediaType = 'video';
                $category  = 'video';
            } elseif ($ext === 'pdf' || str_starts_with($ext, 'site')) {
                $mediaType = 'sitemap';
                $category  = 'sitemap';
            } else {
                $mediaType = 'image';
                $category  = 'actual';
            }

            $upload = property_handle_upload($file, $mediaType);
            if ($upload['error']) {
                return $upload['error'];
            }

            if (($upload['path'] ?? null) === null) {
                continue;
            }

            property_insert_media($propertyId, [
                'lot_id'         => $lotId,
                'media_type'     => $mediaType,
                'media_category' => $category,
                'file_path'      => $upload['path'],
                'external_url'   => null,
                'title'          => null,
                'alt_text'       => null,
                'is_main'        => 0,
                'display_order'  => $i,
            ]);
        }
    }

    return null;
}

function property_handle_upload(array $file, string $mediaType): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'path' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $message = property_upload_error_message((int) ($file['error'] ?? UPLOAD_ERR_OK));

        return ['ok' => false, 'path' => null, 'error' => $message !== '' ? $message : 'File upload failed.'];
    }

    $maxBytes = property_max_upload_bytes($mediaType);
    if (($file['size'] ?? 0) > $maxBytes) {
        return [
            'ok'    => false,
            'path'  => null,
            'error' => 'File exceeds ' . property_max_upload_label($mediaType) . ' limit for ' . $mediaType . ' uploads.',
        ];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, property_allowed_extensions($mediaType), true)) {
        return ['ok' => false, 'path' => null, 'error' => 'Invalid file type for ' . $mediaType . '.'];
    }

    $subdir = property_upload_subdir($mediaType);
    $dir    = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = uniqid('prop_', true) . '.' . $ext;
    $dest     = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'path' => null, 'error' => 'Could not save uploaded file.'];
    }

    return ['ok' => true, 'path' => $subdir . '/' . $filename, 'error' => null];
}

function property_insert_media(int $propertyId, array $row): void
{
    db()->prepare(
        'INSERT INTO property_media
         (property_id, lot_id, media_type, media_category, file_path, external_url, title, alt_text, is_main, display_order)
         VALUES (:property_id, :lot_id, :media_type, :media_category, :file_path, :external_url, :title, :alt_text, :is_main, :display_order)'
    )->execute([
        'property_id'    => $propertyId,
        'lot_id'         => $row['lot_id'] ?? null,
        'media_type'     => $row['media_type'],
        'media_category' => $row['media_category'],
        'file_path'      => $row['file_path'],
        'external_url'   => $row['external_url'],
        'title'          => $row['title'],
        'alt_text'       => $row['alt_text'],
        'is_main'        => $row['is_main'],
        'display_order'  => $row['display_order'],
    ]);
}

function property_process_new_media(int $propertyId, array $post, array $files): ?string
{
    $types     = $post['new_media_type'] ?? [];
    $categories = $post['new_media_category'] ?? [];
    $urls      = $post['new_external_url'] ?? [];
    $titles    = $post['new_media_title'] ?? [];
    $alts      = $post['new_alt_text'] ?? [];
    $orders    = $post['new_display_order'] ?? [];
    $mainFlags = $post['new_is_main'] ?? [];

    $fileList = $files['new_media_file'] ?? null;
    $count    = max(count($types), is_array($fileList['name'] ?? null) ? count($fileList['name']) : 0);

    for ($i = 0; $i < $count; $i++) {
        $mediaType = trim($types[$i] ?? 'image');
        if (!in_array($mediaType, property_media_types(), true)) {
            $mediaType = 'image';
        }

        $file = [
            'name'     => $fileList['name'][$i] ?? '',
            'type'     => $fileList['type'][$i] ?? '',
            'tmp_name' => $fileList['tmp_name'][$i] ?? '',
            'error'    => $fileList['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $fileList['size'][$i] ?? 0,
        ];

        $externalUrl = trim($urls[$i] ?? '');
        $filePath    = null;

        if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = property_handle_upload($file, $mediaType);
            if ($upload['error']) {
                return $upload['error'];
            }
            $filePath = $upload['path'];
        }

        if ($filePath === null && $externalUrl === '') {
            continue;
        }

        property_insert_media($propertyId, [
            'lot_id'         => null,
            'media_type'     => $mediaType,
            'media_category' => trim($categories[$i] ?? 'actual'),
            'file_path'      => $filePath,
            'external_url'   => $externalUrl !== '' ? $externalUrl : null,
            'title'          => trim($titles[$i] ?? '') ?: null,
            'alt_text'       => trim($alts[$i] ?? '') ?: null,
            'is_main'        => in_array((string) $i, array_map('strval', (array) $mainFlags), true) ? 1 : 0,
            'display_order'  => (int) ($orders[$i] ?? $i),
        ]);
    }

    return null;
}

function property_update_existing_media(array $post): void
{
    $ids    = $post['existing_media_id'] ?? [];
    $titles = $post['existing_media_title'] ?? [];
    $alts   = $post['existing_alt_text'] ?? [];
    $orders = $post['existing_display_order'] ?? [];
    $mainId = (int) ($post['main_media_id'] ?? 0);

    $update = db()->prepare(
        'UPDATE property_media SET title = :title, alt_text = :alt_text,
         display_order = :display_order, is_main = :is_main WHERE id = :id'
    );

    foreach ($ids as $i => $id) {
        $id = (int) $id;
        if ($id <= 0) {
            continue;
        }
        $update->execute([
            'id'            => $id,
            'title'         => trim($titles[$i] ?? '') ?: null,
            'alt_text'      => trim($alts[$i] ?? '') ?: null,
            'display_order' => (int) ($orders[$i] ?? $i),
            'is_main'       => ($id === $mainId) ? 1 : 0,
        ]);
    }
}

function property_delete_media_ids(array $ids): void
{
    if ($ids === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, file_path FROM property_media WHERE id IN ($placeholders)");
    $stmt->execute(array_values($ids));
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        property_unlink_file($row['file_path']);
    }

    $del = db()->prepare("DELETE FROM property_media WHERE id IN ($placeholders)");
    $del->execute(array_values($ids));
}

function property_unlink_file(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    $full = UPLOAD_PATH . '/' . ltrim(str_replace(['..', '\\'], '', $relativePath), '/');
    if (is_file($full)) {
        unlink($full);
    }
}

function property_delete(int $id): bool
{
    $property = property_find($id);
    if (!$property) {
        return false;
    }

    $media = property_media($id);
    foreach ($media as $item) {
        property_unlink_file($item['file_path']);
    }

    db()->prepare('DELETE FROM properties WHERE id = :id')->execute(['id' => $id]);

    return true;
}

function property_media_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    return UPLOAD_URL . '/' . ltrim($path, '/');
}

function property_save_from_request(?int $id, array $post, array $files): array
{
    $basic = property_parse_basic($post);
    $error = property_validate_basic($basic);
    if ($error) {
        return ['success' => false, 'error' => $error];
    }

    $lotsData = property_lots_from_post($post);
    $error    = property_validate_lots($basic, $lotsData);
    if ($error) {
        return ['success' => false, 'error' => $error];
    }

    try {
        db()->beginTransaction();

        $propertyId = property_save_basic($id, $basic);

        property_sync_features(
            $propertyId,
            $post['feature_label'] ?? [],
            $post['feature_value'] ?? [],
            $post['feature_order'] ?? []
        );

        $lotIds = property_sync_lots($propertyId, $lotsData);

        if ($id) {
            $deleteIds = array_map('intval', $post['delete_media'] ?? []);
            property_delete_media_ids(array_filter($deleteIds));

            $lotDeleteIds = array_map('intval', $post['lot_media_delete'] ?? []);
            property_delete_media_ids(array_filter($lotDeleteIds));

            if (!empty($post['new_is_main'])) {
                db()->prepare('UPDATE property_media SET is_main = 0 WHERE property_id = :id AND lot_id IS NULL')
                    ->execute(['id' => $propertyId]);
                $post['main_media_id'] = 0;
            }

            property_update_existing_media($post);
        }

        $mediaError = property_process_new_media($propertyId, $post, $files);
        if ($mediaError) {
            db()->rollBack();

            return ['success' => false, 'error' => $mediaError];
        }

        $lotMediaError = property_process_lot_uploads($propertyId, $lotIds, $files);
        if ($lotMediaError) {
            db()->rollBack();

            return ['success' => false, 'error' => $lotMediaError];
        }

        db()->commit();

        property_sync_price_numeric($propertyId);

        return ['success' => true, 'id' => $propertyId];
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        return ['success' => false, 'error' => 'Save failed: ' . $e->getMessage()];
    }
}

function property_save_media_from_request(int $id, array $post, array $files): array
{
    if ($id <= 0 || !property_find($id)) {
        return ['success' => false, 'error' => 'Property not found.'];
    }

    try {
        db()->beginTransaction();

        $deleteIds = array_map('intval', $post['delete_media'] ?? []);
        property_delete_media_ids(array_filter($deleteIds));

        if (!empty($post['new_is_main'])) {
            db()->prepare('UPDATE property_media SET is_main = 0 WHERE property_id = :id')
                ->execute(['id' => $id]);
            $post['main_media_id'] = 0;
        }

        property_update_existing_media($post);

        $mediaError = property_process_new_media($id, $post, $files);
        if ($mediaError) {
            db()->rollBack();

            return ['success' => false, 'error' => $mediaError];
        }

        db()->commit();

        return ['success' => true, 'id' => $id];
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        return ['success' => false, 'error' => 'Save failed: ' . $e->getMessage()];
    }
}

function property_yes_no(bool $value): string
{
    return $value ? 'Yes' : 'No';
}

function property_media_public_url(?string $path): ?string
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

function property_distinct_values(string $column): array
{
    $allowed = ['property_type', 'listing_purpose', 'status', 'location_name'];
    if (!in_array($column, $allowed, true)) {
        return [];
    }

    $stmt = db()->query(
        "SELECT DISTINCT `$column` AS value
         FROM properties
         WHERE `$column` IS NOT NULL AND TRIM(`$column`) != ''
         ORDER BY value ASC"
    );

    return array_column($stmt->fetchAll(), 'value');
}

function property_media_by_category(int $propertyId, string $mediaType = '', string $mediaCategory = '', ?int $lotId = null): array
{
    $where  = ['property_id = :property_id'];
    $params = ['property_id' => $propertyId];

    if ($lotId !== null) {
        $where[] = 'lot_id = :lot_id';
        $params['lot_id'] = $lotId;
    } else {
        $where[] = 'lot_id IS NULL';
    }

    if ($mediaType !== '') {
        $where[] = 'media_type = :media_type';
        $params['media_type'] = $mediaType;
    }

    if ($mediaCategory !== '') {
        $where[] = 'media_category = :media_category';
        $params['media_category'] = $mediaCategory;
    }

    $sql = 'SELECT * FROM property_media WHERE ' . implode(' AND ', $where)
        . ' ORDER BY display_order ASC, id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function property_sitemaps(int $propertyId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM property_media
         WHERE property_id = :property_id
           AND (media_category = \'sitemap\' OR media_type = \'sitemap\')
         ORDER BY display_order ASC, id ASC'
    );
    $stmt->execute(['property_id' => $propertyId]);

    return $stmt->fetchAll();
}

function property_rentals_available(): bool
{
    $stmt = db()->query(
        "SELECT COUNT(*) FROM properties
         WHERE COALESCE(is_visible, 1) = 1
           AND LOWER(listing_purpose) = 'rent'"
    );

    return (int) $stmt->fetchColumn() > 0;
}

function property_list_admin(array $filters = []): array
{
    $where  = ['1 = 1'];
    $params = [];

    if (!empty($filters['keyword'])) {
        $where[] = '(title LIKE :keyword OR location_name LIKE :keyword OR price LIKE :keyword)';
        $params['keyword'] = '%' . trim($filters['keyword']) . '%';
    }
    if (!empty($filters['property_type'])) {
        $where[] = 'property_type = :property_type';
        $params['property_type'] = $filters['property_type'];
    }
    if (!empty($filters['listing_purpose'])) {
        $where[] = 'listing_purpose = :listing_purpose';
        $params['listing_purpose'] = $filters['listing_purpose'];
    }
    if (!empty($filters['status'])) {
        $where[] = 'status = :status';
        $params['status'] = $filters['status'];
    }
    if (isset($filters['is_visible']) && $filters['is_visible'] !== '') {
        $where[] = 'COALESCE(is_visible, 1) = :is_visible';
        $params['is_visible'] = (int) $filters['is_visible'];
    }

    $sql = 'SELECT p.*,
        (SELECT pm.file_path FROM property_media pm
         WHERE pm.property_id = p.id AND pm.is_main = 1 AND pm.media_type = \'image\'
         ORDER BY pm.display_order ASC LIMIT 1) AS thumb,
        (SELECT COUNT(*) FROM property_lots pl WHERE pl.property_id = p.id) AS lot_count
        FROM properties p
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY p.display_order ASC, p.title ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function property_whatsapp_link(string $whatsappNumber, string $propertyTitle): string
{
    $message = 'Hello UZ Estates, I would like to enquire about ' . $propertyTitle . '.';

    return 'https://wa.me/' . preg_replace('/\D/', '', $whatsappNumber)
        . '?text=' . rawurlencode($message);
}
