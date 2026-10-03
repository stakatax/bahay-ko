# Faculty-owned personal profiling

Admin provisioning collects first/last name, email, and the authorized academic posting assignment. Posted middle name, suffix, gender and birthdate are ignored by provisioning. Faculty supply middle name/suffix (optional), gender and birthdate through My Account after changing their temporary password and accepting current legal documents. Incomplete Faculty profiles are redirected there; AJAX guards return JSON with a local destination. Existing complete Faculty profiles continue normally.

The self-profile action is POST-only, CSRF protected, and uses the authenticated user ID. Its model update is restricted to active Faculty whose required password change is complete. Role, email, first/last name and academic assignment cannot be changed through this action. Password, legal, and Student-profile requirements retain their precedence. Photo controls remain available during Faculty profile completion.

Migration 030 makes gender and age nullable, avoiding fabricated personal data. It is repeat-safe and preserves existing rows. Before local application, the database was dumped privately outside the webroot; all 18 existing account rows were verified unchanged. This does not establish backup restoration readiness.

## Verification

- `C:/xampp/php/php.exe tests/faculty_personal_profile.php`: 21 checks passed; isolated temporary tables and rendered role-specific forms.
- `C:/xampp/php/php.exe tests/faculty_scope.php`: 162 checks passed; temporary tables.
- `C:/xampp/php/php.exe tests/session_security_http.php`: 105 checks passed; isolated sessions and temporary tables.
- `C:/xampp/php/php.exe tests/global_guards_http.php`: 210 checks passed; includes pending Faculty profile redirects, JSON guard responses, and allowed completion routes. These checks do not execute real actions or send notifications.
- `C:/xampp/php/php.exe scripts/check_database_baseline.php`: 65 expected/current tables, zero differences.
- Changed PHP/JavaScript syntax checks and `git diff --check` passed.

Manual acceptance remains: provision College and IBED Faculty, sign in/change password/accept legal documents, save required personal details, confirm reload and Hub access, edit optional details, and verify authorized posting scope on desktop/mobile. Actual browser layout, password-manager behavior, and delivery were not established by these automated results.
