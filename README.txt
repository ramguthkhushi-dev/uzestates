UZ Estates - Real Estate Platform

Requirements
- XAMPP with Apache, MySQL, and PHP 8.2+
- Put this folder inside xampp/htdocs

First-time setup
1. Start Apache and MySQL in XAMPP.
2. Open the installer in your browser:
   http://localhost/uz5/database/install.php
3. Log in to the admin panel:
   http://localhost/uz5/admin/login.php

Default admin login
- Username: admin
- Email: admin@uzestates.com
- Password: admin123

Folder name / URL
- By default the app detects the folder name, so a folder named uz5 runs at:
  http://localhost/uz5/
- If you rename the folder, use that folder name in the URL.
- Advanced override: set APP_BASE_URL before running PHP if you need a custom base path.

Database
- Default database name: uz_estates
- Connection settings: config/config.php
- Installer creates/updates the schema, default admin, fixed gallery slots, and bundled gallery images.
- Manual SQL import is also supported with database/schema.sql, but the installer is recommended.

IMPORTANT — schema.sql and your data
- schema.sql creates EMPTY tables plus default admin/settings only.
- It does NOT contain your property listings, uploads, or enquiries.
- Do NOT import schema.sql on a database that already has live content unless you want to wipe it.
- phpMyAdmin may offer "Drop tables" before import — leave that UNCHECKED if you only want to add missing tables.
- To restore bundled sample listings after a fresh import, run:
  php database/seed_uz_listings.php
  (or open database/seed_uz_listings.php in the browser once)

Uploads and sessions
- Uploaded files are stored in uploads/
- PHP login sessions are stored in storage/sessions/
- Keep uploads/ and storage/ with the folder when sending the project to someone else.

Admin modules
- Properties: add, edit, delete, and media management
- Gallery: fixed slot editor and gallery page hero
- Pages: home, about, contact, and properties page content
- Enquiries: view, update status, add admin notes, delete

Enquiry notifications
- New enquiries trigger an email to the site contact address (Admin > Contact).
- Override recipient: set ENQUIRY_NOTIFY_EMAIL in the server environment.
- Disable notifications: set ENQUIRY_NOTIFICATIONS=0
- Uses PHP mail() by default. On production, configure your host SMTP or mail relay.

Spam protection
- Hidden honeypot field on enquiry forms (bots that fill it are silently ignored).
- Rate limit: 5 enquiries per IP per hour (override with ENQUIRY_RATE_LIMIT).

Production checklist
1. Set APP_BASE_URL=https://yourdomain.com in the server environment.
2. Use a dedicated MySQL user with a strong password in config/config.php.
3. Change the default admin password immediately after install.
4. Enable HTTPS:
   - Set FORCE_HTTPS=1 in the environment, or
   - Copy .htaccess.example to .htaccess on Apache with SSL.
5. Set MAIL_FROM_ADDRESS=noreply@yourdomain.com for outgoing mail.
6. Run scheduled backups: php scripts/backup.php
   (saves to storage/backups/ — keep off public web; .htaccess blocks access).
7. Remove or protect database/install.php after go-live.

Verification scripts (run from project root)
- php scripts/verify-admin-flows.php
- php scripts/verify-admin-ui.php
- php scripts/verify-enquiry-api.php
- php scripts/verify-mobile.php

Optional features (env / setup)
- SMTP: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_ENCRYPTION (tls|ssl)
- reCAPTCHA: RECAPTCHA_SITE_KEY, RECAPTCHA_SECRET_KEY (Google reCAPTCHA v2 checkbox)
- Legal pages: run php database/migrate_optional.php then edit Admin > Legal
- Admin account: Admin sidebar profile block > My account (change email/password)
- Enquiry filters in admin update live without page reload
- Large admin uploads show a progress bar automatically

Before using legal pages or reCAPTCHA, run:
  php database/migrate_optional.php
