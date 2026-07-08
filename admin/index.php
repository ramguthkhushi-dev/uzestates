<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (admin_logged_in()) {
    header('Location: ' . admin_url('dashboard.php'));
    exit;
}

header('Location: ' . admin_url('login.php'));
exit;
