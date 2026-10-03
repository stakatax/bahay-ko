# Parent registration without a child login

Prepared 26 September 2026. **Migration 029 applied locally after explicit approval on 26 September 2026.** The new checkbox is enabled; existing linked-account registration remains available.

## Behavior

Parents may submit the child's full name, optional school Student ID, active grade/section, relationship, reason and optional explanation. Submitted details confer no access. Account and child record remain Pending; legal acceptance and all registration writes share one transaction. No placeholder Student account or password is generated.

Admin reviews the child details and reason in the active `pages/account_approvals.php`, verifies enrollment/relationship through school records, and supplies a verification note. Approval/rejection changes both states transactionally. A reason is not verification. Parent eligibility is derived only from Verified child records or existing Verified links to Active Students.

Manage Users provides **Review child verification** for later correction, revocation and account linking. Review decisions retain Admin ID, timestamp, note and before/after snapshots. Linking validates an active Student and any recorded school ID, deduplicates the existing Parent-Student relationship and retires independent eligibility atomically. It never auto-matches by name or creates a Student account. A later inactive Student does not revive the old independent fallback.

Announcements, events, documents and content surveys use the shared eligibility service. Notification recipient resolution now checks the same verified Parent scope instead of trusting cached Parent academic columns. Existing delivery preferences and notification deduplication remain in force. Parent linkage still does not grant access to private Student questionnaire responses.

## Files

- Registration: `pages/register.php`, `Assets/js/register.js`, `app/controllers/AuthController.php`, `app/services/AuthService.php`.
- Approval/maintenance: `app/controllers/AccountApprovalController.php`, `app/services/AccountApprovalService.php`, `app/models/User.php`, `app/models/ParentChildRecord.php`, `pages/account_approvals.php`, `pages/manage_users.php`, `include/components/parent-child-review.php`, `Assets/js/account-approvals.js`, `Assets/css/account-approvals.css`.
- Eligibility/delivery: `app/models/ContentAudience.php`, `app/services/NotificationService.php`.
- Route: `parent_child_update` in `index.php`; Admin only, POST and CSRF required, valid positive Parent ID, model rechecks active Admin and Parent state. It is a normal HTML form action.
- Schema: `database/migrations/029_parent_child_record.php`; one additive table, four foreign keys, no existing-record rewrites. Active views are in root `pages/`; `config/pages/` is legacy and was not modified.

## Verification evidence

Commands run from the project root, using `C:/xampp/php/php.exe` when `php` is not on PATH:

| Command | Result |
| --- | --- |
| `php tests/parent_child_registration.php` | 59 checks passed: new and legacy registration/approval, validation, rollback, four-type targeting, notification preferences, academic deactivation, revoke/reverify/link, history and denied actors. Temporary tables only. |
| `php tests/parent_child_http.php` | 30 checks passed: actual Admin controller method/role/CSRF/ID guards through PHP CGI; service stub prevents writes. |
| `node tests/parent_child_ui.js` | 12 checks passed: checkbox/role switching, conditional fields, confirmation and duplicate-click state; DOM simulations. |
| `php tests/parent_child_views.php` | 11 checks passed: actual root view rendering, escaping, pending/active/linked actions and option hidden before migration. |
| `php tests/content_access.php` | 124 checks passed. |
| `php tests/faculty_scope.php` | 158 checks passed. |
| `php tests/abuse_protection.php` | 53 checks passed. |
| `php tests/publication_durability.php` | 74 checks passed; no email/push sent. |
| `php tests/request_performance.php` | 101 checks passed. |
| `php tests/session_security.php` | 35 checks passed. |

All suites above passed again after activation (657 checks total). The scratch rehearsal verified migration SQL, its existence guard, four real foreign keys and orphan rejection. The actual migration was applied and rerun successfully; the installed definition matches rehearsal. The local registration GET returned HTTP 200 with the new fields. Visual browser acceptance remains pending. Do not treat source rendering/DOM simulations as a responsive-layout or accessibility pass. No real registration, email/push delivery, production deployment or existing-data rewrite was performed.

## Activation and recovery evidence

Private source backup: `C:/Users/ctrlc/.codex/backups/bahay-ko/parent-registration-20260926-130828/`.

Pre-migration database backup: `C:/Users/ctrlc/.codex/backups/bahay-ko/migration029-20260926-215435/olshcodb-before-029.sql` (500,932 bytes), with a SHA-256 manifest and before/after schema metadata in the same private directory. Backup contents include application data and must remain outside the web root and repository. A complete restore exercise is not claimed.

The scratch database `olshco_parent_029_verify_20260926_215435` contains schema only and was retained. The initial subprocess rehearsal stopped because Windows omitted an empty environment value; the rehearsal then used the configured connection directly. No application records were copied.

The approved live migration created only `parent_child_record`; its repeat invocation reported the table already existed and made no changes. All 64 pre-existing table definitions (excluding next-ID counters), row counts and data checksums matched the pre-migration capture. `push_delivery` advanced only its next-ID counter during the verification window; its rows/checksum were unchanged. The new table had zero rows immediately after installation.

Read-only verification:

```powershell
php database/migrations/029_parent_child_record.php --check
php scripts/check_database_baseline.php
```

Expected: table already exists; **65 baseline tables, 65 current tables, 0 differences**. The [additive baseline](../database/baselines/029_parent_child_record.sql), [data dictionary](database-reference.md) and ER source include 029. It adds 15 columns and four foreign keys, for 624 columns and 125 declared FK constraints overall.

The migration is an existence-guarded installer, not a schema-repair tool. Do not drop a populated child-record table to undo deployment; preserve verification history and plan any rollback explicitly. No production deployment was performed.

## Manual acceptance

Use designated test data: register without a child login; observe Pending status and no access; review the displayed reason/class; approve with a verification note; verify correct and incorrect class content/notifications; correct the class; revoke/reverify; register/approve the actual Student separately and link once. Test keyboard navigation, mobile form width, inline error focus and confirmation cancellation. These browser cases remain Not run.

One initial child claim per new Parent application is supported, matching the current single-child registration form. Adding several independent children in one application is outside this change. Enrollment verification and later class maintenance are manual Admin duties; no school-roster integration or automatic identity matching is claimed.
