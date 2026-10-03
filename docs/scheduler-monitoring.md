# M3: scheduler monitoring and completed registration cleanup

## Verified registrations

Read-only Task Scheduler inspection found two enabled tasks at the root task path:

| Task | Current action at inspection | Schedule / identity |
| --- | --- | --- |
| OLSHCO Browser Push Dispatcher | wscript.exe invoking dispatch_browser_push_hidden.vbs | Every minute; ctrlc; Interactive; IgnoreNew |
| OLSHCO Digital Hub Worker | C:/xampp/php/php-win.exe invoking dispatch_browser_push.php | Every minute; ctrlc; Interactive; IgnoreNew |

Both invoke the same combined publication/reminder/email/push worker. Neither action had an explicit working directory. The older task's argument had an unmatched opening quote. Both reported LastTaskResult=0 at inspection, but the former hidden launcher used shell.Run(..., 0, False), returning before PHP finished and discarding its exit code. IgnoreNew on that short-lived wrapper therefore did not track the real worker's lifetime.

Both task definitions were exported without modifying registrations. Backups and original source files are at C:/Users/ctrlc/.codex/backups/bahay-ko/scheduler-monitoring-20260924-211132. This corrects any assumption that no local worker registration existed: prior work did not install tasks, but these two pre-existing registrations are present.

## Implemented code fixes

- scripts/dispatch_browser_push_hidden.vbs now runs hidden, waits for PHP, propagates its exit code, sets the project working directory, derives the worker path from the launcher location, and returns 2 when the worker/runtime cannot launch. Its PHP executable remains the existing XAMPP path; other hosts require their own approved launcher configuration.
- scripts/dispatch_browser_push.php now reports publication-notification and reminder failure counts as well as email/push failures. Any of these failures, or inability to write/rotate the summary log, returns 1.
- Lock contention returns 75 instead of 0, distinguishing a skipped overlap from completed work. Locking and delivery order are unchanged. This is not proof that the other process completed successfully.
- Exception summaries retain class/code without logging exception messages that may contain credentials or other internal values.

Codes: 0 = completed without reported failures and summary log written; 1 = worker/delivery/logging failure; 2 = launcher/prelaunch failure; 75 (0x4B) = skipped because another worker owns the lock. Scheduler-internal failure codes are separate. A result of 0 is not proof of end-to-end delivery; inspect counts and queue state too.

No real worker was manually started and no test notification was sent. After explicit user approval, the retained task action was updated and the duplicate disabled as recorded below.

## Approved registration change applied

Retain OLSHCO Digital Hub Worker and its existing trigger, identity and settings. Replace its action with the corrected hidden launcher and explicit working directory. Then disable OLSHCO Browser Push Dispatcher; keep its definition for rollback. Do not delete either task, create a new trigger, change credentials, or manually start the worker.

The following configuration-changing commands were applied after explicit user approval on 2026-09-24 at approximately 21:19 Asia/Manila. The retained action was read back and verified before disabling the duplicate. They are recorded for audit, not as routine verification commands:

```powershell
$ErrorActionPreference = 'Stop'
$workerAction = New-ScheduledTaskAction -Execute "$env:SystemRoot\System32\wscript.exe" -Argument '//B //Nologo "C:\xampp\htdocs\bahay-ko\scripts\dispatch_browser_push_hidden.vbs"' -WorkingDirectory 'C:\xampp\htdocs\bahay-ko'
Set-ScheduledTask -TaskName 'OLSHCO Digital Hub Worker' -TaskPath '\' -Action $workerAction
Disable-ScheduledTask -TaskName 'OLSHCO Browser Push Dispatcher' -TaskPath '\'
```

After approval/execution, verify one enabled task, the quoted launcher action, working directory, and preserved one-minute trigger/IgnoreNew setting. Reinspect the next naturally scheduled run; do not manually send notifications as a test. If applying the action fails, stop before disabling the duplicate. Exported XML files preserve prior definitions for an explicitly approved rollback; disabling is reversible.

These Interactive registrations depend on the user being logged in. A production/service-account task that runs without an interactive login requires a separate deployment decision, appropriate credentials/environment, private logs and verified delivery. Do not claim unattended production readiness from the current local setup.

## Safe verification

From C:/xampp/htdocs/bahay-ko:

```powershell
& C:/xampp/php/php.exe tests/scheduler_monitoring.php
& C:/xampp/php/php.exe -l scripts/dispatch_browser_push.php
& C:/xampp/php/php.exe -l tests/scheduler_monitoring.php
Get-ScheduledTask -TaskName 'OLSHCO Browser Push Dispatcher','OLSHCO Digital Hub Worker' | Select-Object TaskName,State,Actions,Triggers,Settings
Get-ScheduledTaskInfo -TaskName 'OLSHCO Digital Hub Worker' -TaskPath '\'
Get-Content -LiteralPath logs/browser-push-dispatch.log -Tail 5
git diff --check
```

The log read requires an existing log; do not create/run the worker merely to populate one. Inspect logs privately because historical entries may predate sanitization. To detect the duplicate, confirm exactly one of the two tasks is enabled after the approved change. Both were enabled before approval; the duplicate is now disabled.

Observed: 30 isolated monitoring checks passed, including actual Windows launcher execution with inert PHP fixtures, waiting, working directory, success/failure exit propagation, missing worker, worker-stage failures, secret-safe exceptions, overlap, log rotation/write failure and HTTP 404. PHP syntax and Git whitespace checks pass. Tests copy the worker into a temporary project, replace all services with inert stubs, and isolate the temporary lock/log paths; they never connect to the database, invoke real delivery, or change registered tasks. Only their own temporary files are removed.

Files changed: scripts/dispatch_browser_push_hidden.vbs, scripts/dispatch_browser_push.php, tests/README.md, docs/deployment-configuration.md. Files added: tests/scheduler_monitoring.php and docs/scheduler-monitoring.md. No routes, permissions, schemas, UI or application records changed.

Completed verification: OLSHCO Digital Hub Worker remained enabled with the corrected quoted hidden-launcher action, explicit project working directory, PT1M trigger, ctrlc Interactive identity and IgnoreNew behavior. OLSHCO Browser Push Dispatcher was disabled, not deleted. The retained task ran naturally at 2026-09-24 21:20:01 Asia/Manila and returned LastTaskResult=0. Its 21:20:02 summary reported zero publication, reminder, email or push failures and no queued/sent deliveries. All 30 isolated monitoring checks passed again after the registration change. This confirms a successful idle scheduled cycle, not end-to-end message delivery. The local lock uses the task account's PHP temporary directory; processes running as other identities/hosts need separately reviewed coordination. This change does not add an external monitoring/alerting service or prove end-to-end email/push delivery.
