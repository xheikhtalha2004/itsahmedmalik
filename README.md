# Ahmed Malik Portfolio CMS

The portfolio keeps its existing `.html` browser URLs and UI, but Apache routes those URLs through a PHP 8.3 front controller. Published content is rendered from MariaDB into the existing DOM classes; the admin panel lives at `/admin/` and public forms post to `/api/`.

## Structure

- `index.php`, `.htaccess`, `views/pages/`, `views/partials/`, `src/content.php`: exact public routing and server-rendered content
- `admin/`, `src/admin.php`, `src/uploads.php`: protected CMS, inboxes, media processing, and previews
- `api/`, `src/submissions.php`, `src/security.php`, `src/mailer.php`: contact, meeting, newsletter, Turnstile, and email
- `migrations/`, `db/migrate.php`, `db/seed_existing.php`, `db/seeds/legacy/`: additive schema and immutable, idempotent legacy-content seed
- `media/cms/`: persistent generated WebP uploads; generated files are intentionally Git-ignored

## Local/SSH setup

Requirements are PHP 8.3, MariaDB/MySQL, Composer 2, and the PHP extensions listed in `composer.json`.

```sh
composer install
cp config.example.php config.php
php db/migrate.php
php db/seed_existing.php
```

Put only local credentials in `config.php`; it is Git-ignored. Production uses the private configuration path described in [docs/HOSTINGER_DEPLOYMENT.md](docs/HOSTINGER_DEPLOYMENT.md).

Useful checks:

```sh
composer validate --strict
composer check-platform-reqs
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
php -d zend.assertions=1 -d assert.exception=1 admin/tests/admin_logic_test.php
php tests/admin_security_test.php
php tests/public_render_test.php
php tests/submission_security_test.php
php db/seed_existing.php --verify-source
```

`tests/mariadb_integration_test.php` and `tests/submission_mariadb_test.php` are optional mutation tests for a disposable database. Set `APP_CONFIG_FILE` to a test-only configuration after running migrations and the seed; never point either test at production.

The root `.htaccess` rules require Apache/LiteSpeed with `mod_rewrite`; PHP's built-in server does not reproduce them.
