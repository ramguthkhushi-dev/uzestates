<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/enquiries.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/contact-page.php';

start_session();

try {
    $page = contact_page_content(settings_contact_page());
} catch (Throwable $e) {
    $page = contact_page_content([]);
}

$contactHeroUrl = contact_page_asset_url($page['hero']['image']);

$contactInfo  = settings_contact_public();
$phoneDisplay = $contactInfo['phone_display'];
$phoneTel     = $contactInfo['phone_tel'];
$whatsappUrl  = $contactInfo['whatsapp_url'];
$email        = $contactInfo['email'];

$formError   = null;
$formSuccess = flash_get('contact_success');
$formOld     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formOld = enquiry_form_old_from_request($_POST);

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $formError = 'Invalid session. Please try again.';
    } else {
        try {
            $_POST['enquiry_type'] = 'General';
            $result = enquiry_save($_POST);
            if ($result['success']) {
                flash_set('contact_success', 'Thank you. Your enquiry has been sent. UZ Estates will contact you directly.');
                header('Location: ' . BASE_URL . '/contact.php#enquiry');
                exit;
            }
            $formError = $result['error'];
        } catch (Throwable $e) {
            $formError = 'Your enquiry could not be sent right now. Please try WhatsApp or phone instead.';
        }
    }
}

$contactRows = [
    [
        'label' => 'Phone',
        'value' => $phoneDisplay,
        'href'  => 'tel:' . $phoneTel,
        'phone' => true,
        'icon'  => 'phone',
    ],
    [
        'label' => 'WhatsApp',
        'value' => $phoneDisplay,
        'href'  => $whatsappUrl,
        'phone' => true,
        'icon'  => 'whatsapp',
    ],
    [
        'label' => 'Email',
        'value' => $email,
        'href'  => 'mailto:' . $email,
        'icon'  => 'email',
    ],
    [
        'label' => 'Business hours',
        'value' => $contactInfo['business_hours'],
        'icon'  => 'clock',
    ],
];
?>
<!DOCTYPE html>
<html lang="en" data-base-url="<?php echo e(BASE_URL); ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contact | UZ Estates</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/header-nav.css" />
  <link rel="stylesheet" href="css/contact.css?v=<?php echo (int) filemtime(__DIR__ . '/css/contact.css'); ?>" />
  <link rel="stylesheet" href="css/page-animate.css?v=<?php echo (int) filemtime(__DIR__ . '/css/page-animate.css'); ?>" />
  <?php enquiry_phone_stylesheet_tag(); ?>
  <style>.contact-hero-media { --contact-hero-photo: url('<?php echo e($contactHeroUrl); ?>'); }</style>
</head>
<body class="contact-body">

<?php require __DIR__ . '/includes/site-header.php'; ?>

<main class="contact-page">

  <section class="contact-hero" aria-labelledby="contact-hero-heading">
    <div class="contact-hero-copy">
      <p class="contact-kicker"><?php echo e($page['hero']['kicker']); ?></p>
      <h1 id="contact-hero-heading"><?php echo e($page['hero']['title']); ?></h1>
      <div class="hero-title-divider hero-title-divider--on-light" aria-hidden="true"></div>
    </div>
    <div class="contact-hero-media" role="img" aria-label="<?php echo e($page['hero']['image_alt']); ?>"></div>
  </section>

  <section class="contact-main" aria-label="Contact details and enquiry form">
    <div class="contact-main-shell">

      <div class="contact-info-block">
        <h2 class="contact-info-heading" data-reveal="fade-up"><?php echo e($page['info']['heading']); ?></h2>
        <ul class="contact-info-rows" data-reveal-group>
          <?php foreach ($contactRows as $row): ?>
            <li class="contact-info-row" data-reveal="fade-up">
              <span class="contact-info-icon" aria-hidden="true">
                <?php if ($row['icon'] === 'phone'): ?>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.5 2.6a2 2 0 0 1-.5 2.1L8 9a16 16 0 0 0 6 6l.6-.6a2 2 0 0 1 2.1-.5c.9.2 1.7.4 2.6.5A2 2 0 0 1 22 16.9z"/></svg>
                <?php elseif ($row['icon'] === 'whatsapp'): ?>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 11.5a8.4 8.4 0 0 1-12.9 7.1L3 21l2.6-5A8.4 8.4 0 1 1 21 11.5z"/></svg>
                <?php elseif ($row['icon'] === 'email'): ?>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                <?php elseif ($row['icon'] === 'clock'): ?>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <?php else: ?>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <?php endif; ?>
              </span>
              <div class="contact-info-text">
                <span class="contact-info-label"><?php echo e($row['label']); ?></span>
                <?php if (!empty($row['href'])): ?>
                  <a href="<?php echo e($row['href']); ?>"
                     class="contact-info-value<?php echo !empty($row['phone']) ? ' contact-info-value-phone' : ''; ?>"
                     <?php echo $row['icon'] === 'whatsapp' ? 'target="_blank" rel="noopener"' : ''; ?>>
                    <?php echo e($row['value']); ?>
                  </a>
                <?php else: ?>
                  <span class="contact-info-value"><?php echo e($row['value']); ?></span>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <section class="contact-form-card" id="enquiry" aria-labelledby="enquiry-heading" data-reveal="scale-up" data-reveal-delay="1">
        <div data-reveal-group>
          <h2 id="enquiry-heading" data-reveal="fade-up"><?php echo e($page['form']['heading']); ?></h2>
          <p class="contact-form-lead" data-reveal="fade-up"><?php echo e($page['form']['lead']); ?></p>
        </div>

        <?php if ($formSuccess): ?>
          <div class="contact-notice contact-notice-ok" role="status"><?php echo e($formSuccess); ?></div>
        <?php endif; ?>

        <?php if ($formError): ?>
          <div class="contact-notice contact-notice-err" role="alert"><?php echo e($formError); ?></div>
        <?php endif; ?>

        <form class="contact-enquiry-form" method="post" action="contact.php#enquiry" novalidate data-reveal="fade-up" data-reveal-delay="2" data-enquiry-api="<?php echo e(BASE_URL); ?>/api/enquiry.php">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>" />
          <input type="hidden" name="enquiry_type" value="General" />
          <?php enquiry_honeypot_field(); ?>
          <?php enquiry_recaptcha_fields(); ?>

          <div class="contact-form-pair">
            <label class="contact-form-field">
              <span>Full Name</span>
              <input type="text" name="name" required autocomplete="name" maxlength="150" value="<?php echo e($formOld['name'] ?? ''); ?>" />
            </label>
            <label class="contact-form-field">
              <span>Mobile number</span>
              <?php enquiry_phone_field($formOld['phone'] ?? '', 'Mobile number'); ?>
            </label>
          </div>

          <div class="contact-form-pair">
            <label class="contact-form-field">
              <span>Email Address</span>
              <input type="email" name="email" required autocomplete="email" maxlength="150" value="<?php echo e($formOld['email'] ?? ''); ?>" />
            </label>
            <label class="contact-form-field">
              <span>Subject</span>
              <input type="text" name="interested_property" placeholder="Optional" maxlength="255" value="<?php echo e($formOld['interested_property'] ?? ''); ?>" />
            </label>
          </div>

          <label class="contact-form-field contact-form-field-full">
            <span>Message</span>
            <textarea name="message" rows="4" required maxlength="5000"><?php echo e($formOld['message'] ?? ''); ?></textarea>
          </label>

          <button type="submit" class="contact-btn-send">Send Enquiry</button>
        </form>
      </section>

    </div>
  </section>

  <section class="contact-quick-chat" aria-label="Quick chat">
    <div class="contact-quick-chat-inner" data-reveal="fade-up">
      <span class="contact-quick-chat-icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </span>
      <div class="contact-quick-chat-copy">
        <p class="contact-quick-chat-title"><?php echo e($page['quick_chat']['title']); ?></p>
        <p class="contact-quick-chat-text"><?php echo e($page['quick_chat']['text']); ?></p>
      </div>
      <a href="tel:<?php echo e($phoneTel); ?>" class="contact-quick-chat-phone"><?php echo e($phoneDisplay); ?></a>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/site-footer.php'; ?>

<script src="js/script.js"></script>
<script src="js/page-animate.js?v=<?php echo (int) filemtime(__DIR__ . '/js/page-animate.js'); ?>"></script>
<?php enquiry_phone_script_tags(); ?>
<?php require_once __DIR__ . '/includes/recaptcha.php'; recaptcha_script_tag(); ?>
<script src="js/enquiry-form.js?v=<?php echo (int) filemtime(__DIR__ . '/js/enquiry-form.js'); ?>"></script>
</body>
</html>
