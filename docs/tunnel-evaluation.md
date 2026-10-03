# Temporary remote evaluation

This is an isolated local evaluation setup, not independent hosting. The PC,
MySQL, evaluation Apache process, workers and tunnel must remain running.
Quick Tunnel hostnames change when restarted. The first public tunnel was started
on October 3, 2026 after explicit approval to expose the copied records and uploads.

## Verified preparation: October 3, 2026

With explicit approval, `scripts/prepare_evaluation_database.php` backed up
`olshcodb`, restored it into `olshco_evaluation`, and created `olshco_eval@localhost`.
All 65 table definitions and 1,927 records matched using schema and record hashes.
The original database fingerprints were unchanged. The SQL backup and verification
report remain in the protected evaluation private directory, excluded from Git.

The evaluation account has only SELECT, INSERT, UPDATE and DELETE on its database.
Read access to the original database and schema changes were explicitly denied.
Transaction and runtime write permissions passed no-row smoke checks.
Apache/PHP syntax checks and 15 local HTTP checks passed. The ordinary XAMPP
server also denies access to evaluation secrets and artifacts via the parent
`deployment/tunnel/.htaccess` rule.

The public URL is now set to the generated HTTPS tunnel hostname in private
settings. The isolated evaluation Apache and tunnel processes are running.
Email and push are now configured, and the isolated evaluation delivery worker
is running. The worker waits 60 seconds between batches and exits when its
evaluation tunnel/server processes are no longer running.
All 32 uploaded files were subsequently copied with matching SHA-256 hashes.
The copied database's eight document/photo/audio references resolve to existing
files. The original files and application code remain unchanged.

Local application testing passed 17 checks: the login form renders, session cookies
carry Secure/HttpOnly/SameSite=Lax, private paths and direct documents return 403,
and expected static assets/photos/audio return 200. This does not replace full
authenticated role walkthroughs or authorized document-download testing.

Portable official `cloudflared` version 2026.9.3 was downloaded from Cloudflare's
GitHub release and verified against its published SHA-256 asset digest. Its binary
and release metadata are in the ignored, HTTP-blocked `deployment/tunnel/tools`.
No Windows service, router forwarding rule, or system installation was created.

Automatic approval review initially rejected starting the public tunnel because the copy
contains real account records and personal uploads and public exposure was not
considered explicitly authorized. The user then explicitly approved public access
including those records and files, and the approval review allowed the tunnel.
No workaround was attempted. The ordinary XAMPP server remains separate.

All 20 public HTTPS smoke checks passed: login rendering, cookie flags, static
assets/photo, private-path denial, unknown-route 404, and invalid AJAX input JSON.
Results and the current public URL are stored privately in
`public-smoke-results.json` and `public-url.txt`. No authenticated roles were used
in those smoke checks; full role walkthroughs remain pending. Use existing copied
account credentials. Changes made here do not synchronize to the original database.

## Evaluation notifications enabled

After explicit user approval, the reviewed existing Gmail SMTP configuration was
copied to the private evaluation settings. Its display name identifies evaluation
messages. Gmail STARTTLS authentication passed, without exposing credentials.

The initialization retained historical notification/delivery rows, revoked the
one copied original push subscription only in the evaluation database, and set a
fresh email-enabled cutoff. Master and category preferences remain unchanged.
New P-256 VAPID keys are separate from the original site's keys. Key validation
and VAPID signing passed. OpenSSL configuration must be present in the process
environment before PHP starts on this XAMPP build; setting it late inside PHP
was insufficient for library key generation. The supervisor supplies it explicitly.

One deduplicated evaluation test notification was created for the user's designated
active Admin account. The unmodified copied dispatcher queued it and recorded
SMTP acceptance as Sent on its first attempt. The batch released no old content,
created no old profile/event reminders, and had no delivery failures. Inbox receipt
still requires the user to check their mailbox; SMTP acceptance is not proof of
inbox placement. No evaluation test notification exists in the original database.

Users must enable browser notifications and grant permission on this evaluation
HTTPS origin. No fresh device subscription existed at setup, so actual push receipt
is still pending. A changed Quick Tunnel hostname requires a new browser
subscription and creates stale links in previously sent messages.

The supervisor runs the existing copied `dispatch_browser_push.php` with the
evaluation bootstrap and a separate temporary directory/lock, so it cannot use
the original database or contend with its worker lock. Private initialization,
rollback snapshots and check reports are retained under the blocked private path.
The stop helper now stops the evaluation worker and any verified PHP worker child
before stopping the tunnel/server. It leaves the ordinary XAMPP service alone.

## Prepared files

Run `python scripts/build_tunnel_evaluation.py` once. It refuses to overwrite an
existing copy. Runtime files go in `deployment/tunnel/evaluation/public`; private
settings, sessions and logs stay outside that document root. Local credentials,
database rows and existing uploads are excluded. Review public images before use.
The working application and XAMPP configuration are unchanged.

The separate Apache configuration binds only to `127.0.0.1:8080`, loads no XAMPP
aliases, and denies private application paths. It sets HTTPS at the server for
this dedicated tunnel listener, without trusting forwarded client headers. Use
the public HTTPS link for login; direct local HTTP cannot carry Secure cookies.
Never publish this listener using plain HTTP or bind it to a public interface.

## Required before exposure

1. Take a verified backup. Decide which evaluation data may be copied.
2. With explicit approval, create `olshco_evaluation` and a dedicated database user
   restricted to that database. Do not point this copy at `olshcodb`.
3. Populate the evaluation database using the current verified schema and approved
   records. Database creation/import is a separate step, not done by the builder.
   These database steps are complete for the verified copy above. Do not rerun the
   creation script: it intentionally refuses existing databases or users.
4. Fill private `environment.php` with its database credentials, a new stable app
   key and the generated HTTPS URL. Missing settings return a safe 503.
5. Configure private email/push settings only after deciding whether evaluation
   may send actual messages. Existing approved uploads have been copied separately.
   Copied delivery queues/subscriptions must be reviewed before starting workers,
   to avoid replaying messages or targeting existing devices unintentionally.
6. Install official `cloudflared`, verify the download, and check port 8080 is free.
7. Check Apache syntax, then run the separate Apache process. Starting this process
   does not replace the usual XAMPP Apache service.

```powershell
C:\xampp\apache\bin\httpd.exe -t -f C:/xampp/htdocs/bahay-ko/deployment/tunnel/evaluation/apache.conf
C:\xampp\apache\bin\httpd.exe -f C:/xampp/htdocs/bahay-ko/deployment/tunnel/evaluation/apache.conf
```

Only after private-file HTTP checks pass and exposure is approved:

```powershell
cloudflared tunnel --url http://127.0.0.1:8080
```

The prepared portable executable is `deployment/tunnel/tools/cloudflared.exe`.
Set its generated URL using `php scripts/set_evaluation_url.php https://GENERATED.trycloudflare.com`.
For local-only checks, `--local-preflight` selects a reserved `.invalid` hostname;
after those checks `--clear-url` restores the safe unconfigured state. Never use
the reserved preflight hostname for public evaluation.

Update the evaluation URL before login/testing. Keep the tunnel terminal running.
Do not tunnel port 80 or the whole XAMPP installation. A private-file request must
return 403; it must never return source, logs, backups or configuration content.
Check `/config/database.php`, `/vendor/autoload.php`, `/composer.json`,
`/phpmyadmin/`, `/xampp/`, hidden paths, and direct document-upload URLs.

## Verification and shutdown

Test all four roles from another network, Secure/HttpOnly/SameSite cookies, login
and logout, AJAX errors, recipient visibility, authorized document downloads,
profile photos, approval notifications, and recovery links. Workers must explicitly
use the evaluation bootstrap/database; never run the original worker accidentally.
Run the existing deployment preflight against this copy with its private bootstrap.
Changing the public hostname affects recovery/email links and browser push origins.

Stop the tunnel when evaluation ends. Stop only the separate evaluation Apache
process, leaving the normal XAMPP service alone. Retain the verified backup.

```powershell
powershell -File C:\xampp\htdocs\bahay-ko\scripts\stop_tunnel_evaluation.ps1
```

The stop helper verifies each saved process ID, executable path and evaluation
command arguments before stopping it, then clears only the evaluation URL.

Reference: [Cloudflare Quick Tunnels](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/).
