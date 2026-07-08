<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$target = admin_url('reset-password.php');
$query  = $_SERVER['QUERY_STRING'] ?? '';

if ($query !== '') {
    $target .= '?' . $query;
}

header('Location: ' . $target, true, 301);
exit;
