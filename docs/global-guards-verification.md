# M5 global guards and HTTPS

Implemented locally on 2026-09-24. AJAX guard handling is complete. Hosting has not been selected, so host-specific TLS/proxy configuration and external HTTPS verification remain deployment steps. No server configuration, database schema or existing records were changed.

## Guard behavior

The required-password, legal-consent and Student-profile-survey guards in index.php now return HTTP 403 JSON to JSON endpoints or requests declaring Accept: application/json / X-Requested-With: XMLHttpRequest. Responses contain success=false, a safe message, a stable code and a fixed local redirect_url. No Location header or HTML is returned. Responses are not cached.

| Requirement | Code | Destination |
| --- | --- | --- |
| Password change | password_change_required | index.php?page=required_password_change |
| Legal consent | legal_consent_required | index.php?page=legal_reconsent |
| Student profile survey | student_survey_required | index.php?page=student_profile |

Normal page navigation retains the original 302 redirects. Password-change precedence, legal-consent precedence, Student-only survey enforcement, remediation/save allowlists and logout access are unchanged. The guards stop execution before controllers, publishing work or protected actions.

Page login/role checks use the same response helper: AJAX receives 401 authentication_required or 403 access_denied; ordinary navigation retains existing redirects/messages. Session revocation continues using its existing 401/503 behavior. Both session and global guards now reuse requestErrorExpectsJson(), avoiding separate endpoint lists.

The existing news, notification, posting and session clients can parse the message instead of receiving HTML. redirect_url is an additive response field; this change does not introduce automatic navigation or modify UI styling. Browser feedback should still be checked in a normal user session.

## HTTPS decision in plain language

HTTPS encrypts the browser-to-website connection. A host may handle it directly on the PHP web server, or a proxy/load balancer may handle it before forwarding requests to PHP. Hosting is not chosen yet; local HTTP XAMPP behavior stays unchanged.

Policy: PHP trusts only the web server's HTTPS value. It never infers secure transport from OLSHCO_APP_URL or client-supplied Forwarded, X-Forwarded-Proto or X-Forwarded-Ssl headers. requestUsesHttps() makes this existing boundary explicit; HttpOnly, SameSite=Lax and conditional Secure remain unchanged.

Once the host is selected, verify the appropriate case:

1. Direct HTTPS: the web server must supply HTTPS=on to PHP for browser HTTPS requests.
2. TLS proxy: the hosting administrator must configure the trusted server integration to supply HTTPS=on only for HTTPS requests arriving through approved proxy connections. The proxy must overwrite external forwarding headers and the origin must restrict untrusted direct access. The exact server configuration depends on the host and is deliberately not guessed here.
3. The hosting layer should redirect public HTTP to HTTPS. Inspect the HTTPS response's Set-Cookie header for Secure, HttpOnly and SameSite=Lax. Check sign-in, renewal and sign-out through the external hostname; do not add a public diagnostic endpoint or share cookie values.
4. Confirm the trusted client-IP setup too: M4 uses REMOTE_ADDR. Without server-side restoration, all users behind the proxy share its IP rate bucket.

CLI configuration checks cannot prove browser-facing TLS or proxy trust. The final host configuration and an external HTTPS cookie check are required before deployment.

## Files and verification

- index.php: stopForRouteGuard(), three requirement guards and page login/role checks.
- config/authenticated-session.php: reuse the shared JSON request detector.
- config/security.php: named requestUsesHttps() helper preserving detection behavior.
- tests/global_guards_http.php: current front-controller prefix executed in isolated CGI; temporary account tables; survey lookup stubbed; stops before real route dispatch/publishing. Also inspects actual session Set-Cookie headers.
- docs/deployment-configuration.md and tests/README.md: deployment decision and test references.

From C:\xampp\htdocs\bahay-ko:

```powershell
& C:/xampp/php/php.exe -l index.php
& C:/xampp/php/php.exe -l config/authenticated-session.php
& C:/xampp/php/php.exe -l config/security.php
& C:/xampp/php/php.exe -l tests/global_guards_http.php
& C:/xampp/php/php.exe tests/global_guards_http.php
& C:/xampp/php/php.exe tests/session_security_http.php
& C:/xampp/php/php.exe tests/request_errors_http.php
& C:/xampp/php/php.exe tests/deployment_configuration.php
git diff --check
```

Results: 174 new guard/HTTPS checks, 105 session HTTP, 79 request-error HTTP and 34 deployment-configuration checks passed (392 total). Syntax and whitespace checks passed. Coverage includes all ten known JSON routes, both AJAX header signals, all roles, guard precedence, unchanged allowlists, ordinary redirects, page authorization, cleared requirements and spoofed forwarding headers. No real publishing, notifications or user data changes occur in these tests.

Backup: C:/Users/ctrlc/.codex/backups/bahay-ko/global-guards-20260924-220828.
