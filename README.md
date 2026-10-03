# OLSHCO Digital Hub

OLSHCO Digital Hub is a school information and content-management system built with PHP, MySQL/MariaDB, JavaScript and CSS. The repository directory is `bahay-ko`; its scope extends beyond the original institutional landing page.

This README describes the implementation reviewed on **2 October 2026**. The current focus is stabilization, security, deployment readiness and evaluation. Local verification does not establish production readiness or independent certification.

## Documentation for evaluation and users

- [System evaluation guide](docs/system-evaluation.md): objectives, architecture, permissions, security, testing evidence, installation and operations.
- [Separate user manual](docs/user-manual.md): task instructions for Administrator, Faculty, Student and Parent.
- [Database reference](docs/database-reference.md) and ER diagram source (local only: `docs/database-schema.mmd`): 65 tables, 624 columns, indexes and declared relationships.
- DFD, use-case and UML diagrams (local only: `docs/system-diagrams.md`): current process, role, class, publication sequence and content-state models.
- [Latest deployment re-audit](docs/deployment-re-audit-2026-10-02.md): fixed issues, 47 regression suites and remaining launch gates.
- [Complete route reference](docs/routes-reference.md): all 86 registered pages and actions.
- [Evaluation checklist](docs/evaluation-checklist.md) and [fillable results CSV](docs/evaluation-results.csv): 56 acceptance cases plus browser/accessibility coverage and evidence fields.

## Features and roles

The system supports public information pages; account registration and approval; announcements, events, documents and surveys; recipient targeting; Faculty review workflows; engagement; notifications; Student profile questionnaires and update cycles; government-advisory review; analytics, exports and activity logging.

| Role | Main capabilities and boundaries |
| --- | --- |
| Guest | Public pages, sign-in, Student/Parent registration and password recovery. |
| Student | Eligible content, enabled engagement and surveys, own account and Student profile responses. No access to other students' private responses or management tools. |
| Parent | Eligible content through verified linked-student relationships, own account and permitted participation. A relationship does not grant access to private Student questionnaire responses. |
| Faculty | Create and manage permitted content, submit for Administrator review, and access authorized staff functions. No self-approval or direct publishing. College Faculty are limited to their assigned program; IBED Faculty to their assigned education level. |
| Administrator (`Admin`) | User and academic administration, Faculty assignments, content review/publishing, schoolwide or specific recipient targeting, profile-cycle management, advisory review and authorized analytics. |

Permissions and recipient eligibility are checked on the server. The current IBED rule distinguishes Elementary, Junior High and Senior High; it does not permit all IBED levels simply because an account belongs to IBED. See [Faculty scope evidence](docs/faculty-scope-verification.md) and [content-access evidence](docs/content-access-verification.md).

The [Parent registration extension](docs/parent-registration-verification.md) is implemented and tested, with migration 029 applied locally on 26 September 2026. It supports verified child enrollment without requiring a child login; existing links remain supported. Active views are in the root `pages/` directory. The unused `config/pages/` copies were archived outside the web root on 28 September 2026; see the Home feed and cleanup plan (local only: `docs/home-feed-plan.md`).

## Main workflows

- **Accounts:** Student/Parent registration requires approval. Parent relationships require verification. Sign-in supports Student ID or email, account lockout and server-side rate limits. Password recovery uses expiring, single-use tokens and email delivery.
- **Content:** announcements, events, documents and surveys use drafts, review, rejection, scheduled/published and archived states. Faculty submissions require Administrator approval. Editing, restoration and targeting remain subject to role, ownership and workflow restrictions.
- **Engagement:** eligible users may view, react, comment/reply or acknowledge where enabled. Active code uses the unified `content_view`, `content_reaction`, `content_comment` and `content_acknowledgment` tables. Historical announcement-specific tables remain preserved.
- **Notifications:** publication creates a durable notification-outbox entry in the publication transaction. Dispatch, email and browser push use preferences, recipient eligibility and deduplication. Successful queueing is not proof of external delivery.
- **Surveys:** participation uses a normal POST form. Results require five distinct respondents per survey/question and suppress small choice groups, with no Administrator bypass. This reduces disclosure risk; it does not guarantee anonymity of free text.
- **Student profiles:** versioned questionnaires, draft-only question editing, update cycles, assignments, consent and version-specific responses are separate from content surveys.
- **Government advisories:** Administrator review and conversion into targeted announcements; advisory management is not a Student function.

## Architecture and repository map

Browser requests enter [index.php](index.php), which applies session and global guards, checks page/action routes, dispatches controllers and renders page views. The application generally follows:

```text
Browser -> index.php -> Controller -> Service -> Model -> Database
                          |             |
                          +---- response/view data ----> PHP page or JSON

Scheduled worker -> publishing/reminders/notification queues -> delivery services
```

| Location | Responsibility |
| --- | --- |
| [index.php](index.php) | Front controller, route groups, guard responses and page asset selection. |
| [app/controllers](app/controllers/) | Request/session input, method and CSRF checks, responses and redirects. |
| [app/services](app/services/) | Business rules, workflows, scope, validation and orchestration. |
| [app/models](app/models/) | MySQLi queries and database operations. |
| [config](config/) | Database, session/security, error handling, activity logging and delivery configuration. |
| [pages](pages/) | Server-rendered views. |
| [include/sidebar.php](include/sidebar.php) | Shared navigation used by the main shell. Authentication pages use a separate minimal shell. |
| [Assets/css/root.css](Assets/css/root.css) | Shared visual foundation; UI changes must follow its typography, colors and component conventions. |
| [Assets/js](Assets/js/) | Page behavior and shared interactions. Survey participation currently needs no dedicated script. |
| [Assets/uploads](Assets/uploads/) | Runtime storage with access restrictions; document delivery goes through authorization. |
| [database](database/) | Numbered migrations, additive schema baselines and reviewed bootstrap material. |
| [scripts](scripts/) | CLI verification, setup and operational workers. Some scripts perform real operations; read their guide before running. |
| [tests/README.md](tests/README.md) | Focused regression suites, fixtures and test prerequisites. |
| [docs](docs/) | Deployment guidance and evidence from readiness changes. |

Older per-folder READMEs may describe earlier versions; they are not a complete current API reference. Use this README, the linked verification reports and actual code as the starting point.

## Routes

Routes use `index.php?page=...`. The complete registry is the route groups and switch in [index.php](index.php); the following are representative, not an exhaustive authorization matrix.

| Route | Purpose |
| --- | --- |
| `home`, `about`, `contact`, `academic` | Public information. |
| `login`, `register`, `forgot_password`, `password_reset` | Account entry and recovery. |
| `news`, `calendar`, `notifications`, `account_profile` | Authenticated browsing and account functions. |
| `postings`, `content_workspace` | Staff content creation and workflow. |
| `survey_participate`, `survey_results` | Participation and authorized results. |
| `student_profile`, `student_profile_management` | Own Student profile and Administrator management, respectively. |
| `dashboard`, `account_approvals`, `manage_users`, `academic_management`, `government_advisories` | Administrator functions. |
| `login_action`, `register_action`, `post_store`, `survey_submit_response` | Form actions; their controllers enforce method and validation requirements. |
| `content_open`, `content_react`, `content_comment`, `content_acknowledge` | JSON engagement endpoints. |
| `document_download` | Authorized document GET/HEAD delivery. |

**Known routing cleanup remains:** the unknown-route fallback is duplicated and currently selects Home. A dedicated unknown-route 404 response has not yet been implemented. This documentation update does not change routing behavior.

## Runtime and local setup

[composer.json](composer.json) requires **PHP ^8.2** (compatible PHP 8.x starting at 8.2). Use [composer.lock](composer.lock) for dependency versions; PHP 7.4 is not supported. Dependencies include PHPMailer, Web Push support and PDF parsing.

The verified local environment is XAMPP at `C:\xampp\htdocs\bahay-ko`, with database `olshcodb`. PHP capabilities checked by the preflight include `mysqli`, `fileinfo`, `mbstring`, `openssl`, `curl`, DOM/libxml, Phar and sessions. Confirm the locked dependencies' platform requirements for both web and CLI PHP.

1. Place the application in the intended local web directory and install locked dependencies using `composer install`. Use `composer check-platform-reqs` to verify the runtime.
2. Configure database settings, a stable application key, canonical application URL, email and browser push as described in [config/README.md](config/README.md) and [deployment configuration](docs/deployment-configuration.md). Use the provided `.local.php.example` files for development. There is no automatic dotenv loader; do not commit secrets.
3. For a new database, follow the [schema baseline guide](docs/database-baseline-readiness.md) and [fresh-install rehearsal](docs/fresh-install-bootstrap.md). The rehearsal tool is restricted to its named scratch database; it is not a general production installer. Review school reference data, legal text and Administrator provisioning separately.
4. Preserve the original 62-table [schema snapshot](olshcodb-structure.sql), then account for additive migrations 027 (publication notification outbox), 028 (request rate limits), and 029 (verified child records). The current expected schema has 65 tables. Do not import the destructive snapshot over a populated database or blindly replay historical migrations.
5. Configure writable uploads/session/temp/log locations and web-server access restrictions using the deployment guide. Open `http://localhost/bahay-ko/index.php` for local development.

Safe inspection commands from the project root (use `C:/xampp/php/php.exe` if PHP is not on PATH):

```powershell
php scripts/check_deployment_configuration.php
php scripts/check_database_baseline.php
```

The configuration check makes no database connection or delivery attempt. The baseline check reads database metadata and compares definitions; it does not install or migrate the database. Neither proves SMTP delivery, browser push, external HTTPS or complete installation.

## Security and error responses

Implemented controls include password hashing, account/session validation, session regeneration, CSRF checks, server-side permissions and targeting, rate limits, upload validation, restricted document access and survey-result suppression. Their evidence and limitations are linked below.

Cookies use HttpOnly, SameSite=Lax and conditional Secure. HTTPS detection trusts the web server's `HTTPS` state, not client forwarding headers. Hosting has not been selected; TLS-proxy integration and external cookie verification remain deployment work.

There is a **shared custom error screen**, rendered by [config/request-errors.php](config/request-errors.php), using [root.css](Assets/css/root.css) and [request-error.css](Assets/css/request-error.css). It gives safe messages and, for internal failures, a reference identifier. AJAX failures receive JSON. Rate limits return 429 with Retry-After. Global requirements return JSON to AJAX clients and normal redirects for page navigation.

This is not yet a complete set of dedicated 403/404/419/429/500 pages. Some controllers return plain text or form redirects; unknown routes still resolve to Home. Apache-level errors and failures before PHP starts require separate server configuration.

Activity logging uses [config/logging.php](config/logging.php) and the configured action catalog. Do not assume every request is an audit event; catalog completeness is part of installation verification. Request diagnostics and worker logs are separate from business activity records.

## Workers and deployment

[scripts/dispatch_browser_push.php](scripts/dispatch_browser_push.php) is the combined publishing, reminder and notification-delivery worker. Running it can publish due content and send queued messages; it is not a harmless test command.

The latest local scheduler report records one enabled minute-based task, `OLSHCO Digital Hub Worker`, and a disabled duplicate. The hidden launcher waits for PHP and propagates its exit status. This local task uses an interactive account; unattended production scheduling is not established by that configuration. See [scheduler monitoring](docs/scheduler-monitoring.md) and [publication durability](docs/publication-notification-readiness.md).

Before deployment, verify the selected host's HTTPS/proxy behavior, least-privilege credentials, schema/reference data, private storage and logs, backup/restore, worker identity, actual email/push delivery and the complete browser/role matrix. Exclude credentials, database dumps, archives and development artifacts from the public package. Deployment has not been performed by these readiness changes.

## Evidence for IT evaluation

Use the following reading order to connect system claims with implementation and repeatable checks. Reports describe the checks run at the time of each change; they are not an independent evaluation or a claim that all scenarios have been retested against every later edit.

| Evaluation area | Evidence and verification guide |
| --- | --- |
| Installation and schema | [Deployment configuration](docs/deployment-configuration.md), [database baseline](docs/database-baseline-readiness.md), [bootstrap rehearsal](docs/fresh-install-bootstrap.md) |
| Role and recipient boundaries | [Content access](docs/content-access-verification.md), [Faculty scope](docs/faculty-scope-verification.md) |
| Authentication and abuse controls | [Session security](docs/session-security-verification.md), [rate limits](docs/abuse-protection-verification.md) |
| Privacy and uploads | [Survey privacy](docs/survey-privacy-verification.md), [document validation/storage](docs/document-upload-verification.md) |
| Errors and request behavior | [Request failures](docs/request-error-verification.md), [global guards and HTTPS](docs/global-guards-verification.md) |
| Reliability and operations | [Publication notifications](docs/publication-notification-readiness.md), [scheduler monitoring](docs/scheduler-monitoring.md) |
| Performance and counts | [Request performance](docs/request-performance-verification.md), [workspace counts](docs/workspace-counts-verification.md) |
| Running regression suites | [Test catalog and prerequisites](tests/README.md) |

For formal evaluation, record the application revision, PHP/database versions, environment, role/scenario, expected behavior, actual result and evidence for each test. Use designated evaluation accounts/data. Verify both permitted and denied access, review/publishing, targeted visibility, survey privacy, upload rejection, recovery, queued delivery and error feedback. Keep real credentials and personal Student responses out of submitted evidence.

The linked documentation package is ready for review. Manual evaluation cases remain **Not run** until an evaluator records the actual results. Browser/accessibility execution, evaluator sign-off, host-specific deployment checks and full backup/restore acceptance remain outstanding; historical automated evidence is labeled separately.
