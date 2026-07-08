<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$adminExtraScripts = $adminExtraScripts ?? [];

function admin_render(string $title, string $navKey, callable $content): void
{
    $adminPageTitle = $title;
    $adminNavKey    = $navKey;

    require __DIR__ . '/../includes/admin-header.php';
    $content();
    require __DIR__ . '/../includes/admin-footer.php';
}

function admin_back_link(string $label, string $path): void
{
    ?>
    <p class="panel-text"><a href="<?php echo e(admin_url($path)); ?>">← <?php echo e($label); ?></a></p>
    <?php
}

function admin_view_site_link(string $publicPath, string $label = 'View on site'): void
{
    $url = str_starts_with($publicPath, 'http')
        ? $publicPath
        : rtrim(BASE_URL, '/') . '/' . ltrim($publicPath, '/');
    ?>
    <p class="panel-text admin-page-links"><a href="<?php echo e($url); ?>" target="_blank" rel="noopener"><?php echo e($label); ?> ↗</a></p>
    <?php
}
