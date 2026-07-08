<?php

declare(strict_types=1);

require_once __DIR__ . '/settings.php';

$footerContact      = settings_contact_public();
$footerPhoneDisplay = $footerContact['phone_display'];
$footerPhoneTel     = $footerContact['phone_tel'];
$footerEmail        = $footerContact['email'];
$footerWhatsapp     = $footerContact['whatsapp'];
$footerFacebook     = $footerContact['facebook_link'] ?: (defined('FACEBOOK_URL') ? FACEBOOK_URL : '');

$footerUrl = static fn (string $path): string => e(BASE_URL . '/' . ltrim($path, '/'));
?>
<footer class="site-footer">
  <div class="site-footer-shell">
    <div class="site-footer-brand">
      <a href="<?php echo $footerUrl('home.php'); ?>" class="site-footer-logo-link">
        <img src="<?php echo e(BASE_URL); ?>/images/logo.png" alt="<?php echo e(APP_NAME); ?>" class="site-footer-logo-img" />
      </a>
      <p class="site-footer-text">Plots and villas across Mauritius.</p>
      <div class="site-footer-social" aria-label="Social links">
        <a href="<?php echo e($footerFacebook); ?>" class="site-social-link" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
        </a>
        <a href="<?php echo e(whatsapp_url($footerWhatsapp)); ?>" class="site-social-link" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.9 7.1L3 21l2.6-5A8.4 8.4 0 1 1 21 11.5z"/></svg>
        </a>
        <a href="mailto:<?php echo e($footerEmail); ?>" class="site-social-link" aria-label="Email">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
        </a>
      </div>
    </div>

    <div class="site-footer-col">
      <p class="site-footer-heading">Quick Links</p>
      <ul>
        <li><a href="<?php echo $footerUrl('home.php'); ?>">Home</a></li>
        <li><a href="<?php echo $footerUrl('properties.php'); ?>">Properties</a></li>
        <li><a href="<?php echo $footerUrl('gallery.php'); ?>">Gallery</a></li>
        <li><a href="<?php echo $footerUrl('about.php'); ?>">About</a></li>
        <li><a href="<?php echo $footerUrl('contact.php'); ?>">Contact</a></li>
      </ul>
    </div>

    <div class="site-footer-col">
      <p class="site-footer-heading">Property Types</p>
      <ul>
        <li><a href="<?php echo $footerUrl('properties.php?category=plots'); ?>">Plots</a></li>
        <li><a href="<?php echo $footerUrl('properties.php?category=villas'); ?>">Villas</a></li>
        <li><a href="<?php echo $footerUrl('properties.php?category=off-plan'); ?>">Off-Plan</a></li>
        <li><a href="<?php echo $footerUrl('properties.php'); ?>">All Listings</a></li>
      </ul>
    </div>

    <div class="site-footer-col">
      <p class="site-footer-heading">Contact</p>
      <ul class="site-footer-contact">
        <li><a href="tel:<?php echo e($footerPhoneTel); ?>"><?php echo e($footerPhoneDisplay); ?></a></li>
        <li><a href="mailto:<?php echo e($footerEmail); ?>" class="site-footer-email"><?php echo e($footerEmail); ?></a></li>
      </ul>
    </div>
  </div>

  <div class="site-footer-bar">
    <p>© <?php echo date('Y'); ?> <?php echo e(APP_NAME); ?></p>
    <p class="site-footer-legal">
      <a href="<?php echo $footerUrl('privacy.php'); ?>">Privacy Policy</a>
      <span aria-hidden="true">|</span>
      <a href="<?php echo $footerUrl('terms.php'); ?>">Terms &amp; Conditions</a>
    </p>
  </div>
</footer>
