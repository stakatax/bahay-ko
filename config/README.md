# Config

This folder contains system-level configuration files loaded early in the request lifecycle.

---

## dbconnect.php

Establishes the MySQLi database connection and exposes it as `$conn` in the global scope.

```
Host:     localhost
User:     root
Password: (empty)
Database: olshcodb
```

This file is `require`-d by `BaseModel` (for models) and `BaseController` (for controllers). It must run before any database interaction.

> **For production:** Replace the hardcoded credentials with environment variables. Never commit real passwords to version control.

If the connection fails, the script dies immediately with the MySQLi error message.

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

### Required: Seed the `actions` table

The following action names must exist in the database before logging will work. Run this once during setup:

```sql
INSERT INTO actions (action_name) VALUES
    ('LOGIN'),
    ('LOGOUT'),
    ('REGISTER_ACCOUNT'),
    ('POST_EVENT'),
    ('UPLOAD_ANNOUNCEMENT'),
    ('UPLOAD_DOCUMENT');
```

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
