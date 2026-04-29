# Bahay Ko — AresVer
## Developer Documentation

**Project:** OLSCO (Our Lady of the Sacred Heart College of Oroquieta) Landing System  
**Version:** AresVer  
**Stack:** PHP (MVC), MySQL, Vanilla JS, CSS

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Folder Structure](#2-folder-structure)
3. [Architecture](#3-architecture)
4. [Database Schema](#4-database-schema)
5. [Routing & Request Lifecycle](#5-routing--request-lifecycle)
6. [Folder Documentation](#6-folder-documentation)
7. [Frontend Assets](#7-frontend-assets)
8. [Activity Logging](#8-activity-logging)
9. [Security Reminders](#9-security-reminders)
10. [Local Setup Guide](#10-local-setup-guide)

---

## 1. Project Overview

Bahay Ko is a PHP-based institutional web portal for OLSCO. It serves as a landing page system that provides:

- Public-facing pages: Home, About, Contact, Academics
- Authenticated features: News/Announcements, Event Calendar, Document Postings
- Role-based access (Admin / Student / Guest)
- Activity logging for audit trails

The system uses a **Front Controller** pattern — all requests pass through `index.php`, which dispatches to controllers and includes the appropriate page view.

---

## 2. Folder Structure

```
bahay-ko-AresVer/
│
├── index.php                    # Front controller / entry point
├── olshco.sql                   # Database schema (run this to set up the DB)
│
├── config/                      # See config/README.md
│   ├── README.md
│   ├── dbconnect.php            # MySQL connection setup
│   └── logging.php              # Activity logging function
│
├── app/
│   ├── controllers/             # See app/controllers/README.md
│   │   ├── README.md
│   │   ├── BaseController.php
│   │   ├── AuthController.php
│   │   ├── EventController.php
│   │   └── PostController.php
│   │
│   ├── models/                  # See app/models/README.md
│   │   ├── README.md
│   │   ├── BaseModel.php
│   │   ├── User.php
│   │   ├── Event.php
│   │   ├── Announcement.php
│   │   └── Document.php
│   │
│   └── services/                # See app/services/README.md
│       ├── README.md
│       ├── AuthService.php
│       ├── EventService.php
│       └── PostService.php
│
├── pages/                       # See pages/README.md
│   ├── README.md
│   ├── home.php
│   ├── about.php
│   ├── academic.php
│   ├── contact.php
│   ├── login.php
│   ├── register.php
│   ├── dashboard.php
│   ├── news.php
│   ├── calendar.php
│   └── postings.php
│
├── include/
│   └── navbar.php               # Shared navigation bar partial
│
└── Assets/
    ├── css/                     # Per-page stylesheets + root variables
    ├── js/                      # See Assets/js/README.md
    │   └── README.md
    ├── Images/                  # Static image assets
    └── uploads/                 # User-uploaded files (runtime, not in version control)
```

---

## 3. Architecture

### Pattern: Front Controller + MVC

```
Browser Request
      │
      ▼
  index.php  (Front Controller)
      │
      │── reads ?page= from URL
      │── dispatches to Controller
      │
      ▼
  Controller  (e.g., EventController)
      │
      │── calls Service(s)
      │
      ▼
  Service  (e.g., EventService)
      │
      │── calls Model(s)
      │
      ▼
  Model  (e.g., Event)
      │
      │── executes SQL via MySQLi
      │
      ▼
  Data returned up the chain
      │
      ▼
  index.php renders the HTML template
  (includes pages/<page>.php inline)
```

### Request Flow Summary

1. User visits `index.php?page=<pagename>`
2. `index.php` reads `$_GET['page']`, validates it against `$allowedPages`
3. A `switch` dispatches to the relevant controller method or sets metadata
4. The controller calls a service, which calls models
5. Data is returned as an associative array, `extract()`-ed into scope
6. The HTML shell in `index.php` includes the appropriate `pages/<page>.php` file
7. `navbar.php` and per-page CSS/JS are injected dynamically

---

## 4. Database Schema

Database name: `olshcodb`  
Schema file: `olshco.sql`

### Tables at a Glance

| Table | Purpose |
|---|---|
| `role` | User roles (e.g., Admin, Student) |
| `department` | School departments |
| `user` | Registered users |
| `announcements` | Posted announcements |
| `documents` | Uploaded file records |
| `events` | Scheduled events |
| `actions` | Allowed log action names |
| `activity_log` | Audit log of user actions |

### Entity Relationships

```
role ──< user >── department
                  │
          ┌───────┼──────────┐
          ▼       ▼          ▼
    announcements events  documents
                  │
            activity_log >── actions
```

### Key Column Notes

- `user.studID` — used as the login username (unique, not the PK)
- `user.password` — stored as a bcrypt hash via `password_hash()`
- `documents.file_name` — stored as `{timestamp}_{original_name}` to avoid collisions
- `events.status` / `announcements.status` / `documents.status` — soft-delete flag; only `'active'` records are shown
- `actions.action_name` — must be seeded manually; logging silently does nothing if a name is missing

---

## 5. Routing & Request Lifecycle

All routing is handled in `index.php` via a `switch` on `$_GET['page']`.

### Allowed View Pages

```
home | about | contact | academic | news | calendar |
postings | login | register | dashboard
```

Any unrecognized page defaults to `home`.

### Action Endpoints (exit immediately, no page rendered)

| `?page=` value | What it does |
|---|---|
| `login_action` | `AuthController::login()` → redirect |
| `register_action` | `AuthController::register()` → redirect |
| `logout` | `AuthController::logout()` → redirect |
| `post_store` | `PostController::store()` → redirect |
| `event_store` | `EventController::store()` → JSON response |

### Session Variables

| Key | Set by | Contains |
|---|---|---|
| `$_SESSION['user_id']` | AuthController login | Integer user ID |
| `$_SESSION['role']` | AuthController login | Role prefix string (e.g. `"Admin"`) |
| `$_SESSION['name']` | AuthController login | Full name string |

---

## 6. Folder Documentation

Each major folder has its own `README.md` with detailed per-file and per-method documentation. Start there when working in a specific layer:

| Folder | README | What's covered |
|---|---|---|
| `config/` | [config/README.md](config/README.md) | DB connection, logging function, required seed data |
| `app/controllers/` | [app/controllers/README.md](app/controllers/README.md) | All controller methods, input/output, session handling |
| `app/services/` | [app/services/README.md](app/services/README.md) | Business logic, validation, dispatch rules |
| `app/models/` | [app/models/README.md](app/models/README.md) | All model methods, SQL behavior, return types |
| `pages/` | [pages/README.md](pages/README.md) | View variable reference per page |
| `Assets/js/` | [Assets/js/README.md](Assets/js/README.md) | JS behavior per file, dependencies, DOM targets |

---

## 7. Frontend Assets

### CSS

Each page has a dedicated stylesheet in `Assets/css/`. A shared `root.css` defines CSS custom properties used across all pages.

| File | Purpose |
|---|---|
| `root.css` | CSS variables (colors, fonts, spacing) |
| `index.css` | Global layout: `fullscreen`, `gradient-frame`, `main-card` |
| `login.css` | Login form |
| `register.css` | Registration form |
| `news.css` | Slideshow and news cards |
| `calendar.css` | Calendar grid and event panel |
| `posting.css` | Post creation form |
| `academic.css` | Academics hero and content |
| `about.css` | About page layout |
| `contact.css` | Contact page layout |
| `dashboard.css` | Admin dashboard layout |

### External Libraries (CDN)

| Library | Used for |
|---|---|
| Google Fonts — Poppins | Typography throughout |
| Font Awesome 6.5.1 | Icons |
| SweetAlert2 | Confirmation dialogs on postings and register pages |

---

## 8. Activity Logging

All user actions are recorded in the `activity_log` table via `logActivity()` in `config/logging.php`.

Logging is silent when the user is not logged in, or when the action name doesn't exist in the `actions` table. See [config/README.md](config/README.md) for the full function reference and required seed SQL.

---

## 9. Security Reminders

These are known gaps to address before going to production:

**No file type validation on upload**  
`PostService::handleUpload()` accepts any file extension. Add a whitelist (e.g. `pdf`, `docx`, `jpg`, `png`) before moving the uploaded file.

**Hardcoded DB credentials**  
`config/dbconnect.php` contains `root` with no password. Replace with environment variables or a `.env` file excluded from version control.

**No auth guards on protected pages**  
`dashboard.php` and `postings.php` do not verify that the user is logged in or has the correct role. Add session/role checks at the top of those files.

---

## 10. Local Setup Guide

### Requirements

- PHP 7.4+ with MySQLi extension enabled
- MySQL / MariaDB
- A local web server (XAMPP, Laragon, or similar)

### Steps

**1. Place the project in your web root**
```
htdocs/bahay-ko/   (XAMPP)
www/bahay-ko/      (Laragon)
```

**2. Create the database**
```sql
-- In phpMyAdmin or MySQL CLI:
source /path/to/olshco.sql;
```

**3. Seed required data**
```sql
USE olshcodb;

-- Roles (required for registration and login)
INSERT INTO role (role_prefix) VALUES ('Admin'), ('Student');

-- Departments (required for registration)
INSERT INTO department (department_name) VALUES
    ('College'), ('Senior High'), ('Junior High'), ('Elementary');

-- Action names (required for activity logging)
INSERT INTO actions (action_name) VALUES
    ('LOGIN'), ('LOGOUT'), ('REGISTER_ACCOUNT'),
    ('POST_EVENT'), ('UPLOAD_ANNOUNCEMENT'), ('UPLOAD_DOCUMENT');
```

**4. Configure DB credentials** (if your setup differs from the defaults)  
Edit `config/dbconnect.php` — change `$user`, `$pass`, and `$db` as needed.

**5. Set upload folder permissions**
```bash
chmod 755 Assets/uploads/
```

**6. Open in browser**
```
http://localhost/bahay-ko/
```

---

*Documentation — bahay-ko-AresVer, April 2026*
