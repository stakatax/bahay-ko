# Coolify preparation for OLSHCO Digital Hub

This prepares the application for Yno's Coolify server named `localhost`, with the separately created private MariaDB resource. Do not alter the existing Cloudflare Tunnel service. No database import, deployment or real notification delivery occurs by preparing these files.

## Repository configuration

Use repository `stakatax/bahay-ko`, branch `stax`, Git source with Docker Compose build pack, base directory `/`, Compose location `/docker-compose.yaml`. The `server` service receives the application domain, `https://olshco-digihub.aleczr.link`, on internal port 80. The `worker` service has no domain. There are no host-published ports and no second MariaDB service in this Compose file.

Coolify must connect this Compose application to the database resource's predefined destination network. Confirm the network with Yno; same server alone does not establish service connectivity. Use the internal hostname shown in the MariaDB internal URL, not `localhost` and not the database's display name. Leave the MariaDB resource Private.

## Required runtime variables

Enter privately in Coolify; do not commit completed settings or paste connection URLs into chat.

| Variable | Value |
|---|---|
| OLSHCO_APP_URL | `https://olshco-digihub.aleczr.link` |
| OLSHCO_APP_KEY | One stable private random key, at least 64 characters; generate 32 random bytes encoded as hex. Preserve it across redeployments. |
| OLSHCO_DB_HOST | Exact internal MariaDB hostname from the resource connection URL |
| OLSHCO_DB_PORT | `3306` |
| OLSHCO_DB_NAME | `olshcodb` |
| OLSHCO_DB_USER | `olshco_app` |
| OLSHCO_DB_PASSWORD | Generated normal-user password; never the root password |
| OLSHCO_TRUSTED_PROXY_CIDRS | Comma-separated actual proxy peer IPs/CIDRs confirmed by Yno; prefer specific addresses. Broad `/0` networks are refused. |
| OLSHCO_EMAIL_ENABLED | `0` initially |
| OLSHCO_WORKER_ENABLED | `0` initially |

The Compose file sets production mode for both services. Apache recognizes forwarded HTTPS only from the configured connection peer addresses; application code continues to trust server-provided HTTPS state. Confirm which proxy connects directly to Apache and that it supplies/overwrites X-Forwarded-Proto. Do not guess a Docker subnet or trust all clients. The existing Cloudflare tunnel/proxy routing must be confirmed by Yno before public testing.

## Private delivery configuration

Container startup generates existing PHP configuration formats under `/run/olshco`, outside the document root. Code/config files stay root-owned; only uploads, sessions, temporary files and logs are writable by the application identity. The original local configuration is untouched.

For email, privately set `OLSHCO_SMTP_HOST`, `OLSHCO_SMTP_PORT` (587), `OLSHCO_SMTP_ENCRYPTION` (tls), `OLSHCO_SMTP_USER`, `OLSHCO_SMTP_PASSWORD`, `OLSHCO_EMAIL_FROM` and optionally `OLSHCO_EMAIL_REPLY_TO`. Set `OLSHCO_EMAIL_ENABLED=1` only after sender and intended recipients are approved. Password recovery cannot deliver while email is disabled.

Set `OLSHCO_VAPID_SUBJECT`, `OLSHCO_VAPID_PUBLIC_KEY` and `OLSHCO_VAPID_PRIVATE_KEY` privately before enabling the combined worker. Use one stable matching pair; redeployment must not regenerate it. Enabling the worker can publish due content, generate reminders and send actual messages. Keep it disabled until schema, reference data, assignments, queued deliveries and configuration have been reviewed. Do not add another scheduler for the same combined dispatch script.

## Persistence and permissions

Named volumes preserve `Assets/uploads`, application logs, sessions and worker locks/temp. Never mount over the entire app or config directory. The entrypoint restores upload access-control files to mounts and grants runtime writes only to necessary locations. Existing upload contents are not bundled into the image and require a separately approved transfer. Database and file volumes need backups; persistence is not a backup.

## Database initialization and launch gate

The running MariaDB resource is not initialized with our application tables yet. Before enabling public use, import the reviewed 66-table schema into an empty target and provision approved reference data/accounts, or restore an explicitly approved operational backup and its uploaded files. Choose one path deliberately. Do not import the historical DROP-containing snapshot or replay every migration.

`scripts/setup_local.php` is intentionally local-only and cannot seed this server database. Production/evaluation reference and Administrator provisioning must be prepared and approved separately. The Docker image does not import SQL or create/reset accounts on restart.

The health check tests database connectivity and Apache availability only; it is not evidence of imported tables, working login, successful email/push or full role acceptance. Run the production configuration preflight inside `server`, inspect logs and verify public HTTPS cookies, internal URL denial, authorized document access, all four roles, uploads across redeployment, and controlled notification delivery before launch.

## Local validation

```text
php tests/docker_deployment.php
python tests/docker_apache_http.py
docker compose config --quiet
docker compose build
```

The Apache test uses a separate loopback listener on port 8092 with Windows XAMPP and synthetic files; it does not touch existing application data or services. The Docker commands require Docker Engine and all required runtime variables supplied privately. If Docker is unavailable locally, PHP/shell/YAML checks alone are not a successful image-build claim: review the first Coolify build/start logs and complete container-level checks before calling the deployment ready.

References: [Coolify Compose](https://coolify.io/docs/applications/builds/docker-compose), [Coolify database networking](https://coolify.io/docs/databases), [official PHP image](https://github.com/docker-library/docs/blob/master/php/README.md), [Apache expressions](https://httpd.apache.org/docs/current/expr.html).
