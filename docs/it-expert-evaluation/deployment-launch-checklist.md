> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Deployment launch checklist

Latest source/security verification: [2 October deployment re-audit](deployment-re-audit-2026-10-02.md). Its 47-suite results supplement the earlier checkpoint below.

Prepared 2 October 2026 (Asia/Manila). This is a local readiness checkpoint, not evidence of a deployed or fully tested production system. No hosting provider has been selected.

## Verified local checkpoint

| Check | Observed result |
| --- | --- |
| Local configuration preflight | 0 failures; expected local-only warning |
| Production preflight in this local process | 3 failures: production DB credentials, explicit application key, canonical HTTPS URL |
| Read-only schema comparison | 65 expected tables, 65 current tables, 0 differences; includes migration 030 adjustments |
| Deployment configuration tests | 34 passed |
| Database configuration tests | 38 passed |
| Scheduler monitoring tests | 33 passed; isolated worker fixtures |
| Notification destination tests | 26 passed; fixtures only |
| Request failure handling tests | 79 passed; isolated HTTP fixtures |
| Session security tests | 105 passed; temporary tables and session files |
| Document validation tests | 125 passed; temporary files |

440 focused regression checks passed in this preparation run. No real email/push delivery was triggered and no existing application records were changed. This is not a full release regression or load test. The local combined worker was observed running/enabled; the duplicate dispatcher was disabled. This observation does not establish production scheduling or successful delivery.

## 1. Choose hosting

Confirm these capabilities with the provider before purchase:

- PHP 8.2+ for both web requests and CLI, with the extensions listed by the configuration checker and Composer platform requirements.
- MySQL/MariaDB supporting the current InnoDB schema, foreign keys, and utf8mb4; rehearse the actual schema on the selected engine/version.
- HTTPS certificate and a domain/subdomain. If TLS terminates at a proxy, the trusted web server must supply correct HTTPS state to PHP; a request header alone is not trusted by the application.
- A scheduled CLI PHP command approximately once per minute, persistent worker logs, a writable temporary directory, and sufficient execution time for the existing combined worker.
- Outbound SMTP, HTTPS for browser push, and official advisory-link checks.
- Environment variables or another reviewed mechanism for private configuration in both web and worker processes. An uploaded .env alone does not configure this application.
- Server access rules protecting internal files, upload handler restrictions, backups, and an isolated staging/restore database.

A shared-hosting plan is suitable only if these capabilities are available. The Windows VBS launcher and workstation Task Scheduler registration are not portable hosting configuration.

## 2. Prepare the release

- Identify the actual source snapshot and record its file hashes. There were 209 changed/untracked Git entries at this checkpoint; review them before packaging. A package from the last commit alone may omit current application files.
- Include runtime routes, controllers/services/models, components, assets, the locked Composer dependencies, and required worker files.
- Exclude local credentials, .git, development/seed scripts, test fixtures, SQL dumps, private backups, temporary passwords, and logs from public delivery. Keep deployment tools/reference files privately available to the operator where required.
- Decide deliberately whether to transfer current operational records or start a clean evaluation database. Do not import a seed-containing or stale backup by default.
- Preserve uploads and the configuration needed to read them. Do not run historical DROP-containing snapshots against populated databases.

## 3. Configure the selected host

Follow [deployment configuration](deployment-configuration.md) for exact setting names and private SMTP/VAPID file formats.

- Set OLSHCO_APP_ENV=production, dedicated OLSHCO_DB_* credentials, stable OLSHCO_APP_KEY, and canonical HTTPS OLSHCO_APP_URL.
- Provision private email/push configuration without publishing credentials. Configure web and CLI timezone to Asia/Manila and check database scheduling behavior.
- Disable PHP error display, enable private error logging, and verify session cookies include Secure under real HTTPS, HttpOnly, and SameSite=Lax.
- Restrict runtime database grants; use separately controlled migration/backup credentials.
- Grant write access only to required upload/temp/session/log locations.
- Configure direct HTTP denial for config, app, include, vendor, database, scripts, tests, development files, backups and secrets, without blocking PHP's internal includes or CLI workers. The root currently has no .htaccess; directory listing prevention alone is insufficient.
- Preserve the reviewed Assets/uploads .htaccess protections on Apache with suitable AllowOverride permissions. Other servers need equivalent rules, including no script execution and no direct document access. Test real HTTP denial; merely copying a rule file is insufficient.

## 4. Database and recovery

The current application expects the reviewed baseline plus additive migrations 027-030. Inspect each migration and its documented CLI interface before applying; do not replay all historical migrations blindly. Migration 030 allows incomplete Faculty personal details during onboarding.

Before any import/migration, obtain the exact target and approval, make a coordinated database/uploads/configuration backup, and rehearse restoration to a separate database/storage location. Verify schema, representative content, uploads, account login, and recipient relationships after restoration. Keep workers disabled in the restoration environment to prevent real deliveries.

The [fresh-install rehearsal](fresh-install-bootstrap.md) originally covered a 62-table scratch baseline. It is not a turnkey installer for the current 65-table production application. Choose and rehearse the current installation path before launch.

## 5. Staging acceptance

- Run production preflight under the selected host's actual configuration and Composer platform checks.
- Verify blocked internal URLs and upload execution/direct-download protection return an appropriate denial response.
- Complete Admin, Faculty, Student and Parent browser walkthroughs; record results in the [evaluation checklist](evaluation-checklist.md). Current user-reported Admin/Student testing is encouraging; full Faculty/Parent acceptance remains unrecorded.
- Verify Faculty temporary-password change, legal consent, profile completion, assigned scope, submission/review and rejection/resubmission.
- Verify both Parent paths: existing Student relationship and child without login; read-only pending claims, approval/rejection, eligible content, linking and revocation.
- Check notification destinations, read state, category/master preferences, real password recovery, and controlled SMTP/browser-push delivery using approved recipients.
- Schedule the existing combined worker, verify exit status/logs, scheduled publication, outbox retry/deduplication and profile-cycle reminders. Avoid duplicate schedulers.
- Check mobile/desktop critical flows, failed network requests, console/server errors, and database query behavior with representative data.
- Record the restore test and rollback procedure. Configuration passes alone do not prove recovery or live delivery.

## Safe local verification commands

Run from the project directory; substitute the host's PHP executable where appropriate:

```powershell
& C:/xampp/php/php.exe scripts/check_deployment_configuration.php
& C:/xampp/php/php.exe scripts/check_deployment_configuration.php --production
& C:/xampp/php/php.exe scripts/check_database_baseline.php
& C:/xampp/php/php.exe tests/deployment_configuration.php
& C:/xampp/php/php.exe tests/database_configuration.php
& C:/xampp/php/php.exe tests/scheduler_monitoring.php
& C:/xampp/php/php.exe tests/notification_action_urls.php
& C:/xampp/php/php.exe tests/request_errors_http.php
& C:/xampp/php/php.exe tests/session_security_http.php
& C:/xampp/php/php.exe tests/document_upload_http.php
composer check-platform-reqs --no-dev
git diff --check
```

The first three commands inspect configuration/schema. The listed tests use their documented isolated fixtures. Composer platform checking is required on the host and was not run in this checkpoint. The combined dispatch command changes records and can send real messages; it is deliberately not listed as a safe preflight.

## Launch gate

Launch only after the host-specific configuration, protected runtime package, staging acceptance, real delivery, scheduled-worker monitoring, and restoration rehearsal are recorded and reviewed. Start with invited evaluators if that is the agreed scope; expand access after resolving material findings. No deployment, purchase, server change, database migration, or account reset was performed by this preparation task.
