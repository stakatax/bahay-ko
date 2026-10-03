# Tests

These are developer verification scripts, not application pages or features. They run only when explicitly invoked and help catch regressions after changes. Keeping them does not add work to normal page requests.

| Script | What it checks | Side effects |
| --- | --- | --- |
| `database_baseline.php` | The schema comparison utility correctly detects differences. | Reads the schema snapshot; no database connection or writes. |
| `database_configuration.php` | Credential rules, local connection, safe CLI/HTTP errors. | Read-only database connection; isolated temporary error log and subprocesses. |
| `faculty_scope.php` | Faculty scope, assignment save/reload, provisioning, draft review guards. | Synthetic records in connection-local temporary tables; existing records are not updated. |
| `faculty_scope_ui.js` | Assignment selectors, confirmation, duplicate submission, posting scope controls. | Simulated DOM in Node; no database/network writes. Not a real browser test. |
| `faculty_scope_http.php` | HTTP authorization/CSRF/method checks and rendered forms. | Reads existing metadata; temporary CGI harness/session files outside the webroot. Mutation requests stop before existing-record writes. |
| `content_access.php` | Target eligibility, engagement, and document download authorization. | Synthetic temporary-table records and one random temporary upload file, cleaned up afterward. Notifications are replaced with a test double. |
| `content_access_http.php` | Download/engagement HTTP guards and file delivery. | Reads metadata/existing document bytes; isolated temporary CGI/session files. No existing-record changes. |

## Running locally

From `C:/xampp/htdocs/bahay-ko`, choose the test relevant to the change:

```powershell
& C:/xampp/php/php.exe tests/database_baseline.php
& C:/xampp/php/php.exe tests/database_configuration.php
& C:/xampp/php/php.exe tests/faculty_scope.php
node tests/faculty_scope_ui.js
& C:/xampp/php/php.exe tests/faculty_scope_http.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe tests/content_access_http.php
```

A successful script prints `PASS`. These tests require the local/staging environment and, depending on the script, the current schema, suitable catalog/account/document metadata, PHP CGI, temporary-table privileges, or Node. A fixture/configuration failure does not automatically mean the application is broken.

Temporary tables exist only for the test connection and disappear when it closes. If their creation fails, the fixture tests stop before inserting test data. Ordinary cleanup removes the files each test created. An abruptly terminated process can leave temporary files requiring review.

Use these tests in development or a dedicated staging environment, not against production. Keep them in source control for maintenance, and exclude `tests/` from the public production package. All PHP test entry points currently reject browser execution with HTTP 404 before setup. The JavaScript test is a Node script and is not loaded by the application.

Passing tests demonstrates the checked cases, not a guarantee that every feature is unchanged. Browser layout, keyboard/modal behavior, complete role journeys, actual email/push delivery, scheduling, and backup recovery still require their own verification.

The C5 scratch database `olshco_c5_verify_20260923` is separate from these temporary-table tests. It was created only for an explicitly approved schema restore and remains empty of data. The application still uses its original database.

## Fresh-install rehearsal test

`php tests/fresh_install_bootstrap.php` is separate from the temporary-table tests. It requires the explicitly approved, empty `olshco_c5_verify_20260923` database. It inserts configuration and a synthetic Administrator inside transactions, checks normal model lookups, repeated execution and failure handling, then rolls back every record. It refuses the application's database. Scratch auto-increment counters may advance even after rollback. This test does not create a permanent Administrator or send notifications.

## Session security tests (H1)

`php tests/session_security.php` checks revocation and refreshed academic scope using synthetic accounts in connection-local temporary tables. `php tests/session_security_http.php` checks HTTP redirects/JSON responses, session rotation, the normal login session initializer, and the front-controller guard using isolated CGI sessions. `tests/support/session_fixture.php` is their internal fixture helper, not a standalone test. No existing accounts are modified. Detailed scope and browser checks are in `docs/session-security-verification.md`.

## Survey privacy tests (H2)

`php tests/survey_privacy.php` checks five-person suppression across survey/question types, choice complements, distinct respondents, identifier removal, escaped HTML, role/ownership access, and invalid IDs. It uses synthetic rows in five connection-local temporary tables; permanent responses are unchanged. See `docs/survey-privacy-verification.md` for commands and browser checks.

## Publication notification durability (H3)

`php tests/publication_durability.php` checks transactional publication, queue failure rollback, partial notification delivery/retry, deduplication/read state, claim expiry, preferences, and real service/release paths using temporary tables. `php tests/publication_outbox_schema.php` checks the queue definition independently. Neither sends email or push. See `docs/publication-notification-readiness.md` for results, safe checks, and the operational worker command (which is not a test).

## Request error handling (H4)

`php tests/request_errors_http.php` injects synthetic failures through isolated CGI entry points and actual controller/bootstrap paths. It verifies HTML/JSON/status behavior, login flash privacy, partial-output cleanup, logging, and preserved validation without application writes. See `docs/request-error-verification.md` for scope and deployment limitations.

## Document upload protections (H5)

`php tests/document_upload_http.php` sends real multipart uploads through isolated CGI, checks format/MIME/size validation and storage, and verifies replacement success/rollback with connection-local temporary tables. `php tests/upload_directory_http.php` checks local XAMPP Apache with uniquely named inert files in uploads and removes those files. Neither modifies existing records or sends notifications. The Apache test requires http://127.0.0.1/bahay-ko. See `docs/document-upload-verification.md` for commands, dependencies, coverage and manual checks.

## Deployment configuration (H6)

`php scripts/check_deployment_configuration.php` inspects settings without connecting to the database or sending anything; use `--production` with deployment settings for strict readiness checks. `php tests/deployment_configuration.php` exercises missing/complete configurations, production requirements, secret-safe output, HTTP denial and lazy Web Push loading with isolated synthetic files. See `docs/deployment-configuration.md` for the complete inventory, examples, limits and worker setup.

## Request performance (M1)

`php tests/request_performance.php` compares original and batched engagement/participation results, measures statement counts, and tests the request publishing pre-check with connection-local temporary tables. It sends no external notifications and changes no existing records. See `docs/request-performance-verification.md` for results, exact files and remaining staging checks.

## Workspace counts (M2)

`php tests/workspace_counts.php` checks uncapped totals, all scheduled content types, owner isolation, document filtering before the list cap, and controller output using connection-local temporary tables. No existing records or deliveries are changed. See `docs/workspace-counts-verification.md` for results and commands.

## Scheduler monitoring (M3)

`php tests/scheduler_monitoring.php` exercises worker exit reporting and the real Windows hidden launcher against inert temporary fixtures. It covers failures, overlapping runs, log rotation/write failure and exit-code propagation without database connections, deliveries or scheduled-task changes. See `docs/scheduler-monitoring.md` for the two existing registrations, approved-scope code fixes, and completed registration cleanup.

## Abuse protection (M4)

Run `php tests/abuse_protection.php` for temporary-table limiter, login, Student/Parent registration and rollback checks. Run `php tests/abuse_protection_http.php` for real controller/service integration with isolated CGI fixtures: HTTP 429/Retry-After, role coverage, validation/CSRF, expiry and storage failures. No existing records or external deliveries are changed. Migration 028 is applied locally; see `docs/abuse-protection-verification.md` for thresholds, commands and deployment requirements.

## Global guards and HTTPS (M5)

Run `php tests/global_guards_http.php` for request-aware global guards and actual cookie flags in isolated CGI. It uses the current index prefix with a stubbed survey lookup and temporary accounts, stopping before route dispatch or publishing. See `docs/global-guards-verification.md` for coverage and the host-specific HTTPS decision still needed before deployment.

## Parent registration without a child login

See [verification and migration status](../docs/parent-registration-verification.md). Run `php tests/parent_child_registration.php`, `php tests/parent_child_http.php`, `node tests/parent_child_ui.js`, and `php tests/parent_child_views.php`. The database suite uses temporary tables; the HTTP suite uses PHP CGI, a private temporary directory and a service stub. No real delivery or existing-data edits are performed. Migration 029 is installed locally; visual browser acceptance remains separate.

## Deployment re-audit input and content validation

Run `php tests/request_input.php`, `php tests/form_input_validation.php`, `php tests/academic_input_validation.php`, `php tests/rich_text_sanitization.php`, `php tests/browser_push_input.php`, and `php tests/push_endpoint.php`. These focused tests exercise malformed shapes/IDs, password preservation, nested HTML sanitization and push payload/destination/key validation without changing operational records or sending notifications. Controller fixtures may need access to PHP session storage; push key validation requires OpenSSL. Existing HTTP abuse and request-error suites also cover malformed authentication fields and 404 responses. See [the deployment re-audit](../docs/deployment-re-audit-2026-10-02.md) for results and remaining staging checks.
