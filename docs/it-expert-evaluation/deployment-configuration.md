> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Deployment configuration and H6 verification

This is a preparation guide, not approval to deploy. H6 removes the top-level call to pushConfiguration() from BrowserPushDeliveryService.php. Including that service no longer requires private Web Push settings before the combined worker can publish content. Constructing the delivery service still validates its configuration; missing credentials never enable delivery or fall back to example keys. Existing local credentials were not changed.

## Configuration inventory

| Setting / file | Required when | Source and behavior |
| --- | --- | --- |
| OLSHCO_APP_ENV | Production | Set production explicitly in web PHP and CLI workers. Database configuration defaults to production when no development file is present. |
| OLSHCO_DB_HOST, OLSHCO_DB_USER, OLSHCO_DB_PASSWORD, OLSHCO_DB_NAME | Every application request and database worker | Environment variables. OLSHCO_DB_PORT is optional (3306). Partial environment credentials fail closed; production rejects root and empty passwords. |
| OLSHCO_APP_KEY | Password recovery tokens and throttling | Stable, securely generated random value of at least 64 characters; recommended 32 random bytes encoded as 64 hex characters. Provision privately through the environment. Rotation invalidates outstanding recovery token verification; plan it deliberately. |
| OLSHCO_APP_URL | Recovery links and email links | Canonical application directory URL, e.g. https://hub.example.edu/bahay-ko. Do not append index.php, query strings, fragments or credentials. Production uses HTTPS. Never derive it from a request Host header. |
| config/email.local.php | EmailSender construction, including password recovery and combined worker | PHP file returning the documented array. There is no native SMTP environment-variable mapping. A secret manager can provision this file or its return values can explicitly read environment variables. Disabled email skips notification delivery; it does not provide working password-reset delivery. |
| config/push.local.php | Browser Push client configuration and actual delivery-service construction | PHP file returning subject, public_key, private_key. There is no native VAPID environment-variable mapping. There is no global disabled-push setting. Missing/incomplete settings fail at feature use. |
| OPENSSL_CONF | Some Windows/XAMPP OpenSSL installations | pushConfiguration attempts the existing XAMPP OpenSSL path only when this variable is absent. Other installations must configure their own OpenSSL environment if required. |

There is no dotenv loader. An .env file alone does nothing. Configure the PHP/Apache or PHP-FPM process and the scheduled worker's process environment; your interactive shell environment does not automatically reach an already running web server or task.

Database/security local files are workstation fallbacks and must be excluded from production packages. Unlike database configuration, security.php can currently fall back to security.local.php even when production is selected; production preflight therefore requires explicit OLSHCO_APP_KEY and OLSHCO_APP_URL environment values. This does not change the runtime helper's existing behavior.

## Secret-free examples

These examples contain no working secret and are safe to review:

- config/database.local.php.example — local development only.
- config/security.local.php.example — local development only; application_key intentionally empty.
- config/email.local.php.example — complete option list, disabled by default.
- config/push.local.php.example — required fields, keys intentionally empty.

For a new local installation, copy each needed example to the same filename without .example only if that destination does not exist. Do not overwrite working credential files. Fill credentials privately; do not commit them, paste them in chat, or place them in shell history. The four *.local.php destinations are already ignored by Git. Ignore rules do not protect files copied into deployment archives.

SMTP options: enabled boolean; host; port (1–65535, default 587); encryption tls or ssl; username (currently must be an email address); password; valid from_email; nonempty from_name; optional valid reply_to_email; timeout (clamped to 5–60 seconds, default 20). Existing email.php removes whitespace from passwords, matching its SMTP app-password usage; no password normalization was changed by H6. Use credentials compatible with that behavior and verify the chosen provider on staging.

Web Push uses a stable VAPID pair. public_key is the URL-safe base64 encoding of a 65-byte uncompressed P-256 public key; private_key encodes 32 bytes. subject is a mailto contact or HTTPS URI. Generate and provision a genuine matching pair through an authorized administrative process; the examples cannot send push. Do not regenerate the pair per request or release. Replacing it can require browser resubscription. Client responses contain the public key only.

For production, provision the email/push files separately from the release source, readable by PHP/worker identities and inaccessible over HTTP. The current loader paths remain config/email.local.php and config/push.local.php; arbitrary external paths are not supported. A PHP return file can read secrets injected by your secret manager, but the mapping must be explicit in that private file.

## Read-only preflight

From C:/xampp/htdocs/bahay-ko:

```powershell
& C:/xampp/php/php.exe scripts/check_deployment_configuration.php --help
& C:/xampp/php/php.exe scripts/check_deployment_configuration.php
```

On staging with production settings injected into the process:

```powershell
& C:/xampp/php/php.exe scripts/check_deployment_configuration.php --production
```

Exit codes: 0 = no failed configuration checks; 1 = one or more checks failed; 2 = unsupported arguments. Warnings still need review. OLSHCO_APP_ENV=production also enables production checks without the flag. --production changes that variable only inside this CLI process; it does not edit configuration or the server environment. HTTP execution returns 404 before checks.

The command checks PHP/extensions, Composer classes, database configuration structure, the application key/URL, SMTP configuration, VAPID encoding/contact format, writable upload/temp directories and PHP timezone. Output contains fixed labels and corrective hints, never credential values or exception messages. Unexpected output from loaded configuration is discarded. No database connection, filesystem writes, scheduled publication, email or push delivery is performed. pushConfiguration can set OPENSSL_CONF within this process, as it already does at runtime.

A passing preflight does not prove database connectivity/privileges/schema, SMTP authentication/delivery, matching VAPID cryptographic keys, browser permission, HTTPS/proxy setup, web-SAPI environment, Apache access protection or scheduler execution. It checks VAPID shape only. Production email-disabled configuration is a failure because password recovery is an existing feature. Local inspection reports it as a warning.

## Runtime and web server

Use the project's PHP requirement (^8.2), committed composer.json and composer.lock. Prepare dependencies using the locked versions, not composer update:

```text
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
```

These are deployment/build steps, not commands run during this H6 change. Required PHP capabilities include mysqli, fileinfo, mbstring, openssl, curl, DOM/libxml, Phar ZIP support, sessions and Composer's platform requirements. ZipArchive is not needed by document validation. Verify both CLI and web PHP use the intended extensions and configuration.

Configure date.timezone=Asia/Manila consistently for web/CLI and align MySQL session/server time used by NOW() with scheduling expectations. EventController sets a timezone for its own path; it is not a global worker configuration. Inspect timezones before changing them; do not reinterpret stored dates silently.

Serve the application over HTTPS. Secure session cookies depend on PHP's HTTPS server variable, not OLSHCO_APP_URL. If TLS terminates at a proxy, configure the trusted web-server integration to convey HTTPS correctly; do not blindly trust client-supplied forwarded headers. Verify HttpOnly, SameSite=Lax and Secure on staging. Hosting/TLS topology is not selected yet. The M5 decision is to keep HTTPS detection server-controlled, with no application trust of forwarded headers. See global-guards-verification.md for the direct-server/proxy decision and external cookie verification required once a host is chosen.

In production PHP configuration set display_errors=Off, display_startup_errors=Off, log_errors=On and a private error_log path with rotation. H4's request handler cannot cover errors before PHP executes the entry point. Ensure a writable private session directory, upload temporary directory and adequate upload_max_filesize/post_max_size (document limit is 20 MiB; post_max_size must also cover multipart overhead and any cover image).

Keep application source/configuration read-only to the web identity where possible. Allow writes only to required uploads, PHP sessions/temp and worker logs. The worker currently logs to project/logs/browser-push-dispatch.log and rotates one 5 MiB archive; create that directory with appropriate permissions and deny HTTP access. Its lock uses PHP's temporary directory. Do not relocate these paths by assumption.

Protect app/, config/, vendor/, database/, dev/, docs/, scripts/, tests/, logs/ and .git from direct HTTP access, and exclude unnecessary development files, SQL dumps and ZIP archives from the public package. Keep operational scripts/schema/backups in private administrative storage. PHP can still include files denied by Apache. Retain upload .htaccess files and ensure Apache honors the Options, FileInfo and authorization directives. Other servers need equivalent rules. See document-upload-verification.md for actual denial checks. No web-server configuration was changed by H6.

## Database and release setup

Use a dedicated runtime database account, with separate migration/backup credentials. A correct credential configuration is not a schema installation. Follow database-baseline-readiness.md, fresh-install-bootstrap.md and publication-notification-readiness.md. Do not import the destructive snapshot over an existing database or blindly replay all historical migrations. The current baseline includes the H3 outbox and M4 request-rate-limit additions (64 tables). Apply migrations 027 and 028 before activating the corresponding code on a new installation; see abuse-protection-verification.md for M4 verification. The earlier fresh-install rehearsal documents the original 62-table baseline and requires both subsequent additive migrations.

Initial administrator provisioning, approved school reference data, production migrations, backup/restore rehearsal and deployment are separately authorized operations. No new Administrator or production credentials were created here. Review the dirty/untracked working tree before constructing a release; a release from the old commit alone would omit needed files.

## Workers and operational commands

The primary scheduled entry point is scripts/dispatch_browser_push.php. Despite its name, it processes due scheduled/calendar publications and their durable notifications, upcoming-event reminders, email delivery and browser push. It takes a nonblocking lock and logs summaries. Provision all its DB/security/email/push settings before enabling it. Removing the premature Web Push load allows earlier stages to start; a missing delivery configuration still causes an operational failure at that delivery stage.

An authorized deployment can schedule this command about once per minute, using the same configured environment, PHP runtime and suitable working directory:

```text
C:/xampp/php/php.exe C:/xampp/htdocs/bahay-ko/scripts/dispatch_browser_push.php
```

That command changes records and can send real email/push. It is not a verification command and was not run for H6. scripts/dispatch_browser_push_hidden.vbs is a workstation-specific launcher with a fixed XAMPP PHP path. M3 makes it wait for PHP, propagate exit status and set the project working directory. See scheduler-monitoring.md for the two discovered local registrations and completed cleanup; it is not a portable deployment setup.

scripts/dispatch_publication_notifications.php [1-100] can separately drain the durable publication-notification outbox. It creates in-system notification records; it does not itself send email/push. It does not replace scheduled publication or the combined delivery worker. See publication-notification-readiness.md. Do not use scripts/send_browser_push_test.php as a harmless preflight; it sends a real test notification.

No scheduled task was installed, enabled or changed. Verify task exit status/logs, due publications, deduplication, retry handling and delivery using approved staging recipients before release. Advisory intake has separate administrative configuration; these worker commands do not establish an advisory-fetch schedule.

## H6 changes and verification

Changed: app/services/BrowserPushDeliveryService.php (remove eager credential call only), config/README.md and tests/README.md. Added: four config/*.local.php.example files, scripts/check_deployment_configuration.php, tests/deployment_configuration.php and this document. No routes, request methods, authorization, CSRF, database writes or UI changed.

Backups: C:/Users/ctrlc/.codex/backups/bahay-ko/deployment-config-20260924-141722.

Safe regression commands:

```powershell
& C:/xampp/php/php.exe tests/deployment_configuration.php
& C:/xampp/php/php.exe tests/database_configuration.php
& C:/xampp/php/php.exe tests/publication_durability.php
& C:/xampp/php/php.exe tests/request_errors_http.php
& C:/xampp/php/php.exe -l app/services/BrowserPushDeliveryService.php
& C:/xampp/php/php.exe -l scripts/check_deployment_configuration.php
& C:/xampp/php/php.exe -l tests/deployment_configuration.php
git check-ignore config/database.local.php config/security.local.php config/email.local.php config/push.local.php
git diff --check
```

The new tests use copied loaders and synthetic private configuration in a temporary directory outside the webroot. They verify missing configuration, complete production configuration without network connections, unsafe URLs, required production security environment, disabled email handling, secret-safe failures, CLI-only access and lazy delivery-class loading. Test keys are format fixtures, not a real VAPID pair. Files created by the tests are removed; existing local secrets are never copied or changed. Existing DB/publication/error regressions use their documented read-only or temporary-table fixtures and send no email/push.

Observed H6 results: 34 deployment-configuration checks, 38 database-configuration checks, 74 publication-durability checks and 79 request-error checks passed (225 total). All changed/new PHP files and PHP configuration examples passed syntax checks. Git whitespace checks passed, with only existing line-ending warnings. The actual local preflight reported zero failures and one local-only warning. No production preflight, live delivery, browser journey or scheduler run was claimed.

Migration 029 was applied locally on 26 September 2026. New installations must also include the [verified child-record table](../../database/baselines/029_parent_child_record.sql); the current read-only baseline expects 65 tables. See [Parent registration verification](parent-registration-verification.md).


## Local launch-preparation checkpoint (30 September 2026)

Read-only configuration inspection passed locally with zero failures and the expected
local-only warning. Running the same script with --production in this local process
reported three failures: production database credentials, OLSHCO_APP_KEY, and
OLSHCO_APP_URL. This is evidence about this process environment, not an inspection
of a selected hosting provider. No settings were changed or secrets printed.

Before launch, provision a dedicated database account, stable private application
key, and canonical HTTPS URL on the selected host, then rerun the production check
under both web/worker-equivalent configuration. Configuration passes do not establish
actual database connectivity, email/push delivery, backup restoration, scheduled
worker execution or real-session user journeys; verify those separately on staging.

Commands used:

```powershell
& C:/xampp/php/php.exe scripts/check_deployment_configuration.php
& C:/xampp/php/php.exe scripts/check_deployment_configuration.php --production
```


### Profile-update assignment reminders

Activation attempts up to 100 missing reminders using committed assignments. The combined worker (`scripts/dispatch_browser_push.php`) retries up to 100 per run before email/push delivery. The standalone notification worker also retries these reminders using its supplied batch limit. No extra scheduled task or schema migration is needed.

Active, non-default cycles with unfinished assignments to active Students are eligible. Existing notification deduplication keys prevent repeat alerts, including concurrent retries. Completed/exempt assignments and closed cycles are skipped. Previously missing eligible reminders can therefore be recovered on the next worker run. Reminder channel preferences still apply. Future-opening messages state the opening time; activation creates the advance notice, not a second notice at opening.

Keep the worker running for retries and larger batches. Worker output reports profile reminder creation/failures; failures produce a nonzero exit. A persistent notification-storage failure still requires operator attention. No worker was run against real records to verify this change.

Safe regression commands (temporary database tables/isolated workers; no real delivery):

```powershell
& C:/xampp/php/php.exe tests/profile_cycle_notifications.php
& C:/xampp/php/php.exe tests/student_guard_writes.php
& C:/xampp/php/php.exe tests/global_guards_http.php
& C:/xampp/php/php.exe tests/scheduler_monitoring.php
```

Faculty onboarding requires migration 030 (`database/migrations/030_faculty_personal_profile.php --apply`). It makes gender/age nullable for pending personal profiles and preserves existing records. Apply it after the schema baseline and migrations 027-029 before enabling simplified provisioning. Verify with `php scripts/check_database_baseline.php`. Faculty must change their temporary password, accept legal documents, and complete My Account before accessing content or publishing.

See the [2 October deployment launch checklist](deployment-launch-checklist.md) for the current verified checkpoint, hosting requirements, and remaining launch gates.
