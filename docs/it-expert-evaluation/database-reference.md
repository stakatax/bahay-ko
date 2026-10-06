> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Database reference and physical ER diagram

Metadata verified **2 October 2026** against the local application database using read-only information_schema queries. **65 tables, 624 columns, 125 declared foreign-key constraints.** No application rows, secrets, credential hashes or personal answers were exported.

Sources: [original schema](../../olshcodb-structure.sql), additive baselines [027](../../database/baselines/027_publication_notification_outbox.sql), [028](../../database/baselines/028_request_rate_limit.sql), [029](../../database/baselines/029_parent_child_record.sql), and [migration 030](../../database/migrations/030_faculty_personal_profile.php). Migration 030 allows `user.gender` and `user.age` to be NULL while newly provisioned Faculty complete their personal details; birthdate remains nullable. The re-audit changed validation code, not table definitions. `php scripts/check_database_baseline.php` reports 65 expected/current tables and zero differences.

[Evaluator guide](system-evaluation.md) | [User manual](user-manual.md) | DFD, use-case and UML diagrams (local only: `system-diagrams.md`) | [Machine-readable metadata](../database-schema-metadata.json)

## Reading the model

PK/FK attributes are shown in the ERD for readability; the dictionary contains every column. `o|` means an optional single reference, `||` a required single reference, and `o{` zero or many rows. SQL cardinalities do not imply account approval or recipient eligibility. Nullable references and matching unique indexes determine physical cardinality.

Polymorphic `content_type`/`content_id` relationships are service-enforced, not invented SQL foreign keys. Legacy announcement engagement tables are retained; active engagement uses unified content tables. Parent content access requires a Verified child record or Verified linked Active Student. Parent linkage never authorizes private Student questionnaire access. Small-group survey suppression is application logic; raw responses require restricted database access.

Metadata defaults are shown as returned by MariaDB. A NULL metadata default does not always distinguish no explicit default from SQL NULL. Index column order is retained; row-dependent next-ID and cardinality estimates are omitted. SQL nullability/defaults do not replace service validation.

## Table directory

| Table | Purpose |
| --- | --- |

| [academic_program](#academic-program) | Programs and strands associated with education levels. |
| [academic_structure_history](#academic-structure-history) | History of academic-catalog changes. |
| [actions](#actions) | Business audit action catalog. |
| [activity_log](#activity-log) | Recorded business actions and actor/target references. |
| [announcements](#announcements) | Announcement content and publication workflow. |
| [announcement_acknowledgment](#announcement-acknowledgment) | Historical announcement-specific acknowledgments; retained legacy data. |
| [announcement_comment](#announcement-comment) | Historical announcement-specific comments; retained legacy data. |
| [announcement_reaction](#announcement-reaction) | Historical announcement-specific reaction table; old records cleared by the approved voting reset. |
| [announcement_target](#announcement-target) | Announcement recipient criteria. |
| [announcement_view](#announcement-view) | Historical announcement-specific views; retained legacy data. |
| [calendar_holiday](#calendar-holiday) | Calendar holiday definitions. |
| [content_acknowledgment](#content-acknowledgment) | Unified acknowledgment records across supported content. |
| [content_comment](#content-comment) | Unified comments and reply relationships. |
| [content_interest](#content-interest) | Content topic/interest catalog. |
| [content_interest_assignment](#content-interest-assignment) | Topic assignments to content. |
| [content_reaction](#content-reaction) | Unified upvotes and downvotes. One vote per user and content; selecting the same vote withdraws it. Upvote means Like; Downvote means Dislike. Counts are displayed separately, with no combined score. |
| [content_view](#content-view) | Unified content views. |
| [department](#department) | School-division catalog. |
| [documents](#documents) | Document metadata, storage references and publication workflow. |
| [document_download](#document-download) | Document-download records; presence alone does not prove every current download writes here. |
| [document_target](#document-target) | Document recipient criteria. |
| [education_level](#education-level) | Education levels within divisions. |
| [email_delivery](#email-delivery) | Queued email delivery and delivery status. |
| [events](#events) | Event schedule, content and publication workflow. |
| [event_target](#event-target) | Event recipient criteria. |
| [government_advisory](#government-advisory) | Imported/reviewed government advisory records. |
| [government_advisory_rule](#government-advisory-rule) | Advisory relevance rules. |
| [government_source](#government-source) | Trusted government-source definitions. |
| [grade_level](#grade-level) | Grade/year levels. |
| [legal_document_version](#legal-document-version) | Versioned Terms and Privacy documents. |
| [notification](#notification) | In-system notifications and read state. |
| [notification_category_preference](#notification-category-preference) | User delivery preferences by category. |
| [notification_preference](#notification-preference) | User channel/master preferences. |
| [parent_child_record](#parent-child-record) | Account-independent child enrollment claim and Admin verification history. |
| [parent_student](#parent-student) | Parent-to-Student relationship and verification state. |
| [password_reset_request](#password-reset-request) | Password-recovery request/throttle records. |
| [password_reset_token](#password-reset-token) | Password-reset token verification and lifecycle records. |
| [publication_notification_outbox](#publication-notification-outbox) | Durable publication-to-notification work, leases and retries. |
| [push_delivery](#push-delivery) | Queued browser-push delivery state. |
| [push_subscription](#push-subscription) | Browser push subscriptions and cryptographic delivery information. |
| [request_rate_limit](#request-rate-limit) | HMAC-keyed attempt counters and expiries. |
| [role](#role) | Authenticated role catalog. |
| [section](#section) | Sections assigned to grade/year and optionally program/strand. |
| [student_profile](#student-profile) | Student profile and survey metadata. |
| [student_profile_consent](#student-profile-consent) | Version-related Student profile consent decisions. |
| [student_profile_consent_definition](#student-profile-consent-definition) | Definitions of optional profile consent. |
| [student_profile_cycle](#student-profile-cycle) | Profile update cycles and lifecycle. |
| [student_profile_cycle_assignment](#student-profile-cycle-assignment) | Per-Student cycle assignments and completion state. |
| [student_profile_cycle_scope](#student-profile-cycle-scope) | Academic recipient scopes attached to cycles. |
| [student_profile_interest](#student-profile-interest) | Student topic preferences. |
| [student_profile_question](#student-profile-question) | Versioned Student profile questions. |
| [student_profile_response](#student-profile-response) | Individual Student profile answers. |
| [student_profile_survey_version](#student-profile-survey-version) | Student profile questionnaire versions. |
| [survey](#survey) | Published-content surveys and workflow. |
| [survey_answer](#survey-answer) | Answers to content-survey questions. |
| [survey_answer_choice](#survey-answer-choice) | Selected choices attached to survey answers. |
| [survey_choice](#survey-choice) | Content-survey choice options. |
| [survey_question](#survey-question) | Content-survey questions. |
| [survey_response](#survey-response) | Survey participation records. |
| [survey_target](#survey-target) | Content-survey recipient criteria. |
| [user](#user) | Accounts, credential hashes, roles, academic assignments and access state. |
| [user_legal_acceptance](#user-legal-acceptance) | User acceptance of exact legal-document versions. |
| [user_optional_consent](#user-optional-consent) | User-level optional-consent records. |
| [user_role_history](#user-role-history) | Account role-change history. |
| [user_status_history](#user-status-history) | Account status-change history. |

## Complete physical ER diagram

Standalone Mermaid source (local only: `database-schema.mmd`) | Rendered SVG (local only: `diagrams/database-schema.svg`). Use zoom for the complete schema.

```mermaid
erDiagram
    academic_program {
        int academic_program_id PK
        int education_level_id FK
    }
    education_level ||--o{ academic_program : "fk_academic_program_level"
    academic_structure_history {
        int academic_history_id PK
        int changed_by FK
    }
    user ||--o{ academic_structure_history : "fk_academic_history_actor"
    actions {
        int action_id PK
    }
    activity_log {
        int log_id PK
        int action_id FK
        int user_id FK
        int target_user_id FK
    }
    actions o|--o{ activity_log : "activity_log_ibfk_1"
    user o|--o{ activity_log : "activity_log_ibfk_2"
    user o|--o{ activity_log : "fk_activity_log_target_user"
    announcements {
        int announcement_id PK
        int calendar_event_id FK
        int reviewed_by FK
        int user_id FK
    }
    user o|--o{ announcements : "announcements_ibfk_1"
    events o|--o{ announcements : "fk_announcement_calendar_event"
    user o|--o{ announcements : "fk_announcement_reviewer"
    announcement_acknowledgment {
        int acknowledgment_id PK
        int announcement_id FK
        int user_id FK
    }
    announcements ||--o{ announcement_acknowledgment : "fk_acknowledgment_announcement"
    user ||--o{ announcement_acknowledgment : "fk_acknowledgment_user"
    announcement_comment {
        int comment_id PK
        int announcement_id FK
        int user_id FK
    }
    announcements o|--o{ announcement_comment : "announcement_comment_ibfk_1"
    user o|--o{ announcement_comment : "announcement_comment_ibfk_2"
    announcement_reaction {
        int reaction_id PK
        int announcement_id FK
        int user_id FK
    }
    announcements o|--o{ announcement_reaction : "announcement_reaction_ibfk_1"
    user o|--o{ announcement_reaction : "announcement_reaction_ibfk_2"
    announcement_target {
        int target_id PK
        int announcement_id FK
        int role_id FK
        int department_id FK
        int education_level_id FK
        int academic_program_id FK
        int grade_level_id FK
        int section_id FK
    }
    announcements ||--o{ announcement_target : "announcement_target_ibfk_1"
    role o|--o{ announcement_target : "announcement_target_ibfk_2"
    department o|--o{ announcement_target : "announcement_target_ibfk_3"
    education_level o|--o{ announcement_target : "announcement_target_ibfk_4"
    grade_level o|--o{ announcement_target : "announcement_target_ibfk_5"
    section o|--o{ announcement_target : "announcement_target_ibfk_6"
    academic_program o|--o{ announcement_target : "fk_announcement_target_program"
    announcement_view {
        int view_id PK
        int announcement_id FK
        int user_id FK
    }
    announcements o|--o{ announcement_view : "announcement_view_ibfk_1"
    user o|--o{ announcement_view : "announcement_view_ibfk_2"
    calendar_holiday {
        int calendar_holiday_id PK
        int created_by FK
    }
    user o|--o{ calendar_holiday : "fk_calendar_holiday_creator"
    content_acknowledgment {
        int acknowledgment_id PK
        int user_id FK
    }
    user ||--o{ content_acknowledgment : "fk_content_acknowledgment_user"
    content_comment {
        int comment_id PK
        int user_id FK
        int parent_comment_id FK
    }
    content_comment o|--o{ content_comment : "fk_content_comment_parent"
    user ||--o{ content_comment : "fk_content_comment_user"
    content_interest {
        int interest_id PK
    }
    content_interest_assignment {
        int content_interest_assignment_id PK
        int interest_id FK
        int assigned_by FK
    }
    content_interest ||--o{ content_interest_assignment : "fk_content_interest_assignment_interest"
    user o|--o{ content_interest_assignment : "fk_content_interest_assignment_user"
    content_reaction {
        int reaction_id PK
        int user_id FK
    }
    user ||--o{ content_reaction : "fk_content_reaction_user"
    content_view {
        int view_id PK
        int user_id FK
    }
    user ||--o{ content_view : "fk_content_view_user"
    department {
        int department_id PK
    }
    documents {
        int document_id PK
        int reviewed_by FK
        int user_id FK
    }
    user o|--o{ documents : "documents_ibfk_1"
    user o|--o{ documents : "fk_document_reviewer"
    document_download {
        int download_id PK
        int document_id FK
        int user_id FK
    }
    documents ||--o{ document_download : "fk_document_download_document"
    user ||--o{ document_download : "fk_document_download_user"
    document_target {
        int target_id PK
        int document_id FK
        int academic_program_id FK
    }
    documents o|--o{ document_target : "document_target_ibfk_1"
    academic_program o|--o{ document_target : "fk_document_target_program"
    education_level {
        int education_level_id PK
        int department_id FK
    }
    department ||--o{ education_level : "fk_education_level_department"
    email_delivery {
        bigint delivery_id PK
        int notification_id FK
    }
    notification ||--o| email_delivery : "fk_email_delivery_notification"
    events {
        int event_id PK
        int reviewed_by FK
        int user_id FK
    }
    user o|--o{ events : "events_ibfk_1"
    user o|--o{ events : "fk_event_reviewer"
    event_target {
        int target_id PK
        int event_id FK
        int academic_program_id FK
    }
    events o|--o{ event_target : "event_target_ibfk_1"
    academic_program o|--o{ event_target : "fk_event_target_program"
    government_advisory {
        int government_advisory_id PK
        int government_source_id FK
        int reviewed_by FK
        int submitted_by FK
    }
    user o|--o{ government_advisory : "fk_government_advisory_reviewer"
    government_source ||--o{ government_advisory : "fk_government_advisory_source"
    user o|--o{ government_advisory : "fk_government_advisory_submitter"
    government_advisory_rule {
        int government_advisory_rule_id PK
        int created_by FK
    }
    user o|--o{ government_advisory_rule : "fk_government_advisory_rule_creator"
    government_source {
        int government_source_id PK
        int created_by FK
    }
    user o|--o{ government_source : "fk_government_source_creator"
    grade_level {
        int grade_level_id PK
        int education_level_id FK
    }
    education_level o|--o{ grade_level : "grade_level_ibfk_1"
    legal_document_version {
        int legal_document_version_id PK
    }
    notification {
        int notification_id PK
        int user_id FK
    }
    user o|--o{ notification : "notification_ibfk_1"
    notification_category_preference {
        bigint category_preference_id PK
        int user_id FK
    }
    user ||--o{ notification_category_preference : "fk_notification_category_user"
    notification_preference {
        int preference_id PK
        int user_id FK
    }
    user o|--o| notification_preference : "notification_preference_ibfk_1"
    parent_child_record {
        int parent_user_id PK,FK
        int section_id FK
        int verified_by FK
        int linked_student_user_id FK
    }
    user ||--o| parent_child_record : "fk_child_record_parent"
    section ||--o{ parent_child_record : "fk_child_record_section"
    user o|--o{ parent_child_record : "fk_child_record_student"
    user o|--o{ parent_child_record : "fk_child_record_verifier"
    parent_student {
        int parent_student_id PK
        int parent_user_id FK
        int student_user_id FK
        int verified_by FK
    }
    user ||--o{ parent_student : "fk_parent_student_parent"
    user ||--o{ parent_student : "fk_parent_student_student"
    user o|--o{ parent_student : "fk_parent_student_verifier"
    password_reset_request {
        bigint password_reset_request_id PK
        int user_id FK
    }
    user o|--o{ password_reset_request : "fk_password_reset_request_user"
    password_reset_token {
        bigint password_reset_token_id PK
        int user_id FK
    }
    user ||--o{ password_reset_token : "fk_password_reset_token_user"
    publication_notification_outbox {
        bigint outbox_id PK
    }
    push_delivery {
        bigint delivery_id PK
        int notification_id FK
        bigint subscription_id FK
    }
    notification ||--o{ push_delivery : "fk_push_delivery_notification"
    push_subscription ||--o{ push_delivery : "fk_push_delivery_subscription"
    push_subscription {
        bigint subscription_id PK
        int user_id FK
    }
    user ||--o{ push_subscription : "fk_push_subscription_user"
    request_rate_limit {
        char key_hash PK
    }
    role {
        int role_id PK
    }
    section {
        int section_id PK
        int grade_level_id FK
        int academic_program_id FK
    }
    academic_program o|--o{ section : "fk_section_academic_program"
    grade_level o|--o{ section : "section_ibfk_1"
    student_profile {
        int student_profile_id PK
        int user_id FK
    }
    user ||--o| student_profile : "fk_student_profile_user"
    student_profile_consent {
        bigint student_profile_consent_id PK
        int student_profile_id FK
    }
    student_profile ||--o{ student_profile_consent : "fk_student_profile_consent_profile"
    student_profile_consent_definition {
        int student_profile_consent_definition_id PK
    }
    student_profile_cycle {
        int student_profile_cycle_id PK
        int survey_version FK
        int created_by FK
        int activated_by FK
        int closed_by FK
    }
    user o|--o{ student_profile_cycle : "fk_student_profile_cycle_activated_by"
    user o|--o{ student_profile_cycle : "fk_student_profile_cycle_closed_by"
    user o|--o{ student_profile_cycle : "fk_student_profile_cycle_created_by"
    student_profile_survey_version ||--o{ student_profile_cycle : "fk_student_profile_cycle_version"
    student_profile_cycle_assignment {
        bigint student_profile_cycle_assignment_id PK
        int student_profile_cycle_id FK
        int student_profile_id FK
        int assigned_by FK
    }
    user o|--o{ student_profile_cycle_assignment : "fk_student_profile_cycle_assignment_assigned_by"
    student_profile_cycle ||--o{ student_profile_cycle_assignment : "fk_student_profile_cycle_assignment_cycle"
    student_profile ||--o{ student_profile_cycle_assignment : "fk_student_profile_cycle_assignment_profile"
    student_profile_cycle_scope {
        bigint student_profile_cycle_scope_id PK
        int student_profile_cycle_id FK
        int department_id FK
        int education_level_id FK
        int academic_program_id FK
        int grade_level_id FK
        int section_id FK
    }
    student_profile_cycle ||--o{ student_profile_cycle_scope : "fk_student_profile_cycle_scope_cycle"
    department o|--o{ student_profile_cycle_scope : "fk_student_profile_cycle_scope_department"
    grade_level o|--o{ student_profile_cycle_scope : "fk_student_profile_cycle_scope_grade"
    education_level o|--o{ student_profile_cycle_scope : "fk_student_profile_cycle_scope_level"
    academic_program o|--o{ student_profile_cycle_scope : "fk_student_profile_cycle_scope_program"
    section o|--o{ student_profile_cycle_scope : "fk_student_profile_cycle_scope_section"
    student_profile_interest {
        int student_profile_interest_id PK
        int student_profile_id FK
        int interest_id FK
    }
    content_interest ||--o{ student_profile_interest : "fk_student_interest_catalog"
    student_profile ||--o{ student_profile_interest : "fk_student_interest_profile"
    student_profile_question {
        int student_profile_question_id PK
        int survey_version FK
    }
    student_profile_survey_version ||--o{ student_profile_question : "fk_student_profile_question_survey_version"
    student_profile_response {
        bigint student_profile_response_id PK
        int student_profile_id FK
        int student_profile_question_id FK
    }
    student_profile ||--o{ student_profile_response : "fk_student_profile_response_profile"
    student_profile_question ||--o{ student_profile_response : "fk_student_profile_response_question"
    student_profile_survey_version {
        int survey_version PK
        int created_by FK
        int activated_by FK
    }
    user o|--o{ student_profile_survey_version : "fk_student_profile_survey_version_activated_by"
    user o|--o{ student_profile_survey_version : "fk_student_profile_survey_version_created_by"
    survey {
        int survey_id PK
        int calendar_event_id FK
        int reviewed_by FK
        int user_id FK
    }
    events o|--o{ survey : "fk_survey_calendar_event"
    user o|--o{ survey : "fk_survey_reviewer"
    user o|--o{ survey : "survey_ibfk_1"
    survey_answer {
        int answer_id PK
        int response_id FK
        int question_id FK
        int user_id FK
    }
    survey_response o|--o{ survey_answer : "fk_survey_answer_response"
    survey_question o|--o{ survey_answer : "survey_answer_ibfk_1"
    user o|--o{ survey_answer : "survey_answer_ibfk_2"
    survey_answer_choice {
        int answer_id PK,FK
        int choice_id PK,FK
    }
    survey_answer ||--o{ survey_answer_choice : "fk_survey_answer_choice_answer"
    survey_choice ||--o{ survey_answer_choice : "fk_survey_answer_choice_choice"
    survey_choice {
        int choice_id PK
        int question_id FK
    }
    survey_question o|--o{ survey_choice : "survey_choice_ibfk_1"
    survey_question {
        int question_id PK
        int survey_id FK
    }
    survey o|--o{ survey_question : "survey_question_ibfk_1"
    survey_response {
        int response_id PK
        int survey_id FK
        int user_id FK
    }
    survey ||--o{ survey_response : "fk_survey_response_survey"
    user ||--o{ survey_response : "fk_survey_response_user"
    survey_target {
        int target_id PK
        int survey_id FK
        int role_id FK
        int department_id FK
        int education_level_id FK
        int academic_program_id FK
        int grade_level_id FK
        int section_id FK
    }
    department o|--o{ survey_target : "fk_survey_target_department"
    education_level o|--o{ survey_target : "fk_survey_target_education_level"
    grade_level o|--o{ survey_target : "fk_survey_target_grade_level"
    academic_program o|--o{ survey_target : "fk_survey_target_program"
    role o|--o{ survey_target : "fk_survey_target_role"
    section o|--o{ survey_target : "fk_survey_target_section"
    survey o|--o{ survey_target : "survey_target_ibfk_1"
    user {
        int user_id PK
        int approved_by FK
        int account_reviewed_by FK
        int provisioned_by FK
        int role_id FK
        int department_id FK
        int education_level_id FK
        int academic_program_id FK
        int grade_level_id FK
        int section_id FK
    }
    academic_program o|--o{ user : "fk_user_academic_program"
    user o|--o{ user : "fk_user_account_reviewed_by"
    user o|--o{ user : "fk_user_approved_by"
    education_level o|--o{ user : "fk_user_education"
    grade_level o|--o{ user : "fk_user_grade"
    user o|--o{ user : "fk_user_provisioned_by"
    section o|--o{ user : "fk_user_section"
    role o|--o{ user : "user_ibfk_1"
    department o|--o{ user : "user_ibfk_2"
    user_legal_acceptance {
        int user_legal_acceptance_id PK
        int user_id FK
        int legal_document_version_id FK
    }
    legal_document_version ||--o{ user_legal_acceptance : "fk_user_legal_acceptance_document"
    user ||--o{ user_legal_acceptance : "fk_user_legal_acceptance_user"
    user_optional_consent {
        int user_optional_consent_id PK
        int user_id FK
    }
    user ||--o{ user_optional_consent : "fk_user_optional_consent_user"
    user_role_history {
        int user_role_history_id PK
        int user_id FK
        int previous_role_id FK
        int new_role_id FK
        int changed_by FK
    }
    user ||--o{ user_role_history : "fk_user_role_history_changed_by"
    role ||--o{ user_role_history : "fk_user_role_history_new"
    role ||--o{ user_role_history : "fk_user_role_history_previous"
    user ||--o{ user_role_history : "fk_user_role_history_user"
    user_status_history {
        int user_status_history_id PK
        int user_id FK
        int changed_by FK
    }
    user ||--o{ user_status_history : "fk_user_status_history_changed_by"
    user ||--o{ user_status_history : "fk_user_status_history_user"
```

## Complete data dictionary

### academic program

Programs and strands associated with education levels.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `academic_program_id` | `int(11)` | NO | NULL | auto_increment |
| `education_level_id` | `int(11)` | NO | NULL |  |
| `program_name` | `varchar(150)` | NO | NULL |  |
| `program_code` | `varchar(30)` | NO | NULL |  |
| `program_type` | `enum('Program','Strand')` | NO | NULL |  |
| `description` | `text` | YES | NULL |  |
| `status` | `enum('Active','Inactive')` | NO | 'Active' |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_academic_program_level` | No | `education_level_id` |
| `idx_academic_program_status` | No | `status` |
| `PRIMARY` | Yes | `academic_program_id` |
| `uq_academic_program_code` | Yes | `education_level_id`, `program_code` |
| `uq_academic_program_name` | Yes | `education_level_id`, `program_name` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_academic_program_level` | `education_level_id` ? `education_level.education_level_id` | CASCADE | RESTRICT |

### academic structure history

History of academic-catalog changes.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `academic_history_id` | `int(11)` | NO | NULL | auto_increment |
| `entity_type` | `enum('department','education_level','academic_program','grade_level','section')` | NO | NULL |  |
| `entity_id` | `int(11)` | NO | NULL |  |
| `change_type` | `enum('create','update','activate','deactivate')` | NO | NULL |  |
| `previous_data` | `longtext` | YES | NULL |  |
| `new_data` | `longtext` | YES | NULL |  |
| `reason` | `varchar(1000)` | NO | NULL |  |
| `changed_by` | `int(11)` | NO | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_academic_history_actor` | No | `changed_by`, `created_at` |
| `idx_academic_history_entity` | No | `entity_type`, `entity_id`, `created_at` |
| `PRIMARY` | Yes | `academic_history_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_academic_history_actor` | `changed_by` ? `user.user_id` | CASCADE | RESTRICT |

### actions

Business audit action catalog.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `action_id` | `int(11)` | NO | NULL | auto_increment |
| `action_name` | `varchar(100)` | NO | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `PRIMARY` | Yes | `action_id` |
| `uq_actions_action_name` | Yes | `action_name` |

### activity log

Recorded business actions and actor/target references.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `log_id` | `int(11)` | NO | NULL | auto_increment |
| `description` | `varchar(255)` | NO | NULL |  |
| `ip_address` | `varchar(50)` | YES | NULL |  |
| `browser` | `varchar(150)` | YES | NULL |  |
| `device` | `varchar(150)` | YES | NULL |  |
| `timestamp` | `datetime` | YES | current_timestamp() |  |
| `action_id` | `int(11)` | YES | NULL |  |
| `user_id` | `int(11)` | YES | NULL |  |
| `target_user_id` | `int(11)` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `action_id` | No | `action_id` |
| `idx_activity_log_target_user` | No | `target_user_id`, `timestamp` |
| `PRIMARY` | Yes | `log_id` |
| `user_id` | No | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `activity_log_ibfk_1` | `action_id` ? `actions.action_id` | RESTRICT | RESTRICT |
| `activity_log_ibfk_2` | `user_id` ? `user.user_id` | RESTRICT | RESTRICT |
| `fk_activity_log_target_user` | `target_user_id` ? `user.user_id` | CASCADE | RESTRICT |

### announcements

Announcement content and publication workflow.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `announcement_id` | `int(11)` | NO | NULL | auto_increment |
| `title` | `varchar(100)` | NO | NULL |  |
| `content` | `text` | NO | NULL |  |
| `image_path` | `varchar(500)` | YES | NULL |  |
| `audio_path` | `varchar(500)` | YES | NULL |  |
| `audio_file_name` | `varchar(255)` | YES | NULL |  |
| `audio_mime_type` | `varchar(100)` | YES | NULL |  |
| `audio_file_size` | `bigint(20) unsigned` | YES | NULL |  |
| `audio_transcript` | `text` | YES | NULL |  |
| `reference_link` | `varchar(500)` | YES | NULL |  |
| `type` | `varchar(50)` | NO | NULL |  |
| `category` | `varchar(50)` | NO | 'general' |  |
| `priority` | `enum('Low','Normal','Medium','High','Important','Urgent','Emergency')` | YES | 'Medium' |  |
| `created_at` | `datetime` | YES | current_timestamp() |  |
| `published_at` | `datetime` | YES | NULL |  |
| `allow_reactions` | `tinyint(1)` | NO | 1 |  |
| `allow_comments` | `tinyint(1)` | NO | 1 |  |
| `require_acknowledgment` | `tinyint(1)` | NO | 0 |  |
| `send_notification` | `tinyint(1)` | NO | 1 |  |
| `status` | `varchar(50)` | YES | NULL |  |
| `workflow_status` | `enum('draft','pending_review','approved','rejected','scheduled','published','archived')` | NO | 'draft' |  |
| `release_mode` | `enum('immediate','scheduled','calendar')` | NO | 'immediate' |  |
| `scheduled_publish_at` | `datetime` | YES | NULL |  |
| `calendar_event_id` | `int(11)` | YES | NULL |  |
| `reviewed_by` | `int(11)` | YES | NULL |  |
| `reviewed_at` | `datetime` | YES | NULL |  |
| `review_notes` | `text` | YES | NULL |  |
| `user_id` | `int(11)` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_announcement_reviewer` | No | `reviewed_by` |
| `idx_announcement_calendar_event` | No | `calendar_event_id` |
| `idx_announcement_scheduled_publish` | No | `scheduled_publish_at` |
| `idx_announcement_workflow_status` | No | `workflow_status` |
| `PRIMARY` | Yes | `announcement_id` |
| `user_id` | No | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `announcements_ibfk_1` | `user_id` ? `user.user_id` | RESTRICT | SET NULL |
| `fk_announcement_calendar_event` | `calendar_event_id` ? `events.event_id` | CASCADE | SET NULL |
| `fk_announcement_reviewer` | `reviewed_by` ? `user.user_id` | CASCADE | SET NULL |

### announcement acknowledgment

Historical announcement-specific acknowledgments; retained legacy data.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `acknowledgment_id` | `int(11)` | NO | NULL | auto_increment |
| `announcement_id` | `int(11)` | NO | NULL |  |
| `user_id` | `int(11)` | NO | NULL |  |
| `acknowledged_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_acknowledgment_announcement` | No | `announcement_id` |
| `idx_acknowledgment_user` | No | `user_id` |
| `PRIMARY` | Yes | `acknowledgment_id` |
| `uq_announcement_acknowledgment` | Yes | `announcement_id`, `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_acknowledgment_announcement` | `announcement_id` ? `announcements.announcement_id` | RESTRICT | CASCADE |
| `fk_acknowledgment_user` | `user_id` ? `user.user_id` | RESTRICT | CASCADE |

### announcement comment

Historical announcement-specific comments; retained legacy data.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `comment_id` | `int(11)` | NO | NULL | auto_increment |
| `announcement_id` | `int(11)` | YES | NULL |  |
| `user_id` | `int(11)` | YES | NULL |  |
| `comment` | `text` | YES | NULL |  |
| `created_at` | `datetime` | YES | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `announcement_id` | No | `announcement_id` |
| `PRIMARY` | Yes | `comment_id` |
| `user_id` | No | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `announcement_comment_ibfk_1` | `announcement_id` ? `announcements.announcement_id` | RESTRICT | CASCADE |
| `announcement_comment_ibfk_2` | `user_id` ? `user.user_id` | RESTRICT | CASCADE |

### announcement reaction

Historical announcement-specific reaction table; old records cleared by the approved voting reset.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `reaction_id` | `int(11)` | NO | NULL | auto_increment |
| `announcement_id` | `int(11)` | YES | NULL |  |
| `user_id` | `int(11)` | YES | NULL |  |
| `reaction` | `enum('Like','Love','Care','Wow')` | YES | NULL |  |
| `created_at` | `datetime` | YES | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `announcement_id` | No | `announcement_id` |
| `PRIMARY` | Yes | `reaction_id` |
| `user_id` | No | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `announcement_reaction_ibfk_1` | `announcement_id` ? `announcements.announcement_id` | RESTRICT | CASCADE |
| `announcement_reaction_ibfk_2` | `user_id` ? `user.user_id` | RESTRICT | CASCADE |

### announcement target

Announcement recipient criteria.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `target_id` | `int(11)` | NO | NULL | auto_increment |
| `announcement_id` | `int(11)` | NO | NULL |  |
| `role_id` | `int(11)` | YES | NULL |  |
| `department_id` | `int(11)` | YES | NULL |  |
| `education_level_id` | `int(11)` | YES | NULL |  |
| `academic_program_id` | `int(11)` | YES | NULL |  |
| `grade_level_id` | `int(11)` | YES | NULL |  |
| `section_id` | `int(11)` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `announcement_id` | No | `announcement_id` |
| `department_id` | No | `department_id` |
| `education_level_id` | No | `education_level_id` |
| `grade_level_id` | No | `grade_level_id` |
| `idx_announcement_target_program` | No | `academic_program_id` |
| `PRIMARY` | Yes | `target_id` |
| `role_id` | No | `role_id` |
| `section_id` | No | `section_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `announcement_target_ibfk_1` | `announcement_id` ? `announcements.announcement_id` | RESTRICT | CASCADE |
| `announcement_target_ibfk_2` | `role_id` ? `role.role_id` | RESTRICT | CASCADE |
| `announcement_target_ibfk_3` | `department_id` ? `department.department_id` | RESTRICT | CASCADE |
| `announcement_target_ibfk_4` | `education_level_id` ? `education_level.education_level_id` | RESTRICT | CASCADE |
| `announcement_target_ibfk_5` | `grade_level_id` ? `grade_level.grade_level_id` | RESTRICT | CASCADE |
| `announcement_target_ibfk_6` | `section_id` ? `section.section_id` | RESTRICT | CASCADE |
| `fk_announcement_target_program` | `academic_program_id` ? `academic_program.academic_program_id` | CASCADE | SET NULL |

### announcement view

Historical announcement-specific views; retained legacy data.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `view_id` | `int(11)` | NO | NULL | auto_increment |
| `announcement_id` | `int(11)` | YES | NULL |  |
| `user_id` | `int(11)` | YES | NULL |  |
| `viewed_at` | `datetime` | YES | current_timestamp() |  |
| `duration_seconds` | `int(11)` | YES | NULL |  |
| `source` | `enum('Dashboard','Notification','Search')` | YES | 'Dashboard' |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `announcement_id` | No | `announcement_id` |
| `PRIMARY` | Yes | `view_id` |
| `user_id` | No | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `announcement_view_ibfk_1` | `announcement_id` ? `announcements.announcement_id` | RESTRICT | CASCADE |
| `announcement_view_ibfk_2` | `user_id` ? `user.user_id` | RESTRICT | CASCADE |

### calendar holiday

Calendar holiday definitions.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `calendar_holiday_id` | `int(11)` | NO | NULL | auto_increment |
| `holiday_date` | `date` | NO | NULL |  |
| `title` | `varchar(150)` | NO | NULL |  |
| `holiday_type` | `enum('Regular','SpecialNonWorking','SpecialWorking','Local','School')` | NO | NULL |  |
| `scope` | `enum('Nationwide','Local','School')` | NO | 'Nationwide' |  |
| `description` | `varchar(500)` | YES | NULL |  |
| `proclamation_reference` | `varchar(150)` | YES | NULL |  |
| `status` | `enum('Active','Inactive')` | NO | 'Active' |  |
| `created_by` | `int(11)` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_calendar_holiday_creator` | No | `created_by` |
| `idx_calendar_holiday_directory` | No | `holiday_date`, `status`, `scope` |
| `PRIMARY` | Yes | `calendar_holiday_id` |
| `uq_calendar_holiday` | Yes | `holiday_date`, `title`, `scope` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_calendar_holiday_creator` | `created_by` ? `user.user_id` | CASCADE | SET NULL |

### content acknowledgment

Unified acknowledgment records across supported content.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `acknowledgment_id` | `int(11)` | NO | NULL | auto_increment |
| `content_type` | `enum('announcement','event','document','survey')` | NO | NULL |  |
| `content_id` | `int(11)` | NO | NULL |  |
| `user_id` | `int(11)` | NO | NULL |  |
| `acknowledged_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_content_acknowledgment_lookup` | No | `content_type`, `content_id` |
| `idx_content_acknowledgment_user` | No | `user_id` |
| `PRIMARY` | Yes | `acknowledgment_id` |
| `uq_content_acknowledgment` | Yes | `content_type`, `content_id`, `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_content_acknowledgment_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### content comment

Unified comments and reply relationships.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `comment_id` | `int(11)` | NO | NULL | auto_increment |
| `content_type` | `enum('announcement','event','document','survey')` | NO | NULL |  |
| `content_id` | `int(11)` | NO | NULL |  |
| `user_id` | `int(11)` | NO | NULL |  |
| `parent_comment_id` | `int(11)` | YES | NULL |  |
| `comment` | `text` | NO | NULL |  |
| `status` | `enum('Active','Hidden','Deleted')` | NO | 'Active' |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_content_comment_lookup` | No | `content_type`, `content_id`, `status`, `created_at` |
| `idx_content_comment_parent` | No | `parent_comment_id` |
| `idx_content_comment_user` | No | `user_id` |
| `PRIMARY` | Yes | `comment_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_content_comment_parent` | `parent_comment_id` ? `content_comment.comment_id` | CASCADE | CASCADE |
| `fk_content_comment_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### content interest

Content topic/interest catalog.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `interest_id` | `int(11)` | NO | NULL | auto_increment |
| `interest_name` | `varchar(100)` | NO | NULL |  |
| `interest_slug` | `varchar(100)` | NO | NULL |  |
| `description` | `varchar(500)` | YES | NULL |  |
| `status` | `enum('Active','Inactive')` | NO | 'Active' |  |
| `sort_order` | `int(11)` | NO | 0 |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_content_interest_directory` | No | `status`, `sort_order`, `interest_name` |
| `PRIMARY` | Yes | `interest_id` |
| `uq_content_interest_name` | Yes | `interest_name` |
| `uq_content_interest_slug` | Yes | `interest_slug` |

### content interest assignment

Topic assignments to content.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `content_interest_assignment_id` | `int(11)` | NO | NULL | auto_increment |
| `content_type` | `enum('announcement','event','document','survey')` | NO | NULL |  |
| `content_id` | `int(11)` | NO | NULL |  |
| `interest_id` | `int(11)` | NO | NULL |  |
| `assigned_by` | `int(11)` | YES | NULL |  |
| `assigned_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_content_interest_assigned_by` | No | `assigned_by` |
| `idx_content_interest_content` | No | `content_type`, `content_id` |
| `idx_content_interest_topic` | No | `interest_id`, `content_type` |
| `PRIMARY` | Yes | `content_interest_assignment_id` |
| `uq_content_interest_assignment` | Yes | `content_type`, `content_id`, `interest_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_content_interest_assignment_interest` | `interest_id` ? `content_interest.interest_id` | CASCADE | CASCADE |
| `fk_content_interest_assignment_user` | `assigned_by` ? `user.user_id` | CASCADE | SET NULL |

### content reaction

Unified upvotes and downvotes. One vote per user and content; selecting the same vote withdraws it. Upvote means Like; Downvote means Dislike. Counts are displayed separately, with no combined score.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `reaction_id` | `int(11)` | NO | NULL | auto_increment |
| `content_type` | `enum('announcement','event','document','survey')` | NO | NULL |  |
| `content_id` | `int(11)` | NO | NULL |  |
| `user_id` | `int(11)` | NO | NULL |  |
| `reaction_type` | `enum('Upvote','Downvote')` | NO | NULL |  |
| `reacted_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_content_reaction_lookup` | No | `content_type`, `content_id` |
| `idx_content_reaction_user` | No | `user_id` |
| `PRIMARY` | Yes | `reaction_id` |
| `uq_content_reaction` | Yes | `content_type`, `content_id`, `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_content_reaction_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### content view

Unified content views.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `view_id` | `int(11)` | NO | NULL | auto_increment |
| `content_type` | `enum('announcement','event','document','survey')` | NO | NULL |  |
| `content_id` | `int(11)` | NO | NULL |  |
| `user_id` | `int(11)` | NO | NULL |  |
| `viewed_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_content_view_lookup` | No | `content_type`, `content_id` |
| `idx_content_view_user` | No | `user_id` |
| `PRIMARY` | Yes | `view_id` |
| `uq_content_view` | Yes | `content_type`, `content_id`, `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_content_view_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### department

School-division catalog.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `department_id` | `int(11)` | NO | NULL | auto_increment |
| `department_name` | `varchar(100)` | YES | NULL |  |
| `department_code` | `varchar(20)` | YES | NULL |  |
| `description` | `text` | YES | NULL |  |
| `status` | `enum('Active','Inactive')` | YES | 'Active' |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `department_name` | Yes | `department_name` |
| `PRIMARY` | Yes | `department_id` |
| `uq_department_code` | Yes | `department_code` |

### documents

Document metadata, storage references and publication workflow.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `document_id` | `int(11)` | NO | NULL | auto_increment |
| `title` | `varchar(100)` | NO | NULL |  |
| `description` | `text` | NO | NULL |  |
| `file_name` | `varchar(255)` | NO | NULL |  |
| `file_path` | `varchar(500)` | YES | NULL |  |
| `cover_image_path` | `varchar(500)` | YES | NULL |  |
| `file_type` | `varchar(100)` | NO | NULL |  |
| `file_size` | `bigint(20)` | YES | NULL |  |
| `created_at` | `timestamp` | NO | current_timestamp() |  |
| `status` | `varchar(50)` | YES | 'active' |  |
| `workflow_status` | `enum('draft','pending_review','approved','scheduled','published','rejected','archived')` | NO | 'draft' |  |
| `release_mode` | `enum('immediate','scheduled','calendar')` | NO | 'immediate' |  |
| `scheduled_publish_at` | `datetime` | YES | NULL |  |
| `calendar_event_id` | `int(11)` | YES | NULL |  |
| `reviewed_by` | `int(11)` | YES | NULL |  |
| `reviewed_at` | `datetime` | YES | NULL |  |
| `review_notes` | `text` | YES | NULL |  |
| `send_notification` | `tinyint(1)` | NO | 1 |  |
| `user_id` | `int(11)` | YES | NULL |  |
| `allow_reactions` | `tinyint(1)` | NO | 1 |  |
| `allow_comments` | `tinyint(1)` | NO | 1 |  |
| `require_acknowledgment` | `tinyint(1)` | NO | 0 |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_document_reviewer` | No | `reviewed_by` |
| `idx_document_workflow_status` | No | `workflow_status` |
| `PRIMARY` | Yes | `document_id` |
| `user_id` | No | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `documents_ibfk_1` | `user_id` ? `user.user_id` | RESTRICT | SET NULL |
| `fk_document_reviewer` | `reviewed_by` ? `user.user_id` | CASCADE | SET NULL |

### document download

Document-download records; presence alone does not prove every current download writes here.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `download_id` | `int(11)` | NO | NULL | auto_increment |
| `document_id` | `int(11)` | NO | NULL |  |
| `user_id` | `int(11)` | NO | NULL |  |
| `downloaded_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_document_download_document` | No | `document_id` |
| `idx_document_download_user` | No | `user_id` |
| `PRIMARY` | Yes | `download_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_document_download_document` | `document_id` ? `documents.document_id` | CASCADE | CASCADE |
| `fk_document_download_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### document target

Document recipient criteria.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `target_id` | `int(11)` | NO | NULL | auto_increment |
| `document_id` | `int(11)` | YES | NULL |  |
| `role_id` | `int(11)` | YES | NULL |  |
| `department_id` | `int(11)` | YES | NULL |  |
| `education_level_id` | `int(11)` | YES | NULL |  |
| `academic_program_id` | `int(11)` | YES | NULL |  |
| `grade_level_id` | `int(11)` | YES | NULL |  |
| `section_id` | `int(11)` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `document_id` | No | `document_id` |
| `idx_document_target_program` | No | `academic_program_id` |
| `PRIMARY` | Yes | `target_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `document_target_ibfk_1` | `document_id` ? `documents.document_id` | RESTRICT | CASCADE |
| `fk_document_target_program` | `academic_program_id` ? `academic_program.academic_program_id` | CASCADE | SET NULL |

### education level

Education levels within divisions.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `education_level_id` | `int(11)` | NO | NULL | auto_increment |
| `department_id` | `int(11)` | NO | NULL |  |
| `education_level_name` | `varchar(100)` | YES | NULL |  |
| `status` | `enum('Active','Inactive')` | YES | 'Active' |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `education_level_name` | Yes | `education_level_name` |
| `idx_education_level_department` | No | `department_id` |
| `PRIMARY` | Yes | `education_level_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_education_level_department` | `department_id` ? `department.department_id` | CASCADE | RESTRICT |

### email delivery

Queued email delivery and delivery status.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `delivery_id` | `bigint(20)` | NO | NULL | auto_increment |
| `notification_id` | `int(11)` | NO | NULL |  |
| `recipient_email` | `varchar(254)` | NO | NULL |  |
| `delivery_status` | `enum('Pending','Sent','Failed','Skipped')` | NO | 'Pending' |  |
| `attempt_count` | `int(11)` | NO | 0 |  |
| `last_error` | `varchar(1000)` | YES | NULL |  |
| `queued_at` | `datetime` | NO | current_timestamp() |  |
| `last_attempt_at` | `datetime` | YES | NULL |  |
| `sent_at` | `datetime` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_email_delivery_status_queue` | No | `delivery_status`, `queued_at` |
| `PRIMARY` | Yes | `delivery_id` |
| `uq_email_delivery_notification` | Yes | `notification_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_email_delivery_notification` | `notification_id` ? `notification.notification_id` | CASCADE | CASCADE |

### events

Event schedule, content and publication workflow.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `event_id` | `int(11)` | NO | NULL | auto_increment |
| `title` | `varchar(100)` | NO | NULL |  |
| `description` | `text` | YES | NULL |  |
| `location` | `varchar(255)` | YES | NULL |  |
| `image_path` | `varchar(500)` | YES | NULL |  |
| `event_date` | `datetime` | YES | NULL |  |
| `end_date` | `datetime` | YES | NULL |  |
| `created_at` | `datetime` | YES | current_timestamp() |  |
| `status` | `varchar(50)` | YES | NULL |  |
| `workflow_status` | `enum('draft','pending_review','approved','scheduled','published','rejected','archived')` | NO | 'draft' |  |
| `release_mode` | `enum('immediate','scheduled','calendar')` | NO | 'immediate' |  |
| `scheduled_publish_at` | `datetime` | YES | NULL |  |
| `calendar_event_id` | `int(11)` | YES | NULL |  |
| `reviewed_by` | `int(11)` | YES | NULL |  |
| `reviewed_at` | `datetime` | YES | NULL |  |
| `review_notes` | `text` | YES | NULL |  |
| `send_notification` | `tinyint(1)` | NO | 1 |  |
| `user_id` | `int(11)` | YES | NULL |  |
| `allow_reactions` | `tinyint(1)` | NO | 1 |  |
| `allow_comments` | `tinyint(1)` | NO | 1 |  |
| `require_acknowledgment` | `tinyint(1)` | NO | 0 |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_event_reviewer` | No | `reviewed_by` |
| `idx_event_workflow_status` | No | `workflow_status` |
| `PRIMARY` | Yes | `event_id` |
| `user_id` | No | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `events_ibfk_1` | `user_id` ? `user.user_id` | RESTRICT | SET NULL |
| `fk_event_reviewer` | `reviewed_by` ? `user.user_id` | CASCADE | SET NULL |

### event target

Event recipient criteria.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `target_id` | `int(11)` | NO | NULL | auto_increment |
| `event_id` | `int(11)` | YES | NULL |  |
| `role_id` | `int(11)` | YES | NULL |  |
| `department_id` | `int(11)` | YES | NULL |  |
| `education_level_id` | `int(11)` | YES | NULL |  |
| `academic_program_id` | `int(11)` | YES | NULL |  |
| `grade_level_id` | `int(11)` | YES | NULL |  |
| `section_id` | `int(11)` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `event_id` | No | `event_id` |
| `idx_event_target_program` | No | `academic_program_id` |
| `PRIMARY` | Yes | `target_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `event_target_ibfk_1` | `event_id` ? `events.event_id` | RESTRICT | CASCADE |
| `fk_event_target_program` | `academic_program_id` ? `academic_program.academic_program_id` | CASCADE | SET NULL |

### government advisory

Imported/reviewed government advisory records.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `government_advisory_id` | `int(11)` | NO | NULL | auto_increment |
| `government_source_id` | `int(11)` | NO | NULL |  |
| `external_reference` | `varchar(150)` | YES | NULL |  |
| `source_url` | `varchar(1000)` | NO | NULL |  |
| `source_url_hash` | `char(64)` | NO | NULL |  |
| `source_page_mode` | `enum('Specific','Reusable')` | NO | 'Specific' |  |
| `title` | `varchar(255)` | NO | NULL |  |
| `summary` | `text` | YES | NULL |  |
| `extracted_text` | `mediumtext` | YES | NULL |  |
| `content_hash` | `char(64)` | YES | NULL |  |
| `advisory_type` | `enum('Holiday','EducationPolicy','ClassSuspension','Emergency','Weather','HealthSafety','Scholarship','Compliance','Other')` | NO | 'Other' |  |
| `geographic_scope` | `enum('Nationwide','Region','Province','Municipality','School')` | NO | 'Nationwide' |  |
| `scope_value` | `varchar(150)` | YES | NULL |  |
| `issued_at` | `datetime` | YES | NULL |  |
| `effective_from` | `datetime` | YES | NULL |  |
| `effective_until` | `datetime` | YES | NULL |  |
| `relevance_score` | `tinyint(3) unsigned` | NO | 0 |  |
| `relevance_reasons` | `text` | YES | NULL |  |
| `fetch_status` | `enum('Pending','Fetched','Failed','Manual')` | NO | 'Pending' |  |
| `retrieval_error` | `varchar(500)` | YES | NULL |  |
| `review_status` | `enum('Pending','Relevant','Irrelevant','Converted','Archived')` | NO | 'Pending' |  |
| `review_notes` | `varchar(1000)` | YES | NULL |  |
| `reviewed_by` | `int(11)` | YES | NULL |  |
| `reviewed_at` | `datetime` | YES | NULL |  |
| `linked_content_type` | `enum('announcement','event','document','survey','holiday')` | YES | NULL |  |
| `linked_content_id` | `int(11)` | YES | NULL |  |
| `submitted_by` | `int(11)` | YES | NULL |  |
| `fetched_at` | `datetime` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_government_advisory_reviewer` | No | `reviewed_by` |
| `fk_government_advisory_submitter` | No | `submitted_by` |
| `idx_government_advisory_content_hash` | No | `content_hash` |
| `idx_government_advisory_link` | No | `linked_content_type`, `linked_content_id` |
| `idx_government_advisory_reference` | No | `government_source_id`, `external_reference` |
| `idx_government_advisory_review` | No | `review_status`, `relevance_score`, `created_at` |
| `idx_government_advisory_type` | No | `advisory_type`, `geographic_scope`, `effective_from` |
| `PRIMARY` | Yes | `government_advisory_id` |
| `uq_government_advisory_url_reference` | Yes | `source_url_hash`, `external_reference` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_government_advisory_reviewer` | `reviewed_by` ? `user.user_id` | CASCADE | SET NULL |
| `fk_government_advisory_source` | `government_source_id` ? `government_source.government_source_id` | CASCADE | RESTRICT |
| `fk_government_advisory_submitter` | `submitted_by` ? `user.user_id` | CASCADE | SET NULL |

### government advisory rule

Advisory relevance rules.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `government_advisory_rule_id` | `int(11)` | NO | NULL | auto_increment |
| `rule_name` | `varchar(150)` | NO | NULL |  |
| `match_field` | `enum('Title','Content','CombinedText','AgencyCategory','SourceHost')` | NO | NULL |  |
| `match_value` | `varchar(150)` | NO | NULL |  |
| `advisory_type` | `varchar(40)` | YES | NULL |  |
| `geographic_scope` | `varchar(40)` | YES | NULL |  |
| `score_adjustment` | `smallint(6)` | NO | 0 |  |
| `priority_order` | `int(11)` | NO | 100 |  |
| `status` | `enum('Active','Inactive')` | NO | 'Active' |  |
| `created_by` | `int(11)` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_government_advisory_rule_creator` | No | `created_by` |
| `idx_government_advisory_rule_engine` | No | `status`, `priority_order`, `match_field` |
| `PRIMARY` | Yes | `government_advisory_rule_id` |
| `uq_government_advisory_rule_name` | Yes | `rule_name` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_government_advisory_rule_creator` | `created_by` ? `user.user_id` | CASCADE | SET NULL |

### government source

Trusted government-source definitions.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `government_source_id` | `int(11)` | NO | NULL | auto_increment |
| `source_name` | `varchar(150)` | NO | NULL |  |
| `agency_code` | `varchar(30)` | YES | NULL |  |
| `agency_category` | `enum('NationalGovernment','Education','WeatherEmergency','LocalGovernment','HealthSafety','Other')` | NO | 'Other' |  |
| `base_url` | `varchar(500)` | NO | NULL |  |
| `allowed_host` | `varchar(255)` | NO | NULL |  |
| `connector_type` | `enum('ManualUrl','Html','Rss','JsonApi')` | NO | 'ManualUrl' |  |
| `authority_weight` | `tinyint(3) unsigned` | NO | 50 |  |
| `status` | `enum('Active','Inactive')` | NO | 'Active' |  |
| `created_by` | `int(11)` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_government_source_creator` | No | `created_by` |
| `idx_government_source_directory` | No | `status`, `agency_category`, `source_name` |
| `PRIMARY` | Yes | `government_source_id` |
| `uq_government_source_host` | Yes | `allowed_host` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_government_source_creator` | `created_by` ? `user.user_id` | CASCADE | SET NULL |

### grade level

Grade/year levels.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `grade_level_id` | `int(11)` | NO | NULL | auto_increment |
| `education_level_id` | `int(11)` | YES | NULL |  |
| `grade_level_name` | `varchar(100)` | YES | NULL |  |
| `status` | `enum('Active','Inactive')` | YES | 'Active' |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `PRIMARY` | Yes | `grade_level_id` |
| `uq_grade_level_name` | Yes | `education_level_id`, `grade_level_name` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `grade_level_ibfk_1` | `education_level_id` ? `education_level.education_level_id` | RESTRICT | SET NULL |

### legal document version

Versioned Terms and Privacy documents.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `legal_document_version_id` | `int(11)` | NO | NULL | auto_increment |
| `document_type` | `enum('Terms','Privacy')` | NO | NULL |  |
| `version` | `varchar(30)` | NO | NULL |  |
| `title` | `varchar(150)` | NO | NULL |  |
| `content` | `longtext` | NO | NULL |  |
| `effective_at` | `datetime` | NO | NULL |  |
| `status` | `enum('Draft','Active','Retired')` | NO | 'Draft' |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_legal_document_active` | No | `document_type`, `status`, `effective_at` |
| `PRIMARY` | Yes | `legal_document_version_id` |
| `uq_legal_document_version` | Yes | `document_type`, `version` |

### notification

In-system notifications and read state.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `notification_id` | `int(11)` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | YES | NULL |  |
| `notification_type` | `enum('system','content','reminder','workflow')` | NO | 'system' |  |
| `title` | `varchar(255)` | YES | NULL |  |
| `message` | `text` | YES | NULL |  |
| `content_type` | `enum('announcement','event','document','survey')` | YES | NULL |  |
| `content_id` | `int(11)` | YES | NULL |  |
| `in_system_visible` | `tinyint(1)` | NO | 1 |  |
| `is_read` | `tinyint(1)` | YES | 0 |  |
| `read_at` | `datetime` | YES | NULL |  |
| `deduplication_key` | `varchar(191)` | YES | NULL |  |
| `created_at` | `datetime` | YES | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_notification_content` | No | `content_type`, `content_id` |
| `idx_notification_user_unread_created` | No | `user_id`, `is_read`, `created_at` |
| `idx_notification_user_visible_unread` | No | `user_id`, `in_system_visible`, `is_read`, `created_at` |
| `PRIMARY` | Yes | `notification_id` |
| `uq_notification_user_deduplication` | Yes | `user_id`, `deduplication_key` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `notification_ibfk_1` | `user_id` ? `user.user_id` | RESTRICT | CASCADE |

### notification category preference

User delivery preferences by category.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `category_preference_id` | `bigint(20)` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | NO | NULL |  |
| `notification_category` | `enum('content_updates','discussion','engagement','reminders','workflow','account_system')` | NO | NULL |  |
| `system_enabled` | `tinyint(1)` | NO | 1 |  |
| `email_enabled` | `tinyint(1)` | NO | 1 |  |
| `browser_push_enabled` | `tinyint(1)` | NO | 1 |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_notification_category_delivery` | No | `notification_category`, `system_enabled`, `email_enabled`, `browser_push_enabled` |
| `PRIMARY` | Yes | `category_preference_id` |
| `uq_notification_category_user` | Yes | `user_id`, `notification_category` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_notification_category_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### notification preference

User channel/master preferences.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `preference_id` | `int(11)` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | YES | NULL |  |
| `email_enabled` | `tinyint(1)` | NO | 0 |  |
| `email_enabled_at` | `datetime` | YES | NULL |  |
| `system_enabled` | `tinyint(1)` | YES | 1 |  |
| `browser_push_enabled` | `tinyint(1)` | NO | 0 |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `PRIMARY` | Yes | `preference_id` |
| `user_id` | Yes | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `notification_preference_ibfk_1` | `user_id` ? `user.user_id` | RESTRICT | CASCADE |

### parent child record

Account-independent child enrollment claim and Admin verification history.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `parent_user_id` | `int(11)` | NO | NULL |  |
| `child_name` | `varchar(200)` | NO | NULL |  |
| `child_student_id` | `varchar(50)` | YES | NULL |  |
| `section_id` | `int(11)` | NO | NULL |  |
| `relationship` | `varchar(50)` | NO | NULL |  |
| `reason` | `varchar(32)` | NO | NULL |  |
| `reason_details` | `varchar(500)` | NO | '' |  |
| `status` | `enum('Pending','Verified','Rejected','Revoked','Linked')` | NO | 'Pending' |  |
| `verified_by` | `int(11)` | YES | NULL |  |
| `verified_at` | `datetime` | YES | NULL |  |
| `review_notes` | `varchar(1000)` | YES | NULL |  |
| `review_history` | `longtext` | YES | NULL |  |
| `linked_student_user_id` | `int(11)` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_child_record_section` | No | `section_id` |
| `fk_child_record_student` | No | `linked_student_user_id` |
| `fk_child_record_verifier` | No | `verified_by` |
| `idx_child_record_student_id` | No | `child_student_id` |
| `PRIMARY` | Yes | `parent_user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_child_record_parent` | `parent_user_id` ? `user.user_id` | RESTRICT | RESTRICT |
| `fk_child_record_section` | `section_id` ? `section.section_id` | RESTRICT | RESTRICT |
| `fk_child_record_student` | `linked_student_user_id` ? `user.user_id` | RESTRICT | RESTRICT |
| `fk_child_record_verifier` | `verified_by` ? `user.user_id` | RESTRICT | RESTRICT |

### parent student

Parent-to-Student relationship and verification state.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `parent_student_id` | `int(11)` | NO | NULL | auto_increment |
| `parent_user_id` | `int(11)` | NO | NULL |  |
| `student_user_id` | `int(11)` | NO | NULL |  |
| `relationship` | `varchar(50)` | NO | NULL |  |
| `status` | `enum('Pending','Verified','Rejected')` | NO | 'Pending' |  |
| `verified_by` | `int(11)` | YES | NULL |  |
| `verified_at` | `datetime` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_parent_student_verifier` | No | `verified_by` |
| `idx_parent_student_parent` | No | `parent_user_id` |
| `idx_parent_student_status` | No | `status` |
| `idx_parent_student_student` | No | `student_user_id` |
| `PRIMARY` | Yes | `parent_student_id` |
| `uq_parent_student` | Yes | `parent_user_id`, `student_user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_parent_student_parent` | `parent_user_id` ? `user.user_id` | CASCADE | CASCADE |
| `fk_parent_student_student` | `student_user_id` ? `user.user_id` | CASCADE | CASCADE |
| `fk_parent_student_verifier` | `verified_by` ? `user.user_id` | CASCADE | SET NULL |

### password reset request

Password-recovery request/throttle records.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `password_reset_request_id` | `bigint(20) unsigned` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | YES | NULL |  |
| `identifier_hash` | `char(64)` | NO | NULL |  |
| `request_ip_hash` | `char(64)` | NO | NULL |  |
| `requested_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_password_reset_identifier` | No | `identifier_hash`, `requested_at` |
| `idx_password_reset_ip` | No | `request_ip_hash`, `requested_at` |
| `idx_password_reset_request_user` | No | `user_id`, `requested_at` |
| `PRIMARY` | Yes | `password_reset_request_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_password_reset_request_user` | `user_id` ? `user.user_id` | CASCADE | SET NULL |

### password reset token

Password-reset token verification and lifecycle records.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `password_reset_token_id` | `bigint(20) unsigned` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | NO | NULL |  |
| `token_hash` | `char(64)` | NO | NULL |  |
| `request_ip_hash` | `char(64)` | NO | NULL |  |
| `user_agent_hash` | `char(64)` | YES | NULL |  |
| `expires_at` | `datetime` | NO | NULL |  |
| `used_at` | `datetime` | YES | NULL |  |
| `invalidated_at` | `datetime` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_password_reset_token_state` | No | `user_id`, `used_at`, `invalidated_at`, `expires_at` |
| `idx_password_reset_token_user` | No | `user_id`, `created_at` |
| `PRIMARY` | Yes | `password_reset_token_id` |
| `uq_password_reset_token_hash` | Yes | `token_hash` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_password_reset_token_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### publication notification outbox

Durable publication-to-notification work, leases and retries.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `outbox_id` | `bigint(20) unsigned` | NO | NULL | auto_increment |
| `content_type` | `enum('announcement','event','document','survey')` | NO | NULL |  |
| `content_id` | `int(11)` | NO | NULL |  |
| `delivery_status` | `enum('Pending','Processing','Completed','Cancelled')` | NO | 'Pending' |  |
| `attempt_count` | `int(10) unsigned` | NO | 0 |  |
| `available_at` | `datetime` | NO | current_timestamp() |  |
| `locked_until` | `datetime` | YES | NULL |  |
| `lock_token` | `char(64)` | YES | NULL |  |
| `last_error` | `varchar(1000)` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `completed_at` | `datetime` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_publication_notification_due` | No | `delivery_status`, `available_at`, `outbox_id` |
| `idx_publication_notification_lease` | No | `delivery_status`, `locked_until` |
| `PRIMARY` | Yes | `outbox_id` |
| `uq_publication_notification_content` | Yes | `content_type`, `content_id` |

### push delivery

Queued browser-push delivery state.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `delivery_id` | `bigint(20)` | NO | NULL | auto_increment |
| `notification_id` | `int(11)` | NO | NULL |  |
| `subscription_id` | `bigint(20)` | NO | NULL |  |
| `delivery_status` | `enum('Pending','Sent','Failed','Expired','Skipped')` | NO | 'Pending' |  |
| `attempt_count` | `int(11)` | NO | 0 |  |
| `last_http_status` | `smallint(5) unsigned` | YES | NULL |  |
| `last_error` | `varchar(1000)` | YES | NULL |  |
| `queued_at` | `datetime` | NO | current_timestamp() |  |
| `last_attempt_at` | `datetime` | YES | NULL |  |
| `sent_at` | `datetime` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_push_delivery_status_queue` | No | `delivery_status`, `queued_at` |
| `idx_push_delivery_subscription` | No | `subscription_id` |
| `PRIMARY` | Yes | `delivery_id` |
| `uq_push_delivery_notification_subscription` | Yes | `notification_id`, `subscription_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_push_delivery_notification` | `notification_id` ? `notification.notification_id` | CASCADE | CASCADE |
| `fk_push_delivery_subscription` | `subscription_id` ? `push_subscription.subscription_id` | CASCADE | CASCADE |

### push subscription

Browser push subscriptions and cryptographic delivery information.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `subscription_id` | `bigint(20)` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | NO | NULL |  |
| `endpoint_hash` | `char(64)` | NO | NULL |  |
| `endpoint` | `text` | NO | NULL |  |
| `public_key` | `varchar(255)` | NO | NULL |  |
| `auth_token` | `varchar(255)` | NO | NULL |  |
| `content_encoding` | `varchar(50)` | NO | 'aes128gcm' |  |
| `user_agent` | `varchar(500)` | YES | NULL |  |
| `device_label` | `varchar(100)` | YES | NULL |  |
| `subscription_status` | `enum('Active','Expired','Revoked')` | NO | 'Active' |  |
| `failure_count` | `int(11)` | NO | 0 |  |
| `last_used_at` | `datetime` | YES | NULL |  |
| `failed_at` | `datetime` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_push_subscription_user_status` | No | `user_id`, `subscription_status` |
| `PRIMARY` | Yes | `subscription_id` |
| `uq_push_subscription_endpoint` | Yes | `endpoint_hash` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_push_subscription_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### request rate limit

HMAC-keyed attempt counters and expiries.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `key_hash` | `char(64)` | NO | NULL |  |
| `scope` | `varchar(32)` | NO | NULL |  |
| `attempts` | `smallint(5) unsigned` | NO | NULL |  |
| `expires_at` | `bigint(20) unsigned` | NO | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_request_rate_limit_expiry` | No | `expires_at` |
| `PRIMARY` | Yes | `key_hash` |

### role

Authenticated role catalog.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `role_id` | `int(11)` | NO | NULL | auto_increment |
| `role_prefix` | `varchar(150)` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `PRIMARY` | Yes | `role_id` |
| `role_prefix` | Yes | `role_prefix` |

### section

Sections assigned to grade/year and optionally program/strand.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `section_id` | `int(11)` | NO | NULL | auto_increment |
| `grade_level_id` | `int(11)` | YES | NULL |  |
| `academic_program_id` | `int(11)` | YES | NULL |  |
| `academic_program_scope_id` | `int(11)` | YES | NULL | STORED GENERATED / coalesce(`academic_program_id`,0) |
| `section_name` | `varchar(100)` | YES | NULL |  |
| `status` | `enum('Active','Inactive')` | YES | 'Active' |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_section_academic_program` | No | `academic_program_id` |
| `PRIMARY` | Yes | `section_id` |
| `uq_section_assignment` | Yes | `grade_level_id`, `academic_program_scope_id`, `section_name` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_section_academic_program` | `academic_program_id` ? `academic_program.academic_program_id` | CASCADE | SET NULL |
| `section_ibfk_1` | `grade_level_id` ? `grade_level.grade_level_id` | RESTRICT | SET NULL |

### student profile

Student profile and survey metadata.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `student_profile_id` | `int(11)` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | NO | NULL |  |
| `completion_status` | `enum('NotStarted','InProgress','Completed')` | NO | 'NotStarted' |  |
| `survey_completion_status` | `enum('NotStarted','InProgress','Completed')` | NO | 'NotStarted' |  |
| `survey_version` | `int(11)` | NO | 1 |  |
| `survey_current_step` | `varchar(50)` | YES | NULL |  |
| `survey_completed_at` | `datetime` | YES | NULL |  |
| `survey_last_saved_at` | `datetime` | YES | NULL |  |
| `personalization_enabled` | `tinyint(1)` | NO | 1 |  |
| `profile_version` | `int(11)` | NO | 1 |  |
| `completed_at` | `datetime` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_profile_completion` | No | `completion_status`, `updated_at` |
| `idx_student_profile_survey_completion` | No | `survey_completion_status`, `survey_version`, `survey_last_saved_at` |
| `PRIMARY` | Yes | `student_profile_id` |
| `uq_student_profile_user` | Yes | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_student_profile_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### student profile consent

Version-related Student profile consent decisions.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `student_profile_consent_id` | `bigint(20)` | NO | NULL | auto_increment |
| `student_profile_id` | `int(11)` | NO | NULL |  |
| `consent_key` | `varchar(100)` | NO | NULL |  |
| `consent_version` | `varchar(30)` | NO | NULL |  |
| `consent_granted` | `tinyint(1)` | NO | 0 |  |
| `granted_at` | `datetime` | YES | NULL |  |
| `withdrawn_at` | `datetime` | YES | NULL |  |
| `acceptance_source` | `enum('Survey','ProfileUpdate')` | NO | 'Survey' |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_profile_consent_lookup` | No | `consent_key`, `consent_granted`, `granted_at` |
| `PRIMARY` | Yes | `student_profile_consent_id` |
| `uq_student_profile_consent` | Yes | `student_profile_id`, `consent_key`, `consent_version` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_student_profile_consent_profile` | `student_profile_id` ? `student_profile.student_profile_id` | CASCADE | CASCADE |

### student profile consent definition

Definitions of optional profile consent.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `student_profile_consent_definition_id` | `int(11)` | NO | NULL | auto_increment |
| `consent_key` | `varchar(100)` | NO | NULL |  |
| `consent_version` | `varchar(30)` | NO | NULL |  |
| `title` | `varchar(150)` | NO | NULL |  |
| `consent_statement` | `text` | NO | NULL |  |
| `status` | `enum('Draft','Active','Retired')` | NO | 'Draft' |  |
| `effective_at` | `datetime` | NO | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_profile_consent_active` | No | `status`, `effective_at`, `consent_key` |
| `PRIMARY` | Yes | `student_profile_consent_definition_id` |
| `uq_student_profile_consent_definition` | Yes | `consent_key`, `consent_version` |

### student profile cycle

Profile update cycles and lifecycle.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `student_profile_cycle_id` | `int(11)` | NO | NULL | auto_increment |
| `cycle_name` | `varchar(150)` | NO | NULL |  |
| `academic_year` | `varchar(30)` | YES | NULL |  |
| `cycle_type` | `enum('Initial','SchoolYear','Semester','Custom')` | NO | 'Custom' |  |
| `academic_term` | `enum('NotApplicable','FirstSemester','SecondSemester','Summer','Custom')` | NO | 'NotApplicable' |  |
| `survey_version` | `int(11)` | NO | NULL |  |
| `status` | `enum('Draft','Scheduled','Active','Closed','Cancelled')` | NO | 'Draft' |  |
| `is_default` | `tinyint(1)` | NO | 0 |  |
| `opens_at` | `datetime` | YES | NULL |  |
| `due_at` | `datetime` | YES | NULL |  |
| `created_by` | `int(11)` | YES | NULL |  |
| `activated_by` | `int(11)` | YES | NULL |  |
| `closed_by` | `int(11)` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `activated_at` | `datetime` | YES | NULL |  |
| `closed_at` | `datetime` | YES | NULL |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_profile_cycle_activated_by` | No | `activated_by` |
| `idx_student_profile_cycle_closed_by` | No | `closed_by` |
| `idx_student_profile_cycle_created_by` | No | `created_by` |
| `idx_student_profile_cycle_default` | No | `is_default`, `status` |
| `idx_student_profile_cycle_status` | No | `status`, `opens_at`, `due_at` |
| `idx_student_profile_cycle_version` | No | `survey_version`, `status` |
| `PRIMARY` | Yes | `student_profile_cycle_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_student_profile_cycle_activated_by` | `activated_by` ? `user.user_id` | CASCADE | SET NULL |
| `fk_student_profile_cycle_closed_by` | `closed_by` ? `user.user_id` | CASCADE | SET NULL |
| `fk_student_profile_cycle_created_by` | `created_by` ? `user.user_id` | CASCADE | SET NULL |
| `fk_student_profile_cycle_version` | `survey_version` ? `student_profile_survey_version.survey_version` | CASCADE | RESTRICT |

### student profile cycle assignment

Per-Student cycle assignments and completion state.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `student_profile_cycle_assignment_id` | `bigint(20)` | NO | NULL | auto_increment |
| `student_profile_cycle_id` | `int(11)` | NO | NULL |  |
| `student_profile_id` | `int(11)` | NO | NULL |  |
| `assignment_status` | `enum('Assigned','InProgress','Completed','Exempt')` | NO | 'Assigned' |  |
| `assigned_by` | `int(11)` | YES | NULL |  |
| `assigned_at` | `datetime` | NO | current_timestamp() |  |
| `started_at` | `datetime` | YES | NULL |  |
| `completed_at` | `datetime` | YES | NULL |  |
| `last_saved_at` | `datetime` | YES | NULL |  |
| `exempted_at` | `datetime` | YES | NULL |  |
| `exemption_reason` | `varchar(1000)` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_profile_cycle_assignment_assigned_by` | No | `assigned_by` |
| `idx_student_profile_cycle_assignment_profile` | No | `student_profile_id`, `assignment_status` |
| `idx_student_profile_cycle_assignment_status` | No | `assignment_status`, `assigned_at`, `completed_at` |
| `PRIMARY` | Yes | `student_profile_cycle_assignment_id` |
| `uq_student_profile_cycle_assignment` | Yes | `student_profile_cycle_id`, `student_profile_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_student_profile_cycle_assignment_assigned_by` | `assigned_by` ? `user.user_id` | CASCADE | SET NULL |
| `fk_student_profile_cycle_assignment_cycle` | `student_profile_cycle_id` ? `student_profile_cycle.student_profile_cycle_id` | CASCADE | CASCADE |
| `fk_student_profile_cycle_assignment_profile` | `student_profile_id` ? `student_profile.student_profile_id` | CASCADE | CASCADE |

### student profile cycle scope

Academic recipient scopes attached to cycles.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `student_profile_cycle_scope_id` | `bigint(20)` | NO | NULL | auto_increment |
| `student_profile_cycle_id` | `int(11)` | NO | NULL |  |
| `scope_type` | `enum('AllStudents','Department','EducationLevel','AcademicProgram','GradeLevel','Section')` | NO | NULL |  |
| `department_id` | `int(11)` | YES | NULL |  |
| `education_level_id` | `int(11)` | YES | NULL |  |
| `academic_program_id` | `int(11)` | YES | NULL |  |
| `grade_level_id` | `int(11)` | YES | NULL |  |
| `section_id` | `int(11)` | YES | NULL |  |
| `scope_signature` | `varchar(180)` | YES | NULL | STORED GENERATED / concat(`scope_type`,':',coalesce(`department_id`,0),':',coalesce(`education_level_id`,0),':',coalesce(`academic_program_id`,0),':',coalesce(`grade_level_id`,0),':',coalesce(`section_id`,0)) |
| `created_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_profile_cycle_scope_department` | No | `department_id` |
| `idx_student_profile_cycle_scope_grade` | No | `grade_level_id` |
| `idx_student_profile_cycle_scope_level` | No | `education_level_id` |
| `idx_student_profile_cycle_scope_program` | No | `academic_program_id` |
| `idx_student_profile_cycle_scope_section` | No | `section_id` |
| `PRIMARY` | Yes | `student_profile_cycle_scope_id` |
| `uq_student_profile_cycle_scope` | Yes | `student_profile_cycle_id`, `scope_signature` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_student_profile_cycle_scope_cycle` | `student_profile_cycle_id` ? `student_profile_cycle.student_profile_cycle_id` | CASCADE | CASCADE |
| `fk_student_profile_cycle_scope_department` | `department_id` ? `department.department_id` | CASCADE | SET NULL |
| `fk_student_profile_cycle_scope_grade` | `grade_level_id` ? `grade_level.grade_level_id` | CASCADE | SET NULL |
| `fk_student_profile_cycle_scope_level` | `education_level_id` ? `education_level.education_level_id` | CASCADE | SET NULL |
| `fk_student_profile_cycle_scope_program` | `academic_program_id` ? `academic_program.academic_program_id` | CASCADE | SET NULL |
| `fk_student_profile_cycle_scope_section` | `section_id` ? `section.section_id` | CASCADE | SET NULL |

### student profile interest

Student topic preferences.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `student_profile_interest_id` | `int(11)` | NO | NULL | auto_increment |
| `student_profile_id` | `int(11)` | NO | NULL |  |
| `interest_id` | `int(11)` | NO | NULL |  |
| `preference_weight` | `tinyint(4)` | NO | 3 |  |
| `selected_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_interest_lookup` | No | `interest_id`, `preference_weight` |
| `PRIMARY` | Yes | `student_profile_interest_id` |
| `uq_student_profile_interest` | Yes | `student_profile_id`, `interest_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_student_interest_catalog` | `interest_id` ? `content_interest.interest_id` | CASCADE | CASCADE |
| `fk_student_interest_profile` | `student_profile_id` ? `student_profile.student_profile_id` | CASCADE | CASCADE |

### student profile question

Versioned Student profile questions.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `student_profile_question_id` | `int(11)` | NO | NULL | auto_increment |
| `question_key` | `varchar(100)` | NO | NULL |  |
| `section_key` | `varchar(50)` | NO | NULL |  |
| `section_label` | `varchar(100)` | NO | NULL |  |
| `section_sort_order` | `int(11)` | NO | 0 |  |
| `question_text` | `varchar(500)` | NO | NULL |  |
| `help_text` | `varchar(500)` | YES | NULL |  |
| `response_type` | `enum('SingleChoice','MultipleChoice','Boolean','ShortText','LongText','Number')` | NO | NULL |  |
| `options_json` | `longtext` | YES | NULL |  |
| `is_required` | `tinyint(1)` | NO | 0 |  |
| `is_sensitive` | `tinyint(1)` | NO | 0 |  |
| `consent_key` | `varchar(100)` | YES | NULL |  |
| `analytics_enabled` | `tinyint(1)` | NO | 1 |  |
| `survey_version` | `int(11)` | NO | 1 |  |
| `status` | `enum('Active','Inactive')` | NO | 'Active' |  |
| `sort_order` | `int(11)` | NO | 0 |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_profile_question_directory` | No | `survey_version`, `status`, `section_key`, `sort_order` |
| `idx_student_profile_question_form` | No | `survey_version`, `status`, `section_sort_order`, `sort_order` |
| `idx_student_profile_question_sensitive` | No | `is_sensitive`, `consent_key` |
| `PRIMARY` | Yes | `student_profile_question_id` |
| `uq_student_profile_question_key` | Yes | `question_key`, `survey_version` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_student_profile_question_survey_version` | `survey_version` ? `student_profile_survey_version.survey_version` | CASCADE | RESTRICT |

### student profile response

Individual Student profile answers.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `student_profile_response_id` | `bigint(20)` | NO | NULL | auto_increment |
| `student_profile_id` | `int(11)` | NO | NULL |  |
| `student_profile_question_id` | `int(11)` | NO | NULL |  |
| `response_json` | `longtext` | NO | NULL |  |
| `responded_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_profile_response_question` | No | `student_profile_question_id`, `updated_at` |
| `PRIMARY` | Yes | `student_profile_response_id` |
| `uq_student_profile_response` | Yes | `student_profile_id`, `student_profile_question_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_student_profile_response_profile` | `student_profile_id` ? `student_profile.student_profile_id` | CASCADE | CASCADE |
| `fk_student_profile_response_question` | `student_profile_question_id` ? `student_profile_question.student_profile_question_id` | CASCADE | CASCADE |

### student profile survey version

Student profile questionnaire versions.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `survey_version` | `int(11)` | NO | NULL |  |
| `version_name` | `varchar(150)` | NO | NULL |  |
| `description` | `varchar(1000)` | YES | NULL |  |
| `status` | `enum('Draft','Active','Retired')` | NO | 'Draft' |  |
| `created_by` | `int(11)` | YES | NULL |  |
| `activated_by` | `int(11)` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `activated_at` | `datetime` | YES | NULL |  |
| `retired_at` | `datetime` | YES | NULL |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_student_profile_survey_version_activated_by` | No | `activated_by` |
| `idx_student_profile_survey_version_created_by` | No | `created_by` |
| `idx_student_profile_survey_version_status` | No | `status`, `survey_version` |
| `PRIMARY` | Yes | `survey_version` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_student_profile_survey_version_activated_by` | `activated_by` ? `user.user_id` | CASCADE | SET NULL |
| `fk_student_profile_survey_version_created_by` | `created_by` ? `user.user_id` | CASCADE | SET NULL |

### survey

Published-content surveys and workflow.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `survey_id` | `int(11)` | NO | NULL | auto_increment |
| `title` | `varchar(255)` | YES | NULL |  |
| `description` | `text` | YES | NULL |  |
| `status` | `enum('Draft','Published','Archived')` | YES | NULL |  |
| `workflow_status` | `enum('draft','pending_review','approved','rejected','scheduled','published','archived')` | NO | 'draft' |  |
| `release_mode` | `enum('immediate','scheduled','calendar')` | NO | 'immediate' |  |
| `scheduled_publish_at` | `datetime` | YES | NULL |  |
| `calendar_event_id` | `int(11)` | YES | NULL |  |
| `published_at` | `datetime` | YES | NULL |  |
| `opens_at` | `datetime` | YES | NULL |  |
| `closes_at` | `datetime` | YES | NULL |  |
| `reviewed_by` | `int(11)` | YES | NULL |  |
| `reviewed_at` | `datetime` | YES | NULL |  |
| `review_notes` | `text` | YES | NULL |  |
| `allow_comments` | `tinyint(1)` | NO | 0 |  |
| `send_notification` | `tinyint(1)` | NO | 1 |  |
| `created_at` | `datetime` | YES | current_timestamp() |  |
| `user_id` | `int(11)` | YES | NULL |  |
| `allow_reactions` | `tinyint(1)` | NO | 1 |  |
| `require_acknowledgment` | `tinyint(1)` | NO | 0 |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_survey_reviewer` | No | `reviewed_by` |
| `idx_survey_calendar_event` | No | `calendar_event_id` |
| `idx_survey_workflow_status` | No | `workflow_status` |
| `PRIMARY` | Yes | `survey_id` |
| `user_id` | No | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_survey_calendar_event` | `calendar_event_id` ? `events.event_id` | CASCADE | SET NULL |
| `fk_survey_reviewer` | `reviewed_by` ? `user.user_id` | CASCADE | SET NULL |
| `survey_ibfk_1` | `user_id` ? `user.user_id` | RESTRICT | RESTRICT |

### survey answer

Answers to content-survey questions.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `answer_id` | `int(11)` | NO | NULL | auto_increment |
| `response_id` | `int(11)` | YES | NULL |  |
| `question_id` | `int(11)` | YES | NULL |  |
| `user_id` | `int(11)` | YES | NULL |  |
| `answer` | `text` | YES | NULL |  |
| `submitted_at` | `datetime` | YES | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_survey_answer_response` | No | `response_id` |
| `PRIMARY` | Yes | `answer_id` |
| `question_id` | No | `question_id` |
| `user_id` | No | `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_survey_answer_response` | `response_id` ? `survey_response.response_id` | CASCADE | CASCADE |
| `survey_answer_ibfk_1` | `question_id` ? `survey_question.question_id` | RESTRICT | RESTRICT |
| `survey_answer_ibfk_2` | `user_id` ? `user.user_id` | RESTRICT | RESTRICT |

### survey answer choice

Selected choices attached to survey answers.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `answer_id` | `int(11)` | NO | NULL |  |
| `choice_id` | `int(11)` | NO | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_survey_answer_choice_choice` | No | `choice_id` |
| `PRIMARY` | Yes | `answer_id`, `choice_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_survey_answer_choice_answer` | `answer_id` ? `survey_answer.answer_id` | CASCADE | CASCADE |
| `fk_survey_answer_choice_choice` | `choice_id` ? `survey_choice.choice_id` | CASCADE | CASCADE |

### survey choice

Content-survey choice options.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `choice_id` | `int(11)` | NO | NULL | auto_increment |
| `question_id` | `int(11)` | YES | NULL |  |
| `choice_text` | `varchar(255)` | YES | NULL |  |
| `display_order` | `int(11)` | NO | 1 |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `PRIMARY` | Yes | `choice_id` |
| `question_id` | No | `question_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `survey_choice_ibfk_1` | `question_id` ? `survey_question.question_id` | RESTRICT | CASCADE |

### survey question

Content-survey questions.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `question_id` | `int(11)` | NO | NULL | auto_increment |
| `survey_id` | `int(11)` | YES | NULL |  |
| `question` | `text` | YES | NULL |  |
| `question_type` | `enum('Text','Multiple Choice','Checkbox','Rating','Short Text','Long Text','Yes/No')` | YES | NULL |  |
| `is_required` | `tinyint(1)` | NO | 1 |  |
| `display_order` | `int(11)` | NO | 1 |  |
| `rating_min` | `tinyint(4)` | YES | NULL |  |
| `rating_max` | `tinyint(4)` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `PRIMARY` | Yes | `question_id` |
| `survey_id` | No | `survey_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `survey_question_ibfk_1` | `survey_id` ? `survey.survey_id` | RESTRICT | CASCADE |

### survey response

Survey participation records.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `response_id` | `int(11)` | NO | NULL | auto_increment |
| `survey_id` | `int(11)` | NO | NULL |  |
| `user_id` | `int(11)` | NO | NULL |  |
| `started_at` | `datetime` | YES | NULL |  |
| `submitted_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_survey_response_survey` | No | `survey_id` |
| `idx_survey_response_user` | No | `user_id` |
| `PRIMARY` | Yes | `response_id` |
| `uq_survey_user_response` | Yes | `survey_id`, `user_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_survey_response_survey` | `survey_id` ? `survey.survey_id` | CASCADE | CASCADE |
| `fk_survey_response_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### survey target

Content-survey recipient criteria.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `target_id` | `int(11)` | NO | NULL | auto_increment |
| `survey_id` | `int(11)` | YES | NULL |  |
| `role_id` | `int(11)` | YES | NULL |  |
| `department_id` | `int(11)` | YES | NULL |  |
| `education_level_id` | `int(11)` | YES | NULL |  |
| `academic_program_id` | `int(11)` | YES | NULL |  |
| `grade_level_id` | `int(11)` | YES | NULL |  |
| `section_id` | `int(11)` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_survey_target_department` | No | `department_id` |
| `fk_survey_target_education_level` | No | `education_level_id` |
| `fk_survey_target_grade_level` | No | `grade_level_id` |
| `fk_survey_target_role` | No | `role_id` |
| `fk_survey_target_section` | No | `section_id` |
| `idx_survey_target_program` | No | `academic_program_id` |
| `PRIMARY` | Yes | `target_id` |
| `survey_id` | No | `survey_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_survey_target_department` | `department_id` ? `department.department_id` | CASCADE | SET NULL |
| `fk_survey_target_education_level` | `education_level_id` ? `education_level.education_level_id` | CASCADE | SET NULL |
| `fk_survey_target_grade_level` | `grade_level_id` ? `grade_level.grade_level_id` | CASCADE | SET NULL |
| `fk_survey_target_program` | `academic_program_id` ? `academic_program.academic_program_id` | CASCADE | SET NULL |
| `fk_survey_target_role` | `role_id` ? `role.role_id` | CASCADE | SET NULL |
| `fk_survey_target_section` | `section_id` ? `section.section_id` | CASCADE | SET NULL |
| `survey_target_ibfk_1` | `survey_id` ? `survey.survey_id` | RESTRICT | CASCADE |

### user

Accounts, credential hashes, roles, academic assignments and access state.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `user_id` | `int(11)` | NO | NULL | auto_increment |
| `studID` | `varchar(50)` | YES | NULL |  |
| `first_name` | `varchar(100)` | NO | NULL |  |
| `middle_name` | `varchar(50)` | YES | NULL |  |
| `last_name` | `varchar(100)` | NO | NULL |  |
| `name_suffix` | `varchar(20)` | YES | NULL |  |
| `email` | `varchar(100)` | YES | NULL |  |
| `created_at` | `datetime` | YES | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL |  |
| `password` | `varchar(255)` | NO | NULL |  |
| `must_change_password` | `tinyint(1)` | NO | 0 |  |
| `password_changed_at` | `datetime` | YES | NULL |  |
| `gender` | `enum('Male','Female','Other')` | YES | NULL |  |
| `age` | `int(11)` | YES | NULL |  |
| `birthdate` | `date` | YES | NULL |  |
| `profile_photo` | `varchar(255)` | YES | NULL |  |
| `status` | `enum('Pending','Active','Inactive','Rejected')` | NO | 'Pending' |  |
| `approved_by` | `int(11)` | YES | NULL |  |
| `approved_at` | `datetime` | YES | NULL |  |
| `account_review_notes` | `varchar(1000)` | YES | NULL |  |
| `account_reviewed_at` | `datetime` | YES | NULL |  |
| `account_reviewed_by` | `int(11)` | YES | NULL |  |
| `provisioned_by` | `int(11)` | YES | NULL |  |
| `provisioned_at` | `datetime` | YES | NULL |  |
| `failed_attempts` | `int(11)` | NO | 0 |  |
| `lock_until` | `datetime` | YES | NULL |  |
| `last_login` | `datetime` | YES | NULL |  |
| `role_id` | `int(11)` | YES | NULL |  |
| `department_id` | `int(11)` | YES | NULL |  |
| `education_level_id` | `int(11)` | YES | NULL |  |
| `academic_program_id` | `int(11)` | YES | NULL |  |
| `grade_level_id` | `int(11)` | YES | NULL |  |
| `section_id` | `int(11)` | YES | NULL |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `department_id` | No | `department_id` |
| `email` | Yes | `email` |
| `fk_user_account_reviewed_by` | No | `account_reviewed_by` |
| `fk_user_approved_by` | No | `approved_by` |
| `fk_user_education` | No | `education_level_id` |
| `fk_user_grade` | No | `grade_level_id` |
| `fk_user_section` | No | `section_id` |
| `idx_user_academic_program` | No | `academic_program_id` |
| `idx_user_provisioned_by` | No | `provisioned_by` |
| `idx_user_status_created` | No | `status`, `created_at` |
| `PRIMARY` | Yes | `user_id` |
| `role_id` | No | `role_id` |
| `studID` | Yes | `studID` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_user_academic_program` | `academic_program_id` ? `academic_program.academic_program_id` | CASCADE | SET NULL |
| `fk_user_account_reviewed_by` | `account_reviewed_by` ? `user.user_id` | CASCADE | SET NULL |
| `fk_user_approved_by` | `approved_by` ? `user.user_id` | CASCADE | SET NULL |
| `fk_user_education` | `education_level_id` ? `education_level.education_level_id` | RESTRICT | RESTRICT |
| `fk_user_grade` | `grade_level_id` ? `grade_level.grade_level_id` | RESTRICT | RESTRICT |
| `fk_user_provisioned_by` | `provisioned_by` ? `user.user_id` | CASCADE | RESTRICT |
| `fk_user_section` | `section_id` ? `section.section_id` | RESTRICT | RESTRICT |
| `user_ibfk_1` | `role_id` ? `role.role_id` | CASCADE | SET NULL |
| `user_ibfk_2` | `department_id` ? `department.department_id` | CASCADE | SET NULL |

### user legal acceptance

User acceptance of exact legal-document versions.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `user_legal_acceptance_id` | `int(11)` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | NO | NULL |  |
| `legal_document_version_id` | `int(11)` | NO | NULL |  |
| `accepted_at` | `datetime` | NO | current_timestamp() |  |
| `acceptance_source` | `enum('Registration','Reconsent')` | NO | 'Registration' |  |
| `user_agent` | `varchar(500)` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_user_legal_acceptance_document` | No | `legal_document_version_id`, `accepted_at` |
| `idx_user_legal_acceptance_user` | No | `user_id`, `accepted_at` |
| `PRIMARY` | Yes | `user_legal_acceptance_id` |
| `uq_user_legal_acceptance` | Yes | `user_id`, `legal_document_version_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_user_legal_acceptance_document` | `legal_document_version_id` ? `legal_document_version.legal_document_version_id` | CASCADE | RESTRICT |
| `fk_user_legal_acceptance_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### user optional consent

User-level optional-consent records.

Engine: `InnoDB`; collation: `utf8mb4_unicode_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `user_optional_consent_id` | `int(11)` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | NO | NULL |  |
| `consent_type` | `enum('SensitiveSurveyData')` | NO | NULL |  |
| `consent_version` | `varchar(30)` | NO | NULL |  |
| `granted` | `tinyint(1)` | NO | 0 |  |
| `responded_at` | `datetime` | NO | current_timestamp() |  |
| `withdrawn_at` | `datetime` | YES | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |
| `updated_at` | `datetime` | YES | NULL | on update current_timestamp() |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_optional_consent_lookup` | No | `consent_type`, `consent_version`, `granted` |
| `PRIMARY` | Yes | `user_optional_consent_id` |
| `uq_user_optional_consent` | Yes | `user_id`, `consent_type`, `consent_version` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_user_optional_consent_user` | `user_id` ? `user.user_id` | CASCADE | CASCADE |

### user role history

Account role-change history.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `user_role_history_id` | `int(11)` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | NO | NULL |  |
| `previous_role_id` | `int(11)` | NO | NULL |  |
| `new_role_id` | `int(11)` | NO | NULL |  |
| `reason` | `varchar(1000)` | NO | NULL |  |
| `changed_by` | `int(11)` | NO | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `fk_user_role_history_new` | No | `new_role_id` |
| `fk_user_role_history_previous` | No | `previous_role_id` |
| `idx_user_role_history_admin` | No | `changed_by`, `created_at` |
| `idx_user_role_history_user` | No | `user_id`, `created_at` |
| `PRIMARY` | Yes | `user_role_history_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_user_role_history_changed_by` | `changed_by` ? `user.user_id` | CASCADE | RESTRICT |
| `fk_user_role_history_new` | `new_role_id` ? `role.role_id` | CASCADE | RESTRICT |
| `fk_user_role_history_previous` | `previous_role_id` ? `role.role_id` | CASCADE | RESTRICT |
| `fk_user_role_history_user` | `user_id` ? `user.user_id` | CASCADE | RESTRICT |

### user status history

Account status-change history.

Engine: `InnoDB`; collation: `utf8mb4_general_ci`.

| Column | SQL type | Nullable | Default | Extra / generated expression |
| --- | --- | --- | --- | --- |
| `user_status_history_id` | `int(11)` | NO | NULL | auto_increment |
| `user_id` | `int(11)` | NO | NULL |  |
| `previous_status` | `enum('Pending','Active','Inactive','Rejected')` | NO | NULL |  |
| `new_status` | `enum('Pending','Active','Inactive','Rejected')` | NO | NULL |  |
| `reason` | `varchar(1000)` | NO | NULL |  |
| `changed_by` | `int(11)` | NO | NULL |  |
| `created_at` | `datetime` | NO | current_timestamp() |  |

| Index | Unique | Columns in order |
| --- | --- | --- |
| `idx_user_status_history_admin` | No | `changed_by`, `created_at` |
| `idx_user_status_history_user` | No | `user_id`, `created_at` |
| `PRIMARY` | Yes | `user_status_history_id` |

| Foreign key | Columns ? referenced columns | On update | On delete |
| --- | --- | --- | --- |
| `fk_user_status_history_changed_by` | `changed_by` ? `user.user_id` | CASCADE | RESTRICT |
| `fk_user_status_history_user` | `user_id` ? `user.user_id` | CASCADE | RESTRICT |
