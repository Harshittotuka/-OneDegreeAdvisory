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
