# Evaluator accounts — prepared 6 October 2026

Status: account creation is pending the operator's choice of local olshcodb, tunnel olshco_evaluation, or both. The plan and actual insertion/hash/identifier/scope checks were verified using connection-local temporary users and rolled back. No permanent evaluator accounts were created by that rehearsal.

## Planned login identifiers

Use these in the Student ID / email sign-in field. These are synthetic identifiers, not real enrollment IDs. Each account receives a different random initial password through a private handoff, outside this documentation folder and Git.

| Login | Role / assignment |
| --- | --- |
| testadmin | Administrator |
| testfaculty-bsit | College / BSIT |
| testfaculty-bsba | College / BSBA |
| testfaculty-bsed | College / BSED |
| testfaculty-beed | College / BEED |
| testfaculty-btled | College / BTLED |
| testfaculty-bece | College / BECE |
| testfaculty-bscrim | College / BSCRIM |
| testfaculty-bsoad | College / BSOAD |
| testfaculty-bshm | College / BSHM |
| testfaculty-ibed-elementary | IBED / Elementary |
| testfaculty-ibed-junior-high | IBED / Junior High |
| testfaculty-ibed-senior-high | IBED / Senior High |
| teststudents-ibed | IBED / first complete active Elementary grade/section |
| teststudents-college | College / first complete active program/year/section (current catalog: BSIT) |

The actual department table contains IBED and College; College courses are academic programs. Therefore one Student per department means two Students, while the nine Faculty accounts cover every active College program. Catalog-derived plan records are verified before creation; a changed catalog can change the resulting assignments.

## First login and workflow

Accounts are Active synthetic fixtures and require an initial password change. Evaluators must provide their own legal acceptance through the normal consent screen; the setup tool does not fabricate acceptance records. Student survey requirements remain enabled. Complete them normally when testing the Student journey. No real person is represented by the synthetic date/name fields.

Faculty must use their assigned scope and submit through review; they cannot publish directly, post schoolwide, target another College course/division, or approve their own submissions. A read-only current-catalog policy test passed **114 checks across all nine College programs and three IBED levels**, including every different College-program pair. Existing Faculty regression coverage passed 162 checks. Browser submission and workflow acceptance are still separate evaluator tasks.

Use disposable evaluation content with a clear test prefix; avoid real confidential announcements or existing operational content. Give Administrator credentials only to the assigned administrator evaluator.

## Email and push limitation

Account emails end in example.invalid and cannot receive mail. Mark real email receipt/password-reset delivery **Blocked** for these dummy mailboxes rather than Pass or Fail. In-system notification behavior can be evaluated separately. Browser push also has separate subscription/preferences/HTTPS prerequisites; a dummy email does not itself prevent browser push.

For delivery acceptance, separately designate a consenting evaluator's real mailbox or a controlled SMTP capture service, then verify approved/rejected registration messages, publication notices and recovery. Do not route every dummy account to one real Gmail address or claim that a queued email proves delivery. No messages are sent during account setup.

## Operator tools

scripts/prepare_test_accounts.php defaults to a read-only --plan. --verify inserts only temporary users and rolls back. --create requires the explicitly chosen local/evaluation database; it inserts new users transactionally, refuses existing identifiers and never resets an existing password. Its private credential output is under deployment/test-accounts/private/, excluded from Git and denied over HTTP. Account creation is not part of deploying the application.

After evaluation, disable synthetic accounts through normal Administrator management and handle retained test content under the agreed retention policy. Do not automatically delete users or audit/history data.
