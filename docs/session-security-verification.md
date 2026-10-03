# H1: session account revocation

The old request path checked inactivity and session age but trusted the account role saved at login. The new guard verifies the current account before route role snapshots, controllers, publishing work, and protected content.

## Behavior

- Active Admin, Faculty, Student, and Parent sessions continue normally.
- Missing/deleted, inactive/pending/rejected, invalid-role, changed-role, and changed-password accounts lose their old session on their next request.
- Revocation clears the full session, including CSRF, and regenerates the session ID. Normal page requests redirect to login; JSON/AJAX requests return JSON 401.
- Current academic assignment IDs/names and the required-password-change flag refresh on each authenticated request.
- A lookup failure stops the request with a safe 503 rather than trusting stale authorization. It retains the session for a retry.
- Login records a SHA-256 fingerprint of the stored password hash in the server-side session, not the password or the original hash. Password reset/change invalidates old fingerprints even when changes share a timestamp. A completed first-login password change records the new fingerprint, regenerates the session ID, and clears the previous CSRF token.
- **Sessions created before this change must sign in once again.** Missing fingerprints are deliberately not upgraded from an unverified legacy session.
- Public guest requests do not perform the account lookup. Authenticated requests add one prepared lookup through the existing connection.

## Exact files

- `index.php`: calls the guard before controllers and route initialization.
- `app/controllers/AuthController.php`: records the credential fingerprint and rotates the session after required password changes.
- `app/models/SessionAccount.php` (new): narrow prepared account/academic snapshot query; returns only the password fingerprint.
- `app/services/SessionSecurityService.php` (new): compares session authority and refreshes scope.
- `config/authenticated-session.php` (new): handles revocation, JSON/HTML errors, and fail-closed lookup failures.
- `tests/support/session_fixture.php` (new): synthetic accounts in connection-local temporary tables.
- `tests/session_security.php` (new): session-state and model checks.
- `tests/session_security_http.php` (new): isolated CGI checks, including actual index entry on denied/protected requests.
- `docs/session-security-verification.md` and `tests/README.md`: documentation.

Existing source originals were backed up outside the webroot at `C:/Users/ctrlc/.codex/backups/bahay-ko/session-revocation-20260923-222355`.

No schema, existing-account data, deployment settings, UI, or worker configuration changed.

## Verification

```powershell
& C:/xampp/php/php.exe tests/session_security.php
& C:/xampp/php/php.exe tests/session_security_http.php
& C:/xampp/php/php.exe tests/faculty_scope.php
& C:/xampp/php/php.exe tests/faculty_scope_http.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe tests/content_access_http.php
git diff --check
```

Tests use synthetic accounts in temporary tables plus isolated CGI/session/log files outside the webroot. Revoked requests stop before mutations. Actual index checks use the document route, which does not trigger the request publishing worker. The normal login session initializer is exercised with a stubbed read-only legal lookup; no real user is logged in or changed. Original content and Faculty tests remain relevant regression coverage.

## Recorded results (2026-09-24)

- Session service: 35 checks passed.
- Session HTTP: 105 checks passed.
- Faculty scope service/HTTP: 158 / 37 checks passed.
- Content access service/HTTP: 124 / 95 checks passed.
- Total: 554 focused checks passed.
- PHP syntax checks passed for all eight affected PHP files. `git diff --check` passed (existing line-ending warnings only).
- Compared the two modified existing runtime files against the pre-H1 backup: only the account guard, login fingerprint, and required-password-change session rotation were added.
- Full browser verification remains pending; these results are not a full-system regression guarantee.

## Limits and remaining browser checks

Revocation is checked on the next request, not by pushing a forced browser logout. Already displayed content is not erased. The current schema has no persistent per-account session revocation counter: if a role/status is changed and restored before an old session makes any request, this current-state comparison cannot identify that transient change. Once a revoked session has been checked, restoring the account does not restore that cleared session. Password changes remain detectable through the credential fingerprint.

The browser checks still needed are: normal sign-in for all roles; required first-login password change; separate-browser deactivation/role/password reset followed by a protected page, AJAX action and keep-alive; re-login after revocation; and ordinary academic assignment changes. Use designated test accounts. Full end-to-end browser login/reset behavior is not claimed from the focused CGI checks.
