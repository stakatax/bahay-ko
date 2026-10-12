# Clean-checkout verification — 12 October 2026

Verified a clean checkout of the reviewed source with no original `.local.php` credentials, installed vendor directory, operational records or uploads carried over.

- Composer installed all 24 locked packages; the non-development platform requirements passed on PHP 8.2.12. ZIP extraction was unavailable locally, so Composer's Git source fallback was used. The setup guide explains enabling ZIP or an extraction tool.
- The local configurator created private settings and runtime directories, with separate application-key generation and disabled delivery settings.
- Imported only the current 66-table schema into the newly created isolated `olshco_development` database. Its definitions match the source schema with zero differences.
- Seeded 221 reference rows and one generated fixture Administrator transactionally. A matching repeat added no rows. The older scratch bootstrap's 31 rollback-only checks still pass.
- Twelve loopback HTTP checks passed: login form/CSRF, Administrator login, HttpOnly session, required password change, legal acceptance, Home feed, account profile, Create Post, profile management, user management, academic management and POST logout.
- The rehearsal found and fixed the required-password-change session handoff: the authentication service now supplies the new credential fingerprint without returning its password hash. The controller accepts the validated fingerprint and rejects missing proof.
- Focused regression checks passed: 53 profile lifecycle, 28 reminders, 4 stale delivery queue suppression, 26 notification routing, 231 global guards/HTTPS, 105 session security, 26 required-password-change, 18 theme, 9 Administrator rendering, 4 placement method/CSRF and 12 baseline utility checks. Changed PHP and JavaScript files passed syntax checks.

The test server bound only to loopback. Fixture credentials, sessions, dependency installations and test outputs are private/ignored. No existing application or evaluation account was reset, no real notification was sent, and no production deployment was performed. This is local installation verification, not a full production acceptance or load test. Existing installations still require reviewed database upgrades; Git pull does not migrate records or uploads.
