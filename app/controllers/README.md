# Controllers

Controllers are the entry point for all HTTP actions. They receive input from `index.php` (via `$_POST`, `$_GET`, `$_SESSION`, `$_FILES`), delegate work to a **Service**, and then either redirect the user or return data for the view.

Controllers do **not** contain business logic or SQL — those belong in Services and Models respectively.

All controllers extend `BaseController`.

---

## BaseController.php

Provides three shared helper methods available to all controllers:

| Method | Parameters | Description |
|---|---|---|
| `view` | `$view: string`, `$data: array = []` | Includes a page template from `/pages/`. Extracts `$data` into local variable scope so the view can use them directly. |
| `redirect` | `$url: string` | Sends an HTTP `Location` header and calls `exit`. |
| `log` | `$action: string`, `$description: string` | Calls `logActivity()` from `config/logging.php` using the global `$conn`. The `$action` string must match an existing `action_name` in the `actions` table. |

---

## AuthController.php

Depends on: `AuthService`

Handles all authentication actions. These are invoked by `index.php` for `?page=login_action`, `?page=register_action`, and `?page=logout`. All three call `exit` after completion — they never render a page.

### `login()`

Reads `$_POST['studentID']` and `$_POST['password']`.

On success:
- Sets `$_SESSION['user_id']`, `$_SESSION['role']`, `$_SESSION['name']`
- Logs `"LOGIN"` action
- Redirects `Admin` users to `dashboard`, all others to `home`

On failure:
- Redirects to `?page=login&error=<message>`

### `register()`

Reads all registration fields from `$_POST`: `studentID`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `gender`, `age`, `department`.

On success:
- Logs `"REGISTER_ACCOUNT"` action
- Redirects to `?page=login&success=Registration successful!`

On failure:
- Redirects to `?page=register&error=<message>`

### `logout()`

- Logs `"LOGOUT"` action (before clearing session, so `user_id` is still available)
- Clears `$_SESSION`, destroys session cookie, destroys session
- Redirects to `?page=home`

---

## EventController.php

Depends on: `EventService`

### `calendar()`

Called by `index.php` for the `?page=calendar` route. Returns an array of view data.

- Timezone is set to `Asia/Manila`
- Validates and sanitizes `?month=` (1–12) and `?year=` (1970–2100) from `$_GET`; falls back to the current month/year if invalid or absent
- Builds calendar metadata: `$daysInMonth`, `$dayOfWeek`, `$monthName`, prev/next navigation values
- Calls `EventService::getCalendarEvents($year)` to get all events for the year (DB + auto-generated First Friday Mass entries)
- Determines the `$selectedDate` from `?date=` or defaults to today

**Returns array with keys:** `events`, `selectedEvents`, `month`, `year`, `monthName`, `daysInMonth`, `dayOfWeek`, `prevMonth`, `prevYear`, `nextMonth`, `nextYear`, `currentDate`, `selectedDate`

### `store()`

Called by `index.php` for the `?page=event_store` route. Responds with JSON — does **not** render a page.

- Reads JSON body from `php://input` — expects `{ "date": "YYYY-MM-DD", "title": "..." }`
- Returns `{ "status": "error", "message": "Missing data" }` if either field is absent
- Calls `EventService::storeEvent($title, $date, $user_id)` using `$_SESSION['user_id']`
- On success: logs `"POST_EVENT"`, returns `{ "status": "success" }`
- On failure: returns `{ "status": "error", "message": "Failed to save event" }`

---

## PostController.php

Depends on: `PostService`

### `news()`

Called for `?page=news`. Returns the result of `PostService::getNewsFeed()` directly as view data. The view receives `$events`, `$announcements`, and `$documents` arrays.

### `store()`

Called for `?page=post_store`. Accepts form submission from the postings page.

- Passes `$_POST`, `$_FILES`, and `$_SESSION['user_id']` to `PostService::create()`
- On success: redirects to `?page=postings&success=1`
- On failure: redirects to `?page=postings&error=<message>`
