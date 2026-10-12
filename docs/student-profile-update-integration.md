# Required profile update integration: implementation prerequisites

Status: implemented locally and synced to the isolated evaluation tunnel on
10 October 2026 after user approval. Both databases were backed up before the
additive placement migration. Evaluator accounts and uploads were preserved.

The sync passed 20 public HTTPS smoke checks. Dark-mode rendered checks covered
16 main pages at 390px and 1365px, plus privacy, terms and profile-request dialogs;
no unintended white backgrounds or horizontal overflow were found in those checks.
The notification worker was restarted; this does not certify inbox delivery.

## Confirmed existing behavior

- StudentProfileService resolves open Active cycle assignments; future opening
  dates do not trigger the survey requirement before the cycle opens.
- index.php checks the requirement on every authenticated Student request and
  currently redirects to the questionnaire, with only a limited route allowlist.
- Activation creates assignments and initial reminder notifications; publication
  dispatch recovers missing initial notifications.
- Notification deduplication keys and existing email/push delivery queues can
  support periodic reminders without a separate reminder table.
- Assignment rows now store account confirmation and staged placement; a dedicated
  table preserves reported placement changes and Administrator review metadata.

## Persisted additions

See database/migrations/student_profile_placement_update.sql. Two nullable columns
on assignments hold confirmation time and the staged account-detail draft. A new
table records one initial placement change per assignment and subsequent review
metadata. Existing user placements and questionnaire responses are preserved.
No existing tables are dropped. scripts/migrate_profile_placement.php --apply-local
backs up olshcodb before applying the additive migration. Tests shadow application
tables with connection-local temporary tables; existing student records are not
reassigned during integration testing.

## Implementation behavior

1. Assigned students first confirm their account details, then complete the
   assigned questionnaire, then review and submit. Student ID, role and login
   email remain read-only; this work does not introduce identity/email changes.
2. Validated placement selections are staged on the assignment. Saving progress
   does not change user placement. Final completion atomically saves responses,
   updates placement and records the change. A failed submission changes neither
   the assignment completion status nor the effective placement.
3. Changes take effect on final submission and appear as student-reported until
   an Administrator verifies them. Corrections require an audited authorized
   action. Unchanged placement still records account confirmation.
4. New requests default to 14 days from the opening/activation date. Custom dates
   remain supported. Missing default deadlines are resolved at activation so a
   draft left unsent does not consume its completion window.
5. Reminder worker selects open Active cycles and incomplete active Students;
   initial request, every three days, day before due, due day. Use the cycle and
   calendar day as the deduplication identity. Avoid catch-up spam, coalesce
   overlapping reminder days, stop after completion or cycle closure. Late
   submission remains allowed; overdue is derived from due_at, not a new enum.
6. Required-update navigation resumes the unfinished step. Preserve logout,
   notifications and urgent announcement access using server eligibility checks;
   avoid a broad bypass that enables unrelated management or engagement routes.
7. Notifications remain preference-aware. Browser push needs a live subscription;
   email needs configured delivery and a running worker. Existing queued reminders
   are rechecked before delivery. Stale queued messages are marked Failed with
   retries exhausted and an explanatory reason, rather than being sent or falsely
   marked Sent. Existing delivery enums are preserved.

## Operation

Both scripts/dispatch_browser_push.php (existing combined delivery worker) and
scripts/dispatch_publication_notifications.php generate periodic profile reminders.
The combined worker also handles email and browser push delivery. Schedule the
combined worker every minute or keep the approved deployment worker running;
deduplication prevents repeated notifications on a reminder day. Workers were not
started to send real messages during development. No SMTP inbox/push-device delivery
claim is made by the fixture tests.

Administrator: Request profile update, choose students and period, Review students,
Send update request. Blank deadlines default to 14 days at activation. Placement
changes appear below the update monitor; verify them against school records or
correct a still-current reported placement. Verification of a historical record
does not overwrite the student's current placement.

Student: new required updates redirect to account confirmation. Save details and
continue, answer/save the questionnaire, then review the old/new placement in the
submission dialog. Notifications resume confirmed students at the questionnaire;
Review account details explicitly returns to step one. Identity, Student ID, role
and login email are read-only. Grade/section choices do not change placement before
final completion. Late submission is supported while the cycle stays Active.

Urgent access: a pending Student may open news&urgent_only=1; the server filters
this view to eligible Emergency/Important announcements. Normal publication/audience
checks still apply to opening and acknowledging those posts. Other content remains
behind the required-update gate.

Verified parent_student links follow the student's live placement. Independent
verified parent_child_record section claims are not silently reassigned by a
student's report; Administrators maintain those separately.

## Verification results

- 53 lifecycle/placement tests: staging, IDOR, hierarchy, final revalidation,
  transactional rollback, completion/history, Admin correction, concurrent school
  correction protection and 14-day default.
- 28 reminder tests: initial recovery, preferences, intervals/deadline days,
  deduplication, completion/closure suppression and future openings.
- 26 notification destination/ownership checks; 11 category/role checks; 4 queue
  suppression checks preserving already-sent deliveries.
- 231 global route/HTTPS checks including confirmation-before-questionnaire and
  urgent-feed exception; 105 session, 95 content-access and 83 safe-error HTTP checks.
- 4 dedicated placement HTTP checks for POST-only actions and CSRF rejection.
- 61 Parent/child registration, 35 session-service, 30 Parent HTTP checks.
- 9 Administrator rendering, 10 profile rendering and 18 theme preference checks.
- PHP/JavaScript syntax checks and synthetic Chrome desktop/mobile light/dark QA.

No real update cycle was activated and no email/push was sent by these tests.

## Checks before and after integration

Before: verify actual column types/indexes and migration state, establish private
backup, confirm active academic choices and parent linkage behavior, inspect queue
worker scheduling, and run baseline cycle/notification tests.

After: PHP/JS syntax, exact routes and POST-only actions, CSRF, Student self-only
access and IDOR, Faculty/Parent denial, hierarchy validation, transaction rollback,
staged progress/resume, double submissions, completed/closed/future/overdue cycles,
deduplication and preference-aware email/push queueing, stale queue cancellation,
Administrator review/correction, session and cache refresh, parent targeting,
urgent content accessibility, mobile/keyboard/light/dark checks.

Use temporary tables or disposable fixtures for tests; do not send evaluator email
or push during regression tests. Deploy/sync only with explicit authorization.
