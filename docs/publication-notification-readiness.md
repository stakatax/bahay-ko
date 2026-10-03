# H3: durable publication notifications

Implemented on 2026-09-24 after explicit approval of migration 027. The additive migration created `publication_notification_outbox` in local `olshcodb`. Existing tables and records were not migrated or replayed. The live queue was empty after isolated verification.

## Result

Previously publication committed before notification creation; exceptions were logged and published items were never selected again. Now content, audience targets, and a pending notification job commit together. Survey creation/edit also includes questions. If queue insertion fails, those publication writes roll back. Notification delivery occurs after commit, so delivery failure preserves successful publication and leaves durable work for retry.

Immediate create/edit, workspace approvals, the legacy survey approval route, scheduled release and calendar release use the queue. Draft/pending/scheduled/disabled-notification saves do not enqueue publication delivery. Scheduled UPDATE statements are restricted to the exact locked IDs that are queued, avoiding a timing gap between selection and UPDATE.

The dispatcher claims one job atomically with a random token and a five-minute lease. Failures retry after 30 seconds, exponentially increasing to at most one hour; there is no retry count that silently discards work. Expired claims can be reclaimed. Completion/retry updates require the current token. Partial recipient delivery is safe to repeat because existing per-user publication keys deduplicate notifications without resetting read state.

Archived/deleted/notification-disabled content is cancelled at dispatch. A later explicit publication can reactivate a cancelled job. Completed jobs retain existing one-publication-per-content semantics. Recipient eligibility and preferences are evaluated by the existing NotificationService at dispatch time. Publication category mapping now recognizes `content` as Content Updates. Nonduplicate notification INSERT failures propagate instead of being silently ignored.

## Exact files

- `app/models/BaseModel.php`: optional connection injection and explicit accessor; ordinary construction remains unchanged.
- `app/models/PublicationNotificationOutbox.php`: enqueue, claim, completion, cancellation and retry persistence.
- `app/services/PublicationTransaction.php`: shared transaction for content/targets/queue.
- `app/services/PublicationNotificationDispatcher.php`: bounded retry dispatch using existing notification logic.
- `app/services/PostService.php`: six create/edit publication boundaries; existing upload cleanup remains in place.
- `app/services/SurveyService.php`: create/edit and legacy approval boundaries.
- `app/models/Survey.php`: question replacement uses a savepoint inside an existing publication transaction.
- `app/services/ContentWorkspaceService.php`: atomic approval and queueing.
- `app/services/ContentReleaseService.php`: transactional enqueueing and bounded dispatch, even on runs with no newly due content.
- `app/services/NotificationService.php`: optional connection injection for isolated tests; default construction preserved.
- `app/models/Notification.php`: genuine duplicate handling and Content Updates preference mapping.
- `scripts/dispatch_publication_notifications.php`: independent CLI retry worker.
- `database/migrations/027_publication_notification_outbox.php`: approved additive migration, CLI only; default `--check` makes no changes.
- `database/baselines/027_publication_notification_outbox.sql` and `scripts/check_database_baseline.php`: explicit additive schema expectation; original snapshot unchanged.
- `tests/publication_durability.php`, `tests/publication_outbox_schema.php`, `tests/README.md`, and this document.

Originals backed up outside the webroot at `C:/Users/ctrlc/.codex/backups/bahay-ko/publication-durability-20260924-033250`.

No UI, role/scope policy, CSRF, route registration, deployment configuration, OS scheduled task, SMTP or push configuration was changed. Existing content-topic assignment/audit operations retain their separate transaction behavior. This fix covers publication notifications, not every workflow/review notification in the application.

## Verification

```powershell
& C:/xampp/php/php.exe tests/publication_durability.php
& C:/xampp/php/php.exe tests/publication_outbox_schema.php
& C:/xampp/php/php.exe tests/database_baseline.php
& C:/xampp/php/php.exe tests/faculty_scope.php
& C:/xampp/php/php.exe tests/faculty_scope_http.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe tests/content_access_http.php
& C:/xampp/php/php.exe tests/survey_privacy.php
& C:/xampp/php/php.exe tests/session_security.php
& C:/xampp/php/php.exe tests/session_security_http.php
& C:/xampp/php/php.exe scripts/check_database_baseline.php
git diff --check
```

Passed: publication durability 74, outbox schema 14, baseline verifier 12, Faculty service/HTTP 158/37, content access service/HTTP 124/95, survey privacy 166, session service/HTTP 35/105: **820 focused checks**. All 16 affected PHP files passed syntax checks. Whitespace verification passed with existing line-ending warnings only.

Read-only schema check: **63 expected / 63 actual tables, zero differences**. Original 62-table snapshot SHA-256 remains `53666033fc425f0702a2183a368807a73fe1257d9385ebaa919592f7047d9af5`. C5 fresh-install/restore scripts remain based on that historical snapshot; apply migration 027 after restoring it, before enabling this runtime code.

Tests use synthetic rows in connection-local temporary tables, including simulated database constraints to force failures. They test partial delivery/restart, duplicate/read-state preservation, active/stale claims, restored content, preferences, empty audiences, invalid IDs, actual service create/edit/approval, scheduled/calendar release and rollback. Expected failure-injection log entries are normal. No test email or browser push was sent. Lease competition is tested through sequential competing claims and stale tokens; simultaneous-process load testing is not claimed.

## Worker operation

The existing `scripts/dispatch_browser_push.php` already calls `ContentReleaseService::processPendingReleases()`, which now retries pending jobs. Its existing request-time counterpart also drains a bounded batch. A running scheduler is still needed for retries when there is no website traffic; no OS task was installed or modified here.

Independent publication-only worker command (creates real eligible in-system notifications; do not use as a harmless test command):

```powershell
& C:/xampp/php/php.exe C:/xampp/htdocs/bahay-ko/scripts/dispatch_publication_notifications.php 25
```

It creates in-system notification rows only; the existing email/push workers handle their queues. Exit 0 means the batch had no recorded delivery failures, 1 reports a delivery/worker failure, and 2 means invalid arguments. Run periodically in deployment; overlapping invocations use database claims. A process killed after a claim leaves it recoverable after the lease expires.

Read-only monitoring SQL:

```sql
SELECT delivery_status, COUNT(*) AS jobs, MIN(available_at) AS earliest_attempt
FROM publication_notification_outbox GROUP BY delivery_status;
SELECT outbox_id, content_type, content_id, attempt_count, available_at, last_error
FROM publication_notification_outbox
WHERE delivery_status = 'Pending' AND attempt_count > 0
ORDER BY available_at LIMIT 25;
```

Investigate persistent failures; do not delete pending jobs as a repair. Existing missed historical publications are not automatically replayed, to avoid unexpected mass notifications. No external-channel exactly-once delivery guarantee is claimed. Browser publishing/upload smoke checks and deployment scheduler/email/push verification remain pending. Keep the queue table if reverting PHP; do not drop stored work.
