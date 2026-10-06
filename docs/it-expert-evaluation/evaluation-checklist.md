> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Role-based evaluation checklist

Version 1.1 - 2 October 2026

This is a ready-to-use acceptance protocol. All manual cases start **Not run**; related historical automated passes do not populate manual results. Use a controlled evaluation environment and designated accounts/data. Cases that send email/push, change assignments or exercise recovery need the evaluation operator's planned test scope, not production records.

[Evaluator guide](system-evaluation.md) | [User manual](user-manual.md) | [Fillable CSV](evaluation-record.md)

## Evaluation record

Record evaluator/name and role, date/time, application release/commit and dirty-tree state, host/environment, PHP/database/browser versions, viewport/device, test dataset, evidence folder, and any approved deviations. Keep secrets, session cookies and individual real Student responses out of evidence.

Prepare Admin, College Faculty (two different programs), IBED Faculty (at least two levels), eligible/ineligible Students, verified/Pending/revoked Parent links, and inactive/locked accounts. Prepare content in every workflow state, public/private test attachments, small-group survey fixtures, and versioned profile cycles. Use isolated suites where a scenario would otherwise be destructive or expose sensitive records.

Result vocabulary: **Pass**, **Fail**, **Blocked** (missing prerequisite), **Not run**, **Not applicable** (documented reason). A blank actual-result field is not a pass. For every executed row record actual outcome, evidence ID/path, evaluator/date, defect ID and follow-up. The CSV provides these columns.

## Acceptance scenarios

| ID | Actor | Area / steps | Expected result | Severity if unmet | Manual status |
| --- | --- | --- | --- | --- | --- |
| AUTH-01 | Guest | **Public access:** Open Home, Academics, About School and Contact without signing in. | Public pages load; protected account data is not exposed. | Medium | Not run |
| AUTH-02 | Student applicant | **Registration:** Submit a valid Student application with approved test academic values and legal acceptance; try sign-in before approval. | Registration is Pending, legal acceptance is retained, and sign-in does not grant active access. | High | Not run |
| AUTH-03 | Parent applicant / Admin | **Parent verification:** Register against an active test Student, then approve/reject through the designated verification process. | Unverified relationships do not grant the same eligibility as verified links; decision and account state are consistent. | Critical | Not run |
| AUTH-04 | Guest | **Registration validation:** Submit duplicate email/Student ID and omit legal acceptance in separate test requests. | Server rejects each invalid submission without duplicate/partial accounts. | High | Not run |
| AUTH-05 | Admin / Faculty | **Faculty provisioning:** Create Faculty with a valid assignment; sign in with temporary credentials. | Forced password change occurs and scope matches the saved assignment. | High | Not run |
| AUTH-06 | All roles | **Sign-in / lockout:** Sign in correctly; separately make five wrong-password attempts on a designated test account. | Normal login works; wrong/unknown/locked credentials remain generic and the lock is enforced. | High | Not run |
| AUTH-07 | All roles / Admin | **Session revocation:** Use two test sessions; change role/status or password through an authorized workflow. | Stale sessions cannot retain their former access on a subsequent request. | Critical | Not run |
| AUTH-08 | Guest | **Password recovery:** Request a reset with configured test email; redeem once, retry the used link and an expired fixture. | Valid reset works; used/expired tokens are rejected; no account existence disclosure in request response. | Critical | Not run |
| AUTH-09 | All roles | **CSRF and methods:** Send missing/incorrect CSRF and wrong-method requests to representative mutation endpoints. | Requests are denied before business writes; response matches HTML/JSON contract. | Critical | Not run |
| AUTH-10 | All roles | **Global requirements:** With password, legal and Student-profile requirements, attempt JSON actions and ordinary navigation. | Correct precedence; JSON stays JSON with reason/destination; normal pages redirect; logout/remediation remain reachable. | High | Not run |
| AUTH-11 | Guest / all roles | **Rate limits:** Exercise configured thresholds using isolated suites/test buckets, then test expiry. | 429 and retry guidance after threshold; no blocked action writes; eligible attempts resume after expiry. | High | Not run |
| ROLE-01 | Student / Parent / Faculty | **Admin URL tampering:** Directly request account approvals, user management, academic/profile management and dashboard exports. | Unauthorized roles are denied by the server, including direct URL/form requests. | Critical | Not run |
| ROLE-02 | College Faculty | **Program boundary:** Save/submit valid assigned-program content; alter target IDs to another program. | In-scope request works; cross-program request is rejected without writes. | Critical | Not run |
| ROLE-03 | IBED Faculty | **Education-level boundary:** Try own-level targets and different Elementary/JHS/SHS targets. | Only the assigned education level and its valid narrowing are accepted. | Critical | Not run |
| ROLE-04 | Faculty | **Missing assignment:** Remove/inactivate assignment in an isolated fixture; try authoring and submitting an old draft. | Scope validation fails closed with useful guidance. | High | Not run |
| ROLE-05 | Admin | **Broad/custom targets:** Create test content for schoolwide and specific role/academic groups; inspect with eligible/ineligible accounts. | Recipient visibility matches saved targets without broadening Faculty authority. | Critical | Not run |
| ROLE-06 | Parent | **Linked-child privacy:** Use verified, Pending and revoked links; request private Student profile URLs/IDs. | Only permitted content eligibility follows verified links; private Student responses remain inaccessible. | Critical | Not run |
| ROLE-07 | Faculty / Admin | **Analytics boundary:** Request Department Analytics/export as Faculty and as Admin; request Dashboard as each. | Faculty-only department controller and Admin-only Dashboard rules match the documented matrix. | High | Not run |
| CONTENT-01 | Faculty | **Create and draft:** Create each of announcement/event/document/survey; save, reload and inspect fields/targets. | Drafts retain values and are not published to recipients. | High | Not run |
| CONTENT-02 | Faculty / Admin | **Review / rejection:** Submit a draft, reject with a reason, revise and resubmit. Attempt Faculty self-approval. | Valid review cycle succeeds; rejection reason retained; self-approval denied. | Critical | Not run |
| CONTENT-03 | Admin / recipients | **Immediate publication:** Approve/publish an eligible fixture and inspect matching/nonmatching recipients. | Content/targets/outbox commit together; only eligible recipients see published content. | Critical | Not run |
| CONTENT-04 | Admin / operator | **Scheduled/calendar release:** Approve future release (and calendar-linked release where offered), inspect before/after due condition. | Not visible early; released by the authorized process after its condition; failed delivery does not corrupt content. | High | Not run |
| CONTENT-05 | Owner / Admin | **Archive and restore:** Archive permitted published content; use available owner restore-to-draft action. | Active circulation stops; restore yields Draft and does not immediately republish. | High | Not run |
| CONTENT-06 | Faculty | **Ownership and invalid IDs:** Try another owner, missing, zero, negative, malformed and nonexistent content IDs. | No unauthorized view/edit/action or unintended write. | Critical | Not run |
| CONTENT-07 | Eligible recipients | **Engagement / duplicates:** Open repeatedly, react twice, reply, and acknowledge twice; repeat with interaction disabled. | Expected deduplication and reply linkage; disabled actions denied; no repeated acknowledgment record. | High | Not run |
| CONTENT-08 | Eligible / ineligible users | **Document delivery:** Use route GET/HEAD and preview; tamper document ID/context and try raw storage URL. | Authorized bytes/headers work; unauthorized and direct storage access are denied. | Critical | Not run |
| CONTENT-09 | Staff | **Upload validation:** Upload valid supported formats, misleading extension/MIME, oversized/empty/encrypted file; attempt replacement failure. | Invalid files rejected; valid replacement succeeds; failure preserves prior attachment/transaction state. | Critical | Not run |
| SURVEY-01 | Eligible recipients | **Content survey:** Answer each configured question type; omit required answers; retry duplicate submission and closed period. | Valid response saved once; invalid/duplicate/unavailable submission denied. | High | Not run |
| SURVEY-02 | Admin / owner Faculty | **Small survey:** Inspect zero, four and five distinct respondent fixtures. | Zero remains empty; one-four suppressed; threshold applied without Admin bypass. | Critical | Not run |
| SURVEY-03 | Admin / owner Faculty | **Question/choice privacy:** Use optional question answered by four and an imbalanced small-choice group among larger overall responses. | Question/complementary breakdown suppressed without leaking identity/time metadata. | Critical | Not run |
| SURVEY-04 | Student / Parent / other Faculty | **Results access:** Request another survey results URL directly. | Only permitted Admin/owner Faculty access; participant status alone does not grant results management. | Critical | Not run |
| PROFILE-01 | Student | **Interests:** Save/update/clear interest levels and reload; inspect unrelated targeted content. | Preferences persist without overriding content eligibility. | Medium | Not run |
| PROFILE-02 | Student | **Partial/completed profile:** Save a partial assignment, resume, then complete all required sections. | Partial data preserved without claiming completion; completion updates the assignment/guard. | High | Not run |
| PROFILE-03 | Student | **Optional sensitive consent:** Submit optional sensitive answers with and without consent using synthetic values. | Storage and display follow consent; no invented mandatory sensitive consent. | Critical | Not run |
| PROFILE-04 | Admin | **Questionnaire version:** Create Draft from a version; add/edit/change question status; try equivalent non-Draft alteration. | Draft edits retained; immutable version restrictions enforced. | High | Not run |
| PROFILE-05 | Admin | **Cycle preview/activation:** Create each supported scope fixture, preview recipients, activate and close an isolated cycle. | Preview matches scope; assignments/notifications and closure respect transactional/history rules. | High | Not run |
| PROFILE-06 | Admin / Student | **History and ownership:** Inspect version/cycle history after a new cycle; attempt another Student response access. | History remains tied to its version/cycle; private responses cannot be accessed by another Student. | Critical | Not run |
| NOTIFY-01 | All roles | **Channel/category preferences:** Change master/category delivery preferences and reload. | Saved preferences govern eligible future delivery without corrupting read state. | High | Not run |
| NOTIFY-02 | Recipients / operator | **Real delivery:** Use designated test inbox/device for one approved publication/reminder and inspect queue plus received message. | In-system/email/push behavior matches enabled channels; failures and retries are visible without unintended duplicates. | High | Not run |
| NOTIFY-03 | All roles | **Read state:** Open a notice, mark all read, reload; revoke content eligibility before reopening an old notice. | Read state persists; notification links do not bypass content authorization. | High | Not run |
| NOTIFY-04 | Operator | **Outbox failure/retry:** Run isolated durability fixtures for enqueue rollback, partial dispatch and expired lease. | Atomic publication, retry recovery and deduplication match the documented tests. | Critical | Not run |
| ADMIN-01 | Admin | **Academic structure:** Create/edit/change status of isolated catalog records with reasons; test mismatched parent IDs. | Valid hierarchy and history preserved; invalid hierarchy rejected. | High | Not run |
| ADMIN-02 | Admin | **Advisory intake:** Preview approved official source, queue/review it and create an announcement draft; test untrusted source rejection. | Administrative access, trusted-source checks and normal draft review remain in force. | High | Not run |
| ADMIN-03 | Admin / Faculty | **Reports and counts:** Compare workspace counts with controlled totals beyond list cap; inspect scheduled content types and permitted exports. | Counts are uncapped and include scheduled types; reports respect controller scope and documented period/current-state distinctions. | High | Not run |
| OPS-01 | Operator | **Configuration/baseline:** Run read-only preflight and schema comparison using the evaluation environment. | Expected settings and 65-table definitions verified; failures recorded without secrets. | High | Not run |
| OPS-02 | Operator | **Scheduler monitoring:** Inspect the intended task, next natural run, exit code, log summary and queue state. | One intended worker registration, propagated results and visible failures; no assumption that idle success proves delivery. | High | Not run |
| OPS-03 | Operator | **HTTPS/proxy:** Use chosen external HTTPS host and inspect cookie flags; test untrusted forwarding headers in staging. | Secure/HttpOnly/SameSite correct; trusted client IP and TLS behavior do not trust arbitrary request headers. | Critical | Not run |
| OPS-04 | Operator | **Private artifacts:** Request protected config/source/test/log/dump locations from the deployed web server using inert checks. | No secrets, source, test fixtures or private documents exposed. | Critical | Not run |
| OPS-05 | Operator | **Backup/restore:** Execute the approved isolated database/uploads/config recovery procedure and record time/data age. | Restored workflows/files/history verified; measured RTO/RPO meet agreed objectives without live delivery. | Critical | Not run |
| OPS-06 | Evaluator | **Unknown route:** Request a nonexistent page selector. | HTTP 404 is returned with a safe themed HTML page or JSON for an AJAX request; no internal details or silent Home fallback. | Medium | Not run |
| UI-01 | All roles | **Keyboard and focus:** Navigate primary forms/modals using keyboard only; open/close confirmation and error dialogs. | Controls usable, focus visible/restored, labels meaningful; no keyboard trap. | High | Not run |
| UI-02 | All roles | **Responsive layout:** Exercise phone/tablet/desktop widths and zoom on sign-in, posting, tables, survey and profile screens. | No concealed clipping or unintended horizontal overflow; content/actions remain readable and reachable. | Medium | Not run |
| UI-03 | All roles | **Feedback and duplicate submit:** Try slow response, validation errors and repeated clicks on key forms. | Clear pending/error/success state; no duplicate business effect; controls recover on failure. | High | Not run |
| UI-04 | Evaluator | **Browser/asset errors:** Walk the manual in selected browsers; inspect network/console with sensitive values redacted. | No unexpected PHP/JS errors or failed local assets; survey participation no longer requests missing JS. | Medium | Not run |
| PERF-01 | Evaluator | **Database/query behavior:** Run focused query-count tests and measure representative feeds/workspace on staging at recorded data volumes. | Batching/count behavior matches tests; report measured latency/query counts without invented throughput claims. | Medium | Not run |
| PERF-02 | Evaluator | **Concurrency:** Use approved isolated concurrency/load scenarios for registration, engagement, publication and worker overlap. | No unauthorized duplicate/partial effect; documented throttling and overlap behavior; saturation/failure thresholds recorded. | High | Not run |

## Browser and accessibility execution matrix

Run the relevant UI/user tasks on the actual institution-supported combinations. The following is the proposed coverage, not a support certification: desktop Chrome/Edge/Firefox, mobile Chrome on Android, Safari on iOS if used, keyboard-only desktop, screen-reader sampling, 200% zoom, narrow phone width (about 360 CSS pixels), tablet and desktop widths. Record exact versions and observed issues. Do not claim a browser passed because a Node simulated-DOM test passed.

## Evidence and verdict

Attach redacted screenshots/network summaries, command output with exit codes, schema comparison, controlled before/after observations, worker delivery receipts and recovery measurements. For each failed case include reproduction steps and affected role. Repeat affected checks after a fix and retain the earlier failure record.

Release-blocking defects include unauthorized access/disclosure, credential leakage, broken critical account/publication/submission workflows, unhandled partial writes and missing required hosting/recovery controls. Lesser findings need owner, severity and target date. Aggregate scores must show blocked/not-run cases separately.

Final assessment fields: evaluator, environment/release, executed/pass/fail/blocked/not-run totals, critical unresolved defects, accepted limitations, recommendation (approve / conditional / do not approve), conditions, signature and date. These fields are intentionally completed by the evaluator after execution; no result or signature is prefilled.
