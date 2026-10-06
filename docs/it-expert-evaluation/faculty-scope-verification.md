> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Faculty posting scope verification

Verified locally on 2026-09-23. This is the C3 deployment-readiness change, following the content-access fixes.

## Confirmed behavior

The user's latest confirmation narrows the earlier broad IBED allowance in AGENTS.md/rule.md:

- Administrator: schoolwide or specific recipient roles and academic groups.
- College Faculty: assigned division, education level, and College program; may narrow to year/section.
- IBED Faculty: assigned division and education level only (Elementary, Junior High, and Senior High are separate boundaries); may narrow to valid strand, grade, or section within that level.
- Faculty: draft or submit for Administrator review; no direct publishing.
- Student/Parent: no content-management or Faculty-assignment authority.

The server reads the current active account and academic assignment. Incomplete/inactive assignments fail closed with an Administrator-assignment message. Changes apply on the next submission without relying on cached session academic fields. A stale staff role requires signing in again.

## Causes and changes

Both target builders previously forced the session department and then cleared it for schoolwide requests. They did not constrain College program or IBED education level. The posting controller wrote unrelated form keys that did not protect the actual target fields.

Faculty provisioning also attempted to obtain education_level_id from a department lookup that does not return it; creation stored program as NULL, and assignment updates cleared it. The Administrator forms now explicitly select education level and College program. The new correction endpoint is Faculty-only; Student academic editing was not exposed.

Target validation now runs before uploads/content writes on all announcement/event/document create/edit paths and before survey writes. Existing drafts submitted directly from the workspace must already have valid targets within the current assignment; otherwise the Faculty member must edit them first. Existing content and target rows were not automatically rewritten.

The academic lookup previously coupled whole-program targeting to incidental existing sections. Section/program matching is now conditional on an actual section selection. All active College programs, including those without sections, were exercised.

## Exact affected files

| File | Change |
| --- | --- |
| app/services/FacultyScopeService.php (new) | Shared fresh-account assignment, submission, and saved-draft scope authorization. |
| app/services/PostService.php | Scope checks, target validation before writes/uploads, section-optional program validation. |
| app/services/SurveyService.php | Same protection for survey creation/editing. |
| app/services/ContentWorkspaceService.php | Check saved targets before direct draft/rejected submission for review. |
| app/services/UserManagementService.php | Validate and persist explicit Faculty assignments; Administrator-only Faculty correction wrapper. |
| app/models/User.php | Persist College program when provisioning Faculty. |
| app/controllers/PostController.php | Load current assignment for form display; remove ineffective legacy POST-field override. |
| app/controllers/UserManagementController.php | Accept explicit provisioning fields; new protected Faculty assignment action. |
| index.php | Allowlist and register manage_user_update_faculty_assignment with Admin role gate. |
| pages/manage_users.php | Faculty assignment correction form; shared fields in provisioning form. |
| include/components/faculty-assignment-fields.php (new) | Escaped division, education-level, and College-program selects. |
| Assets/js/manage-users.js | Dependent assignment options, confirmation, duplicate-submit prevention. |
| Assets/css/manage-users.css | Hide the inapplicable College-program label despite its grid display rule. |
| pages/postings.php | Administrator-only schoolwide choice, Faculty scope message, assignment configuration. |
| Assets/js/posting.js | Lock assigned academic boundaries and filter narrower recipients. |
| tests/faculty_scope.php (new) | Server-side and database-fixture regression checks. |
| tests/faculty_scope_http.php (new) | Isolated CGI request guards and rendered form checks. |
| tests/faculty_scope_ui.js (new) | Focused simulations of the actual JavaScript form functions. |
| docs/faculty-scope-verification.md (new) | This verification record. |

Original versions of the 13 modified existing files are backed up outside the webroot at:
`C:/Users/ctrlc/.codex/backups/bahay-ko/faculty-scope-20260923-151130/manifest.json`.
No commits, deployment, migrations, permanent schema changes, or updates to existing account/content records were performed. Unrelated working-tree edits were preserved.

## Verification commands and results

Run from `C:/xampp/htdocs/bahay-ko` in PowerShell:

```powershell
& C:/xampp/php/php.exe tests/faculty_scope.php
node tests/faculty_scope_ui.js
& C:/xampp/php/php.exe tests/faculty_scope_http.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe tests/content_access_http.php
```

Observed results:

- 158 Faculty server-side checks passed: current actor/status, missing assignment, invalid/array/negative IDs, cross-program/cross-IBED/mixed scopes, narrower scopes, all active College programs, Administrator broad/specific targeting, stale role, both real builders, pre-write denial for eight create/edit paths, all four saved-draft submission paths, assignment save/reload/repeat, and Faculty provisioning/password-change requirement.
- 15 JavaScript behavior checks passed: dependent assignment fields, incompatible-value clearing, confirmation cancellation, duplicate submission, College locks, Junior/Senior High boundaries, optional Senior High strand, and editable Administrator filters.
- 37 CGI HTTP/render checks passed: GET returns 405 with Allow: POST; invalid/missing CSRF returns 419; anonymous/non-Admin redirects follow existing app conventions; invalid managed IDs and missing confirmation reject before writes; forms render with correct fields, CSRF and schoolwide visibility.
- Existing content-access regressions passed: 124 server-side checks and 95 CGI checks.

The Faculty database test shadows tables with connection-local temporary tables, copying academic metadata only. Fixture accounts are synthetic. Temporary tables disappear when the connection closes. The CGI suite creates and removes an isolated harness/session directory outside the webroot and calls controllers directly, avoiding index.php's unrelated publishing worker. Its valid mutation paths are not executed against existing records. The existing content-access suite also creates/removes one temporary upload fixture.

Syntax checks passed for all 14 affected/new PHP files and all three affected/new JavaScript files. `git diff --check` passed.

```powershell
$phpFiles = @(
  'app/services/FacultyScopeService.php', 'app/services/PostService.php',
  'app/services/SurveyService.php', 'app/services/ContentWorkspaceService.php',
  'app/services/UserManagementService.php', 'app/models/User.php',
  'app/controllers/UserManagementController.php', 'app/controllers/PostController.php',
  'pages/manage_users.php', 'pages/postings.php',
  'include/components/faculty-assignment-fields.php', 'index.php',
  'tests/faculty_scope.php', 'tests/faculty_scope_http.php'
)
foreach ($file in $phpFiles) { & C:/xampp/php/php.exe -l $file }
node --check Assets/js/manage-users.js
node --check Assets/js/posting.js
node --check tests/faculty_scope_ui.js
git diff --check
rg -n 'manage_user_update_faculty_assignment' index.php pages/manage_users.php
```

Expected: syntax checks succeed; whitespace check has no errors; the route occurs in the action allowlist, switch, and form action.

## Remaining browser checks and operational step

There was no browser automation tool available. DOM simulations and server rendering do not establish visual layout, keyboard/modal behavior, or complete interactive browser flows.

On an authorized test environment:

1. As Administrator, open Manage Users, select a Faculty account, and use Overview > Faculty Posting Assignment. Verify cancellation, confirmation, saved-value reload, keyboard navigation, and narrow-screen layout. Repeat with College and each IBED level.
2. Test Faculty creation with the new fields and the existing temporary-password modal. Verify incompatible fields reset when division changes, and only College requires a program.
3. For each Faculty scope, open the posting form. Confirm School-wide is absent, assigned selectors are locked, and narrower valid recipients remain selectable. Save/reload and submit a test announcement, event, document, and survey for review.
4. Try submitting an older broad-scope draft from the workspace: it must request an edit before review submission. After a test assignment change, an old-program draft must also be rejected.
5. As Administrator, verify schoolwide and custom posting remain available and Faculty submissions still follow the normal approval workflow.

The existing College Faculty account currently has no program assignment. An Administrator must choose its actual College education level/program through the new form before it can post; no program was guessed or assigned automatically.

This change governs Faculty authoring and review submission. Existing department analytics/preview permissions and historic published audiences were not changed. It does not resolve the remaining deployment audit items or establish overall deployment readiness.
