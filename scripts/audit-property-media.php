<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/properties.php';

foreach (property_list_all() as $p) {
    $id = (int) $p['id'];
    $title = (string) $p['title'];
    $card = property_card_gallery_images($p);
    $photos = property_detail_photo_images($p);
    $maps = property_detail_sitemap_images($p);
    $vids = property_detail_videos($p);

    echo "#{$id} {$title}\n";
    echo '  card:' . count($card) . ' photos:' . count($photos) . ' sitemaps:' . count($maps) . ' videos:' . count($vids) . "\n";

    if (property_is_villa($p)) {
        foreach (property_lots_for_display($id, $p) as $i => $lot) {
            echo '  lot ' . property_lot_label($lot, $i)
                . ': photos=' . count($lot['photos'])
                . ' vids=' . count($lot['videos'])
                . ' maps=' . count($lot['sitemaps']) . "\n";
        }
    }
}
