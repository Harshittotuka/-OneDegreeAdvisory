# One Degree Advisory Laravel Website

This is the Laravel conversion of the One Degree Advisory static website. Shared layout, navigation, footer, destination data, assets, and routing now live in reusable Laravel structure.

## Structure

- `app/Http/Controllers/PageController.php` renders top-level and country pages.
- `config/site.php` stores shared brand, contact, and destination navigation data.
- `resources/views/layouts/app.blade.php` contains the shared document shell.
- `resources/views/partials/` contains reusable header, footer, and EU flag partials.
- `resources/views/pages/` contains home, insights, and contact pages.
- `resources/views/countries/` contains country guide pages.
- `public/styles.css`, `public/script.js`, and `public/assets/heroes/` contain the migrated static assets.

## Run Locally

```bash
composer install
php artisan serve
```

The app supports clean URLs like `/contact` and legacy URLs like `/contact.html` and `/countries/study-in-uk.html`.

## CMS and CRM backups

Successful CMS and CRM data changes create one rolling restore point after the
HTTP response is sent. Each restore point contains a consistent SQLite copy or
MySQL SQL dump, the top-level CMS JSON/XLSX files, uploaded CMS assets, and a
checksummed manifest. Multiple writes made by one user action are coalesced into
one restore point. Only the newest five are retained by default.

The feature is opt-in. Keep `CMS_CRM_BACKUP_ENABLED=false` (or omit it) on local
and UAT. Set it to `true` only in the production VPS environment.

```bash
# Verify that the VPS can create a restore point (and that mysqldump is found).
php artisan backup:run --reason=deployment-check

# List retained restore points.
php artisan backup:list
```

Backups default to `storage/app/backups/cms-crm`. For real server-loss
protection, set `CMS_CRM_BACKUP_PATH` to a mounted/off-server backup volume.
Set `CMS_CRM_MYSQLDUMP_PATH=/usr/bin/mysqldump` if the executable is not on the
PHP-FPM process PATH. Backup failures are written to the Laravel log and never
undo a CMS/CRM save that already succeeded.

## Verify

```bash
php artisan test
```

## Production Mail

The contact and careers forms send email directly through SMTP. No queue worker
is required.

Set these on the server for the two Google Workspace mailboxes:

```bash
MAIL_MAILER=contact_form
MAIL_FROM_ADDRESS="Admissions@onedegreeadvisory.com"
MAIL_FROM_NAME="One Degree Advisory"
MAIL_TIMEOUT=10
MAIL_EHLO_DOMAIN=onedegreeadvisory.com

CONTACT_FORM_MAILER=contact_form
CONTACT_MAIL_HOST=smtp.gmail.com
CONTACT_MAIL_PORT=587
CONTACT_MAIL_SCHEME=smtp
CONTACT_MAIL_USERNAME="Admissions@onedegreeadvisory.com"
CONTACT_MAIL_PASSWORD="PASTE_ADMISSION_GOOGLE_APP_PASSWORD_HERE"
CONTACT_FORM_TO="Admissions@onedegreeadvisory.com"
CONTACT_FORM_FROM="Admissions@onedegreeadvisory.com"
CONTACT_FORM_FROM_NAME="One Degree Advisory"

CAREERS_FORM_MAILER=careers_form
CAREERS_MAIL_HOST=smtp.gmail.com
CAREERS_MAIL_PORT=587
CAREERS_MAIL_SCHEME=smtp
CAREERS_MAIL_USERNAME="Smita@onedegreeadvisory.com"
CAREERS_MAIL_PASSWORD="PASTE_SMITA_GOOGLE_APP_PASSWORD_HERE"
CAREERS_FORM_TO="Smita@onedegreeadvisory.com"
CAREERS_FORM_FROM="Smita@onedegreeadvisory.com"
CAREERS_FORM_FROM_NAME="One Degree Advisory Careers"

# CRM login OTP + CRM notifications only — its own mailbox, so changing it
# never affects the contact/careers forms. Omit the block and the CRM falls
# back to the contact_form mailer.
CRM_MAILER=crm
CRM_MAIL_HOST=smtp.gmail.com
CRM_MAIL_PORT=587
CRM_MAIL_SCHEME=smtp
CRM_MAIL_USERNAME="onedegreeadvisory1@gmail.com"
CRM_MAIL_PASSWORD="PASTE_CRM_GOOGLE_APP_PASSWORD_HERE"
CRM_MAIL_FROM="onedegreeadvisory1@gmail.com"
CRM_MAIL_FROM_NAME="One Degree CRM"
```

After changing mail env values:

```bash
php artisan config:clear
php artisan mail:doctor
php artisan mail:test you@example.com --mailer=contact_form
php artisan mail:test you@example.com --mailer=careers_form
php artisan config:cache
```
