<?php
require 'c:/xampp/htdocs/uz5/config/config.php';
require 'c:/xampp/htdocs/uz5/includes/properties.php';
$p = property_find(35);
$dir = property_card_gallery_dir($p);
echo "dir: $dir\n";
echo "exists: ".(is_dir($dir)?'yes':'no')."\n";
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) echo "  $f\n";
    foreach (['option1','option2','lot1','lot2'] as $sub) {
        $sd = $dir.DIRECTORY_SEPARATOR.$sub;
        if (is_dir($sd)) {
            echo "subdir $sub:\n";
            foreach (scandir($sd) as $f) if ($f[0]!=='.') echo "    $f\n";
        }
    }
}
echo "photos:\n";
foreach (array_slice(property_detail_photo_images($p),0,5) as $u) echo "  $u\n";
