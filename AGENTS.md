# OLSHCO Digital Hub Development Rules

## Project

OLSHCO Digital Hub is a PHP and MySQL school information and content-management system running locally through XAMPP.

Project path:

C:\xampp\htdocs\bahay-ko

Database:

olshcodb

The system is already feature-complete. The current objective is deployment readiness, stabilization, security tightening, performance optimization, UI consistency, regression testing, and final documentation—not major feature expansion.

## Working Method

1. Inspect the actual current files before recommending or making changes.
2. Work on only one subsystem or page at a time.
3. Diagnose and explain the cause before implementing a fix.
4. Make the smallest coherent change.
5. Preserve existing formatting and architecture.
6. Do not perform broad rewrites unless explicitly authorized.
7. Never assume database columns. Inspect the schema first.
8. Do not remove populated or apparently legacy tables without explicit approval.
9. Do not create ZIP patches.
10. Do not commit, push, deploy, or alter production data without explicit approval.
11. Preserve all unrelated user changes in the working tree.
12. After every change, run relevant syntax checks and focused regression tests.

## Required Checks

PHP:

php -l path\to\file.php

JavaScript:

node --check path\to\file.js

Also verify:

- exact route registration in index.php;
- HTTP method restrictions;
- JSON responses for AJAX actions;
- server-side authorization;
- CSRF validation;
- database transaction behavior;
- direct URL tampering;
- empty and invalid IDs;
- role-specific visibility and access.

## Existing Architecture

Application flow generally follows:

Controller → Service → Model → Database

The application contains:

- authentication and account approval;
- Student and Parent registration;
- legal acceptance records;
- academic-structure management;
- Information Hub content;
- announcement, event, document, and survey publishing;
- Faculty submission and Administrator review workflow;
- recipient targeting;
- unified content engagement;
- notifications, email, and browser push;
- Student profile questionnaires and update cycles;
- government-advisory intake and conversion;
- analytics, exports, and activity logs.

## Role Restrictions

### Student

- Can manage their own account and Student profile.
- Can browse eligible targeted content.
- Can participate and engage where enabled.
- Cannot manage users, academic structures, questionnaires, cycles, or other students’ responses.

### Parent

- Can access eligible content using a validated linked-student relationship.
- Parent linkage does not grant access to private Student questionnaire responses.

### Faculty

- Can create and manage permitted content.
- College Faculty must remain restricted to their assigned department/program scope.
- IBED Faculty may use the broader permitted IBED scope.
- Faculty cannot approve their own submissions or access Administrator-only management pages.

### Administrator

- Manages users, academic structure, content review, questionnaires, profile cycles, advisories, analytics, and audit functions.

All permissions and content eligibility must be enforced server-side, not only through hidden UI elements.

## Content Workflow

Preserve these states and restrictions:

- Draft
- Pending review
- Rejected
- Scheduled
- Published
- Archived
- Restored

Faculty content must follow the review workflow. Administrator content may use authorized publishing options. Draft and rejected content editing restrictions must remain enforced.

## Engagement

The current engagement architecture uses:

- content_view
- content_reaction
- content_comment
- content_acknowledgment

The older announcement-specific engagement tables contain historical data and must not be deleted without explicit approval.

## Notifications

Preserve:

- in-system notifications;
- read/unread state;
- master delivery preferences;
- category-specific preferences;
- email delivery queue;
- browser push subscriptions and delivery;
- deduplication;
- targeted recipient eligibility;
- Student profile-cycle assignment notifications.

## Student Profile Management

Preserve:

- versioned questionnaires;
- Draft-only question editing;
- profile update cycles;
- All Active Students, College Students, or IBED Students scope;
- cycle preview;
- assignment generation;
- completion monitoring;
- optional consent handling;
- version-specific responses.

Students cannot access questionnaire configuration or other students’ responses.

## Government Advisories

Do not delete the advisory subsystem.

Recommended deployment behavior:

- advisory source/rule management remains Administrator-only;
- raw government advisories are not exposed as a large Student-facing feed;
- Administrators may review and convert relevant advisories into normal targeted announcements;
- Students receive only the resulting relevant announcement.

## Security Requirements

Audit and preserve:

- prepared SQL statements;
- CSRF validation;
- output escaping;
- server-side role and scope authorization;
- secure session regeneration;
- HttpOnly and SameSite cookies;
- Secure cookies only when HTTPS is enabled;
- upload MIME, extension, and size validation;
- randomized uploaded filenames;
- password-reset expiration and single use;
- safe user-facing errors;
- server-side error logging;
- privacy-safe analytics;
- suppression of individual survey results for small groups.

Add rate limiting where appropriate for:

- login;
- registration;
- password recovery;
- public submissions;
- repeated engagement endpoints.

## UI Rules

Preserve the existing maroon/orange visual identity.

Improve:

- readability;
- responsive layout;
- keyboard accessibility;
- visible focus states;
- modal behavior;
- consistent buttons and forms;
- loading, empty, success, and error states;
- prevention of duplicate form submissions.

Do not introduce unnecessary multicolored statistic cards or visual clutter.

Use skeleton loading only for genuinely asynchronous content. Do not add artificial loading delays.

## Deployment-Readiness Priority

Work in this order:

1. Establish a clean baseline and backups.
2. Inventory routes and role access.
3. Audit broken links and actions.
4. Audit PHP errors, logs, and unsafe error exposure.
5. Audit security and session configuration.
6. Audit database queries and indexes.
7. Check scheduled workers and notification delivery.
8. Check file-storage paths and permissions.
9. Apply focused performance improvements.
10. Apply focused UI/QoL improvements.
11. Run the full role-based regression matrix.
12. Prepare deployment configuration and final documentation.

Stop and report before making any destructive, schema-changing, or deployment action.