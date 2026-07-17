# Hostinger deployment runbook

This is the production checklist for `itsahmedmalik.com`. Do not commit database, mailbox, Turnstile, or admin credentials.

## 1. Back up and prepare

1. In hPanel, create a restorable backup of `public_html` and the current database. Separately copy `public_html/media/cms`; CMS uploads must survive every deployment.
2. Record the currently deployed commit so it can be redeployed without guessing.
3. Select PHP 8.3 in **Websites → Dashboard → PHP Configuration**. Enable cURL, DOM, Fileinfo, GD, mbstring, OpenSSL, PDO, and PDO MySQL. Hostinger documents the [PHP version](https://www.hostinger.com/support/1575755-how-to-change-the-php-version-of-your-hostinger-hosting-plan/) and [extension controls](https://www.hostinger.com/support/4667515-how-to-manage-php-extensions-and-options-in-hostinger/).
4. Set `upload_max_filesize` to at least `10M`, `post_max_size` to at least `128M`, `max_file_uploads` to at least `12`, `memory_limit` to `512M` if the plan permits, and `max_execution_time` to at least `60`. Keep `display_errors` off in production. The separate `api/.htaccess` applies 16–64 KiB web-server request caps to public forms without reducing the larger `/admin` image-upload allowance.
5. Enable SSH in **Websites → Dashboard → SSH Access**. The website PHP selection does not necessarily select the same PHP binary for SSH or Composer, so the commands below explicitly use Hostinger's PHP 8.3 binary.

## 2. Create external services

1. Create the Hostinger database and its single user in hPanel. Copy the complete Hostinger-prefixed database and user names; the database host is `localhost`. See [Hostinger's database guide](https://www.hostinger.com/support/1583542-how-to-create-a-new-mysql-database-in-hostinger/).
2. Create a Cloudflare Turnstile widget that permits both `itsahmedmalik.com` and `www.itsahmedmalik.com`. The server follows Cloudflare's [mandatory Siteverify flow](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/) and checks the token, expected form action, and hostname.
3. Create an app password for `hello@itsahmedmalik.com`. SMTP is `smtp.hostinger.com`, port `465`, implicit TLS; compare the mailbox against [Hostinger's current email settings](https://www.hostinger.com/support/1575756-how-to-get-email-account-configuration-details-for-hostinger-email/). Visitor email addresses are used only as `Reply-To` or as the recipient of an approved-meeting notice.
4. Generate one non-default admin username and a password-manager password of at least 20 characters. Store the password only in hPanel's directory protection—not in PHP or MariaDB.

## 3. Install the private configuration

The application automatically reads:

```text
/home/<account>/domains/itsahmedmalik.com/private/app.php
```

Before the first Git deployment, connect over SSH and run these commands from `/home/<account>/domains/itsahmedmalik.com`:

```sh
install -d -m 700 private
if [ ! -e private/app.php ]; then
  install -m 600 /dev/null private/app.php
fi
chmod 600 private/app.php
install -d -m 755 public_html/media/cms
```

Open `private/app.php` with a terminal editor, or upload the local `config.example.php` file to that exact private path using SFTP, before deploying the new PHP application. Do not rely on copying from `public_html` during the first rollout because the template is not present there until after Git deployment. On future deployments, never overwrite the existing private file.

Edit `private/app.php` and set all of the following:

- `env` to `production`
- `app_url` to `https://itsahmedmalik.com`
- `timezone` to `Asia/Karachi`
- `allowed_hosts` to the apex and `www` hostnames
- `admin_username` to exactly the username configured for hPanel directory protection
- the full database name/user, password, `localhost`, and port `3306`
- Turnstile public site key and secret
- Hostinger SMTP username, app password, sender, and admin recipient

Do not move this file into `public_html`. Confirm its directory is mode `700` and the file is mode `600`.

## 4. Deploy and initialize

Connect the GitHub repository under **Advanced → Git**, select `main`, and deploy manually to `public_html`. Keep automatic deployment disabled. Hostinger's current process uses [GitHub OAuth and manual redeploys](https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/).

Run these commands over SSH from `public_html`. They pin both Composer and every check to PHP 8.3 even if the default SSH `php` command points elsewhere:

```sh
PHP83=/opt/alt/php83/usr/bin/php
COMPOSER2=/usr/local/bin/composer2

"$PHP83" -v
"$PHP83" "$COMPOSER2" validate --strict
"$PHP83" "$COMPOSER2" audit --locked --no-dev
"$PHP83" "$COMPOSER2" install --no-dev --prefer-dist --classmap-authoritative --no-interaction
"$PHP83" "$COMPOSER2" check-platform-reqs --no-dev
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 "$PHP83" -l
"$PHP83" -d zend.assertions=1 -d assert.exception=1 admin/tests/admin_logic_test.php
"$PHP83" tests/admin_security_test.php
"$PHP83" tests/public_render_test.php
"$PHP83" tests/submission_security_test.php
"$PHP83" db/seed_existing.php --verify-source
"$PHP83" db/migrate.php
"$PHP83" db/seed_existing.php
```

Do not run `tests/mariadb_integration_test.php` or `tests/submission_mariadb_test.php` on production: they intentionally exercise mutations and are reserved for a disposable staging database.

The Composer install must use the committed lockfile; do not substitute `composer update` in production. Hostinger provides Composer 2 through `composer2` and documents both [Composer usage](https://www.hostinger.com/support/5792078-how-to-use-composer-at-hostinger/) and [running Composer with an explicit PHP version](https://www.hostinger.com/support/5792082-how-to-solve-common-composer-issues-at-hostinger/).

The migration runner takes a database advisory lock and applies only unrecorded, additive migrations. The seed is safe to rerun: it preserves later admin edits and verifies the legacy IDs and collection counts.

For a two-stage rollout, first exercise the protected previews on a staging copy (or retain the previous production rewrite file), then put the repository's final `.htaccess` in place. Do not switch the public routes until the seeded previews match the existing pages.

## 5. Protect `/admin/`

In **Websites → Manage → Password Protect Directories**, protect the `public_html/admin` directory with the exact username configured in `private/app.php`. Hostinger documents the [directory-protection workflow](https://www.hostinger.com/support/1583470-how-to-password-protect-a-website-in-hostinger/).

Verify all three cases:

- `/admin` permanently redirects to `/admin/`.
- `/admin/` and nested files prompt for the hPanel username/password.
- If PHP does not receive a server-authenticated `REMOTE_USER`/`REDIRECT_REMOTE_USER` (or `PHP_AUTH_USER` accompanied by the server’s Basic `AUTH_TYPE` marker), or the username does not exactly match, the dashboard returns `403`. A raw `PHP_AUTH_USER` value alone must fail.

Recheck directory protection and the persistent `media/cms` directory after every Git redeployment.

HTTP Basic credentials are browser-managed, so practical logout means closing the browser session.

## 6. HTTPS and cache rules

Force HTTPS in hPanel. Leave Cache Manager's **Automatic cache** disabled for the initial CMS launch because the current hPanel controls do not provide dependable route-level exclusions. If Hostinger later provides verified exclusions, exclude `/admin/`, `/api/`, `/sitemap.xml`, all public `.html` routes, and `/blog-*.html`; CSS, JavaScript, and images may still use normal static caching.

The application sends `no-store` for admin/API/errors and revalidation headers for public CMS HTML/XML, but the hosting cache exclusion must also be confirmed.

## 7. Production acceptance

Check public status and access boundaries first:

```sh
curl -I https://itsahmedmalik.com/
curl -I https://itsahmedmalik.com/blog-1.html
curl -I https://itsahmedmalik.com/sitemap.xml
curl -I https://itsahmedmalik.com/admin
curl -I https://itsahmedmalik.com/admin/
curl -I https://itsahmedmalik.com/src/bootstrap.php
curl -I https://itsahmedmalik.com/config.example.php
curl -I https://itsahmedmalik.com/media/cms/
```

Expected results are `200` for published public routes, `301` for `/admin`, an authentication challenge for `/admin/`, and denial for private/config/directory paths.

Then use a real browser to test:

1. Valid and invalid contact, newsletter, and meeting submissions, including Turnstile reset and preserved values after an error.
2. Database rows in all three submission tables and the two admin notification emails.
3. Meeting approval with an adjusted PKT time, the visitor email, a duplicate-slot rejection, and failed-email Retry.
4. Create, edit, preview, publish, unpublish, reorder, archive, and restore in all seven CMS modules.
5. Education on Home/About, experience on Home/Work, testimonials on Home/About, and project/event embedded collections.
6. Empty and one-item collection states, mobile layouts, keyboard navigation, modals, reduced motion, and the dynamic sitemap.
7. A JPEG/PNG/WebP upload and rejection of SVG, forged MIME, oversized, corrupt, and over-dimension images.
8. PHP error logs after the complete pass.

## Rollback

Prefer reverting the CMS commit on `main` and manually redeploying, or deploy a rollback branch created from the recorded static commit. The current pre-CMS baseline is `c2254cb3b6904eaeafb9e8c80661c89b24eaaa48`; it predates Composer and therefore has no matching lockfile. An emergency hPanel file restore should restore `public_html` only, not the new CMS database. Do not run a destructive down migration. Keep the private configuration, new tables, submissions, and uploaded media intact, then correct the issue with a forward deployment.
