> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# M2: workspace totals

## Outcome and evidence

ContentWorkspaceService::getStatusCounts previously counted arrays returned by getByWorkflowStatus, which caps each type at 250 rows. Its scheduled branch loaded announcements only, omitting Events, Documents and Surveys. Document ownership filtering also ran after the global list cap, so newer documents from other authors could hide a Faculty member's older documents.

Totals now come from one aggregate statement across announcements, events, documents and survey, grouped by workflow_status. Admin sees all owners; Faculty sees only their own content. The service independently validates role and positive user ID, returns all six existing status keys with integer zero defaults, and excludes unsupported workflow states from the displayed totals. No content rows are loaded to count them and no count cache is used.

Document::getByWorkflowStatus accepts an optional third owner argument. The workspace supplies it for Faculty so SQL filters ownership before ORDER BY/LIMIT. Existing two-argument callers remain compatible. The existing service ownership check remains in place too.

Counts are uncapped; displayed lists retain the existing 250-per-type limit. Pagination was not added, so a total may correctly exceed the displayed rows. All workflow actions, status fallback behavior, targeting rules, route registration, request methods and CSRF protection remain unchanged. No CSS, JavaScript, schema or existing records changed.

## Files

- app/models/ContentWorkspace.php — grouped count query using the existing connection.
- app/services/ContentWorkspaceService.php — authorize/read aggregate totals and supply the document owner filter.
- app/models/Document.php — optional SQL owner filter before the list limit.
- tests/workspace_counts.php — aggregate, owner, cap, scheduled-type and controller regression checks.
- tests/README.md — test entry.
- docs/workspace-counts-verification.md — this record.

Backups: C:/Users/ctrlc/.codex/backups/bahay-ko/workspace-counts-20260924-144400.

## Verification

From C:/xampp/htdocs/bahay-ko in PowerShell:

```powershell
& C:/xampp/php/php.exe tests/workspace_counts.php
& C:/xampp/php/php.exe tests/publication_durability.php
& C:/xampp/php/php.exe tests/faculty_scope.php
& C:/xampp/php/php.exe tests/faculty_scope_http.php
& C:/xampp/php/php.exe -l app/models/ContentWorkspace.php
& C:/xampp/php/php.exe -l app/models/Document.php
& C:/xampp/php/php.exe -l app/services/ContentWorkspaceService.php
& C:/xampp/php/php.exe -l tests/workspace_counts.php
git diff --check
```

Results: 37 workspace-count, 74 publication-durability, 158 Faculty-scope and 37 Faculty HTTP/render checks passed: 306 total. PHP syntax and Git whitespace checks pass. Existing line-ending warnings are unrelated.

The new test clones actual table definitions into connection-local temporary tables. It verifies an Admin total of 2,080 drafts, Faculty totals of 1,040 each, all scheduled types, empty/missing-owner totals, denied roles/invalid IDs, one-statement aggregation, controller output, workflow-transition refresh, and Faculty document visibility with more than 250 newer documents owned by another author. Test assertions use the existing normalized author_id field and preserve the existing invalid-status fallback to draft. Temporary tables disappear on connection close; no permanent rows, email or push are changed/sent.

Remaining manual check: view all six workspace tabs as Admin and Faculty and confirm the badges and scheduled items look correct. No browser visual test was performed; the existing root.css-based presentation is unchanged.
