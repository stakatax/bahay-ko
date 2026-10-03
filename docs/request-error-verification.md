# H4: safe request failures

Implemented 2026-09-24. No database, publishing, role/scope, CSRF, session, or deployment configuration changes.

## Behavior

`index.php` installs the request handler before security/session/database/controller initialization. Uncaught internal failures return HTTP 500 with generic text and a request reference. Explicit validation/authorization exceptions retain 422/403 handling. Known JSON routes, Accept: application/json, and XMLHttpRequest receive JSON rather than an HTML error page.

Warnings are logged without contaminating responses or changing existing control flow. Uncaught exceptions, PHP errors and runtime fatal errors get safe responses. Buffered partial page/JSON output and stale redirect/download headers are discarded. Ordinary downloads/exports remain unbuffered to avoid holding complete files in memory. Internal errors caught by controllers use the same safe-message policy in rendered data, redirects, session flash and JSON. Unexpected errors previously mislabeled as validation/conflict errors in the affected JSON handlers now return 500; expected validation keeps its existing status.

User messages preserve existing InvalidArgumentException/DomainException feedback and an explicit reviewed list of legacy RuntimeException business-rule messages. Numeric login attempt/lockout feedback is preserved through narrowly anchored patterns. Unknown runtime/database/programming errors are never passed through merely because of their exception type or message prefix. When adding new public validation, use the explicit validation types or deliberately extend the reviewed list; do not expose arbitrary caught exceptions.

Logs include the request reference, exception class, code and source file/line. The new diagnostic helper deliberately excludes raw exception text, SQL, URLs, request bodies, cookies and stack arguments. Existing deeper service/server logs may still contain their own diagnostics. References correlate displayed failures with server logs. The error page uses root.css typography/color/border/radius tokens and a keyboard-visible home link.

## Files changed

- `index.php`: early handler installation.
- `config/request-errors.php`: shared message/status policy, logging, response negotiation, exception/warning/fatal handling.
- `config/public-error-messages.php`: reviewed legacy business feedback.
- `Assets/css/request-error.css`: minimal error-page styling using root.css.
- `tests/request_errors_http.php`: isolated CGI regression harness.
- `tests/README.md` and this document.

Controllers (BaseController loads the shared helper; the others route caught messages through it):

- `app/controllers/AcademicController.php`
- `app/controllers/AcademicManagementController.php`
- `app/controllers/AccountApprovalController.php`
- `app/controllers/AccountProfileController.php`
- `app/controllers/AnnouncementController.php`
- `app/controllers/AuthController.php`
- `app/controllers/BaseController.php`
- `app/controllers/BrowserPushController.php`
- `app/controllers/ContentEngagementController.php`
- `app/controllers/ContentWorkspaceController.php`
- `app/controllers/DepartmentAnalyticsController.php`
- `app/controllers/DocumentDownloadController.php`
- `app/controllers/GovernmentAdvisoryController.php`
- `app/controllers/NotificationController.php`
- `app/controllers/PasswordRecoveryController.php`
- `app/controllers/PostController.php`
- `app/controllers/StudentProfileController.php`
- `app/controllers/StudentProfileManagementController.php`
- `app/controllers/SurveyController.php`
- `app/controllers/UserManagementController.php`

Original files backed up outside the webroot: `C:/Users/ctrlc/.codex/backups/bahay-ko/request-errors-20260924-090908`. Diff review confirmed controller edits are confined to error-message/status handling and the shared include.

## Verification

```powershell
& C:/xampp/php/php.exe tests/request_errors_http.php
& C:/xampp/php/php.exe tests/faculty_scope.php
& C:/xampp/php/php.exe tests/faculty_scope_http.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe tests/content_access_http.php
& C:/xampp/php/php.exe tests/survey_privacy.php
& C:/xampp/php/php.exe tests/session_security.php
& C:/xampp/php/php.exe tests/session_security_http.php
& C:/xampp/php/php.exe tests/publication_durability.php
git diff --check
```

Observed: 79 error HTTP checks; Faculty 158/37, content access 124/95, survey privacy 166, sessions 35/105, publication durability 74: **873 checks passed**. Syntax checks passed for index, both new configuration files, the new test, and all controllers. Whitespace checks passed with existing line-ending warnings only.

The error harness verifies safe HTML/JSON, SQL/runtime/type/fatal failures, removal of partial output/stale headers, warning-free JSON, unchanged success, validation escaping/statuses, attachment failure behavior, actual index bootstrap failure before database access, actual guest document denial, sanitized login flash/redirects, caught engagement failure status, and server diagnostic logs. CGI/session/log artifacts are isolated outside the webroot and removed afterward. Existing application data is not changed; related database tests use their established temporary fixtures. No test email/push is sent.

## Remaining deployment/browser checks

- Visually check the error page at mobile/desktop widths and keyboard focus. Browser layout verification was not available here.
- Configure PHP/Apache display_errors and display_startup_errors off at deployment. A PHP runtime handler cannot hide startup/configuration errors or a syntax error in the entry file before its first statement executes. Server/proxy error pages remain deployment work.
- A response already streamed/flushed cannot be retracted or have its status rewritten. After headers are sent the handler logs and stops adding output, rather than appending diagnostic text to downloads. Do not deliberately flush sensitive partial page data before work succeeds.
- Configure private server log storage, rotation and monitoring; no logging destination or OS/server settings were changed.
- The framework cannot automatically roll back operations that already committed before an unrelated later error. The response does not automatically resubmit a failed form. Existing transaction behavior, including H3's durable queue, remains in place.
