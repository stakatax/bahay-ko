# C5: database and release baseline

Verification on 2026-09-23: **schema restore passed; C5 remains open for bootstrap seeds, release baseline, and recovery testing**.

## Verified

- `olshcodb-structure.sql` matches all 62 live base-table definitions, including columns, indexes, foreign keys, engine, and collation. Only row-dependent next AUTO_INCREMENT counters are ignored.
- Snapshot SHA-256: `53666033fc425f0702a2183a368807a73fe1257d9385ebaa919592f7047d9af5`.
- The new checker only performs metadata SELECT and SHOW CREATE queries; it never executes the snapshot.
- Twelve focused verifier checks pass, covering missing/extra/changed tables, column/index/engine/charset changes, whitespace preservation in quoted values, and AUTO_INCREMENT counter differences.
- The initial audit was read-only. The subsequent user-approved schema restore is recorded below. No existing account/content records, application database schema, commits, or deployment were changed.

## Deployment blockers and evidence

1. **No verified empty-database bootstrap.** `database/migrations/001_auth_parent_support.php` alters an existing user table. `002_academic_program_support.php` is a catalog seeder, not a base-schema creation migration. Later migrations assume earlier tables/data. The full numbered directory is not a demonstrated fresh-install sequence.
2. **Missing migration coverage.** The snapshot includes `user.name_suffix`, `user.must_change_password`, `user.password_changed_at`, `user.provisioned_by`, `user.provisioned_at`, and `activity_log.target_user_id`, but these field names do not occur in the migration directory. Current `app/models/User.php` and `config/logging.php` depend on them.
3. **Schema-only snapshot is not an operational installation.** It contains DROP TABLE and CREATE TABLE statements and no INSERT/REPLACE seed statements. Do not import it over an existing database. Essential roles, actions, approved academic catalog, legal documents, questionnaire/version configuration, and initial Administrator setup require a separate reviewed bootstrap policy.
4. **Historical migration replay is unsafe as an upgrade strategy.** `2026_08_11_extend_notifications_v1.sql` unconditionally adds fields/indexes and backfills notifications/preferences. Migration 003 updates existing workflow data. Migration 026 creates baseline profile-cycle assignments from existing profiles. A transaction wrapper does not establish safe rollback for MariaDB DDL. Do not blindly rerun the directory.
5. **Release source baseline is incomplete.** At inspection, Git reported 42 modified, 2 deleted, and 154 untracked top-level entries (untracked directories collapse multiple files). `git ls-files` returned no tracked files for `database/`, `olshcodb-structure.sql`, `composer.json`, or `composer.lock`. A release from the existing commit would omit required code/schema/dependency definitions. The user's unrelated edits must be preserved and reviewed; no blanket staging/commit is authorized.
6. **Recovery is unverified.** Previous source-file backups are not a full database-and-upload backup. No full restore/recovery exercise has been performed in this session.

## Safe verification commands

From `C:/xampp/htdocs/bahay-ko` in PowerShell:

```powershell
& C:/xampp/php/php.exe scripts/check_database_baseline.php
& C:/xampp/php/php.exe tests/database_baseline.php
& C:/xampp/php/php.exe -l scripts/check_database_baseline.php
& C:/xampp/php/php.exe -l tests/database_baseline.php
git diff --check
git status --short
git ls-files -- database olshcodb-structure.sql composer.json composer.lock
rg -n 'must_change_password|password_changed_at|name_suffix|target_user_id|provisioned_by|provisioned_at' database/migrations olshcodb-structure.sql
rg -n '^INSERT INTO|^REPLACE INTO|^DROP TABLE|^CREATE TABLE' olshcodb-structure.sql
```

Expected now: schema checker reports 62/62 with zero differences; verifier reports 12 passed checks; syntax succeeds. Migration search finds the listed fields in the snapshot only. The Git and seed checks expose the remaining blockers rather than establish readiness.

## Recommended implementation order

1. Approve an isolated schema-restore test targeting **only a new database named `olshco_c5_verify_20260923`** on this local XAMPP server. First confirm the name does not exist; abort if it does. Create it without IF NOT EXISTS and restore the inspected schema snapshot only into it. Never select/import into `olshcodb`; do not run historical migrations or copy existing user/content data. Keep the scratch database afterward for review; no DROP DATABASE is part of this proposal.
2. Compare the restored schema using the same read-only checker and dedicated process environment configuration. Inspect which essential seed records are absent. A successful schema restore alone is not application readiness.
3. Prepare a separate, reviewed fresh-install baseline/seed procedure and forward-only upgrade strategy. Preserve migration history. Do not infer initial passwords, invent academic assignments, or migrate historical profile data without an approved policy.
4. Prepare a release file inventory including tracked and intended untracked source, dependencies, configuration templates, schema, and tests. Exclude development scripts/dumps/archives/secrets from the publicly served release. Review before any commit/package/deploy.
5. Approve a database/upload backup and an isolated full recovery rehearsal. Verify restored roles, accounts, content, targeting, legal/profile histories, file references, and worker configuration without sending notifications.

The user subsequently approved the first isolated schema-restore action. That action is now complete; results follow. The remaining steps have not been executed.

## Files added in this step

- `scripts/check_database_baseline.php`
- `tests/database_baseline.php`
- `docs/database-baseline-readiness.md`

## Approved isolated restore result

Executed only on the local XAMPP server, targeting the previously absent database `olshco_c5_verify_20260923`.

Safety checks and observed results:

- Verified local host, expected live database name, reviewed snapshot SHA-256, and absence of the scratch database before creation.
- Created the scratch database without `IF NOT EXISTS`; a name collision would abort.
- Executed only the 62 extracted, reviewed CREATE TABLE definitions. No DROP, historical migrations, USE statements from the dump, or data import executed.
- Disabled foreign-key checking only in the scratch connection during creation and restored it afterward.
- All 62 restored definitions match the snapshot. Total scratch data rows: **0**.
- Existing `olshcodb` table definitions and per-table row counts were identical before/after. A read-only connection check passed. Matching counts do not constitute a full row-content checksum; the restore code issued no writes to that database.
- Scratch database retained for review. No application configuration or worker target was changed to use it.

This establishes schema reproducibility from the current snapshot, not a working empty installation. The scratch database has no roles, academic catalog, actions, legal documents, questionnaires, or initial Administrator account. Those require a reviewed bootstrap procedure; no real user data or invented defaults were copied.

## Bootstrap preparation follow-up

A reviewable 221-row reference bundle and scratch-only transactional bootstrap are now prepared. The rollback rehearsal passed 31 checks and left the scratch database empty. No permanent Administrator or seed rows were created. See [fresh-install-bootstrap.md](fresh-install-bootstrap.md) for exact files, approval checksum, safeguards, and remaining setup decisions. C5 remains open for reviewed persistent installation, browser verification, release baseline, and recovery testing.
