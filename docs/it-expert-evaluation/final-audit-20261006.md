# Final audit — October 6, 2026

Scope: current local application before synchronization to the isolated evaluation copy. Existing user changes were preserved. No schema migration, permanent account edits, delivery worker, email send, Git commit, or push was performed by this audit.

## Confirmed findings and fixes

- Announcement email enrichment also ran for workflow notices. Pending/rejected announcements cannot pass the published-content lookup, so review and rejection mail would fail. Workflow notices now retain their original message; published announcement mail retains its full text and current recipient eligibility check. Nine focused formatting/workflow checks pass.
- Deployment access rules omitted private-key extensions already blocked locally. The deployment template now denies PEM, KEY, P12, and PFX files too.
- `include/topbar.php` had no active references; only an old planning document mentioned it. Removed the unused template. Dynamically routed pages, test utilities, populated tables, and historical engagement data were retained.

## Verification

- Syntax: 159 PHP files before topbar removal, shared `index.php`, and 22 runtime JavaScript files passed. Changed email service was checked again after the fix.
- Controlled loopback probes: 15 passed, covering SQL identifier payloads, reflected markup, missing/invalid CSRF, unauthenticated object access, traversal, external redirects, frame protection, and denied sensitive paths.
- SQL/SSRF payload checks: 22 passed without outbound requests or account changes.
- Service regressions passed: abuse protection, request input, rich text, Faculty policy and all active College programs, content access, survey privacy, session revocation, contact inquiries, registration email, publication durability, cache failure recovery, feed audience/interests, Parent registration, required password change, and scalar form validation.
- HTTP regressions passed: 224 global guards, 95 content access, 110 abuse protection, 105 session checks, 83 safe-error checks, 37 Faculty checks, 30 Parent checks, 125 multipart uploads, 68 Apache upload checks, and 34 deployment configuration checks.
- Eight Node UI simulations passed: feed votes, content votes, current-tab post navigation, card click exclusions, recent posts, registration preservation, Faculty forms, and Parent forms.

Database fixture tests used connection-local temporary tables. Upload/session fixtures were isolated. No new exploitable issue was reproduced in the tested cases; this is not a guarantee against all attacks.

## Synchronization and limits

Completed: 27 runtime files synchronized, 2,788 file hashes verified, retired runtime files removed from the evaluation copy after backup. Backup: `deployment/tunnel/evaluation/private/audit-sync-backup-20261006-132336`. Apache configuration and the synchronized email service passed syntax checks. All 41 deployment preparation checks passed. Port 8080 was closed at completion; no live evaluation listener or new tunnel URL was started.

Sync only application runtime code and access rules into the existing evaluation copy. Back up replaced/retired files under its private directory and verify copied hashes. Preserve private settings, database contents, accounts, uploaded files, and the evaluation-specific profile-photo rule. Do not rerun the initial builder against the existing copy.

The locally created test accounts and renamed Parent are database changes, so a code-only sync does not transfer them. No new public tunnel URL or production deployment is established by this audit. Real inbox rendering, real browser journeys, external-network tunnel checks, dependency vulnerability inventory, and hosting capacity remain separate checks.
