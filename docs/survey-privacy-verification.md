# H2: survey result privacy

Approved threshold: five distinct respondents, with no Administrator bypass.

## Behavior and affected files

- `app/models/Survey.php`, `getResults()`: suppresses all response data and the exact total for surveys with one to four respondents. Zero responses retain the existing empty state. Each question also requires five distinct people who actually answered it; checkbox selections and duplicate answers do not inflate this threshold.
- Nonempty Multiple Choice, Checkbox, and Yes/No groups with fewer than five respondents suppress the entire question breakdown, including complementary counts, to prevent subtraction from revealing a hidden group. Zero-count choices are allowed when the remaining groups meet the threshold.
- Suppression occurs in the returned model payload: counts/averages are null, text answers are empty, and choice counts are null. It is not CSS hiding.
- Visible text answers contain answer text only, without user IDs or timestamps, and are sorted by answer rather than submission time. Their HTML remains escaped.
- `pages/survey_results.php`: shows a protected state using existing page-card/empty-state classes; no new stylesheet or visual tokens.
- `tests/survey_privacy.php`: regression fixtures and rendered-page checks.
- `tests/README.md`: test command documentation.

Route remains `index.php?page=survey_results&survey_id=ID`. Existing controller access permits Admin and the owning Faculty; Student, Parent, guest, and non-owner Faculty remain denied. No new route, export, mutation, or CSRF-requiring action was introduced. Search found no separate consumer of `Survey::getResults()` beyond `SurveyService::getSurveyResults()`. Student-profile analytics/export is a separate subsystem and was not changed.

No schema, permanent data, survey participation, publishing, or notification changes. Backups: `C:/Users/ctrlc/.codex/backups/bahay-ko/survey-privacy-20260924-031531`.

## Safe verification

Run in local development:

```powershell
& C:/xampp/php/php.exe -l app/models/Survey.php
& C:/xampp/php/php.exe -l pages/survey_results.php
& C:/xampp/php/php.exe -l tests/survey_privacy.php
& C:/xampp/php/php.exe tests/survey_privacy.php
& C:/xampp/php/php.exe tests/faculty_scope.php
& C:/xampp/php/php.exe tests/content_access.php
git diff --check
```

Observed: 166 privacy checks, 158 Faculty scope checks, and 124 content-access checks passed (448 total). PHP syntax and whitespace checks passed; git emitted existing line-ending warnings only.

Privacy fixtures clone five table definitions into connection-local temporary tables before inserting synthetic rows. No existing responses are read or changed. Tests exercise real model/service/controller result methods and PHP page rendering; redirects are intercepted in a test subclass. This is not a browser/HTTP end-to-end test.

## Remaining checks and limitations

Use designated test surveys to visually check zero, four, and five respondents as Admin and owning Faculty, including an optional question answered by only four people and a 9/1 choice split. Verify the existing back link and mobile layout. Browser verification remains pending.

Five-person suppression reduces small-group disclosure; it does not promise anonymity. Free text can identify its author through its content, and comparing live result snapshots over time can reveal newly added answers. Larger-group text remains available as before, minus identity/time metadata. A release-only reporting policy or text moderation would require a separate product decision. Other workspace/participation counters were not changed by this result-page fix.
