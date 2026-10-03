# Namecheap preparation and handoff

Prepared 2 October 2026 for an Apache-compatible cPanel shared-hosting deployment. The exact plan, domain, account paths and PHP handler are not confirmed. This is a locally verified preparation package, not a deployed system. No database, credentials, uploads or scheduled tasks were changed.

## What is ready locally

- Secret-free release builder: `python scripts/build_namecheap_release.py`. It copies the current working files, including installed dependencies, rather than an older Git commit. It refuses to overwrite an existing release. No ZIP, Git push or deployment is performed.
- Root access-denial and PHP-setting templates in [deployment/namecheap](../deployment/namecheap/).
- A private production bootstrap shared by web PHP and CLI, with placeholder settings. It preserves password values and fails safely if configuration is incomplete.
- SMTP and push configuration wrappers pointing outside the public document root.
- Isolated verification: `python tests/namecheap_preparation.py` passed **41 checks**, covering CLI/web configuration, safe failures, blocked paths, allowed assets, internal PHP includes and certificate challenge access.

No template has been installed on the live local application. The generated package has placeholder paths and empty settings and must not be activated unchanged. Generated packages are ignored by Git.

## 1. Confirm the plan before uploading

Ask the friend/provider to confirm:

1. PHP 8.2+ for **both the website and CLI cron**, with the installed Composer lock's requirements. Enable `mysqli`, `fileinfo`, `mbstring`, `openssl`, `curl`, `dom`, `Phar`, sessions and any additional Composer requirements. Confirm document inspection works on the host.
2. InnoDB MySQL/MariaDB, foreign keys and utf8mb4; enough storage for the current database and uploads. Validate the actual engine/schema, not just the product name.
3. Working HTTPS, Apache-compatible rewrite/access-denial rules, PHP `putenv`, and a supported way to load a private `auto_prepend_file` for web requests. Confirm `open_basedir` permits the private directory. Do not bypass failed protection or configuration checks.
4. SSH/cPanel Terminal or an operator-supported way to run preflight, approved setup and a CLI worker; confirm the exact PHP executable. A web PHP selection does not prove the CLI version.
5. Outbound authenticated SMTP, HTTPS to supported push providers and official advisory sources. Confirm email sending limits against expected recipients and recovery use.
6. Private writable sessions/logs/temp, required upload storage and enough execution time/resources for the combined worker.

Namecheap documents a minimum **five-minute cron interval** on shared servers and no more than five simultaneous cron jobs. Use one combined worker, not duplicate email/push/release schedules. [Namecheap cron instructions](https://www.namecheap.com/support/knowledgebase/article.aspx/9453/29/how-to-run-scripts-via-cron-jobs/)

PHP settings and supported extensions vary by handler/plan. Confirm the selected account against [Namecheap PHP settings](https://www.namecheap.com/support/knowledgebase/article.aspx/9314/2219/how-to-edit-phpini-on-shared-servers/) and [extension documentation](https://www.namecheap.com/support/knowledgebase/article.aspx/9697/2219/php-modules-limits-and-extensions-on-shared-hosting-servers/). The sample `.user.ini` is a candidate configuration, not proof that the host reads it.

## 2. Review the release

The builder produces:

```text
deployment/namecheap/release/
  public/                 -> chosen application document root
  private/                -> /home/CPANEL_USER/olshco-private/ (outside ALL web roots)
  manifest.json           -> private review record of copied source hashes
  NOT_READY_TO_UPLOAD.txt -> preparation warning
```

Do not upload the parent `release/` folder wholesale. Only the contents of `public/` belong in the application web root. Keep the manifest/warning and private settings outside web roots. Confirm the actual domain/subdomain document root; it may differ from `/home/CPANEL_USER/public_html`.

The package includes runtime PHP/views/CSS/JavaScript/images, Composer dependencies, the combined worker, read-only configuration checker, upload protections and empty runtime storage directories. It excludes local `*.local.php` credentials, examples/READMEs, tests, development/seed tools, schema dumps, current uploads and logs. Review intended public `Assets/Images` files before upload; naming alone cannot establish whether an image is intended for publication.

Existing database rows and uploaded documents/photos/media need a separately reviewed transfer. Omitting uploads from the code package is not a migration strategy: transfer every referenced authorized file separately while preserving paths and protection. Do not assume a new code upload contains those files.

Prepare Composer with locked versions, not `composer update`, if rebuilding dependencies:

```text
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
```

Run platform checks again on the host. Linux paths are case-sensitive: preserve the case of `Assets`, `Images`, files and referenced paths. The packaged source is a snapshot; rebuild deliberately if source changes before launch. The builder does not delete/overwrite an earlier release.

## 3. Provision private configuration

Replace `CPANEL_USER` and the application path in the prepared settings/wrappers privately. Suggested layout:

```text
/home/CPANEL_USER/olshco-private/
  bootstrap.php
  environment.php
  email.php
  push.php
  sessions/
  php-error.log          (created by PHP when configured)
  cron-error.log         (created by shell redirection)
```

Fill `environment.php` with production mode, the complete cPanel database host/name/user/password/port, canonical HTTPS URL and one stable application key. Generate a key once using `bin2hex(random_bytes(32))` in an approved private session; never send it through chat or commit it. Keep the same key across releases; unplanned rotation affects recovery verification and rate-limit identities.

The private bootstrap sets the application's actual `OLSHCO_*` variables without reading a generic `.env`. It does not open a database or set `$_SERVER['HTTPS']`. It requires complete production settings and rejects unknown keys. Keep existing passwords unchanged when writing the PHP return file; escape quotes/backslashes correctly rather than interpolating secrets into shell commands.

Fill private `email.php` using the existing sample keys: enabled, host, port, encryption, username, password, from_email/name, reply_to_email and timeout. Email must work for password recovery. Configure authenticated SMTP and verify the sender. Fill private `push.php` with one stable matching VAPID key pair and contact subject; changing the pair can require browser resubscription.

Public `config/email.local.php` and `config/push.local.php` contain only absolute-path wrappers to those private files. No loader change is needed in the application. The root denial rule blocks direct HTTP access to `config/`; outside-root private storage adds another boundary.

Use restrictive host-compatible permissions: private files readable by the PHP/cron account only (commonly 0600), private directories commonly 0700. Confirm the host's execution identity; do not use 0777. Application source should not need routine write permission; uploads/sessions/temp/logs do.

## 4. Configure web PHP and access protection

Review the packaged `.htaccess` with existing host rules before installation. It denies direct internal-directory access, hidden/sensitive files, source dumps and retired document folders; authorized PHP includes still work. Preserve upload-specific `.htaccess` files and test document access through the authorized route. Never remove denial rules merely to make a 500 error disappear.

Apply the PHP directives through the **confirmed handler mechanism**. The `.user.ini` template supplies private bootstrap/session/error-log paths, no displayed errors, Asia/Manila timezone, 20M uploads and 32M POST allowance. If the host uses `php.ini`/PHP Selector instead, configure the same settings there and confirm they actually apply. Configuration caches can delay updates; consult support rather than leaving a public `phpinfo()` page.

Enable HTTPS only after the certificate works, then use the host's Force HTTPS Redirect. Confirm PHP sees the trusted server HTTPS state and login session cookies have Secure, HttpOnly and SameSite=Lax. A canonical HTTPS application URL alone does not prove transport security. Do not enable trust of arbitrary forwarded headers.

Check authenticated sessions and uploads using the browser; CLI success alone cannot prove web configuration. A temporary settings check must print only pass/fail, use access protection and be removed afterward; never expose credentials or full environment output.

## 5. Rehearse the database and files

Do not import a historical DROP-containing dump onto populated hosting. Confirm the target, backup and approval before import/migration or real account changes. The application expects **65 tables including migrations 027–030**; the original 62-table scratch bootstrap is not a turnkey current installer. Prepare and verify the chosen current schema/data path in staging first. Use dedicated runtime credentials and separately controlled migration/backup permissions.

For an existing-data transfer, back up database, uploads and required private configuration consistently; keep workers disabled during the copy/restore. Verify representative file references, all role logins, Parent eligibility, profile history and content state. Do not transfer demo/seed records unintentionally or invent new administrator credentials automatically.

Record backup/restore evidence and a rollback plan before a public cutover. None of these database actions were performed in this preparation.

## 6. Preflight and the five-minute worker

Replace `PHP_BIN` and every path with confirmed values. First check `PHP_BIN -v` and host extensions. CLI does **not** automatically load the web `.user.ini`; supply the same private bootstrap explicitly:

```sh
PHP_BIN -d auto_prepend_file=/home/CPANEL_USER/olshco-private/bootstrap.php /home/CPANEL_USER/APP_ROOT/scripts/check_deployment_configuration.php --production
```

This preflight is read-only: no DB connection, publication or delivery. Require zero failures, review warnings, and separately confirm web settings and real connectivity. Do not substitute a guessed executable path just because it runs.

Only after the separate staging/activation decision, create **one** cPanel cron job with schedule `*/5 * * * *` and command:

```sh
PHP_BIN -d auto_prepend_file=/home/CPANEL_USER/olshco-private/bootstrap.php /home/CPANEL_USER/APP_ROOT/scripts/dispatch_browser_push.php >> /home/CPANEL_USER/olshco-private/cron-error.log 2>&1
```

Replace `APP_ROOT` with the actual app root relative to the account home; for a standard root this might be `public_html`, not the literal placeholder. This command changes records and can send real messages. Do not run it as a harmless configuration test. It uses the existing lock, durable outbox, preference/recipient checks and combined release/reminder/email/push stages. No Windows launcher or Task Scheduler registration is deployed.

The worker writes its existing stage summary to `APP_ROOT/logs/browser-push-dispatch.log`; root HTTP rules protect it. Monitor that log plus private cron output and queue status, and arrange host-appropriate rotation for private logs. Exit codes remain 0 success, 1 failure, 75 overlap skip. Cron does not itself prove that messages were delivered.

Worker-dependent release/retry/email/push work may wait for the next five-minute invocation; failures, backlog or runtime limits can extend this. Normal immediate publication and eligible in-system dispatch retain their existing application behavior. The code was not changed to introduce artificial delays. Test reminder/schedule expectations with the school and confirm this host interval is acceptable.

## 7. Launch gates and friend handoff

- Confirm actual plan, domain, document root, PHP web/CLI version and configuration method.
- Check HTTP denial for internal source, dumps, private config and raw documents; check CSS/JS/images, certificate validation and authorized downloads still work.
- Verify HTTPS cookies, registration/recovery, forced password change, legal consent and Faculty onboarding.
- Run Admin/Faculty/Student/Parent walkthroughs, including direct denied requests, Faculty scope, Parent verification and five-respondent survey privacy.
- Test real SMTP and browser push with approved recipients, then scheduled release, profile reminders, retry/deduplication and worker monitoring.
- Complete schema/file transfer and backup/restore rehearsal with the approved target and rollback plan.
- Resolve or disable the deferred Contact inquiry submission before advertising it as operational.
- Only then approve public deployment. Do not describe this local package as a hosted/evaluated system.

Related: [deployment configuration](deployment-configuration.md), [launch checklist](deployment-launch-checklist.md), [latest re-audit](deployment-re-audit-2026-10-02.md), [manual evaluation](evaluation-checklist.md).
