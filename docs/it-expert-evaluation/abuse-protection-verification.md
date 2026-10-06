> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# M4 abuse protection

Implemented and activated locally on 2026-09-24 after approval. Migration 028 added only request_rate_limit; a second --apply detected the existing table without changing it. The read-only baseline check reports 64 tables and zero differences. The original schema snapshot is unchanged.

## Behavior

Unknown accounts, wrong passwords and locked accounts receive the same invalid-credentials message. Five failed passwords still lock an active account for 15 minutes. Successful login resets attempts and updates last login. Pending/inactive/rejected guidance requires a correct password. A dummy hash puts missing accounts through password verification; response timing is not guaranteed identical.

| Action | Bucket | Limit |
| --- | --- | --- |
| Login | IP | 120 / 15 minutes |
| Login | normalized identifier | 20 / 15 minutes |
| Registration | IP | 30 / hour |
| Open content | authenticated user | 120 / minute |
| React | authenticated user | 60 / minute |
| Comment/reply | authenticated user | 10 / minute |
| Acknowledge | authenticated user | 60 / minute |

Both login buckets apply, including successful attempts; the existing five-failure account lock remains independent. Registration counts attempts, including invalid forms. Engagement buckets are per action and shared across content IDs/types. No Administrator bypass. Limits persist across PHP requests and browser sessions; expiry permits another attempt. Password-recovery throttling remains unchanged.

Controllers still validate HTTP method, session (engagement), CSRF and request IDs. Service limits precede content queries, password verification or registration transactions. Content authorization and feedback settings remain enforced. Callers cannot use a submitted user_id to select an engagement bucket. AuthController supplies REMOTE_ADDR; untrusted forwarded headers are ignored.

Excess requests return HTTP 429, Retry-After, no-store and a safe wait message with seconds. Engagement responses retain their existing JSON contract, which news.js displays. Authentication uses the existing root.css error page (or JSON when requested); safe login/registration flash values are preserved, never passwords. Ordinary validation still follows existing form redirects. A storage failure prevents the protected operation instead of allowing an uncounted write; existing safe error handling applies.

## Files

- app/models/RequestRateLimit.php: atomic InnoDB upsert/read transaction; bounded counters; fixed expiry; rejects a caller transaction; deletes at most 100 entries expired over a day ago per attempt.
- app/services/RequestRateLimitService.php: policies, IP normalization, scoped HMAC identifiers and retry exception. Stores no raw IP/email/password values. Uses the application security key.
- app/services/AuthService.php: generic failures, login/registration enforcement using the User connection; existing account and registration workflows preserved.
- app/controllers/AuthController.php: trusted request IP and safe 429 responses.
- app/services/ContentEngagementService.php: four action limits using the engagement connection.
- config/request-errors.php: recognizes the rate-limit exception, supplies 429/retry headers and avoids logging expected throttling as an internal error.
- database/migrations/028_request_rate_limit.php: CLI-only additive migration; defaults to read-only --check. Existing table detection skips creation.
- database/baselines/028_request_rate_limit.sql and scripts/check_database_baseline.php: verified table definition and additive baseline.
- tests/abuse_protection.php, tests/abuse_protection_http.php, tests/content_access.php: focused tests; content fixtures now isolate limiter writes too.
- tests/README.md and docs/deployment-configuration.md: test and installation requirements.

The table contains key_hash, scope, attempts, expires_at, a primary key and an expiry index. It contains no content or account records. Before installing this code elsewhere, apply 028 with migration credentials and verify the baseline. Runtime credentials need SELECT, INSERT, UPDATE and DELETE on the limiter table, not schema-changing permissions.

## Safe verification

From C:\xampp\htdocs\bahay-ko:

```powershell
& C:/xampp/php/php.exe database/migrations/028_request_rate_limit.php --check
& C:/xampp/php/php.exe scripts/check_database_baseline.php
& C:/xampp/php/php.exe tests/abuse_protection.php
& C:/xampp/php/php.exe tests/abuse_protection_http.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe tests/content_access_http.php
& C:/xampp/php/php.exe tests/session_security_http.php
& C:/xampp/php/php.exe tests/request_errors_http.php
& C:/xampp/php/php.exe tests/database_baseline.php
git diff --check
```

Results: 53 abuse-protection, 84 abuse HTTP, 124 content-access, 95 content HTTP, 105 session HTTP, 79 request-error HTTP and 12 baseline-verifier checks passed: 552 total. All 11 changed/new PHP files passed php -l. Whitespace checks passed. Tests use temporary tables and inert fixtures; registration notification delivery is stubbed. Existing records and external deliveries are not touched.

Coverage includes exact policy thresholds, key normalization/isolation, fixed-window expiry, counter saturation, bounded cleanup, caller-transaction protection, generic errors, five-failure lockout, successful real-model login, Student/Parent registration and consent records, duplicate registration, registration rollback, actual controller 429 responses, all four roles, no blocked engagement writes, method/session/CSRF/ID validation, audience denial and storage failures.

Backups: C:/Users/ctrlc/.codex/backups/bahay-ko/abuse-protection-20260924-214040 and C:/Users/ctrlc/.codex/backups/bahay-ko/abuse-activation-20260924-215304.

## Remaining checks and limits

Manually check browser retry messaging and tune thresholds for shared school IPs in staging. Fixed windows permit bursts around boundaries. Concurrent multi-connection load has not been stress-tested; atomic updates and transaction locking are used. Distributed traffic still needs server/proxy protection. Cleanup depends on subsequent attempts; storage is not globally capped against unlimited distinct identifiers/IPs. Behind a proxy, REMOTE_ADDR is the proxy address unless the trusted web-server configuration restores client IPs; do not trust arbitrary forwarded headers in application code. Rotating the application security key starts new limiter buckets.
