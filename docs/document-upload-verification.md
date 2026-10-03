# H5: document uploads and upload-directory protection

## Outcome

New document uploads are checked before storage. The shared create/replacement path checks upload provenance and upload errors, a safe basename, the supported extension, actual byte size (nonempty, at most 20 MiB), server-detected MIME, and format signatures. It ignores browser-reported MIME and size. Randomized filenames and existing transaction/cleanup behavior remain in place.

PDF and plain text remain supported. DOC/XLS/PPT require compound-file signatures and the corresponding Office stream marker. DOCX/XLSX/PPTX require ZIP format, the matching main part, and a matching Office content-type manifest. Manifest inspection is bounded to 64 KiB, rejects DTD/entity declarations, disables XML network access, and never extracts archives or reads serialized archive metadata. Password-encrypted or incompatible Office containers are rejected with a validation message.

Apache now serves only the image/audio extensions produced by existing upload services directly: JPG/JPEG, PNG, WEBP, MP3, M4A, WAV, WEBM. Other extensions, hidden filenames and executable double extensions are denied. CGI/includes and directory listings are disabled; the default static handler and nosniff header are applied. The existing documents/.htaccess denies every direct request within that directory. Documents still pass through the authenticated document_download route and its audience/ownership checks.

No existing documents, database records, schema, UI, targeting, or publishing permissions were migrated or changed. No global Apache configuration was edited.

## Exact files

- app/services/DocumentUploadValidator.php — new upload validator.
- app/services/PostService.php — storeDocumentUpload delegates validation and stores measured size.
- Assets/uploads/.htaccess — static-media-only access and execution protection.
- tests/document_upload_http.php — real CGI multipart uploads and replacement assertions.
- tests/support/document_upload_fixture.php — connection-local temporary tables for replacement tests.
- tests/upload_directory_http.php — actual local Apache access checks using inert fixtures.
- tests/README.md — test instructions.
- docs/document-upload-verification.md — this record.

Existing files inspected and preserved: Assets/uploads/documents/.htaccess, Assets/uploads/profile-photos/.htaccess, app/controllers/DocumentDownloadController.php, app/services/DocumentDownloadService.php, app/controllers/PostController.php, app/models/Document.php, index.php. The post_store path still enforces Admin/Faculty access, POST and CSRF; document_download still restricts methods and checks fresh authorization.

Backups: C:/Users/ctrlc/.codex/backups/bahay-ko/document-upload-20260924-092843.

## Verification

Run in PowerShell from C:/xampp/htdocs/bahay-ko, on local development or staging:

```powershell
& C:/xampp/php/php.exe -l app/services/DocumentUploadValidator.php
& C:/xampp/php/php.exe -l app/services/PostService.php
& C:/xampp/php/php.exe -l tests/document_upload_http.php
& C:/xampp/php/php.exe -l tests/support/document_upload_fixture.php
& C:/xampp/php/php.exe -l tests/upload_directory_http.php
& C:/xampp/php/php.exe tests/document_upload_http.php
& C:/xampp/php/php.exe tests/upload_directory_http.php
& C:/xampp/php/php.exe tests/content_access.php
& C:/xampp/php/php.exe tests/content_access_http.php
& C:/xampp/php/php.exe tests/publication_durability.php
& C:/xampp/php/php.exe tests/request_errors_http.php
git diff --check
```

Observed: 125 upload checks, 68 Apache checks, 124 content-access checks, 95 download/content HTTP checks, 74 publication checks, and 79 request-error checks: 565 passing assertions. PHP syntax checks pass. Expected injected publication failures appear in the publication test's diagnostic output.

Upload coverage includes supported format classification, uppercase extension, arbitrary/renamed ZIP, mismatched Office types, invalid/oversized/DTD manifests, executable/HTML/binary disguise, missing/incomplete/non-upload files, empty/oversized files, forged size metadata, randomized storage and byte preservation. Replacement checks use the real updateDocument path: saved metadata reloads, the old file is deleted only on success, validation/SQL failures preserve the old file/metadata, and failed replacement leaves no new uploaded file.

The CGI tests create isolated scripts outside the webroot and remove precisely their own upload files. Replacement tests clone actual table definitions into connection-local temporary tables; no permanent records change. They do not send email or push. The Apache test requires local XAMPP Apache at http://127.0.0.1/bahay-ko; it creates uniquely named inert files under uploads, verifies denial/static delivery and removes those files. Do not run these fixture tests against production. Abrupt termination can leave test files requiring review.

## Remaining verification and limits

- Manually upload representative real school PDF/Word/Excel/PowerPoint/text documents and replace a draft through the normal UI; confirm download/preview, Faculty review submission, and profile/cover/audio display. Automated legacy Office fixtures verify classification, not Office rendering. No browser visual test was performed.
- Format/MIME checks are not malware scanning or a guarantee of full document validity. Legacy Office files can contain macros. No antivirus service was introduced; existing stored documents were not rescanned.
- PHP requires fileinfo, Phar ZIP support, DOM/libxml and mbstring. They are available in this XAMPP environment; ZipArchive is not required. PHP/proxy upload limits can be lower than the application's 20 MiB limit.
- Deployment must honor these Apache directory rules (including AllowOverride) or provide equivalent server rules. A different web server may ignore .htaccess. Recheck direct-file denial on staging; do not assume local Apache results establish production protection.
