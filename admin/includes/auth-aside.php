<?php

declare(strict_types=1);

/** @var string $authAsideTagline */
?>
<aside class="admin-auth-aside" aria-hidden="true">
  <div class="admin-auth-aside-inner">
    <div class="admin-auth-brand-lockup">
      <img
        src="<?php echo e(BASE_URL); ?>/images/logo.png"
        alt=""
        class="admin-auth-logo"
        width="120"
        height="120"
      />
      <div class="admin-auth-aside-brand">
        <h1><?php echo e(APP_NAME); ?></h1>
        <p>Admin</p>
      </div>
    </div>
    <p class="admin-auth-aside-tagline"><?php echo e($authAsideTagline); ?></p>
  </div>
</aside>
