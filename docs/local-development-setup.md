# Running a fresh clone locally

This guide is for a new local XAMPP installation. It does not transfer existing users, posts, uploaded files, email credentials or evaluation data. A pull into an existing installation must preserve its private settings and database; see the upgrade section below.

## 1. Get the correct branch and dependencies

Install Git, Composer and PHP 8.2+ with MySQL/MariaDB. Start Apache and MySQL in XAMPP. The required PHP extensions include mysqli, fileinfo, mbstring, openssl, curl, dom, Phar and sessions. Enable the locked Composer dependencies' additional requirements reported by the platform check. Use the same compatible PHP runtime for the website and CLI.

From PowerShell:

```powershell
Set-Location C:\xampp\htdocs
git clone --branch stax https://github.com/stakatax/bahay-ko.git
Set-Location bahay-ko
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
```

Do not use `composer update`: install the versions in composer.lock. Enable PHP's `zip` extension or install an unzip/7-Zip tool so Composer can extract downloads; without one, Git source-download fallback can be much slower. If PHP is not on PATH, use `C:/xampp/php/php.exe` in the PHP commands below, and configure Composer to use that PHP executable.

## 2. Create private local settings

```powershell
php scripts/setup_local.php --configure
```

This creates missing `.local.php` settings, a unique random application key and required runtime directories. Existing settings are never overwritten. Email and browser push start disabled. The default database is **olshco_development**, on local MySQL using the example XAMPP root account. Edit `config/database.local.php` for your actual local credentials and `config/security.local.php` if your URL or Apache port differs. These files are ignored by Git.

## 3. Import the current schema into a new empty database

In local phpMyAdmin, create **olshco_development** with utf8mb4, select it, and import:

`database/baselines/current-development-schema.sql`

The baseline contains the current 66 table definitions and no records. It already includes the vote and profile-placement changes. **Do not replay old migrations after this import.** Do not import it into an existing populated database; it deliberately has no DROP or overwrite behavior. Keep foreign-key enforcement enabled after import.

```powershell
php scripts/setup_local.php --check
```

Expected before seeding: `reference_state: empty`, `users: 0`, `tables: 66`. The tool refuses other database names, remote servers, mismatched schemas, partial reference data and existing operational records.

## 4. Provision your own local Administrator

The repository has no shared/default password. Enter your own details locally:

```powershell
$env:OLSHCO_BOOTSTRAP_ADMIN_FIRST_NAME = Read-Host 'Administrator first name'
$env:OLSHCO_BOOTSTRAP_ADMIN_LAST_NAME = Read-Host 'Administrator last name'
$env:OLSHCO_BOOTSTRAP_ADMIN_EMAIL = Read-Host 'Administrator email'
$env:OLSHCO_BOOTSTRAP_ADMIN_BIRTHDATE = Read-Host 'Birthdate YYYY-MM-DD (adult)'
$env:OLSHCO_BOOTSTRAP_ADMIN_GENDER = Read-Host 'Gender: Male, Female, or Other'
$setupPassword = Read-Host 'Password: 12-72 bytes, upper/lowercase, number and symbol' -AsSecureString
$env:OLSHCO_BOOTSTRAP_ADMIN_PASSWORD = [System.Net.NetworkCredential]::new('', $setupPassword).Password
try {
    $referenceHash = (Get-FileHash database/bootstrap-reference.json -Algorithm SHA256).Hash.ToLowerInvariant()
    php scripts/setup_local.php --apply "--confirm-reference-sha256=$referenceHash"
    if ($LASTEXITCODE -ne 0) { throw 'Local setup failed; read the message above.' }
} finally {
    Remove-Item Env:OLSHCO_BOOTSTRAP_ADMIN_PASSWORD -ErrorAction SilentlyContinue
    $setupPassword = $null
}
```

This inserts the reviewed school/reference catalog and one Active Administrator in a transaction. That account must change its password on first login and accept the current legal notices. Repeating with exactly matching data adds no rows; different credentials or reference data are refused. No notification worker or external delivery is started.

The reference bundle is a development catalog, not a claim that its sections, legal text or questionnaire definitions have been approved for school deployment. Configure government advisory sources, calendar holidays and any additional school settings through authorized management. Register Student/Parent accounts through the UI and approve them; provision Faculty through Administrator management.

## 5. Verify and open the website

```powershell
php scripts/check_database_baseline.php
php scripts/check_deployment_configuration.php
```

Open `http://localhost/bahay-ko/index.php?page=login` and sign in with your new Administrator email/password. Confirm required password change, legal acceptance, account management and a draft content save. Missing historical posts/uploads are expected on a fresh installation.

Email/password-reset delivery and browser push require your own private SMTP/VAPID configuration. They do not work just because the code was cloned. Scheduled publication, queue dispatch and profile reminders require the existing combined CLI worker; read `docs/deployment-configuration.md` before enabling it. A manual worker invocation can send real messages. No Windows Task Scheduler or tunnel settings are shared through Git.

## Pulling into an existing installation

Commit or stash your own edits before pulling `stax`. Install the locked Composer dependencies again if composer.lock changed. Keep all `.local.php` files, your application key, database and uploads unchanged.

Back up your database and uploads first. Do not import the fresh-install baseline or run the new seeder over your existing database. If upgrading from the previous version, review `database/migrations/student_profile_placement_update.sql`; the current profile workflow needs its new table and assignment columns. The existing local migration helper is explicitly limited to olshcodb on Windows XAMPP and makes a private backup; inspect it before an approved run. Migration 031 removes legacy reactions and must not be replayed without deliberately approving that data change. If the schema checker reports differences, resolve the actual missing changes rather than rerunning all migrations.

## Git hygiene

Commit application code, migration/baseline sources, setup instructions, Composer manifests and relevant tests. Never commit `.local.php` settings, runtime uploads, database record exports, logs, vendor, tunnel copies, passwords or diagram artifacts. Git does not synchronize database records or uploaded files.
