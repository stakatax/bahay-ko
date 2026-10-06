> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Fresh-install bootstrap rehearsal

Prepared for C5 on 2026-09-23. This is a guarded local rehearsal tool, not a production deployment command. Application PHP, routes, UI, workers, and existing database configuration were not changed.

## Files

- `database/bootstrap-reference.json`: 221 proposed configuration rows across 12 tables, extracted using explicit column lists. No users, passwords, response/acceptance records, activity history, posts, files, notifications, cycles, or recipient assignments were exported.
- `scripts/bootstrap_fresh_install.php`: CLI-only script; no web execution. Hard-restricted to local database `olshco_c5_verify_20260923`. Does not create/drop tables or replay migrations.
- `tests/fresh_install_bootstrap.php`: rollback-only rehearsal, using a generated fixture password that is neither printed nor saved outside the rolled-back fixture.

## Proposed reference data requiring review

| Table | Rows | Scope |
| --- | ---: | --- |
| role | 4 | Admin, Faculty, Student, Parent |
| actions | 45 | Existing 36 plus nine action names already used by application code |
| department | 2 | Existing local division catalog |
| education_level | 4 | Existing local education levels |
| academic_program | 14 | Existing local programs/strands |
| grade_level | 16 | Existing local grades/years |
| section | 28 | Existing local sections; verify official names before release |
| content_interest | 10 | Existing interest definitions |
| legal_document_version | 2 | Existing legal text, versions, effective dates, and statuses; school review required |
| student_profile_survey_version | 3 | Existing configuration only; no author/activator user references copied |
| student_profile_consent_definition | 3 | Existing optional-consent definitions; no individual consent records |
| student_profile_question | 90 | Existing versioned definitions, including required/sensitive flags; no responses |

This preserves the reviewed configuration, not an independent claim that local catalogs or legal policies are approved for production. Questionnaire versions 1 and 2 are Active and 3 is Draft in the source; no status is silently changed. No profile update cycle is activated or assigned. Government advisory sources/rules and calendar holiday data are intentionally left for explicit post-install setup; advisory fetching and notification delivery never run here.

Nine additional audit actions derived from literal logging call sites:
`UPDATE_ACCOUNT_PROFILE`, `ACCEPT_LEGAL_DOCUMENTS`, `UPDATE_STUDENT_PROFILE`, `CREATE_STUDENT_PROFILE_QUESTIONNAIRE_DRAFT`, `CREATE_STUDENT_PROFILE_QUESTION`, `UPDATE_STUDENT_PROFILE_QUESTION`, `CHANGE_STUDENT_PROFILE_QUESTION_STATUS`, `CREATE_STUDENT_PROFILE_CYCLE`, `UPDATE_FACULTY_ASSIGNMENT`.
The live action table was not updated.

Reference SHA-256: `32296e49d56f355f0c7ebb4d53ed784308f80e3e599024d75faa6c20f15ccbeb`.

## Safeguards and repeat behavior

- Explicit local scratch target; supplying the application database connection directly is rejected before inserts.
- Exact 62-table schema comparison; InnoDB required for rollback.
- No operational rows allowed. At most the single matching initial Administrator is accepted on a repeat.
- Reference state must be entirely empty or match the whole bundle. Conflicts/partial data abort; there is no overwrite, UPDATE, DELETE, or credential reset in the bootstrap implementation.
- All reference inserts and initial Administrator creation are in one transaction with foreign keys enabled. CLI apply uses a named lock to prevent overlapping bootstrap invocations.
- Repeating with matching reference data and matching Administrator credentials adds no rows and leaves the password hash unchanged.
- Application credentials only supply access to the server; the script does not change `OLSHCO_DB_NAME`, local config, Apache, scheduled workers, or routes.
- Default/help and `--check` never write. Apply requires both `--apply` and the reviewed reference checksum.

## Safe checks

From `C:/xampp/htdocs/bahay-ko`:

```powershell
& C:/xampp/php/php.exe scripts/bootstrap_fresh_install.php --check
& C:/xampp/php/php.exe tests/fresh_install_bootstrap.php
& C:/xampp/php/php.exe -l scripts/bootstrap_fresh_install.php
& C:/xampp/php/php.exe -l tests/fresh_install_bootstrap.php
& C:/xampp/php/php.exe scripts/check_database_baseline.php
git diff --check
```

Expected now: reference state empty, users 0, tables 62. The rehearsal checks transaction enforcement, wrong database refusal, incomplete/invalid Administrator details, reference insertion, ordinary application user/legal/catalog/question lookups, password hashing and forced change, idempotence, conflicting credentials/reference data, operational-data refusal, foreign-key failure, and full rollback. Every inserted row is rolled back. Auto-increment counters in the scratch database can advance during rollback; that does not add records or alter the application database.

## Initial Administrator: no default credentials

A permanent initial Administrator was not created. After reviewing the reference bundle, supply these values locally through process environment variables, never in chat or committed files:

- `OLSHCO_BOOTSTRAP_ADMIN_FIRST_NAME`
- `OLSHCO_BOOTSTRAP_ADMIN_LAST_NAME`
- `OLSHCO_BOOTSTRAP_ADMIN_EMAIL`
- `OLSHCO_BOOTSTRAP_ADMIN_BIRTHDATE` (YYYY-MM-DD; adult)
- `OLSHCO_BOOTSTRAP_ADMIN_GENDER` (Male, Female, or Other)
- `OLSHCO_BOOTSTRAP_ADMIN_PASSWORD` (12-72 bytes, upper/lowercase, digit, symbol)

The password is hashed with PHP password_hash. The account is Active/Admin and requires password change on first login. It has no invented academic placement or legal acceptance. Review/consent follows existing application behavior.

**Only after approval**, with those process variables supplied locally:

```powershell
& C:/xampp/php/php.exe scripts/bootstrap_fresh_install.php --apply --confirm-reference-sha256=32296e49d56f355f0c7ebb4d53ed784308f80e3e599024d75faa6c20f15ccbeb
```

Expected: 221 reference rows, one initial Administrator, repeat false on first successful apply. Clear the password environment variable afterward. Do not point the website or workers at this scratch database as part of this command. The tool deliberately cannot initialize a different/production database; promotion requires separate deployment approval and review.

## Observed verification

31 bootstrap checks passed. PHP syntax and Git whitespace checks passed. After rollback, the scratch database reports empty references and zero users; the original database still matches all 62 baseline table definitions. No persistent seed apply was run.

## Still outstanding

- School review of the reference configuration and actual initial Administrator details.
- Authorized persistent scratch apply, isolated full login/role browser journeys, and required school-specific calendar/advisory setup.
- Full database/upload backup and recovery rehearsal, release source inventory, and production provisioning.

Passing these focused tests does not guarantee every application feature works on a brand-new installation. Existing production-like data was not migrated or reinterpreted.
