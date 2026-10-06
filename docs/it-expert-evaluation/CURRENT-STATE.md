# Current review baseline — 6 October 2026

OLSHCO Digital Hub is a PHP 8.2+ and MySQL/MariaDB school information and content-management system. Browser requests use index.php, controllers, services and models. Scheduled PHP workers support publishing, reminders, queued email and browser push. The current evaluation target is local XAMPP; alwaysdata hosting is preparation only.

## Current behavior

- Roles are Administrator, Faculty, Student and Parent. Authorization and recipient eligibility must be enforced server-side. College Faculty are restricted to permitted assignments; verified Parent linkage does not authorize private Student questionnaire responses.
- Public registration is reviewed before account activation. Approved/rejected account decisions have email support, subject to actual host configuration and delivery verification.
- Content preserves Draft, Pending review, Rejected, Scheduled, Published, Archived and Restored workflow rules. Faculty cannot approve their own content.
- Unified engagement uses Upvote and Downvote, with separate counts and withdrawal on selecting the same vote. Historical announcement tables remain preserved; their old enum definitions do not describe active voting.
- Home post bodies, titles, Read more, Discuss and recent-post links now navigate in the **current tab** to the protected full post. Discussion retains its anchor. Older new-tab/drawer descriptions are superseded for this flow.
- Feed ranking retains emergency/important priority, eligible content, chosen interests and bounded learned preferences. It is not a filter that hides all less-interacted-with content.
- Student interests and the school profile survey have separate pages. Required survey completion directs to student_profile_survey; both Student pages remain Student-only.
- Government advisory intake remains Administrator-only; relevant reviewed advisories can become targeted announcements. It was not removed.
  The standalone government_advisories screen is retired; its old Admin URL redirects to Create Post, which retains advisory intake.
- The Contact inquiry form now sends validated inquiries to sapinjanfortun1@gmail.com through configured SMTP, with CSRF/form-token protection, rate limiting and duplicate-submit handling. A clearly marked test inquiry was accepted by SMTP on 6 October; actual inbox receipt remains for the recipient to confirm. Phone/email links remain available as fallback.
- The school logo is configured as the shared browser tab icon.

## Latest verified security work

Controlled local tests covered SQL injection, CSRF, XSS, SSRF, IDOR, traversal, login abuse, clickjacking, open redirects and secret exposure. Confirmed gaps fixed: missing root access-denial rules, missing external-frame protection, and advisory redirect/DNS safety. Frame policy allows same-origin document previews while blocking cross-origin framing. Advisory requests validate each redirect and pin a checked public address.

Fifteen security regression suites, 22 new SQL/SSRF payload checks and 15 loopback HTTP probes passed. These are focused fixture/source/HTTP checks, not an exhaustive penetration-test certification or proof that every browser DOM sink is safe. Details and limitations are in the dated attack audit.

## Performance interpretation

Reference caching covers approved shared catalogs, not cached user authorization or private responses. Mixed feed queries were batched while preserving visibility and data parity. OPcache measurements were collected on local XAMPP with timestamp checks enabled. Load measurements used a small current dataset and bounded read-only traffic; they do not guarantee production capacity or thousands of simultaneous users.

## Evaluation prerequisites and remaining checks

The evaluator/operator must supply designated test accounts, fixtures, environment details and an approved test scope. No test credentials are included here. Before public launch, independently verify real SMTP/push delivery, scheduled workers, HTTPS/session flags on the actual host, file permissions, full role journeys, desktop/mobile accessibility, backup restoration and hosting resource limits.

Schema counts and migration installation statements in supporting documents are dated observations. Inspect current schema/migration state before importing or modifying a database. No destructive schema operation or deployment is authorized by this documentation pack.

Architecture descriptions of the retired public-header shell or unused announcement-specific service/controller are historical: those unused files were removed during the filesystem audit. The active public pages use the unified sidebar shell.
