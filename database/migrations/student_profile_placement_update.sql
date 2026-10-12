-- Proposed migration; not applied automatically.
-- Existing assignments start unconfirmed so account details are reviewed first.
ALTER TABLE student_profile_cycle_assignment
    ADD COLUMN account_details_confirmed_at DATETIME NULL,
    ADD COLUMN account_details_draft_json LONGTEXT NULL;

-- Store a durable audit record, separate from configurable questionnaire answers.
CREATE TABLE student_profile_placement_change (
    student_profile_placement_change_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_profile_cycle_assignment_id BIGINT(20) NOT NULL,
    student_user_id INT(11) NOT NULL,
    actor_user_id INT(11) NOT NULL,
    previous_placement_json LONGTEXT NOT NULL,
    reported_placement_json LONGTEXT NOT NULL,
    review_status ENUM('Unreviewed', 'Verified', 'Corrected') NOT NULL DEFAULT 'Unreviewed',
    reviewed_by INT(11) NULL,
    reviewed_at DATETIME NULL,
    review_notes VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_profile_placement_change_id),
    UNIQUE KEY uq_profile_placement_assignment (student_profile_cycle_assignment_id),
    KEY ix_profile_placement_review (review_status, created_at),
    KEY ix_profile_placement_student (student_user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
