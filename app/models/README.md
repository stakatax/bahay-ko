# Models

This folder contains all database model classes. Each model is responsible for **one table** and handles all SQL queries for that table. No business logic lives here — only data access.

All models extend `BaseModel`, which provides the `$this->conn` MySQLi connection.

---

## BaseModel.php

The parent class for all models. Loads `config/dbconnect.php` in its constructor and assigns the connection to `$this->conn`.

Every model must extend this class to get database access.

---

## User.php

Handles the `user` table.

| Method | Parameters | Returns | Description |
|---|---|---|---|
| `findByStudentID` | `$studID: string` | `array\|null` | Fetches one user row JOINed with their `role_prefix`. Used by login. |
| `create` | `$data: array` | `bool` | Inserts a new user. Expects keys: `studID`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `gender`, `age`, `role_id`, `department_id`. Password must already be hashed before calling this. |
| `getStudentRole` | _(none)_ | `array\|null` | Returns `role_id` for the `"Student"` role. Used during registration to auto-assign student role. |

---

## Event.php

Handles the `events` table.

| Method | Parameters | Returns | Description |
|---|---|---|---|
| `create` | `$title: string`, `$date: string`, `$user_id: int` | `bool` | Inserts a new event. Status is set to `'active'` automatically. Date format: `YYYY-MM-DD`. |
| `getByYear` | `$year: int` | `mysqli_result` | Returns all active events between Jan 1 and Dec 31 of the given year, ordered ascending. Returns a raw result object (not an array) — caller must iterate with `fetch_assoc()`. |
| `getRecent` | `$limit: int = 10` | `array` | Returns the most recent active events up to `$limit`, ordered by date descending. |

---

## Announcement.php

Handles the `announcements` table.

| Method | Parameters | Returns | Description |
|---|---|---|---|
| `create` | `$title: string`, `$content: string`, `$user_id: int` | `bool` | Inserts a new announcement. Type is hardcoded to `'announcement'`, status to `'active'`. |
| `getRecent` | `$limit: int = 5` | `array` | Returns the most recent active announcements up to `$limit`, ordered by `created_at` descending. |

---

## Document.php

Handles the `documents` table. Records metadata about uploaded files — the actual files are stored in `Assets/uploads/`.

| Method | Parameters | Returns | Description |
|---|---|---|---|
| `create` | `$fileName: string`, `$fileType: string`, `$user_id: int` | `bool` | Inserts a document record. `$fileName` is the stored filename (with timestamp prefix). `$fileType` is the file extension (e.g. `pdf`, `docx`). Status defaults to `'active'`. |
| `getRecent` | `$limit: int` | `array` | Returns the most recent active document records up to `$limit`. |

---

## Notes

- All models use **prepared statements** via MySQLi — no raw string interpolation in SQL.
- `getByYear()` in `Event` returns a `mysqli_result` object, while all other `get*` methods return plain PHP arrays (`fetch_all(MYSQLI_ASSOC)`). Be aware of this difference when consuming the data in services.
- Status values (`'active'`) are plain strings — there is no ENUM on the `status` column in the DB schema.
