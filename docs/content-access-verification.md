# Content eligibility and protected documents (C2)

Implemented on 2026-09-23. This addresses recipient access and direct document URLs; it does not complete the deployment audit.

## Behavior

- Information Hub, calendar, and announcement/event/document engagement share target matching against current active account data.
- Parent eligibility requires verified links to active Student accounts. Cached Parent academic details are not used as a fallback.
- Role, department, education level, program, grade/year, and section constraints must match the same profile.
- Engagement authorization runs before views, reactions, comments/replies, acknowledgments, or their notifications are written.
- Normal downloads require published, active, eligible documents.
- Administrator review and Faculty ownership access use an explicit workspace context.
- Department previews reuse existing Faculty department-preview authorization. Program-level governance hardening remains audit item C3; this change does not expand that policy.
- Documents stream through index.php?page=document_download&document_id=ID. Staff previews add an authorized context and inline=1.
- Direct HTTP access to the document directory and legacy PDF/Office/TXT upload URLs is denied. PHP can still read authorized files.
- Documents were not moved; existing database records and uploads were not changed.
- The download route does not run request-triggered scheduled publishing.
- Event detail lookup now returns its three feedback flags.
- No JavaScript or CSS files changed.

## Exact changed files

Paths are relative to C:\xampp\htdocs\bahay-ko.

| File | Change |
| --- | --- |
| app/models/ContentAudience.php | Current actor, verified Student profiles, batch target lookup |
| app/services/ContentAudienceService.php | Shared audience policy and existing specificity scores |
| app/services/PostService.php | Delegate recipient filtering to the shared policy |
| app/services/EventService.php | Apply shared policy to published calendar candidates |
| app/services/ContentEngagementService.php | Authorize before engagement; return protected document URLs |
| app/controllers/ContentEngagementController.php | Safe JSON denial and malformed content-type rejection |
| app/models/Event.php | Return reaction/comment/acknowledgment flags in findById() |
| app/services/DocumentDownloadService.php | Context authorization and contained file-path resolution |
| app/controllers/DocumentDownloadController.php | GET/HEAD streaming, safe errors and private headers |
| index.php | Register document_download; skip publishing for this route |
| include/components/document-card.php | Protected widget link |
| pages/news.php | Protected URL in document drawer data |
| pages/content_workspace.php | Authorized workspace preview URL |
| pages/department_content_preview.php | Authorized department preview URL |
| pages/postings.php | Protected existing-file reference when editing |
| Assets/uploads/.htaccess | Disable listing and direct document-extension access |
| Assets/uploads/documents/.htaccess | Deny direct document-directory requests |
| .gitignore | Track access rules; keep uploaded files ignored |
| tests/content_access.php | Isolated policy/service/SQL regression tests |
| tests/content_access_http.php | Isolated CGI delivery and JSON security tests |
| docs/content-access-verification.md | This change and verification record |

Original edited files and the manifest are backed up outside the web root:
C:\Users\ctrlc\.codex\backups\bahay-ko\content-access-20260923-000913

## Repeatable verification

Run from the project root with local MySQL available:

    & C:/xampp/php/php.exe tests/content_access.php
    & C:/xampp/php/php.exe tests/content_access_http.php
    git diff --check

Expected on the audited local fixture set:

- PASS: 124 content-access checks
- PASS: 95 CGI HTTP checks
- No whitespace diagnostics.

The first test copies schema definitions into connection-local temporary tables and inserts only into those tables. It creates and removes one randomly named upload fixture. The second reads existing metadata/files and creates isolated temporary CGI/session files. It requires an active Administrator, an active Student, and a readable document. PDF/text checks run when those MIME types exist, so the HTTP count can differ on another installation. Neither test runs publishing or sends notifications.

All 17 affected PHP files passed php -l. Original-file comparisons confirmed the focused changes. Git whitespace checks passed.

Actual local Apache checks:

- All 12 existing document/legacy file URLs returned 403.
- Upload and document-directory listings returned 403.
- An ordinary static image returned 200.
- Anonymous download returned 401.
- POST to the download route returned 405.

These commands must return 403 and 401 respectively:

    curl.exe --silent --head --output NUL --write-out '%{http_code}' http://localhost/bahay-ko/Assets/uploads/documents/
    curl.exe --silent --head --output NUL --write-out '%{http_code}' 'http://localhost/bahay-ko/index.php?page=document_download&document_id=1'

CGI checks also verified authorized byte-for-byte delivery, HEAD length/body behavior, attachment and preview headers, denied Student staff contexts, and JSON 401/405/419/422 responses.

## Browser checks still required

Use dedicated accounts/content in an isolated test environment for mutations:

1. Eligible Student: open a document from Information Hub and download it.
2. Ineligible Student: substitute a cross-audience ID; no details/comments/file should return.
3. Verified Parent: check linked Student audiences; pending/rejected/inactive-child links must not grant access.
4. Faculty owner and Administrator: open PDF/text workspace previews and download Office documents.
5. Faculty: verify the department-preview screen uses the authorized route.
6. Confirm event feedback controls agree with saved flags.
7. Check modal rendering, browser console/network errors, and mobile layout.

No authenticated visual-browser checks were performed. CGI verifies backend behavior, not browser PDF-viewer rendering.

## Deployment boundary

Direct-file protection depends on Apache honoring the supplied .htaccess rules. Local Apache does, as verified by actual 403 responses. Hosts that ignore .htaccess, other web servers, or independent static/CDN upload origins need equivalent denial rules before deployment.

Broader audit items, including Faculty publishing scope, schema/bootstrap, global session revocation, and notification reliability, remain separate work.
