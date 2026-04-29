# Services

Services contain the **business logic** of the application. They sit between Controllers (which handle HTTP input/output) and Models (which handle SQL). A service should never read from `$_POST`, `$_GET`, or `$_FILES` directly — it receives clean data from the controller.

---

## AuthService.php

Depends on: `User` model

Handles registration and login logic.

### `register(array $data): bool`

1. Validates that `studID` and `password` are not empty — throws `Exception("Missing required fields")` if so
2. Looks up the `"Student"` role from the DB — throws `Exception("System role not found")` if absent
3. Assigns `role_id` to `$data`
4. Hashes the password using `password_hash($password, PASSWORD_DEFAULT)`
5. Calls `User::create($data)` — throws `Exception("Registration failed. ID might exist.")` on failure
6. Returns `true` on success

> **Note:** The `"Student"` role must be seeded in the `role` table for registration to work.

### `login(string $studID, string $password): array`

1. Calls `User::findByStudentID($studID)` — throws `Exception("User ID not found.")` if no match
2. Verifies password with `password_verify()` — throws `Exception("Incorrect password.")` on mismatch
3. Returns the full user row (includes `role_prefix` from the JOIN)

---

## EventService.php

Depends on: `Event` model

### `storeEvent(string $title, string $date, int $user_id): bool`

A thin wrapper around `Event::create()`. Saves a new event to the database. Returns `true` on success, `false` on failure.

### `getCalendarEvents(int $year): array`

Returns all events for the given year as an associative array keyed by date string (`'YYYY-MM-DD'`). Each value is an array of event objects: `['title' => ..., 'type' => ...]`.

**Process:**
1. Loops through all 12 months and auto-generates a `"FIRST FRIDAY MASS"` entry for the first Friday of each month (type: `"Church Event"`)
2. Fetches all active events from the DB via `Event::getByYear($year)` and merges them into the same date-keyed structure

If multiple events fall on the same date, they are all included in that date's array.

---

## PostService.php

Depends on: `Event`, `Announcement`, `Document` models

### `getNewsFeed(): array`

Returns the latest 5 records from each content type:

```php
[
    'events'        => [...],   // 5 most recent events
    'announcements' => [...],   // 5 most recent announcements
    'documents'     => [...],   // 5 most recent documents
]
```

### `create(array $data, array $files, int $user_id): bool`

Dispatches on `$data['post_type']`:

| `post_type` | Action |
|---|---|
| `'announcement'` | Calls `Announcement::create($title, $content, $user_id)`, logs `UPLOAD_ANNOUNCEMENT` |
| `'event'` | Calls `Event::create($title, $event_date, $user_id)`, logs `POST_EVENT` |
| `'document'` | Calls `handleUpload($files, $user_id)`, logs `UPLOAD_DOCUMENT` |

Throws `Exception("Invalid post type")` if `post_type` does not match any of the above.

Logging is done via `logActivity()` from `config/logging.php`. The logged username is read from `$_SESSION['name']`.

### `handleUpload(array $files, int $user_id): bool` *(private)*

Handles file upload for the `'document'` post type.

1. Validates that `$files['attachment']` has no PHP upload error
2. Creates `Assets/uploads/` directory if it doesn't exist
3. Generates a unique filename: `{timestamp}_{original_filename}`
4. Moves the uploaded file to `Assets/uploads/`
5. Calls `Document::create()` to record the upload in the DB

Throws `Exception("File required")` or `Exception("Upload failed")` on error.

> **Security reminder:** There is currently no file type whitelist. Any file extension can be uploaded. Consider restricting to safe types (e.g., `pdf`, `docx`, `jpg`, `png`) in production.
