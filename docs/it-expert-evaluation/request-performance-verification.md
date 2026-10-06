> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# M1: request publishing and feed queries

## Changes and evidence

Before M1, index.php called processPendingReleases() on every eligible request except document_download. Even when idle, this began a transaction, read seven due-content sets with locking reads, committed and invoked the notification dispatcher. PostService::attachEngagement called getEngagement for every visible item (eight statements each), and getNewsFeed checked survey participation once per survey.

The request path now reuses index.php's database connection and calls processPendingReleasesIfDue(). One SELECT checks for due scheduled content, eligible calendar content, available outbox retries and expired processing leases. If idle it returns the existing zero-result structure without starting a transaction or dispatcher. If work is due it delegates to the original transactional release method. scripts/dispatch_browser_push.php retains its full worker path. No cache or time throttle was added.

Feed engagement uses four grouped queries per content type for up to 500 IDs per chunk. Survey participation uses one query per chunk. Existing audience filtering runs before these reads; the new model methods are aggregation helpers, not authorization entry points. Current 100-item candidate limits, item order, field types, zero defaults, reaction breakdown, viewer flags and existing comment-count semantics (including moderated comments in totals) are preserved. Detail/action endpoints keep the original single-item methods. No cross-request data is cached.

## Measured query reductions

| Fixture workload | Before | After |
| --- | --- | --- |
| 100 items of one content type: engagement | 800 statements | 4 statements |
| 100 surveys: current-user participation | 100 statements | 1 statement |
| Idle request: publication fallback | 7 due-set reads + transaction + dispatcher | 1 SELECT; no transaction/dispatcher |

Counts were measured against actual model/service methods with a counting MySQLi connection and isolated temporary tables. They are statement-count results, not a claimed wall-clock latency improvement or production load test. Existing lookup indexes support the batch filters; no schema/index migration was introduced.

## Exact files

- index.php — request publishing entry point and connection reuse.
- app/services/ContentReleaseService.php — optional injected connection and due-work pre-check.
- app/services/PostService.php — batched engagement and survey-response flags.
- app/models/ContentEngagement.php — getEngagementBatch.
- app/models/Survey.php — getRespondedSurveyIds.
- tests/request_performance.php — parity, query counts, feed attachment and publishing/retry cases.
- tests/README.md — test instructions.
- docs/request-performance-verification.md — this record.

Backups: C:/Users/ctrlc/.codex/backups/bahay-ko/request-performance-20260924-142909.

No route registration, methods, role/target authorization, CSRF, UI, schema, worker installation or existing records changed. The new pre-check only predicts work; the original release transaction/locking and outbox claim rules remain authoritative. Work that becomes due immediately after a negative check is handled by the next request or worker, without an added throttle interval.

## Safe verification

Run from C:/xampp/htdocs/bahay-ko in local development or staging:

```powershell
& C:/xampp/php/php.exe tests/request_performance.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe tests/content_access_http.php
& C:/xampp/php/php.exe tests/survey_privacy.php
& C:/xampp/php/php.exe tests/publication_durability.php
& C:/xampp/php/php.exe tests/session_security_http.php
& C:/xampp/php/php.exe tests/request_errors_http.php
& C:/xampp/php/php.exe -l index.php
& C:/xampp/php/php.exe -l app/services/ContentReleaseService.php
& C:/xampp/php/php.exe -l app/services/PostService.php
& C:/xampp/php/php.exe -l app/models/ContentEngagement.php
& C:/xampp/php/php.exe -l app/models/Survey.php
& C:/xampp/php/php.exe -l tests/request_performance.php
git diff --check
```

Observed: 101 performance, 124 content-access, 95 content HTTP, 166 survey-privacy, 74 publication-durability, 105 session HTTP and 79 request-error checks passed: 744 total. Syntax and whitespace checks pass. Existing Git line-ending warnings are unrelated.

The M1 test clones actual table definitions into connection-local temporary tables; all fixture mutations stay there. It compares old/new engagement for all four content types and multiple actors, verifies survey participation parity, empty/invalid/duplicate IDs, chunk boundaries, real feed attachment, idle/future/due/calendar publishing and retry/lease detection. Its dispatcher stub prevents external delivery; the existing H3 test independently covers real in-system notification delivery with temporary tables. No actual email/push or production worker was run.

## Remaining checks and scope

Manually inspect Information Hub badges/counts and survey participation states for representative roles; no browser visual test was performed. Profile full requests on staging with realistic volumes before claiming latency improvements. This change does not remove every remaining query or model connection; per-type actor/target lookups, tagging, interests and other pages retain existing behavior.

Due work still executes synchronously on a visiting request. Removing all request publishing would require an installed, verified worker to preserve scheduled releases; that deployment step has not been performed. Follow deployment-configuration.md and publication-notification-readiness.md before changing that operating model. Keep predicate tests aligned if release/outbox eligibility rules change later.


## Home audience batch (29 September 2026)

Home now passes its four candidate sets to ContentAudienceService::filterSetsForUser.
Viewer status/role and verified Parent profiles are resolved once per batch; target
maps remain separate per nonempty content type. No authorization state survives the
call. Single-item endpoints still recheck authorization independently. No SQL,
schema, candidate limits, routes, CSRF or engagement behavior changed.

With all four sets nonempty, viewer model calls drop from four to one, and Parent
profile model calls drop from four to one. These are fixture-measured model-call
counts, not full-request SQL or latency measurements. Each viewer lookup executes
one SELECT; a Parent profile lookup includes both linked accounts and verified
child records and may query the academic structure. Empty sets need no lookup.

Changed: app/services/ContentAudienceService.php, app/services/PostService.php.
Verification: tests/feed_audience.php (130 checks), tests/content_access.php (124),
tests/request_performance.php (101), and tests/content_access_http.php (95). New tests cover exact batch/single parity,
all roles, inactive/unknown actors, Parent scopes, guest access, empty/invalid
inputs, ordering/specificity, and fresh authorization on subsequent calls.
The new test uses model fixtures; existing tests use temporary database tables.

Safe commands from the project root:

```powershell
& C:/xampp/php/php.exe tests/feed_audience.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe tests/request_performance.php
& C:/xampp/php/php.exe -l app/services/ContentAudienceService.php
& C:/xampp/php/php.exe -l app/services/PostService.php
& C:/xampp/php/php.exe -l tests/feed_audience.php
git diff --check
```

Backup: C:/Users/ctrlc/.codex/backups/bahay-ko/feed-audience-20260929.
Full authenticated browser comparison and realistic-volume latency profiling remain
pending. Connection consolidation and asynchronous drawer skeletons are separate
follow-up work.


## Home constructor connections (29 September 2026)

PostService now initializes NotificationService and ContentRedundancyService on
first use; PostController does the same for GovernmentAdvisoryIntakeService.
All six original call sites use the getters. Instances are reused within the
owning object. No connection sharing or transaction boundaries changed.

Measured distinct MySQL connections retained by a newly constructed PostController:
11 before, 7 after. The four avoided connections belonged to notification delivery
(two), redundancy assessment (one), and advisory conversion (one). This is a
constructor measurement, not the total connections or latency of a full request.
Once all three dependencies are used, the original eleven connections are available.

The guest feed service returned exactly the same serialized-data SHA256 before and
after: 0e7260c3868362bd80ee979fcd814f7d20083c952a0df1413cf7299b29ada01a.
This read-only comparison did not invoke publishing or change existing records.

Verification: 15 Home connection checks, 74 publication durability checks,
158 Faculty scope checks, 130 audience checks and 101 request performance checks
passed (478 total). PHP syntax checks passed for both changed production files and
the new test. Publication/Faculty fixtures use temporary tables; no email/push sent.
Full authenticated browser journeys, advisory conversion and production-volume
latency measurements remain pending.

```powershell
& C:/xampp/php/php.exe tests/home_connections.php
& C:/xampp/php/php.exe tests/publication_durability.php
& C:/xampp/php/php.exe tests/faculty_scope.php
& C:/xampp/php/php.exe tests/feed_audience.php
& C:/xampp/php/php.exe tests/request_performance.php
& C:/xampp/php/php.exe -l app/services/PostService.php
& C:/xampp/php/php.exe -l app/controllers/PostController.php
& C:/xampp/php/php.exe -l tests/home_connections.php
git diff --check
```

Backups: C:/Users/ctrlc/.codex/backups/bahay-ko/home-connections-20260929.
Next: actual asynchronous drawer loading states. Remaining connection consolidation
and full-request profiling stay open; no global connection singleton was introduced.


## Feed profiling baseline (30 September 2026)

Read-only CLI sampling, five calls per first active account in each role. Service
only: excludes global guards, publishing fallback, controller construction, PHP
page rendering, network and browser costs. No full-page latency claim.

| Sample role | Visible announcements/events/documents/surveys | Median ms | Range ms |
| --- | --- | --- | --- |
| Admin | 60 / 24 / 3 / 7 | 107.84 | 103.42?148.43 |
| Faculty | 30 / 19 / 1 / 4 | 108.67 | 96.26?110.04 |
| Student | 46 / 24 / 1 / 6 | 109.16 | 101.41?120.54 |
| Parent | 0 / 0 / 0 / 0 | 31.00 | 24.24?40.21 |

The sampled Parent had no eligible items; this does not benchmark verified Parent
feeds. Counts from seven instrumented models: 25 statements for Admin/Faculty,
27 Student, 4 Parent. These exclude the separately opened audience and target-tag
connections. Engagement remains four queries per visible content type (16 total).
This is a small local baseline, not a load test or representative role benchmark.

Confirmed next candidate: Announcement::getRecent computes eight counts from
historical announcement-specific engagement tables. EXPLAIN reports eight dependent
subqueries; PostService::attachEngagement overwrites these fields using unified
content engagement. Avoid this discarded work on Home, retaining all historical
tables/data and verifying output parity. Statement counts alone miss the work
inside these subqueries. No index change is justified by this sample alone.

A secondary candidate is the full Student profile load for interest weights;
respect the Student Profile stability boundary and investigate separately.

Reproduce (SELECT and EXPLAIN only; outputs counts/timing, not account IDs/content):

```powershell
& C:/xampp/php/php.exe tests/profile_home_feed.php
& C:/xampp/php/php.exe -l tests/profile_home_feed.php
```

No production code, schema or existing records changed in this profiling pass.


## Skip discarded legacy counts on Home (30 September 2026)

`Announcement::getRecent` now accepts an optional `includeLegacyEngagement` flag
(default true for compatibility). Home's `PostService::getNewsFeed` passes false.
The optimized projection uses zero placeholders for the eight count fields,
preserving array field order before unified engagement replaces those values.
Other callers retain their original behavior. No historical tables or records,
filters, ordering, candidate limits, authorization, or schemas changed.

`tests/feed_legacy_counts.php` compares complete original/optimized feed arrays for
sampled active Admin, Faculty, Student and Parent accounts and a guest. All matched
exactly. The sampled Parent has no visible content; verified-Parent access remains
covered by the separate audience/access tests rather than this sample.

EXPLAIN confirms dependent subqueries drop from eight to zero. In 20 alternating
runs per query variant, local announcement-query median was 3.458 ms before and
1.357 ms after. This is a small local query-level measurement, not a full-page,
production or concurrent-load benchmark. Total SQL statement count is unchanged;
the discarded work was inside one SELECT.

Passed: 9 legacy-count comparisons, 101 request-performance checks, 130 audience
checks, 124 content-access checks (364 total), PHP syntax and whitespace checks.
The new test uses SELECT/EXPLAIN only; existing access/performance fixtures use
temporary tables. No existing records changed or external notifications sent.

```powershell
& C:/xampp/php/php.exe tests/feed_legacy_counts.php
& C:/xampp/php/php.exe tests/request_performance.php
& C:/xampp/php/php.exe tests/feed_audience.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe -l app/models/Announcement.php
& C:/xampp/php/php.exe -l app/services/PostService.php
& C:/xampp/php/php.exe -l tests/feed_legacy_counts.php
git diff --check
```

Backups: `C:/Users/ctrlc/.codex/backups/bahay-ko/feed-legacy-counts-20260930/`.
Full authenticated browser/load profiling remains pending. The existing read-only
`tests/profile_home_feed.php` will now show the optimized announcement plan.


## Minimal Home personalization lookup (30 September 2026)

Added StudentProfile::getPersonalizationInterests for Home only; PostService keeps
its existing Student-only guard and weight normalization. One prepared SELECT
returns active interest IDs/weights only when the profile is Completed and
personalization_enabled is nonzero. Ordering matches getProfileInterests.
Existing full profile lookup, editing, questionnaires, consent saves and cycles
are untouched. No schema, route, authorization or persistent-data changes.

Existing-profile feed reads drop from two to one; missing profiles still use one
read, invalid IDs and other roles use none. The former method loaded profile
metadata and interest labels/descriptions that Home did not need. It did not load
individual questionnaire answers. No latency improvement is claimed from this count.

Passed 27 isolated preference checks (completion states, personalization on/off,
active interests, ordering, role exclusions, empty/missing/invalid IDs), 9 feed
legacy-count comparisons, 130 audience checks and 101 performance checks: 267 total.
PHP syntax and whitespace checks passed. New fixture mutations use connection-local
temporary tables only. Real authenticated UI and full-request load tests remain open.

```powershell
& C:/xampp/php/php.exe tests/feed_interests.php
& C:/xampp/php/php.exe tests/feed_legacy_counts.php
& C:/xampp/php/php.exe tests/feed_audience.php
& C:/xampp/php/php.exe tests/request_performance.php
& C:/xampp/php/php.exe -l app/models/StudentProfile.php
& C:/xampp/php/php.exe -l app/services/PostService.php
& C:/xampp/php/php.exe -l tests/feed_interests.php
git diff --check
```

Backups: `C:/Users/ctrlc/.codex/backups/bahay-ko/feed-interests-20260930/`.


## Shared unread badge connection (30 September 2026)

The shared unread-notification count in index.php now passes the already-open
request connection to NotificationService. That constructor already supports
injection and gives the same connection to its Notification model. This avoids
two additional connections on application pages that need the shared unread
lookup. Notification Center still reuses its previously loaded count. The SELECT,
user validation, failure fallback, notification ownership and count semantics are
unchanged. No new cache, schema, data mutation or transaction change was added.

Read-only runtime probe: 8 checks passed for connection identity, baseline's two
separate connections, equal unread counts for sampled active roles, and invalid
IDs. This probe was run inline; it is not a new repository test. Existing
`tests/global_guards_http.php` passed 174 checks with temporary account/session
fixtures, no real actions or deliveries. PHP syntax, one-line diff review and
whitespace checks passed. The guard suite does not exercise the rendered badge;
that evidence comes from the direct service parity probe. Browser rendering and
full-page timing remain pending.

```powershell
& C:/xampp/php/php.exe -l index.php
& C:/xampp/php/php.exe tests/global_guards_http.php
git diff --check
```

Backups: `C:/Users/ctrlc/.codex/backups/bahay-ko/navigation-connections-20260930/`.
Next candidate: Admin navigation counts a fetched set of up to 100 full pending
registrations. Inspect the exact inclusion rules before replacing with a count;
do not silently change capped badge behavior or expose account details.


## Capped Admin pending badge (30 September 2026)

User::countPendingRegistrations counts the same limited row set using a derived
SELECT with LIMIT, excluding unnecessary academic/child-detail joins and personal
columns. Live schema inspection confirmed those removed joins use unique keys;
the parent_student join remains because multiple child links multiply queue rows.
Status/role predicates and limit normalization (1?200; badge 100) are unchanged.
The Account Approvals page still reuses its loaded list count.

AccountApprovalService accepts an optional mysqli connection (default unchanged).
The shared badge passes index.php's connection; its User and NotificationService
reuse it, avoiding the default path's three extra connections. No approval,
notification, schema or data behavior changed. This preserves the existing
row-count meaning, not a newly introduced distinct-applicant total.

Passed 20 temporary-table badge checks and 59 Parent/child registration checks:
empty/mixed statuses and roles, unmatched role, Parent without login-linked child,
multiple Parent links, small/large queues, cap normalization, shared connections,
and registration/approval/rejection behavior. PHP syntax and whitespace checks
passed. No existing records changed or notifications delivered externally.

```powershell
& C:/xampp/php/php.exe tests/pending_badge.php
& C:/xampp/php/php.exe tests/parent_child_registration.php
& C:/xampp/php/php.exe -l app/models/User.php
& C:/xampp/php/php.exe -l app/services/AccountApprovalService.php
& C:/xampp/php/php.exe -l index.php
& C:/xampp/php/php.exe -l tests/pending_badge.php
git diff --check
```

Backups: `C:/Users/ctrlc/.codex/backups/bahay-ko/pending-badge-20260930/`.
Manual Admin navigation verification and full-page latency profiling remain open.


## Avoid repeated Student guard initialization writes (30 September 2026)

StudentProfile::getCurrentSurveyAssignment now reads an existing profile first,
calling ensureProfile only when missing. Previously each lookup called ensureProfile,
which issued INSERT ... ON DUPLICATE KEY UPDATE before reloading the profile.
Existing profile/assignment requests now issue zero initialization writes; fresh
profiles and absent default assignments still initialize through the original path.
No caching or polling interval was added. Active Student validation and cycle
selection still execute every request, so new assignments take effect immediately.

Tests: 16 temporary-table Student guard checks and 27 feed-interest checks passed.
Cases cover new/repeated access, Assigned/InProgress/Completed/Exempt, newly assigned
non-default cycle precedence, future/closed cycles, invalid/inactive/non-Student
actors, and default-assignment fallback from completed profile state. Existing
profile editing and questionnaire persistence were not changed. PHP syntax,
focused diff and whitespace checks passed; no existing records changed.

```powershell
& C:/xampp/php/php.exe tests/student_guard_writes.php
& C:/xampp/php/php.exe tests/feed_interests.php
& C:/xampp/php/php.exe -l app/models/StudentProfile.php
& C:/xampp/php/php.exe -l tests/student_guard_writes.php
git diff --check
```

Backup: `C:/Users/ctrlc/.codex/backups/bahay-ko/student-guard-20260930/`.
Full authenticated questionnaire journeys and concurrent-load profiling remain open.
The missing-profile path adds an initial read, while the recurring existing-profile
path removes the unconditional upsert. This pass does not claim a measured latency gain.


## Combined checkpoint (30 September 2026)

All 14 backend suites passed (1,168 checks): feed_audience 130, home_connections 15,
feed_legacy_counts 9, feed_interests 27, pending_badge 20, student_guard_writes 16,
request_performance 101, content_access 124, faculty_scope 158,
publication_durability 74, parent_child_registration 59, survey_privacy 166,
content_access_http 95, global_guards_http 174.

Both local Chrome fixtures passed (drawer 80, advisory preview 60; zero uncaught
exceptions). Fetch was mocked. Backend fixtures use read-only methods or isolated
temporary tables/sessions/files, without existing-record changes or external delivery.
Expected simulated publication-failure logs appeared while durability checks passed.
Nine affected production PHP files, both JS files and git whitespace passed.

Reproduce the backend pass:

```powershell
$suiteNames = @('feed_audience','home_connections','feed_legacy_counts','feed_interests','pending_badge','student_guard_writes','request_performance','content_access','faculty_scope','publication_durability','parent_child_registration','survey_privacy','content_access_http','global_guards_http')
foreach ($suiteName in $suiteNames) {
    & C:/xampp/php/php.exe "tests/$suiteName.php"
    if ($LASTEXITCODE -ne 0) { throw "Failed suite: $suiteName" }
}
& C:/xampp/php/php.exe tests/profile_home_feed.php
```

Fresh five-run feed-service medians: Admin 105.85 ms, Faculty 91.07 ms,
Student 107.66 ms, Parent 32.15 ms (sampled Parent has no eligible content).
Visible content counts match the recorded baseline. Announcement EXPLAIN now has
only two SIMPLE rows and zero dependent subqueries. Instrumented Student-profile
reads are one, down from two. Model counts still omit audience/target-tag connections.
The separate paired announcement test measured 6.396 -> 2.689 ms during concurrent
browser testing; do not compare those absolute timings to an idle-machine baseline.
The feed profiler also ran while the guard test process was still active.

These results confirm targeted work reductions and regression coverage, not a
full-page speedup or production capacity. Next measure controlled authenticated
requests including global guards and rendering; complete real-session role journeys.
No application changes were made during this checkpoint. Manual evaluator cases
remain open. Documentation backups: combined-performance-review-20260930 under the
existing private backup directory.


## Authenticated request snapshot baseline (30 September 2026)

Ran index.php through PHP CGI in a private code snapshot, with isolated session
storage and synthetic authenticated sessions for existing active accounts. Credential
version/session-account validation runs normally. Legal-reconsent session flag was
set clear to represent a previously accepted session; login/legal acceptance were
not tested. Selected a separate Student with an already completed/exempt current
assignment to measure Home without bypassing its actual survey guard.

Every snapshot database connection uses a mysqli instrumentation subclass and
SET SESSION TRANSACTION READ ONLY. Instrumentation rejects non-read statements and
transactions; prepare/query/execute_query calls are counted. No blocked writes or
other PHP log lines occurred in the final runs. Scheduled work was idle in this
sample. Asset files were not copied; file-existence/version behavior differs from
real serving. Original vendor dependencies were read from the workspace.

Three runs each, excluding bootstrap/account selection and CGI process startup:

| Role/scenario | HTTP | Median ms | Connections | Statements | CGI response bytes |
| --- | --- | --- | --- | --- | --- |
| Admin Home | 200 | 262.74 | 13 | 34 | 831838 |
| Faculty Home | 200 | 258.08 | 13 | 37 | 490952 |
| Student with satisfied survey requirement | 200 | 258.31 | 14 | 42 | 728226 |
| Student with outstanding survey requirement | 302 | 77.87 | 2 | 5 | 294 |
| Parent Home, no eligible content in sample | 200 | 205.26 | 9 | 10 | 30043 |

Counts exclude connection SET commands and bootstrap reads. Statement counts include
prepared statements, not internal subqueries. Bytes include CGI headers and are
uncompressed; these are not browser transfer-size measurements. A preceding cold
Admin run took 1320.72 ms; the table is a subsequent local sample, not a capacity or
production latency claim. Full browser/network/assets, real login sessions, busy
publishing, representative linked Parents and concurrency remain unmeasured.

Findings: complete populated Home paths still open 13?14 connections. Four target-tag
lookups independently open connections; assess safe reuse before larger query rewrites.
Response volume is substantial (Admin ~832 KB). Inspect markup/data duplication before
considering pagination, since existing search/sort/filter behavior depends on the
server-rendered candidate set. Do not silently truncate eligible content.

Private snapshot, runner and logs:
`C:/Users/ctrlc/.codex/backups/bahay-ko/full-request-profile-20260930/`.

```powershell
python C:/Users/ctrlc/.codex/backups/bahay-ko/full-request-profile-20260930/run.py
```

The snapshot is fixed to this checkpoint; rebuild before claiming results for later
code. It contains private configuration/session artifacts and must remain outside
version control and the webroot. No production files or existing records changed.


## Reuse feed target-tag connection (30 September 2026)

PostService::attachTargetTags now obtains the already-open Announcement connection
instead of opening a connection per content type. SQL, label ordering and data are
unchanged. This private helper is called only while assembling the read-only feed.
No global connection singleton, transaction changes, schema changes or persistent
writes were introduced. Updated profile_home_feed.php to capture the announcement
candidate SELECT explicitly, since its connection now also handles tag SELECTs.
Profiler statement coverage now includes tag queries (previously uninstrumented);
its announcement bucket rises from one to five, not an increase in actual SQL.

Passed 9 legacy-count, 130 audience and 101 request-performance checks, plus five
exact complete-feed comparisons against the backed-up PostService (sampled active
roles and guest). PHP syntax and whitespace checks pass. The parity probe was run
inline, not added as a new test file. Existing fixture mutations are temporary only.

A new private write-blocked CGI snapshot confirms:
- Admin/Faculty Home connections: 13 -> 9.
- Student Home with satisfied survey requirement: 14 -> 10.
- Empty Parent sample: unchanged at 9; no tag lookups in either case.
- Survey-required Student redirects as before, using 2 connections.
- Statement totals, response sizes and status outcomes unchanged; zero blocked
  writes or other PHP log lines across three runs per scenario.

Local snapshot median ms: Admin 243.84, Faculty 214.41, completed Student 213.95,
Parent 190.17. One cold Admin outlier was 6440.39 ms. Do not claim a production speed
percentage from this small variable sample. The usual snapshot/session/asset and
network limitations still apply. Service-only profiler now reports roughly 29?46 ms
for populated feeds in this run, also subject to machine/cache variation.

```powershell
& C:/xampp/php/php.exe tests/feed_legacy_counts.php
& C:/xampp/php/php.exe tests/feed_audience.php
& C:/xampp/php/php.exe tests/request_performance.php
& C:/xampp/php/php.exe tests/profile_home_feed.php
& C:/xampp/php/php.exe -l app/services/PostService.php
& C:/xampp/php/php.exe -l tests/profile_home_feed.php
git diff --check
python C:/Users/ctrlc/.codex/backups/bahay-ko/feed-tag-connections-20260930/request-profile/run.py
```

Backup: `C:/Users/ctrlc/.codex/backups/bahay-ko/feed-tag-connections-20260930/`.
Next investigate Home response volume without silently changing search/filter scope.


## Home response-volume review (30 September 2026)

Measured the existing write-blocked CGI snapshot response body in memory. No HTML
was rewritten, persisted or sent to a third party. Python gzip.compress was applied
to the unchanged bytes as a size experiment, not installed HTTP compression.

| Sample | HTML body bytes | Line-leading spaces/tabs | Gzip bytes (experiment) |
| --- | --- | --- | --- |
| Admin | 831578 | 515459 | 28869 |
| Faculty | 490692 | 301523 | 20480 |
| Student with completed requirement | 727966 | 450427 | 26007 |
| Parent, empty feed sample | 29783 | 12455 | 4783 |

About 62% of the populated Home body is line-leading indentation. Compression of
unchanged HTML reduces this sample by roughly 96%, while retaining all feed/search
attributes and content. This does not reduce DOM size, database work, server-side
rendering or image/script/style transfers. Actual negotiated network measurements
are still required on the chosen host; gzip sizes depend on settings.

Local read-only configuration inspection: Apache httpd.conf has mod_deflate and
mod_brotli load lines commented out; PHP CLI reports zlib.output_compression Off.
This is configuration evidence, not a live Apache response-header verification.
No server configuration changed. Compression remains a host/deployment decision;
review authenticated response handling, compression ownership (server/proxy/PHP),
Vary/Content-Encoding, cache behavior and exclusions before enabling it.

Decision: preserve existing markup/search/filter scope for this pass. Do not add
an HTML regex rewriter, artificial loading screen, or content truncation just to
reduce raw byte count. Consider rendering/DOM changes only with browser evidence.

Reproduce local read-only size measurement:

```powershell
python C:/Users/ctrlc/.codex/backups/bahay-ko/feed-tag-connections-20260930/request-profile/response-size.py
```

The script uses the fixed snapshot described above, mocked authenticated sessions,
isolated session files, and write-blocked connections. Zero attempted writes or PHP
errors occurred. Only documentation changed in the workspace during this pass.


## Shared Home read connection (30 September 2026)

Home now supplies the request connection to PostController and PostService. The
seven feed models and recipient audience lookup reuse that connection. Default
construction remains available to existing posting/write routes. No SQL, recipient
rules, workflow transitions, schema or live records changed in this pass.

Changed implementation: index.php (news route), app/controllers/PostController.php,
and app/services/PostService.php. tests/home_connections.php now checks injected
connection identity and exact feed parity for the sampled roles and guest service.

Focused verification: 442 checks passed across home_connections (28), feed_audience
(130), feed_legacy_counts (9), request_performance (101), and publication_durability
(74). PHP syntax checks and git diff --check passed.

The private write-blocked full-request snapshot ran three times per scenario:

| Scenario | Previous connections | Current connections |
| --- | --- | --- |
| Admin / Faculty populated Home | 9 | 1 |
| Student with satisfied survey requirement | 10 | 2 |
| Parent empty-feed sample | 9 | 1 |
| Student redirected to required survey | 2 | 2 |

Statement totals, response sizes and status outcomes stayed unchanged. No blocked
writes or other PHP errors were recorded. The additional Student connection belongs
to the survey guard. These are local instrumented snapshots with synthetic accepted
sessions, not browser, real-login, concurrent-load or production benchmarks. Parent
coverage here is an empty-feed sample; recipient fixtures cover linkage rules.

The service-only profiler now observes audience reads through the announcement
connection as well; higher captured statement totals reflect wider instrumentation,
not newly introduced queries. No production speed percentage is claimed.

Reproduce focused verification:

```powershell
& C:/xampp/php/php.exe tests/home_connections.php
& C:/xampp/php/php.exe tests/feed_audience.php
& C:/xampp/php/php.exe tests/feed_legacy_counts.php
& C:/xampp/php/php.exe tests/request_performance.php
& C:/xampp/php/php.exe tests/publication_durability.php
& C:/xampp/php/php.exe -l index.php
& C:/xampp/php/php.exe -l app/controllers/PostController.php
& C:/xampp/php/php.exe -l app/services/PostService.php
& C:/xampp/php/php.exe -l tests/home_connections.php
git diff --check
```

Backup and private snapshot:
C:/Users/ctrlc/.codex/backups/bahay-ko/shared-feed-connection-20260930/.
The snapshot's request-profile/run.py reproduces its fixed checkpoint, not future
workspace changes. Browser journeys and deployment-host compression remain separate
verification work.


## Final Home optimization regression checkpoint (30 September 2026)

Fresh combined run passed 1,181 checks across 14 suites: feed_audience (130),
home_connections (28), feed_legacy_counts (9), feed_interests (27), pending_badge
(20), student_guard_writes (16), request_performance (101), content_access (124),
faculty_scope (158), publication_durability (74), parent_child_registration (59),
survey_privacy (166), content_access_http (95), and global_guards_http (174).
Fixtures used isolated temporary tables/files/sessions or read-only queries; no
existing records changed and no email or push was sent. Expected simulated delivery
failures appeared in the durability test output and its assertions passed.

Fresh service-only profiler medians: Admin 23.11 ms, Faculty 28.14 ms, Student 33.01
ms, empty Parent 5.46 ms. These exclude constructors, guards, rendering, assets and
network. Its seven instrumented model connections are a diagnostic setup, not the
shared production request connection count. Captured statements respectively total
30, 34, 35 and 6, including audience reads now observed on the announcement model.
The announcement EXPLAIN remains SIMPLE with no dependent subqueries. The small
local candidate table scan/filesort alone does not justify a schema change.

Reproduce the combined checkpoint (PowerShell):

```powershell
$suites = @('feed_audience','home_connections','feed_legacy_counts','feed_interests','pending_badge','student_guard_writes','request_performance','content_access','faculty_scope','publication_durability','parent_child_registration','survey_privacy','content_access_http','global_guards_http')
foreach ($suite in $suites) {
    & C:/xampp/php/php.exe "tests/$suite.php"
    if ($LASTEXITCODE -ne 0) { throw "Failed: $suite" }
}
& C:/xampp/php/php.exe tests/profile_home_feed.php
git diff --check
```

Current Home optimization implementation pass is complete. This does not close
project-wide performance or final integration gates: real authenticated browser
journeys, representative concurrent load/data volume, host compression, and worker
operations remain to verify. No production code changed at this checkpoint.
