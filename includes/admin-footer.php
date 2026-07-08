<?php

declare(strict_types=1);
?>
    </main>
  </div>
</div>

<div class="sidebar-overlay" aria-hidden="true"></div>

<script src="<?php echo e(BASE_URL); ?>/admin/assets/admin.js?v=<?php echo (int) @filemtime(__DIR__ . '/../admin/assets/admin.js'); ?>"></script>
<script src="<?php echo e(BASE_URL); ?>/admin/assets/upload-progress.js?v=<?php echo (int) @filemtime(__DIR__ . '/../admin/assets/upload-progress.js'); ?>"></script>
<?php if (!empty($adminExtraScripts) && is_array($adminExtraScripts)): ?>
  <?php foreach ($adminExtraScripts as $scriptSrc): ?>
    <script src="<?php echo e($scriptSrc); ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
