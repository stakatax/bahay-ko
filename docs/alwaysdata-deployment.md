# alwaysdata deployment preparation

Prepared 5 October 2026. Local preparation only; no hosting account, database transfer, public deployment or notification delivery was performed.

## Compatibility assessment

The application requires PHP 8.2 or later (composer.json), mysqli, sessions, fileinfo, mbstring, OpenSSL, curl, DOM and the installed Composer lock's platform requirements. It uses MySQL/MariaDB, persistent uploads, private configuration and a combined CLI notification worker. These requirements match the provider's documented services in principle, but must be verified on the chosen account.

The current runtime allowlist in scripts/build_namecheap_release.py measures 2,794 files / 176.35 MiB; existing Assets/uploads measures 24.10 MiB. This is a read-only filesystem estimate, not an upload package or a total hosting footprint. Database storage, mail, logs, caches, backups and future uploads require additional capacity. The free offer currently advertises 1 GB disk, 256 MB RAM and one-quarter CPU for personal needs. Confirm capstone eligibility and monitor account resources; local XAMPP performance is not evidence of host capacity.

Official references:

- [Plans and free limits](https://www.alwaysdata.com/en/offers/)
- [Supported services](https://help.alwaysdata.com/en/docs/admin-billing/billing/choose-its-paas/)
- [PHP configuration](https://help.alwaysdata.com/en/docs/web-hosting/languages/php/configuration/)
- [Scheduled tasks](https://help.alwaysdata.com/en/docs/web-hosting/tasks/)
- [Free-account inactivity suspension](https://help.alwaysdata.com/en/docs/admin-billing/profile/suspension/)

## Account setup and layout

Create the free account from the official plans page and verify email. Record the account name, chosen public HTTPS address and actual website document root. Do not send passwords, database credentials, SMTP credentials or VAPID private keys through chat. Confirm that the offered account suits a student capstone and review inactivity reminders.

Proposed layout, subject to the actual account paths:

```text
/home/ACCOUNT/olshco/public/     website document root
/home/ACCOUNT/olshco/private/    bootstrap, environment, SMTP, VAPID, sessions, logs
```

Private must be outside every website document root. Use the existing private bootstrap/environment and email/push wrapper templates in deployment/namecheap as reusable starting points, replacing every cPanel path/comment with the verified alwaysdata value. Do not activate their placeholders or use the Namecheap package unchanged. No second application configuration loader is needed.

Select PHP 8.2+ for web and CLI. Configure the following through the provider's PHP configuration interface, checking actual values after website restart:

```ini
auto_prepend_file = /home/ACCOUNT/olshco/private/bootstrap.php
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /home/ACCOUNT/olshco/private/php-error.log
date.timezone = Asia/Manila
upload_max_filesize = 20M
post_max_size = 32M
session.save_path = /home/ACCOUNT/olshco/private/sessions
sys_temp_dir = /home/ACCOUNT/olshco/private/tmp
```

Create sessions/tmp first with owner-only permissions where supported. Confirm both web and CLI use a writable account-specific temp directory: caches, rate limits and worker locks depend on it. Supply sys_temp_dir explicitly to CLI if its configuration differs. Confirm the bootstrap's putenv calls work. Check HTTPS detection and Secure/HttpOnly/SameSite cookies without trusting arbitrary forwarded headers. Do not expose phpinfo or private configuration as a public diagnostic.

Use the provider-assigned database host/name and a dedicated runtime user, not localhost/root assumptions. Check MySQL/MariaDB schema compatibility, InnoDB, utf8mb4 and foreign keys in an isolated staging database. The current vote schema must support Upvote/Downvote; old documentation table counts are not migration evidence. Inventory the actual current schema and migration state before proposing import or changes.

## Release and preflight

Prepare a fresh secret-free release after account paths are known. Preserve current working-tree changes and include scripts/clear_reference_cache.php for maintenance. Exclude local credentials, tests, diagrams, dumps, logs and development tools. Keep the manifest outside public storage. Transfer referenced uploads separately with their exact case-sensitive paths and protection rules; do not silently omit files referenced by the database.

Preserve root internal-directory denial and upload .htaccess protections. Test direct access denial for private files, config, scripts, logs and raw documents, while assets and authorized downloads work. Never weaken protections just to resolve a hosting error.

Run the installed lock's platform checks on the host; do not update dependencies as part of deployment. With the actual PHP executable, run:

```sh
PHP_BIN -d auto_prepend_file=/home/ACCOUNT/olshco/private/bootstrap.php /home/ACCOUNT/olshco/public/scripts/check_deployment_configuration.php --production
```

This checker is read-only and does not prove database connectivity or web configuration. Verify those separately, together with representative protected uploads and all role permissions. No live transfer should begin until backup/rollback and the target are reviewed.

## Notifications and scheduled work

Keep in-system notifications, email and browser push enabled in the design. Privately configure authenticated SMTP with a verified sender, confirm sending quotas and outbound connectivity, and use the existing stable VAPID pair when retaining browser subscriptions. Browser push requires working HTTPS and provider outbound HTTPS connectivity. Test recovery and account approval/rejection email as well as normal preference-controlled publication notifications.

After staging approval, configure exactly one command-type scheduled task for the existing combined worker. A five-minute interval is an initial proposal, not an asserted host restriction; confirm it against account resources and required delivery latency. Select the matching CLI PHP version and timezone/environment.

```sh
PHP_BIN -d sys_temp_dir=/home/ACCOUNT/olshco/private/tmp -d auto_prepend_file=/home/ACCOUNT/olshco/private/bootstrap.php /home/ACCOUNT/olshco/public/scripts/dispatch_browser_push.php >> /home/ACCOUNT/olshco/private/cron-error.log 2>&1
```

This command changes records and can send real messages; do not run it as a configuration probe. The existing worker lock prevents overlap; retain queue retries, targeting and deduplication. Monitor worker summaries, pending queues, failures and resource usage. Set log retention and verify delivery using approved test recipients. Do not run separate duplicate publication/email/push workers.

## Activation gates

Before public activation, verify backup restoration, database/file consistency, Admin/Faculty/Student/Parent workflows, direct URL denials, Faculty scope, Parent linkage privacy, profile responses, scheduled publishing, voting/comments/acknowledgments, survey privacy, email recovery and push. Run a bounded host load test only with provider permission and within free-plan limits. Confirm Contact inquiry behavior before presenting it as operational.

Next dependency: an actual free account and its non-secret account name/public URL. Account availability, signup requirements, extensions, SMTP delivery and production capacity remain unverified. Database import/migration and public activation require a separately reviewed decision under AGENTS.md.
