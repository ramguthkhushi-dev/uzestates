-- UZ Estates Database Schema
-- Run via database/install.php or import manually in phpMyAdmin

CREATE DATABASE IF NOT EXISTS uz_estates
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE uz_estates;

-- ---------------------------------------------------------------------------
-- Admins
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(100) NOT NULL DEFAULT '',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Properties
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS properties (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title               VARCHAR(255)   NOT NULL,
  slug                VARCHAR(255)   NULL,
  property_type       VARCHAR(100)   NULL,
  listing_purpose     VARCHAR(100)   NULL,
  status              VARCHAR(100)   NULL,
  price               VARCHAR(100)   NULL,
  price_numeric       INT UNSIGNED   NULL,
  size                VARCHAR(100)   NULL,
  location_name       VARCHAR(255)   NULL,
  short_description   TEXT           NULL,
  full_description    TEXT           NULL,
  is_featured         TINYINT(1)     NOT NULL DEFAULT 0,
  show_on_home        TINYINT(1)     NOT NULL DEFAULT 0,
  is_visible          TINYINT(1)     NOT NULL DEFAULT 1,
  display_order       INT            NOT NULL DEFAULT 0,
  google_maps_link    TEXT           NULL,
  google_maps_embed   TEXT           NULL,
  latitude            VARCHAR(100)   NULL,
  longitude           VARCHAR(100)   NULL,
  created_at          TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP      NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_featured (is_featured),
  INDEX idx_show_home (show_on_home),
  INDEX idx_display (display_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_features (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id     INT UNSIGNED NOT NULL,
  feature_label   VARCHAR(255) NOT NULL,
  feature_value   TEXT         NULL,
  display_order   INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  INDEX idx_property (property_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_lots (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id     INT UNSIGNED NOT NULL,
  label           VARCHAR(100) NULL,
  size            VARCHAR(100) NOT NULL,
  price           VARCHAR(100) NOT NULL,
  description     TEXT         NULL,
  bedrooms        VARCHAR(20)  NULL,
  bathrooms       VARCHAR(20)  NULL,
  villa_size      VARCHAR(100) NULL,
  status          VARCHAR(50)  NULL DEFAULT 'Available',
  display_order   INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  INDEX idx_property (property_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS property_media (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id     INT UNSIGNED NOT NULL,
  lot_id          INT UNSIGNED NULL,
  media_type      VARCHAR(50)  NOT NULL,
  media_category  VARCHAR(100) NULL,
  file_path       TEXT         NULL,
  external_url    TEXT         NULL,
  title           VARCHAR(255) NULL,
  alt_text        VARCHAR(255) NULL,
  is_main         TINYINT(1)   NOT NULL DEFAULT 0,
  display_order   INT          NOT NULL DEFAULT 0,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  FOREIGN KEY (lot_id) REFERENCES property_lots(id) ON DELETE CASCADE,
  INDEX idx_property (property_id),
  INDEX idx_lot (lot_id),
  INDEX idx_type (media_type)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Gallery (fixed slot layout — 11 slots, content editable in admin)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gallery_slots (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slot_number     INT UNSIGNED NOT NULL,
  slot_name       VARCHAR(100) NOT NULL,
  slot_size       VARCHAR(32)  NOT NULL,
  media_type      VARCHAR(20)  NOT NULL DEFAULT 'image',
  title           VARCHAR(255) NULL,
  description     TEXT         NULL,
  file_path       VARCHAR(500) NULL,
  thumbnail_path  VARCHAR(500) NULL,
  external_url    TEXT         NULL,
  button_text     VARCHAR(255) NULL,
  button_link     VARCHAR(500) NULL,
  icon            VARCHAR(50)  NULL,
  card_style      VARCHAR(20)  NOT NULL DEFAULT 'light',
  is_visible      TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at      DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slot_number (slot_number),
  INDEX idx_visible (is_visible)
) ENGINE=InnoDB;

INSERT INTO gallery_slots (
  slot_number, slot_name, slot_size, media_type, title, description,
  file_path, thumbnail_path, button_text, button_link, icon, card_style, is_visible
) VALUES
  (1,  'Wide banner top',        'large_horizontal', 'video', NULL, NULL, 'gallery/up_6a411acc1388d4.79025401.mp4', 'gallery/up_6a411acc144ec1.34528305.png', NULL, NULL, NULL, 'light', 1),
  (2,  'Square top right',       'small',            'image', NULL, NULL, 'gallery/up_6a412bfbc57dd2.42470603.png', NULL, NULL, NULL, NULL, 'light', 1),
  (3,  'Modern Villas card',     'text_card',        'text',  'Modern Villas', 'Contemporary living spaces designed for comfort.', NULL, NULL, 'View Collection', 'properties.php?category=villas', 'home', 'light', 1),
  (4,  'Square mid left',        'small',            'image', NULL, NULL, 'gallery/up_6a412d688ac632.65439732.png', NULL, NULL, NULL, NULL, 'light', 1),
  (5,  'Square mid centre',      'small',            'image', NULL, NULL, 'gallery/up_6a4125a2db50d4.10008345.png', NULL, NULL, NULL, NULL, 'light', 1),
  (6,  'Wide banner mid',        'large_horizontal', 'image', NULL, NULL, 'gallery/up_6a4128a9670e93.45907298.png', NULL, NULL, NULL, NULL, 'light', 1),
  (7,  'Grand Baie card',        'text_card',        'text',  'Grand Baie', 'Villas and plots in the heart of Mauritius'' north coast.', NULL, NULL, 'View Location', 'properties.php?keyword=Grand+Baie', 'pin', 'light', 1),
  (8,  'Tall feature',           'large_vertical',   'video', NULL, NULL, 'gallery/up_6a41209ec748b5.29671819.mp4', 'gallery/up_6a41209ec818f2.97801736.png', NULL, NULL, NULL, 'light', 1),
  (9,  'Wide banner lower',      'large_horizontal', 'image', NULL, NULL, 'gallery/up_6a41234d8e4848.08619337.png', NULL, NULL, NULL, NULL, 'light', 1),
  (10, 'Residential Plots card', 'text_card',        'text',  'Residential Plots', 'Land and villa listings across prime north-coast locations.', NULL, NULL, 'Explore Now', 'properties.php?category=plots', 'layers', 'dark', 1),
  (11, 'Square bottom',          'small',            'image', NULL, NULL, 'gallery/up_6a41293f033378.77223372.png', NULL, NULL, NULL, NULL, 'light', 1)
ON DUPLICATE KEY UPDATE slot_name = VALUES(slot_name), slot_size = VALUES(slot_size);

CREATE TABLE IF NOT EXISTS gallery_page_settings (
  id            INT UNSIGNED NOT NULL PRIMARY KEY,
  hero_kicker   VARCHAR(255) NULL,
  hero_title    VARCHAR(255) NULL,
  hero_lead     TEXT         NULL,
  hero_btn_text VARCHAR(255) NULL,
  hero_btn_url  VARCHAR(500) NULL,
  hero_image    TEXT         NULL,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS properties_page_settings (
  id              INT UNSIGNED NOT NULL PRIMARY KEY,
  hero_kicker     VARCHAR(255) NULL,
  hero_title      VARCHAR(255) NULL,
  hero_lead       TEXT         NULL,
  hero_image      TEXT         NULL,
  hero_image_alt  VARCHAR(255) NULL,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_page_settings (
  id               INT UNSIGNED NOT NULL PRIMARY KEY,
  hero_kicker      VARCHAR(255) NULL,
  hero_title       VARCHAR(255) NULL,
  hero_image       TEXT         NULL,
  hero_image_alt   VARCHAR(255) NULL,
  info_heading     VARCHAR(255) NULL,
  form_heading     VARCHAR(255) NULL,
  form_lead        TEXT         NULL,
  quick_chat_title VARCHAR(255) NULL,
  quick_chat_text  VARCHAR(255) NULL,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Site settings (singleton rows)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS homepage_settings (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hero_images           JSON         NULL COMMENT 'Legacy — unused',
  featured_property_ids JSON         NULL COMMENT 'Legacy — unused',
  gallery_images        JSON         NULL COMMENT 'Legacy — unused',
  visual_section_image  VARCHAR(500) NULL,
  hero_kicker           VARCHAR(255) NULL,
  hero_title            VARCHAR(255) NULL,
  hero_subtitle         TEXT         NULL,
  hero_image            TEXT         NULL,
  cta_text              VARCHAR(255) NULL,
  hero_cta_url          VARCHAR(500) NULL,
  hero_secondary_text   VARCHAR(255) NULL,
  hero_secondary_url    VARCHAR(500) NULL,
  match_json            JSON         NULL,
  lifestyle_json        JSON         NULL,
  contact_json          JSON         NULL,
  visual_kicker         VARCHAR(255) NULL,
  visual_title          VARCHAR(255) NULL,
  visual_subtitle       TEXT         NULL,
  updated_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_settings (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  phone               VARCHAR(30)  NULL,
  whatsapp            VARCHAR(30)  NULL,
  email               VARCHAR(150) NULL,
  business_hours      VARCHAR(255) NULL,
  facebook_link       TEXT         NULL,
  tiktok_link         TEXT         NULL,
  instagram_link      TEXT         NULL,
  office_address      TEXT         NULL,
  google_maps_link    TEXT         NULL,
  google_maps_embed   TEXT         NULL,
  updated_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS legal_settings (
  id            TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  privacy_title VARCHAR(255) NOT NULL DEFAULT 'Privacy Policy',
  privacy_body  TEXT         NULL,
  terms_title   VARCHAR(255) NOT NULL DEFAULT 'Terms & Conditions',
  terms_body    TEXT         NULL,
  updated_at    DATETIME     NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS about_settings (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  about_text         TEXT         NULL,
  mission            TEXT         NULL,
  vision             TEXT         NULL,
  founder_image      VARCHAR(500) NULL,
  office_images      JSON         NULL COMMENT 'Array of image paths',
  hero_kicker        VARCHAR(255) NULL,
  hero_title         VARCHAR(255) NULL,
  hero_title_line2   VARCHAR(255) NULL,
  hero_text          TEXT         NULL,
  hero_btn_text      VARCHAR(255) NULL,
  hero_btn_url       VARCHAR(500) NULL,
  hero_image         TEXT         NULL,
  hero_image_alt     VARCHAR(255) NULL,
  approach_image     TEXT         NULL,
  approach_image_alt VARCHAR(255) NULL,
  story_json         JSON         NULL,
  values_json        JSON         NULL,
  approach_json      JSON         NULL,
  process_json       JSON         NULL,
  faq_json           JSON         NULL,
  cta_json           JSON         NULL,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Clients (public sign-up accounts)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clients (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name    VARCHAR(50)  NOT NULL,
  last_name     VARCHAR(50)  NOT NULL,
  phone         VARCHAR(30)  NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Password reset tokens
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_type  ENUM('client','admin') NOT NULL,
  email      VARCHAR(150) NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME     NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Remember-me tokens
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS remember_tokens (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_type  ENUM('client','admin') NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME     NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_type, user_id),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enquiries (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150) NOT NULL,
  email          VARCHAR(150) NULL,
  phone          VARCHAR(100) NULL,
  message        TEXT         NOT NULL,
  enquiry_type   VARCHAR(100) NOT NULL DEFAULT 'General',
  property_id    INT UNSIGNED NULL,
  property_title VARCHAR(255) NULL,
  status         VARCHAR(100) NOT NULL DEFAULT 'New',
  admin_note     TEXT         NULL,
  ip_address     VARCHAR(45)  NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Default admin (password: admin123 — change after first login)
-- ---------------------------------------------------------------------------
INSERT INTO admins (username, email, password_hash, full_name)
VALUES (
  'admin',
  'admin@uzestates.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Administrator'
) ON DUPLICATE KEY UPDATE username = username;

-- Default settings rows
INSERT INTO homepage_settings (id, hero_kicker, hero_title, hero_subtitle, visual_kicker, visual_title, visual_subtitle)
VALUES (
  1,
  'Mauritius Real Estate',
  'Find your next property in Grand Baie.',
  'Selected plots and villas with trusted local guidance.',
  'Move Forward',
  'Clear information. Serious opportunities.',
  'Simple details, professional support and direct WhatsApp enquiries.'
) ON DUPLICATE KEY UPDATE id = id;

INSERT INTO gallery_page_settings (id, hero_kicker, hero_title, hero_lead, hero_btn_text, hero_btn_url, hero_image)
VALUES (
  1,
  'Gallery',
  'Properties in focus.',
  'A curated collection of our properties, locations and the lifestyle that makes Mauritius exceptional.',
  'Explore Properties',
  'properties.php',
  'images/gallery_page/hero.png'
) ON DUPLICATE KEY UPDATE id = id;

INSERT INTO properties_page_settings (id, hero_kicker, hero_title, hero_lead, hero_image, hero_image_alt)
VALUES (
  1,
  'Browse Listings',
  'Properties in Mauritius.',
  'Plots, villas and investment opportunities across Grand Baie and beyond.',
  'images/properties_page/hero.png',
  'Properties in Mauritius'
) ON DUPLICATE KEY UPDATE id = id;

INSERT INTO contact_page_settings (id, hero_kicker, hero_title, hero_image, hero_image_alt, info_heading, form_heading, form_lead, quick_chat_title, quick_chat_text)
VALUES (
  1,
  'Contact Us',
  'We''re here to help.',
  'images/contact_page/hero.png',
  'Modern interior living space',
  'Contact information',
  'Send an enquiry',
  'Send an enquiry and UZ Estates will contact you directly by phone, WhatsApp or email.',
  'Prefer a quick chat?',
  'Call or WhatsApp us directly.'
) ON DUPLICATE KEY UPDATE id = id;

INSERT INTO contact_settings (id, phone, whatsapp, email, office_address)
VALUES (
  1,
  '58154042',
  '23058154042',
  'Sheikhuzayr8@gmail.com',
  '3rd Floor, Decodesign Building, Chemin 20 Pieds, Grand Baie'
) ON DUPLICATE KEY UPDATE id = id;

INSERT INTO about_settings (id, about_text)
VALUES (
  1,
  'UZ Estates helps clients buy, sell, rent and invest in properties across Mauritius with honest advice and personalised support.'
) ON DUPLICATE KEY UPDATE id = id;

INSERT INTO legal_settings (id, privacy_title, privacy_body, terms_title, terms_body)
VALUES (
  1,
  'Privacy Policy',
  'UZ Estates respects your privacy. We collect information you submit through enquiry forms (name, contact details, and message) to respond to your request. We do not sell your data to third parties.\n\nFor questions about your data, contact us using the details on our Contact page.',
  'Terms & Conditions',
  'Information on this website is provided for general guidance about properties in Mauritius. Listings, prices, and availability may change without notice. Enquiries through this site do not constitute a contract until confirmed directly with UZ Estates.'
) ON DUPLICATE KEY UPDATE id = id;
