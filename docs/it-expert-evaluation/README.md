# OLSHCO Digital Hub — IT expert evaluation pack

Prepared **6 October 2026** for independent technical evaluation of the current local system. This folder contains review documentation, not a deployed release or a certification that every feature is defect-free. No evaluator verdict has been filled in.

## Suggested reading order

1. [Current state and limitations](CURRENT-STATE.md): authoritative summary of the latest reviewed behavior and what remains unverified.
2. [System overview and architecture](system-evaluation.md): purpose, application layers, workflows, role boundaries and data handling.
3. [User manual](user-manual.md): task walkthroughs for Administrator, Faculty, Student and Parent.
4. [Routes and permissions](routes-reference.md), [database dictionary](database-reference.md): technical structure. The dictionary contains dated metadata, not a fresh schema export.
5. [Role-based acceptance checklist](evaluation-checklist.md), [blank evaluation record](evaluation-record.md): execute against controlled accounts and record actual observations.
   See the [test-account plan and email limitations](test-account-guide.md) for evaluator preparation.
6. [Latest security attack audit](security-attack-audit-20261006.md), [filesystem cleanup audit](filesystem-audit-20261005.md): findings, root causes, changes, tests and limits.
7. [Performance evidence](feed-performance-20261005.md), [local load test](local-load-test-20261005.md), [OPcache measurement](opcache-runtime-20261005.md), [reference caching](reference-caching.md): local measurements and their practical limits.
8. [Deployment launch gates](deployment-launch-checklist.md), [configuration](deployment-configuration.md), [alwaysdata preparation](alwaysdata-deployment.md): planned hosting and prerequisites, not evidence of a live deployment.

## Supporting verification documents

| Area | Evidence to review |
| --- | --- |
| Authorization | [Content access](content-access-verification.md), [Faculty scope](faculty-scope-verification.md), [global guards](global-guards-verification.md) |
| Accounts | [Session security](session-security-verification.md), [Parent registration](parent-registration-verification.md), [Faculty onboarding](faculty-profile-onboarding.md), [abuse controls](abuse-protection-verification.md) |
| Content and privacy | [Survey privacy](survey-privacy-verification.md), [document uploads](document-upload-verification.md), [safe errors](request-error-verification.md), [input re-audit](deployment-re-audit-2026-10-02.md) |
| Notifications | [Publication durability](publication-notification-readiness.md), [worker monitoring](scheduler-monitoring.md) |
| Contact | [Inquiry email submission](contact-inquiry-verification.md) |
| Data and performance | [Database baseline](database-baseline-readiness.md), [fresh-install limits](fresh-install-bootstrap.md), [workspace counts](workspace-counts-verification.md), [request batching](request-performance-verification.md) |

## Evidence rules

Original document dates are preserved. Older verification results describe the cases executed at that time; they do not prove that the evaluator's environment or every later change passed. CURRENT-STATE.md resolves known differences between older material and current behavior. Check the actual source and schema when assessing implementation.

Use **Pass / Fail / Blocked / Not run / Not applicable**. Manual cases begin Not run even when related automated tests passed. Record the environment, release/commit and uncommitted-change state, role, steps, expected/actual outcome, redacted evidence and follow-up. Do not copy real Student responses, passwords, SMTP/VAPID secrets, cookies or tokens into findings.

The folder intentionally excludes diagrams, credentials, SQL/data exports, uploaded private documents and raw runtime logs. Markdown documents are readable as a review pack; optional source-code and schema links require the project repository. Test commands require the supplied development/staging environment and are not instructions to attack third-party systems or change production data.

Copies are a dated snapshot of selected source documents under docs/. If application behavior changes, refresh this pack deliberately and record the new review date. No Git commit, public upload or deployment was performed to prepare it.
