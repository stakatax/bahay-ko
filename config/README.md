# Config

This folder contains system-level configuration files. See [the deployment configuration guide](../docs/deployment-configuration.md) for required settings, secret-free examples, load-time behavior, server setup and worker instructions.

Read-only preflight: `php scripts/check_deployment_configuration.php`. On staging with production settings: `php scripts/check_deployment_configuration.php --production`. This validates configuration without opening database connections or sending email/push.

---

## dbconnect.php

Loads `database.php` and exposes a fresh MySQLi connection as `$conn`, preserving existing model/controller include behavior. Connections use `utf8mb4`.

Deployment variables (provide to both PHP/Apache and CLI workers):

- `OLSHCO_APP_ENV=production`
- `OLSHCO_DB_HOST`: database hostname
- `OLSHCO_DB_USER`: dedicated application account; production rejects `root`
- `OLSHCO_DB_PASSWORD`: nonempty password; preserved exactly
- `OLSHCO_DB_NAME`: application database
- `OLSHCO_DB_PORT`: optional, defaults to 3306

Provide these through the hosting environment/secret manager; never commit passwords or put them in command histories. No dotenv loader is present. Production never falls back to the local credential file. Partial environment credentials fail closed instead of mixing with local defaults.

For this XAMPP workstation, `database.local.php` explicitly enables development and preserves its existing connection settings. It is ignored by Git. **Exclude it from deployment packages.** A fresh checkout without environment settings or that local file fails closed. No production account or privileges have been created by this change.

Connection/configuration failures return safe HTTP 503 text or JSON for AJAX/JSON requests. CLI workers exit 1. Server logs record only the exception class/code, never its message or credentials.

Before deployment, provision a dedicated runtime account restricted to the application database. Use separate credentials for migrations/backups, confirm actual application grants and remote transport requirements, and configure both web and scheduled-worker environments. Database provisioning, grant changes, and production smoke tests remain deployment actions requiring authorization/access.

Verification from the project root:

```powershell
& C:/xampp/php/php.exe tests/database_configuration.php
& C:/xampp/php/php.exe -l config/database.php
& C:/xampp/php/php.exe -l config/dbconnect.php
git check-ignore config/database.local.php
git diff --check
```

---

## logging.php

Provides the `logActivity()` function used throughout the application to record user actions.

### `logActivity($conn, $actionName, $description)`

| Parameter | Type | Description |
|---|---|---|
| `$conn` | `mysqli` | Active database connection |
| `$actionName` | `string` | Must match an existing `action_name` in the `actions` table |
| `$description` | `string` | Human-readable log message (max 255 chars) |

**Behavior:**
- If no user is logged in (`$_SESSION['user_id']` not set), it returns silently — nothing is logged
- If `$actionName` does not exist in the `actions` table, it returns silently — nothing is logged
- Otherwise, inserts a row into `activity_log` with the description, resolved `action_id`, and current `user_id`

### Required audit reference data

The actions table must contain the reviewed audit action catalog. Follow [the fresh-install bootstrap guide](../docs/fresh-install-bootstrap.md); do not use a partial ad-hoc INSERT list as a complete installation procedure. Existing populated installations require a separately reviewed reference-data update.

### Where it's called

| Caller | Action name logged |
|---|---|
| `AuthController::login()` | `LOGIN` |
| `AuthController::register()` | `REGISTER_ACCOUNT` |
| `AuthController::logout()` | `LOGOUT` |
| `EventController::store()` | `POST_EVENT` |
| `PostService::create()` — announcement | `UPLOAD_ANNOUNCEMENT` |
| `PostService::create()` — event | `POST_EVENT` |
| `PostService::create()` — document | `UPLOAD_DOCUMENT` |
