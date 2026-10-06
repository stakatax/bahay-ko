# Controlled security attack audit — 6 October 2026

Target: local OLSHCO Digital Hub only. No third-party attack traffic, account brute force against real users, existing-record changes, database migrations or public deployment. Authenticated attack cases use existing isolated temporary-table/CGI fixtures. Public probes are pinned to http://127.0.0.1/bahay-ko/ and do not follow redirects. No credentials or downloaded private file contents are recorded in the report.

## Confirmed gaps fixed

1. Root Apache access rules were absent. Direct HTTP requests could download .git/config, the schema SQL, Composer metadata and logs. Direct config PHP requests also returned 200 instead of being denied. Added .htaccess denying internal directories, hidden paths and sensitive filenames while retaining authorized PHP includes, CLI tools, public assets and certificate challenge paths. Seven sampled sensitive paths now return 403. Existing upload-directory rules remain intact.
2. Application responses lacked clickjacking protection. Early PHP headers now set X-Frame-Options: SAMEORIGIN and CSP frame-ancestors 'self', with nosniff. Root/template Apache rules also set SAMEORIGIN and nosniff. Same-origin document previews remain possible; external framing is blocked. Authorized document responses retain their sandbox directive alongside the frame policy.
3. Advisory fetches followed redirects before checking the final trusted host. The DNS safety check also preceded an independent cURL resolution. Redirect following is now explicit and limited to three hops, with source identity/URL validation before the next connection. Each connection pins the validated globally routable IPv4 address using CURLOPT_RESOLVE; proxy inheritance is disabled. Global-range validation also rejects shared-address/benchmark networks. Relative redirects and approved source aliases remain supported. HTTPS/TLS verification and the existing timeout/body-size limits remain enabled.

## Attack coverage and evidence

| Class | Controlled simulation and result |
| --- | --- |
| SQL injection | Five OR/UNION/comment/time payloads passed to the real prepared identifier lookup returned no account. An injected content ID returned 422. Existing request/academic validation and scoped-content fixture tests passed. |
| CSRF | Public login submissions with absent/wrong tokens redirect without authentication. Authenticated fixture tests verify token/method rejection for protected account/content actions. Abuse HTTP suite: 110 checks; content HTTP suite: 95 checks. |
| XSS | Script text in the login error parameter was not emitted as executable markup. Rich-text sanitizer suite passed 8 hostile-content checks; request error suite passed 83 output/error checks. No real-browser script execution or every possible DOM sink is claimed. |
| SSRF | Push endpoint suite passed 29 checks including loopback, metadata/private addresses, credentials, alternate ports and provider-host spoofing. Advisory tests reject unsafe redirect schemes/hosts/credentials/ports and restricted IPs, while allowing approved relative/alias redirects. No real metadata/internal HTTP requests were made. |
| IDOR | Unauthenticated content POST returned 401. Content access suite passed 139 role/scope/document/engagement checks and its HTTP suite passed 95 checks. Parent/Faculty boundaries use server-side eligibility; no real content ownership changed. |
| Path traversal | Crafted page path returned 404. Authorized document resolution, upload protection and malformed-ID checks passed; raw/internal files are denied by Apache. |
| Weak login | Abuse suite passed 53 checks and HTTP suite passed 110 checks using fake accounts/temporary tables, covering throttling and protected failure handling. Session HTTP suite passed 105 checks. No real accounts were locked out. This does not establish that every existing user chose a strong password. |
| Clickjacking | Live login/error responses now include SAMEORIGIN and frame-ancestors 'self'. Cross-origin framing is blocked without disabling the existing same-origin PDF preview feature. |
| Open redirect | Crafted external redirect parameter did not produce an external Location. Notification URL/ownership suite passed 26 checks; global guards passed 215 checks including fixed local destinations. Reviewed Auth/base-controller redirect callers use application-generated destinations. |
| Secret leakage | Seven sampled source/private paths now return 403, and safe-error tests passed. No leaked values were printed. Protection of an earlier public copy is not established by testing localhost. |

The new tests/security_attack_payloads.php passed 22 SQL/SSRF checks. tests/security_public_probes.py passed 15 live loopback probes. Existing advisory posting suite passed 51 checks; upload validation passed 125 and Apache upload protection passed 68. Runtime PHP/JavaScript syntax checks passed for changed files. Results are in ignored logs/security-regression-20261006.json and logs/security-public-probes-20261006.json.

## Home navigation change

Feed card bodies, titles, Read more, Discuss and recent posts now navigate to the protected post page in the current tab. Discussion retains #discussion. Native title links retain normal keyboard/modifier behavior, and card clicks still ignore interactive controls, selected text and canceled events. External government-source/attachment links retain their separate behavior. Post-page, card-link, recent-post, inline-vote and existing reader simulation tests passed.

## Limits and deployment follow-up

These are controlled payload/fixture checks and source review, not exhaustive penetration-test certification or visual browser testing. No live advisory provider redirect fetch, SMTP/push delivery or production host was tested. Enable equivalent Apache rules and rerun probes on the approved staging host before launch. If a previously public installation exposed secrets or repository content, review its access logs and rotate affected credentials separately; localhost findings alone do not prove external access occurred. No deployment or credential rotation was performed here.
