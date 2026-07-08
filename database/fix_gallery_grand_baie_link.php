<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$pdo->exec(
    "UPDATE gallery_slots SET button_link = REPLACE(button_link, '/properties.php', 'properties.php')
     WHERE button_link LIKE '/properties.php%'"
);

$rows = $pdo->query(
    'SELECT slot_number, title, button_text, button_link FROM gallery_slots WHERE slot_number IN (3, 7, 10) ORDER BY slot_number'
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo sprintf(
        "Slot %d (%s): %s → %s\n",
        $row['slot_number'],
        $row['title'],
        $row['button_text'],
        $row['button_link']
    );
}
