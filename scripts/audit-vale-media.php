<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/properties.php';

$id = 1;
$p = property_find($id);
echo "Property: {$p['title']}\n\n";
echo "DB property_media:\n";
foreach (property_media($id) as $m) {
    echo "  #{$m['id']} {$m['media_type']} / {$m['media_category']} -> {$m['file_path']}\n";
}
if (property_media($id) === []) {
    echo "  (none)\n";
}

echo "\nFolder photos:\n";
foreach (property_detail_photo_images($p) as $u) {
    echo "  $u\n";
}
echo "\nFolder sitemaps:\n";
foreach (property_detail_sitemap_images($p) as $u) {
    echo "  $u\n";
}
echo "\nFolder videos:\n";
foreach (property_detail_videos($p) as $v) {
    echo "  {$v['url']}\n";
}
echo "\nCard images:\n";
foreach (property_card_gallery_images($p) as $u) {
    echo "  $u\n";
}
