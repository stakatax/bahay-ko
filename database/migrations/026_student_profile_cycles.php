<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

mysqli_report(
    MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
);

$conn->set_charset(
    'utf8mb4'
);

echo PHP_EOL;
echo "Starting Student profile cycle migration...";
echo PHP_EOL;

try {
    $conn->begin_transaction();

    /* ==========================================
       QUESTIONNAIRE VERSION DIRECTORY
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
        student_profile_survey_version
        (
            survey_version
                INT NOT NULL,

            version_name
                VARCHAR(150) NOT NULL,

            description
                VARCHAR(1000) DEFAULT NULL,

            status
                ENUM(
                    'Draft',
                    'Active',
                    'Retired'
                ) NOT NULL
                DEFAULT 'Draft',

            created_by
                INT DEFAULT NULL,

            activated_by
                INT DEFAULT NULL,

            created_at
                DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            activated_at
                DATETIME DEFAULT NULL,

            retired_at
                DATETIME DEFAULT NULL,

            updated_at
                DATETIME DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                survey_version
            ),

            KEY
                idx_student_profile_survey_version_status
                (
                    status,
                    survey_version
                ),

            KEY
                idx_student_profile_survey_version_created_by
                (
                    created_by
                ),

            KEY
                idx_student_profile_survey_version_activated_by
                (
                    activated_by
                ),

            CONSTRAINT
                fk_student_profile_survey_version_created_by

                FOREIGN KEY (
                    created_by
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_survey_version_activated_by

                FOREIGN KEY (
                    activated_by
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    $conn->query("
        INSERT INTO
        student_profile_survey_version
        (
            survey_version,
            version_name,
            description,
            status,
            activated_at
        )
        VALUES
        (
            1,
            'Initial Student Profile Survey',
            'Initial questionnaire version established before recurring profile cycles.',
            'Active',
            NOW()
        )

        ON DUPLICATE KEY UPDATE
            survey_version =
                VALUES(survey_version)
    ");

    echo "Questionnaire version directory ready.";
    echo PHP_EOL;

    /* ==========================================
       QUESTIONNAIRE UPDATE CYCLE
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
        student_profile_cycle
        (
            student_profile_cycle_id
                INT NOT NULL AUTO_INCREMENT,

            cycle_name
                VARCHAR(150) NOT NULL,

            academic_year
                VARCHAR(30) DEFAULT NULL,

            cycle_type
                ENUM(
                    'Initial',
                    'SchoolYear',
                    'Semester',
                    'Custom'
                ) NOT NULL
                DEFAULT 'Custom',

            academic_term
                ENUM(
                    'NotApplicable',
                    'FirstSemester',
                    'SecondSemester',
                    'Summer',
                    'Custom'
                ) NOT NULL
                DEFAULT 'NotApplicable',

            survey_version
                INT NOT NULL,

            status
                ENUM(
                    'Draft',
                    'Scheduled',
                    'Active',
                    'Closed',
                    'Cancelled'
                ) NOT NULL
                DEFAULT 'Draft',

            is_default
                TINYINT(1) NOT NULL
                DEFAULT 0,

            opens_at
                DATETIME DEFAULT NULL,

            due_at
                DATETIME DEFAULT NULL,

            created_by
                INT DEFAULT NULL,

            activated_by
                INT DEFAULT NULL,

            closed_by
                INT DEFAULT NULL,

            created_at
                DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            activated_at
                DATETIME DEFAULT NULL,

            closed_at
                DATETIME DEFAULT NULL,

            updated_at
                DATETIME DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                student_profile_cycle_id
            ),

            KEY
                idx_student_profile_cycle_status
                (
                    status,
                    opens_at,
                    due_at
                ),

            KEY
                idx_student_profile_cycle_version
                (
                    survey_version,
                    status
                ),

            KEY
                idx_student_profile_cycle_default
                (
                    is_default,
                    status
                ),

            KEY
                idx_student_profile_cycle_created_by
                (
                    created_by
                ),

            KEY
                idx_student_profile_cycle_activated_by
                (
                    activated_by
                ),

            KEY
                idx_student_profile_cycle_closed_by
                (
                    closed_by
                ),

            CONSTRAINT
                fk_student_profile_cycle_version

                FOREIGN KEY (
                    survey_version
                )

                REFERENCES student_profile_survey_version (
                    survey_version
                )

                ON DELETE RESTRICT
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_created_by

                FOREIGN KEY (
                    created_by
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_activated_by

                FOREIGN KEY (
                    activated_by
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_closed_by

                FOREIGN KEY (
                    closed_by
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Student profile cycle table ready.";
    echo PHP_EOL;

    /* ==========================================
       CYCLE ACADEMIC SCOPE
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
        student_profile_cycle_scope
        (
            student_profile_cycle_scope_id
                BIGINT NOT NULL AUTO_INCREMENT,

            student_profile_cycle_id
                INT NOT NULL,

            scope_type
                ENUM(
                    'AllStudents',
                    'Department',
                    'EducationLevel',
                    'AcademicProgram',
                    'GradeLevel',
                    'Section'
                ) NOT NULL,

            department_id
                INT DEFAULT NULL,

            education_level_id
                INT DEFAULT NULL,

            academic_program_id
                INT DEFAULT NULL,

            grade_level_id
                INT DEFAULT NULL,

            section_id
                INT DEFAULT NULL,

            scope_signature
                VARCHAR(180)
                GENERATED ALWAYS AS
                (
                    CONCAT(
                        scope_type,
                        ':',
                        COALESCE(department_id, 0),
                        ':',
                        COALESCE(education_level_id, 0),
                        ':',
                        COALESCE(academic_program_id, 0),
                        ':',
                        COALESCE(grade_level_id, 0),
                        ':',
                        COALESCE(section_id, 0)
                    )
                ) STORED,

            created_at
                DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (
                student_profile_cycle_scope_id
            ),

            UNIQUE KEY
                uq_student_profile_cycle_scope
                (
                    student_profile_cycle_id,
                    scope_signature
                ),

            KEY
                idx_student_profile_cycle_scope_department
                (
                    department_id
                ),

            KEY
                idx_student_profile_cycle_scope_level
                (
                    education_level_id
                ),

            KEY
                idx_student_profile_cycle_scope_program
                (
                    academic_program_id
                ),

            KEY
                idx_student_profile_cycle_scope_grade
                (
                    grade_level_id
                ),

            KEY
                idx_student_profile_cycle_scope_section
                (
                    section_id
                ),

            CONSTRAINT
                fk_student_profile_cycle_scope_cycle

                FOREIGN KEY (
                    student_profile_cycle_id
                )

                REFERENCES student_profile_cycle (
                    student_profile_cycle_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_scope_department

                FOREIGN KEY (
                    department_id
                )

                REFERENCES department (
                    department_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_scope_level

                FOREIGN KEY (
                    education_level_id
                )

                REFERENCES education_level (
                    education_level_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_scope_program

                FOREIGN KEY (
                    academic_program_id
                )

                REFERENCES academic_program (
                    academic_program_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_scope_grade

                FOREIGN KEY (
                    grade_level_id
                )

                REFERENCES grade_level (
                    grade_level_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_scope_section

                FOREIGN KEY (
                    section_id
                )

                REFERENCES section (
                    section_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Student profile cycle scope table ready.";
    echo PHP_EOL;

    /* ==========================================
       PER-STUDENT CYCLE ASSIGNMENT
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
        student_profile_cycle_assignment
        (
            student_profile_cycle_assignment_id
                BIGINT NOT NULL AUTO_INCREMENT,

            student_profile_cycle_id
                INT NOT NULL,

            student_profile_id
                INT NOT NULL,

            assignment_status
                ENUM(
                    'Assigned',
                    'InProgress',
                    'Completed',
                    'Exempt'
                ) NOT NULL
                DEFAULT 'Assigned',

            assigned_by
                INT DEFAULT NULL,

            assigned_at
                DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            started_at
                DATETIME DEFAULT NULL,

            completed_at
                DATETIME DEFAULT NULL,

            last_saved_at
                DATETIME DEFAULT NULL,

            exempted_at
                DATETIME DEFAULT NULL,

            exemption_reason
                VARCHAR(1000) DEFAULT NULL,

            created_at
                DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at
                DATETIME DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                student_profile_cycle_assignment_id
            ),

            UNIQUE KEY
                uq_student_profile_cycle_assignment
                (
                    student_profile_cycle_id,
                    student_profile_id
                ),

            KEY
                idx_student_profile_cycle_assignment_status
                (
                    assignment_status,
                    assigned_at,
                    completed_at
                ),

            KEY
                idx_student_profile_cycle_assignment_profile
                (
                    student_profile_id,
                    assignment_status
                ),

            KEY
                idx_student_profile_cycle_assignment_assigned_by
                (
                    assigned_by
                ),

            CONSTRAINT
                fk_student_profile_cycle_assignment_cycle

                FOREIGN KEY (
                    student_profile_cycle_id
                )

                REFERENCES student_profile_cycle (
                    student_profile_cycle_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_assignment_profile

                FOREIGN KEY (
                    student_profile_id
                )

                REFERENCES student_profile (
                    student_profile_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_cycle_assignment_assigned_by

                FOREIGN KEY (
                    assigned_by
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE SET NULL
                ON UPDATE CASCADE
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Student profile cycle assignment table ready.";
    echo PHP_EOL;

    /* ==========================================
       VERSION-1 BASELINE CYCLE
    ========================================== */

    $conn->query("
        INSERT INTO
        student_profile_cycle
        (
            cycle_name,
            academic_year,
            cycle_type,
            academic_term,
            survey_version,
            status,
            is_default,
            opens_at,
            activated_at
        )

        SELECT
            'Initial Student Profile Cycle',
            NULL,
            'Initial',
            'NotApplicable',
            1,
            'Active',
            1,
            NOW(),
            NOW()

        WHERE NOT EXISTS (
            SELECT 1

            FROM student_profile_cycle

            WHERE is_default = 1
        )
    ");

    $baselineResult =
        $conn->query("
            SELECT
                student_profile_cycle_id

            FROM student_profile_cycle

            WHERE is_default = 1

            ORDER BY
                student_profile_cycle_id ASC

            LIMIT 1
        ");

    $baselineRow =
        $baselineResult->fetch_assoc();

    $baselineCycleId =
        (int) (
            $baselineRow['student_profile_cycle_id']
            ?? 0
        );

    if ($baselineCycleId <= 0) {
        throw new RuntimeException(
            'The initial Student profile cycle could not be created.'
        );
    }

    $scopeStmt =
        $conn->prepare("
            INSERT IGNORE INTO
            student_profile_cycle_scope
            (
                student_profile_cycle_id,
                scope_type
            )
            VALUES
            (
                ?,
                'AllStudents'
            )
        ");

    $scopeStmt->bind_param(
        'i',
        $baselineCycleId
    );

    $scopeStmt->execute();
    $scopeStmt->close();

    $assignmentStmt =
        $conn->prepare("
            INSERT IGNORE INTO
            student_profile_cycle_assignment
            (
                student_profile_cycle_id,
                student_profile_id,
                assignment_status,
                assigned_at,
                started_at,
                completed_at,
                last_saved_at
            )

            SELECT
                ?,
                profile.student_profile_id,

                CASE
                    WHEN profile.survey_completion_status =
                        'Completed'
                    THEN 'Completed'

                    WHEN profile.survey_completion_status =
                        'InProgress'
                    THEN 'InProgress'

                    ELSE 'Assigned'
                END,

                profile.created_at,

                CASE
                    WHEN profile.survey_completion_status IN
                        (
                            'InProgress',
                            'Completed'
                        )
                    THEN COALESCE(
                        profile.survey_last_saved_at,
                        profile.created_at
                    )

                    ELSE NULL
                END,

                profile.survey_completed_at,
                profile.survey_last_saved_at

            FROM student_profile profile
        ");

    $assignmentStmt->bind_param(
        'i',
        $baselineCycleId
    );

    $assignmentStmt->execute();
    $assignmentStmt->close();

    echo "Existing Student profiles mapped to the initial cycle.";
    echo PHP_EOL;

    /* ==========================================
       QUESTION-TO-VERSION FOREIGN KEY
    ========================================== */

    $constraintResult =
        $conn->query("
            SELECT 1

            FROM information_schema.TABLE_CONSTRAINTS

            WHERE CONSTRAINT_SCHEMA =
                DATABASE()

              AND TABLE_NAME =
                'student_profile_question'

              AND CONSTRAINT_NAME =
                'fk_student_profile_question_survey_version'

            LIMIT 1
        ");

    if ($constraintResult->num_rows === 0) {
        $conn->query("
            ALTER TABLE student_profile_question

            ADD CONSTRAINT
                fk_student_profile_question_survey_version

            FOREIGN KEY (
                survey_version
            )

            REFERENCES student_profile_survey_version (
                survey_version
            )

            ON DELETE RESTRICT
            ON UPDATE CASCADE
        ");
    }

    $conn->commit();

    echo "Student profile cycle migration completed successfully.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    fwrite(
        STDERR,
        'Student profile cycle migration failed: '
            . $exception->getMessage()
            . PHP_EOL
    );

    exit(1);
}
