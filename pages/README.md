# Pages (Views)

This folder contains all page templates. They are plain PHP/HTML files included by `index.php` after the controller has run. They should not contain business logic — only rendering.

Variables are made available to views by `extract()`-ing the `$data` array returned by the controller. Session data (`$_SESSION`) and query params (`$_GET`) are also accessible directly.

---

## Page Reference

| File | Route | Login Required | Variables available |
|---|---|---|---|
| `home.php` | `?page=home` | No | None |
| `about.php` | `?page=about` | No | None |
| `academic.php` | `?page=academic` | No | None |
| `contact.php` | `?page=contact` | No | None |
| `login.php` | `?page=login` | No | `$_GET['error']`, `$_GET['success']` |
| `register.php` | `?page=register` | No | `$_GET['error']` |
| `dashboard.php` | `?page=dashboard` | Admin only | None |
| `news.php` | `?page=news` | No | `$events`, `$announcements`, `$documents` |
| `calendar.php` | `?page=calendar` | No | `$events`, `$selectedEvents`, `$month`, `$year`, `$monthName`, `$daysInMonth`, `$dayOfWeek`, `$prevMonth`, `$prevYear`, `$nextMonth`, `$nextYear`, `$currentDate`, `$selectedDate` |
| `postings.php` | `?page=postings` | Authenticated | None (form only) |

---

## Notes

- `calendar.php` renders the `EVENTS` PHP array as a JavaScript variable so `calendar.js` can read it client-side without additional AJAX calls.
- `login.php` and `register.php` display error/success banners based on `$_GET` parameters set by the controller redirect.
- `postings.php` and `register.php` both use a **hidden submit button** (`#hiddenSubmit`) — the visible button triggers a SweetAlert2 confirmation first, and only clicks the hidden one after the user confirms.
- There are currently **no auth guards** inside these files. Role/session checks should be added at the top of `dashboard.php` and `postings.php`.
