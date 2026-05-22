# OneDegreeAdvisory Laravel Website

This is the Laravel conversion of the OneDegreeAdvisory static website. Shared layout, navigation, footer, destination data, assets, and routing now live in reusable Laravel structure.

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
