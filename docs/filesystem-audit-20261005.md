# Filesystem and functionality audit — 5 October 2026

Scope: current local working tree, runtime syntax, route references, public HTTP pages, existing focused regression suites and manually reviewed unused code. Existing performance/UI changes were preserved. No deployment, migration, operational database edits or external notification delivery was performed.

## Confirmed problems and changes

- Contact displayed editable required inputs and a submit action despite having no inquiry delivery backend. Submitting posted back to the page without delivering anything. The form is now a natively disabled fieldset with an unavailable action label and an associated explanation. Existing call/email links remain available. A future inquiry backend remains separate work; delivery was not implemented or implied.
- tests/feed_audience.php still stubbed the old single-type query interface. After live audience batching it fell through to an uninitialized fixture database connection. Updated the fixture to getTargetSets and asserted one target lookup per batch. All 130 role/status/Parent-scope checks pass.
- tests/global_guards_http.php expected the original Student profile route shape and old mandatory-survey destination. Updated it to recognize both profile routes and the dedicated survey destination; added role checks for the survey page. All 215 checks pass.
- tests/deployment_configuration.php omitted config/push-endpoint.php from its isolated service fixture. Added the actual dependency to the copy list; all 34 configuration checks pass.

## Removed unused code

Before removal, searched runtime PHP/JS/CSS, front-controller routes, scripts and tests for references. No active references or routes were found for:

- app/controllers/AnnouncementController.php — unused announcement-only controller; unified ContentEngagementController owns the active endpoints.
- app/services/AnnouncementService.php — unused service retaining obsolete Like/Love/Care/Wow logic.
- include/public-header.php, Assets/css/public-shell.css and Assets/js/public-shell.js — retired public-header shell. index.php uses the unified sidebar shell for public pages.

Announcement models and historical engagement tables were retained. Test scripts, deployment tools, diagrams, documentation, private settings and existing uploads were not treated as disposable just because they are not loaded by web pages. Unrouted Student management view fragments were retained pending a separate review of intended navigation rather than guessing that an incomplete page should be deleted.

## Verification

Initial runtime syntax scan: 148 PHP files and 23 JavaScript files passed. After removing unused files, repeat syntax checks on remaining runtime files. Literal index.php?page references in pages/include/Assets/js all resolve to switch cases. Dynamic page includes require route analysis; absence of a literal filename in index.php alone is not evidence that a page is unused.

Public GET smoke checks passed for Home, About, Academics, Contact, Login, Register and Forgot Password (HTTP 200). Every local Assets/include reference found in their rendered HTML exists. Contact HTML contains the disabled fieldset. No real form submissions or delivery were attempted.

Existing regression suites passed for feed ranking/interests/targeting/engagement, cache resilience and invalidation, workspace counts, account review statements, notification routing/categories, registration decision email, profile page separation, academic/form/request validation, rich text, push validation, abuse protection, content access, uploads, Faculty scope/profile, Parent registration/linkage, profile cycles/notifications, publication durability/outbox, password requirements, sessions, survey privacy, legacy feed counts, database configuration/baseline, deployment configuration and global HTTP guards. All eight *ui.js simulations passed. These are simulated interactions and fixture HTTP tests, not visual browser acceptance.

Some first-pass CLI fixtures hit Windows sandbox restrictions on temporary/session paths. Those tests were rerun outside the sandbox with isolated fixtures; environment failures were not treated as application defects. The repaired suites passed individually. logs/filesystem-audit-tests-20261005.json records the broad batch before repairing the two stale fixtures; its initial failures are superseded by the individual reruns documented above.

Namecheap preparation remains reusable reference material: 41 isolated checks passed; it does not mean alwaysdata configuration or a public release is active.

## Limits and remaining acceptance

Actual SMTP/browser-push delivery, real scheduled-worker operation on the chosen host, browser layout/keyboard review, complete role journeys and backup restoration still need host/staging acceptance. No claim is made that every possible feature is bug-free. Contact inquiry delivery is explicitly unavailable. No stress load was applied during this audit.
