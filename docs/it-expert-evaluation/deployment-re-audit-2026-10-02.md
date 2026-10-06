> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Deployment re-audit ? 2 October 2026

This audit covers the current local application, route registration, input boundaries, focused security fixes and automated regressions. It is not a production certification or evidence that every page has been manually exercised in every browser. Existing accounts, operational records, historical tables and schema were preserved. No deployment or real notification delivery was performed.

## Findings fixed

| Finding | Root cause and resulting behavior | Relevant files |
| --- | --- | --- |
| Malformed authentication fields could fail before normal error handling | Controllers trimmed or cast untrusted arrays before validation; login error handling could use an uninitialized identifier. Method, CSRF and scalar checks now precede extraction; feedback retains only scalar, non-password values. | AuthController.php, BaseController.php, PasswordRecoveryController.php |
| Inconsistent HTTP field shapes and numeric identifier coercion | Individual controllers could cast values such as `12abc` to record 12 or arrays to integers. A shared boundary now rejects unexpected arrays, excessive nesting, invalid UTF-8/NUL text and malformed numeric record identifiers before session/database work. Academic management also validates positive IDs directly. | config/request-input.php, index.php, AcademicManagementController.php, AcademicManagementService.php |
| Nested rich text could retain executable markup | Removing unsupported wrapper elements skipped sanitizing their children. PHP, the editor and the content reader now sanitize children before unwrapping. Script elements, event handlers and unsafe links are removed while supported formatting is preserved. The reader also protects against previously stored unsafe markup; historical records were not rewritten. | PostService.php, Assets/js/posting.js, Assets/js/news.js |
| Unknown routes silently opened Home | Duplicate fallback logic concealed mistyped or obsolete URLs. One explicit not-found exception now produces HTTP 404 through the existing safe HTML/JSON error handler. | index.php, config/request-errors.php |
| Browser-push JSON accepted malformed field types | Nested subscription and key values were cast without a type contract. Invalid shapes now receive normal validation failures before storage. | BrowserPushController.php |
| Browser-push endpoints could target arbitrary HTTPS servers | HTTPS-only validation allowed user-controlled outbound destinations. Registration and queued delivery now restrict endpoints to supported push provider hosts, disallow credentials/fragments/unexpected ports, and validate subscription key lengths, encoding and P-256 public points before saving. Existing local subscription remains compatible. | config/push-endpoint.php, PushSubscription.php, BrowserPushDeliveryService.php |

Input validation does not rewrite passwords or indiscriminately strip ordinary text. Existing service-level requirements, length limits, enum checks, prepared statements, authorization, CSRF, file validation and output escaping remain responsible for their respective contexts. Legitimate collection fields are explicitly permitted for recipient scope, survey questions/answers, profile responses/interests, question options and notification category preferences. Empty/zero selections still reach existing required-field validation. School-issued Student IDs remain textual identifiers.

Supported push destinations are Google FCM, Mozilla push services, Apple push and Microsoft WNS. Provider references: [Google example](https://web.dev/articles/codelab-notifications-push-server?hl=en), [Mozilla Services](https://blog.mozilla.org/services/page/2/), [Apple Web Push](https://developer.apple.com/documentation/usernotifications/sending-web-push-notifications-in-web-apps-and-browsers), [Microsoft WNS](https://learn.microsoft.com/en-us/windows/apps/develop/notifications/push-notifications/wns-overview). Additional providers need an explicit reviewed allowlist update. The installed PSR-18 Guzzle path disables redirect following. No outbound push request was made during verification.

## Route and page inventory

All 86 unique registered page/action routes have matching switch handlers: 8 public pages, 8 authenticated pages, 5 staff pages, 6 Administrator pages and 59 action routes. No unregistered literal `index.php?page=...` links were found in pages, includes or application JavaScript. Eleven literal local asset references inspected in pages/includes resolve to existing files; dynamic assets, uploaded files and external/CDN availability require browser checks.

| Access group | Pages inspected through route/source inventory |
| --- | --- |
| Public | Home, About, Contact, Academics, Login, Registration, Forgot password, Password reset |
| Authenticated | Information Hub, Calendar, Survey participation, Notifications, Student profile, My Account, Legal reconsent, Required password change |
| Staff | Create content, Content workspace, Survey results, Department analytics, Department content preview |
| Administrator | Dashboard, Account approvals, User management, Academic management, Student profile management, Government advisory management |

Role visibility is not authorization: controller/service checks and targeted-content eligibility remain enforced. Faculty review and academic scope, Parent linkage privacy, five-respondent survey suppression, publication durability and notification preferences were covered by their existing regression suites. The advisory management subsystem remains available to Administrators; normal posting continues to use attached advisories.

## Verification evidence

- **220 syntax checks passed:** PHP sources in app/config/include/pages/scripts/tests plus index.php; application JavaScript in Assets/js.
- **47 regression suites passed:** [machine-readable suite results](../deployment-audit-results.json). Coverage includes authentication/throttling, session security, global guards, request errors, uploads, content access, Faculty scope, Parent verification, publishing/outbox, survey privacy, profile cycles, notifications, scheduler monitoring, database configuration and focused new input/security tests.
- **8 isolated Chrome cases passed:** the actual editor and reader sanitizers remove nested unsafe markup and preserve supported formatting/links.
- **5 actual local HTTP checks passed:** three malformed requests return safe 422 responses; an unknown route returns 404 in HTML and JSON. No operational mutation was triggered.
- Existing local push subscription passed the stricter endpoint/key validation in a read-only compatibility check.

Some tests require PHP CGI session storage or temporary upload access. Sandbox-only failures were rerun with access to those local fixture directories and passed cleanly. Database suites use isolated temporary tables/fixtures; worker tests use inert fixtures rather than sending messages or changing scheduled registrations. Detailed test behavior is documented in [tests/README.md](../../tests/README.md).

## Cleanup

Removed duplicate unknown-route fallback code. Removed the unreferenced zero-byte `dev/seed_activity.php` and temporary audit inventories/results and error-page preview generated during local checking. Kept useful CLI tools, tests, active pages, the advisory subsystem, uploaded assets whose ownership/use is uncertain, and historical engagement tables. No speculative legacy deletion was performed.

## Remaining flags and deployment requirements

1. **Production configuration is incomplete.** Read-only production preflight reports three failures in the current local process: dedicated production database credentials, a stable explicit application key of at least 64 characters, and a canonical HTTPS application URL. Configure these on the chosen host; do not commit secrets.
2. **Host access restrictions need verification.** There is no root .htaccess in this workspace. The deployed server must deny direct access to internal application/configuration directories, development/test scripts, SQL dumps, logs, backups, secrets and repository metadata. Existing upload directory restrictions must also be verified on that host. No server/deployment configuration was changed here.
3. **Fresh-install and migration rehearsal remains required.** The fresh-install bootstrap suite was deliberately excluded because it needs a separately approved scratch database. Validate installation plus migrations, then perform a backup/restore rehearsal before launching. Do not use the operational database for this exercise.
4. **Contact inquiry form remains a deferred feature.** Its `action="#"` has no durable inquiry submission backend. Resolve or remove the advertised submission action before production; the earlier decision to revisit inquiries was preserved.
5. **Real delivery and host operations remain unverified.** Exercise email and browser push, publication/profile-cycle workers, retries, deduplication, HTTPS cookies, proxy configuration, storage permissions and scheduled execution on staging with the selected host.
6. **Complete the manual role walkthrough.** Test Admin, Faculty, Student and Parent flows using the intended accounts on desktop and mobile, including keyboard/modal behavior, invalid inputs, direct URL tampering, empty IDs, browser console/network failures and recipient eligibility. Automated fixtures cannot establish complete visual usability or production behavior.

The code fixes are locally verified. Hosting readiness remains conditional on these configuration, installation and staging checks. No guarantee that all possible defects have been found is implied.
