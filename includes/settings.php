<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function settings_homepage(): array
{
    $row = db()->query('SELECT * FROM homepage_settings WHERE id = 1 LIMIT 1')->fetch();

    return $row ?: [];
}

function settings_save_homepage(array $data): void
{
    $existing = settings_homepage();

    $heroImage = array_key_exists('hero_image', $data)
        ? (trim((string) ($data['hero_image'] ?? '')) ?: null)
        : ($existing['hero_image'] ?? null);

    db()->prepare(
        'UPDATE homepage_settings SET
          hero_kicker = :hero_kicker,
          hero_title = :hero_title,
          hero_subtitle = :hero_subtitle,
          hero_image = :hero_image,
          cta_text = :cta_text,
          hero_cta_url = :hero_cta_url,
          hero_secondary_text = :hero_secondary_text,
          hero_secondary_url = :hero_secondary_url,
          match_json = :match_json,
          lifestyle_json = :lifestyle_json,
          contact_json = :contact_json,
          updated_at = NOW()
         WHERE id = 1'
    )->execute([
        'hero_kicker'         => trim($data['hero_kicker'] ?? '') ?: null,
        'hero_title'          => trim($data['hero_title'] ?? '') ?: null,
        'hero_subtitle'       => trim($data['hero_subtitle'] ?? '') ?: null,
        'hero_image'          => $heroImage,
        'cta_text'            => trim($data['cta_text'] ?? '') ?: null,
        'hero_cta_url'        => trim($data['hero_cta_url'] ?? '') ?: null,
        'hero_secondary_text' => trim($data['hero_secondary_text'] ?? '') ?: null,
        'hero_secondary_url'  => trim($data['hero_secondary_url'] ?? '') ?: null,
        'match_json'          => $data['match_json'] ?? null,
        'lifestyle_json'      => $data['lifestyle_json'] ?? null,
        'contact_json'        => $data['contact_json'] ?? null,
    ]);
}

function settings_about(): array
{
    $row = db()->query('SELECT * FROM about_settings WHERE id = 1 LIMIT 1')->fetch();

    return $row ?: [];
}

function settings_save_about(array $data): void
{
    db()->prepare(
        'UPDATE about_settings SET
          hero_kicker = :hero_kicker,
          hero_title = :hero_title,
          hero_title_line2 = :hero_title_line2,
          hero_text = :hero_text,
          hero_btn_text = :hero_btn_text,
          hero_btn_url = :hero_btn_url,
          hero_image = :hero_image,
          hero_image_alt = :hero_image_alt,
          approach_image = :approach_image,
          approach_image_alt = :approach_image_alt,
          story_json = :story_json,
          values_json = :values_json,
          approach_json = :approach_json,
          process_json = :process_json,
          faq_json = :faq_json,
          cta_json = :cta_json,
          updated_at = NOW()
         WHERE id = 1'
    )->execute([
        'hero_kicker'        => trim($data['hero_kicker'] ?? '') ?: null,
        'hero_title'         => trim($data['hero_title'] ?? '') ?: null,
        'hero_title_line2'   => trim($data['hero_title_line2'] ?? '') ?: null,
        'hero_text'          => trim($data['hero_text'] ?? '') ?: null,
        'hero_btn_text'      => trim($data['hero_btn_text'] ?? '') ?: null,
        'hero_btn_url'       => trim($data['hero_btn_url'] ?? '') ?: null,
        'hero_image'         => trim($data['hero_image'] ?? '') ?: null,
        'hero_image_alt'     => trim($data['hero_image_alt'] ?? '') ?: null,
        'approach_image'     => trim($data['approach_image'] ?? '') ?: null,
        'approach_image_alt' => trim($data['approach_image_alt'] ?? '') ?: null,
        'story_json'         => $data['story_json'] ?? null,
        'values_json'        => $data['values_json'] ?? null,
        'approach_json'      => $data['approach_json'] ?? null,
        'process_json'       => $data['process_json'] ?? null,
        'faq_json'           => $data['faq_json'] ?? null,
        'cta_json'           => $data['cta_json'] ?? null,
    ]);
}

function settings_contact(): array
{
    $row = db()->query('SELECT * FROM contact_settings WHERE id = 1 LIMIT 1')->fetch();

    return $row ?: [];
}

function settings_save_contact(array $data): void
{
    $phoneRaw    = trim($data['phone'] ?? '');
    $whatsappRaw = trim($data['whatsapp'] ?? '');

    $phoneLocal = $phoneRaw !== '' ? phone_tel_local($phoneRaw) : '';
    $whatsapp   = $whatsappRaw !== '' ? phone_whatsapp_digits($whatsappRaw) : '';

    if ($phoneLocal !== '' && $whatsapp === '') {
        $whatsapp = phone_whatsapp_digits($phoneLocal);
    }

    if ($whatsapp !== '' && $phoneLocal === '') {
        $phoneLocal = phone_tel_local($whatsapp);
    }

    db()->prepare(
        'INSERT INTO contact_settings (
          id, phone, whatsapp, email, business_hours, facebook_link, tiktok_link, instagram_link, updated_at
        ) VALUES (
          1, :phone, :whatsapp, :email, :business_hours, :facebook_link, :tiktok_link, :instagram_link, NOW()
        )
        ON DUPLICATE KEY UPDATE
          phone = VALUES(phone),
          whatsapp = VALUES(whatsapp),
          email = VALUES(email),
          business_hours = VALUES(business_hours),
          facebook_link = VALUES(facebook_link),
          tiktok_link = VALUES(tiktok_link),
          instagram_link = VALUES(instagram_link),
          updated_at = NOW()'
    )->execute([
        'phone'          => $phoneLocal !== '' ? $phoneLocal : null,
        'whatsapp'       => $whatsapp !== '' ? $whatsapp : null,
        'email'          => trim($data['email'] ?? '') ?: null,
        'business_hours' => trim($data['business_hours'] ?? '') ?: null,
        'facebook_link'  => trim($data['facebook_link'] ?? '') ?: null,
        'tiktok_link'    => trim($data['tiktok_link'] ?? '') ?: null,
        'instagram_link' => trim($data['instagram_link'] ?? '') ?: null,
    ]);
}

function settings_contact_public(): array
{
    $row = settings_contact();
    $phoneLocal = phone_tel_local((string) ($row['phone'] ?? ''));
    $whatsapp   = phone_whatsapp_digits((string) ($row['whatsapp'] ?? ''));

    if ($phoneLocal === '' && $whatsapp !== '') {
        $phoneLocal = phone_tel_local($whatsapp);
    }

    if ($whatsapp === '' && $phoneLocal !== '') {
        $whatsapp = phone_whatsapp_digits($phoneLocal);
    }

    return [
        'phone'          => $phoneLocal,
        'whatsapp'       => $whatsapp,
        'email'          => $row['email'] ?? 'Sheikhuzayr8@gmail.com',
        'business_hours' => $row['business_hours'] ?? 'Mon – Sun, 8:00 AM – 5:00 PM',
        'facebook_link'  => $row['facebook_link'] ?? (defined('FACEBOOK_URL') ? FACEBOOK_URL : ''),
        'tiktok_link'    => $row['tiktok_link'] ?? '',
        'instagram_link' => $row['instagram_link'] ?? '',
        'phone_display'  => $phoneLocal !== '' ? format_phone_display($phoneLocal) : '',
        'phone_tel'      => $phoneLocal !== '' ? phone_tel_href($phoneLocal) : '',
        'whatsapp_url'   => whatsapp_url($whatsapp),
    ];
}

function settings_contact_page(): array
{
    $row = db()->query('SELECT * FROM contact_page_settings WHERE id = 1 LIMIT 1')->fetch();

    return $row ?: [];
}

function settings_save_contact_page(array $data): void
{
    $existing = settings_contact_page();

    $heroImage = array_key_exists('hero_image', $data)
        ? (trim((string) ($data['hero_image'] ?? '')) ?: null)
        : ($existing['hero_image'] ?? null);

    db()->prepare(
        'INSERT INTO contact_page_settings (
          id, hero_kicker, hero_title, hero_image, hero_image_alt,
          info_heading, form_heading, form_lead, quick_chat_title, quick_chat_text, updated_at
        ) VALUES (
          1, :hero_kicker, :hero_title, :hero_image, :hero_image_alt,
          :info_heading, :form_heading, :form_lead, :quick_chat_title, :quick_chat_text, NOW()
        )
        ON DUPLICATE KEY UPDATE
          hero_kicker = VALUES(hero_kicker),
          hero_title = VALUES(hero_title),
          hero_image = VALUES(hero_image),
          hero_image_alt = VALUES(hero_image_alt),
          info_heading = VALUES(info_heading),
          form_heading = VALUES(form_heading),
          form_lead = VALUES(form_lead),
          quick_chat_title = VALUES(quick_chat_title),
          quick_chat_text = VALUES(quick_chat_text),
          updated_at = NOW()'
    )->execute([
        'hero_kicker'      => trim($data['hero_kicker'] ?? '') ?: null,
        'hero_title'       => trim($data['hero_title'] ?? '') ?: null,
        'hero_image'       => $heroImage,
        'hero_image_alt'   => trim($data['hero_image_alt'] ?? '') ?: null,
        'info_heading'     => trim($data['info_heading'] ?? '') ?: null,
        'form_heading'     => trim($data['form_heading'] ?? '') ?: null,
        'form_lead'        => trim($data['form_lead'] ?? '') ?: null,
        'quick_chat_title' => trim($data['quick_chat_title'] ?? '') ?: null,
        'quick_chat_text'  => trim($data['quick_chat_text'] ?? '') ?: null,
    ]);
}

function settings_gallery_page(): array
{
    $row = db()->query('SELECT * FROM gallery_page_settings WHERE id = 1 LIMIT 1')->fetch();

    return $row ?: [];
}

function settings_save_gallery_page(array $data): void
{
    $existing = settings_gallery_page();

    $heroImage = array_key_exists('hero_image', $data)
        ? (trim((string) ($data['hero_image'] ?? '')) ?: null)
        : ($existing['hero_image'] ?? null);

    db()->prepare(
        'INSERT INTO gallery_page_settings (
          id, hero_kicker, hero_title, hero_lead, hero_btn_text, hero_btn_url, hero_image, updated_at
        ) VALUES (
          1, :hero_kicker, :hero_title, :hero_lead, :hero_btn_text, :hero_btn_url, :hero_image, NOW()
        )
        ON DUPLICATE KEY UPDATE
          hero_kicker = VALUES(hero_kicker),
          hero_title = VALUES(hero_title),
          hero_lead = VALUES(hero_lead),
          hero_btn_text = VALUES(hero_btn_text),
          hero_btn_url = VALUES(hero_btn_url),
          hero_image = VALUES(hero_image),
          updated_at = NOW()'
    )->execute([
        'hero_kicker'   => trim($data['hero_kicker'] ?? '') ?: null,
        'hero_title'    => trim($data['hero_title'] ?? '') ?: null,
        'hero_lead'     => trim($data['hero_lead'] ?? '') ?: null,
        'hero_btn_text' => trim($data['hero_btn_text'] ?? '') ?: null,
        'hero_btn_url'  => trim($data['hero_btn_url'] ?? '') ?: null,
        'hero_image'    => $heroImage,
    ]);
}

function settings_properties_page(): array
{
    $row = db()->query('SELECT * FROM properties_page_settings WHERE id = 1 LIMIT 1')->fetch();

    return $row ?: [];
}

function settings_save_properties_page(array $data): void
{
    $existing = settings_properties_page();

    $heroImage = array_key_exists('hero_image', $data)
        ? (trim((string) ($data['hero_image'] ?? '')) ?: null)
        : ($existing['hero_image'] ?? null);

    db()->prepare(
        'INSERT INTO properties_page_settings (
          id, hero_kicker, hero_title, hero_lead, hero_image, hero_image_alt, updated_at
        ) VALUES (
          1, :hero_kicker, :hero_title, :hero_lead, :hero_image, :hero_image_alt, NOW()
        )
        ON DUPLICATE KEY UPDATE
          hero_kicker = VALUES(hero_kicker),
          hero_title = VALUES(hero_title),
          hero_lead = VALUES(hero_lead),
          hero_image = VALUES(hero_image),
          hero_image_alt = VALUES(hero_image_alt),
          updated_at = NOW()'
    )->execute([
        'hero_kicker'    => trim($data['hero_kicker'] ?? '') ?: null,
        'hero_title'     => trim($data['hero_title'] ?? '') ?: null,
        'hero_lead'      => trim($data['hero_lead'] ?? '') ?: null,
        'hero_image'     => $heroImage,
        'hero_image_alt' => trim($data['hero_image_alt'] ?? '') ?: null,
    ]);
}

function settings_legal_defaults(): array
{
    return [
        'privacy_title' => 'Privacy Policy',
        'privacy_body'  => '',
        'terms_title'   => 'Terms & Conditions',
        'terms_body'    => '',
    ];
}

function settings_legal(): array
{
    try {
        $row = db()->query('SELECT * FROM legal_settings WHERE id = 1 LIMIT 1')->fetch();
    } catch (Throwable $e) {
        return settings_legal_defaults();
    }

    return $row ?: settings_legal_defaults();
}

function settings_save_legal(array $data): void
{
    db()->prepare(
        'INSERT INTO legal_settings (id, privacy_title, privacy_body, terms_title, terms_body, updated_at)
         VALUES (1, :privacy_title, :privacy_body, :terms_title, :terms_body, NOW())
         ON DUPLICATE KEY UPDATE
           privacy_title = VALUES(privacy_title),
           privacy_body = VALUES(privacy_body),
           terms_title = VALUES(terms_title),
           terms_body = VALUES(terms_body),
           updated_at = NOW()'
    )->execute([
        'privacy_title' => trim($data['privacy_title'] ?? '') ?: 'Privacy Policy',
        'privacy_body'  => trim($data['privacy_body'] ?? ''),
        'terms_title'   => trim($data['terms_title'] ?? '') ?: 'Terms & Conditions',
        'terms_body'    => trim($data['terms_body'] ?? ''),
    ]);
}
