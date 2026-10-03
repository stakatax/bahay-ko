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
echo "Starting expanded Student profile survey migration...";
echo PHP_EOL;

try {
    $conn->begin_transaction();

    /* ==========================================
       SURVEY PROGRESS COLUMNS
    ========================================== */

    $columns = [
        'survey_completion_status' => "
            ENUM(
                'NotStarted',
                'InProgress',
                'Completed'
            )
            NOT NULL
            DEFAULT 'NotStarted'
            AFTER completion_status
        ",

        'survey_version' => "
            INT
            NOT NULL
            DEFAULT 1
            AFTER survey_completion_status
        ",

        'survey_current_step' => "
            VARCHAR(50)
            NULL
            DEFAULT NULL
            AFTER survey_version
        ",

        'survey_completed_at' => "
            DATETIME
            NULL
            DEFAULT NULL
            AFTER survey_current_step
        ",

        'survey_last_saved_at' => "
            DATETIME
            NULL
            DEFAULT NULL
            AFTER survey_completed_at
        "
    ];

    foreach (
        $columns
        as $columnName =>
        $columnDefinition
    ) {
        $columnResult =
            $conn->query("
                SHOW COLUMNS

                FROM student_profile

                LIKE '{$columnName}'
            ");

        if ($columnResult->num_rows === 0) {
            $conn->query("
                ALTER TABLE student_profile

                ADD COLUMN {$columnName}
                    {$columnDefinition}
            ");

            echo "Added {$columnName}.";
            echo PHP_EOL;
        } else {
            echo "{$columnName} already exists.";
            echo PHP_EOL;
        }
    }

    $indexResult =
        $conn->query("
            SHOW INDEX

            FROM student_profile

            WHERE Key_name =
                'idx_student_profile_survey_completion'
        ");

    if ($indexResult->num_rows === 0) {
        $conn->query("
            ALTER TABLE student_profile

            ADD INDEX
                idx_student_profile_survey_completion
                (
                    survey_completion_status,
                    survey_version,
                    survey_last_saved_at
                )
        ");

        echo "Student survey progress index added.";
        echo PHP_EOL;
    }

    /* ==========================================
       CONFIGURABLE QUESTION CATALOG
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
            student_profile_question
        (
            student_profile_question_id
                INT
                NOT NULL
                AUTO_INCREMENT,

            question_key
                VARCHAR(100)
                NOT NULL,

            section_key
                VARCHAR(50)
                NOT NULL,

            section_label
                VARCHAR(100)
                NOT NULL,

            question_text
                VARCHAR(500)
                NOT NULL,

            help_text
                VARCHAR(500)
                NULL
                DEFAULT NULL,

            response_type
                ENUM(
                    'SingleChoice',
                    'MultipleChoice',
                    'Boolean',
                    'ShortText',
                    'LongText',
                    'Number'
                )
                NOT NULL,

            options_json
                LONGTEXT
                NULL
                DEFAULT NULL,

            is_required
                TINYINT(1)
                NOT NULL
                DEFAULT 0,

            is_sensitive
                TINYINT(1)
                NOT NULL
                DEFAULT 0,

            consent_key
                VARCHAR(100)
                NULL
                DEFAULT NULL,

            analytics_enabled
                TINYINT(1)
                NOT NULL
                DEFAULT 1,

            survey_version
                INT
                NOT NULL
                DEFAULT 1,

            status
                ENUM(
                    'Active',
                    'Inactive'
                )
                NOT NULL
                DEFAULT 'Active',

            sort_order
                INT
                NOT NULL
                DEFAULT 0,

            created_at
                DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at
                DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                student_profile_question_id
            ),

            UNIQUE KEY
                uq_student_profile_question_key
                (
                    question_key,
                    survey_version
                ),

            KEY
                idx_student_profile_question_directory
                (
                    survey_version,
                    status,
                    section_key,
                    sort_order
                ),

            KEY
                idx_student_profile_question_sensitive
                (
                    is_sensitive,
                    consent_key
                )
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Student profile question catalog ready.";
    echo PHP_EOL;

    /* ==========================================
       STUDENT RESPONSES
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
            student_profile_response
        (
            student_profile_response_id
                BIGINT
                NOT NULL
                AUTO_INCREMENT,

            student_profile_id
                INT
                NOT NULL,

            student_profile_question_id
                INT
                NOT NULL,

            response_json
                LONGTEXT
                NOT NULL,

            responded_at
                DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at
                DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                student_profile_response_id
            ),

            UNIQUE KEY
                uq_student_profile_response
                (
                    student_profile_id,
                    student_profile_question_id
                ),

            KEY
                idx_student_profile_response_question
                (
                    student_profile_question_id,
                    updated_at
                ),

            CONSTRAINT
                fk_student_profile_response_profile

                FOREIGN KEY (
                    student_profile_id
                )

                REFERENCES student_profile (
                    student_profile_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_student_profile_response_question

                FOREIGN KEY (
                    student_profile_question_id
                )

                REFERENCES student_profile_question (
                    student_profile_question_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Student profile response table ready.";
    echo PHP_EOL;

    /* ==========================================
       SEPARATE SENSITIVE-DATA CONSENT
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
            student_profile_consent
        (
            student_profile_consent_id
                BIGINT
                NOT NULL
                AUTO_INCREMENT,

            student_profile_id
                INT
                NOT NULL,

            consent_key
                VARCHAR(100)
                NOT NULL,

            consent_version
                VARCHAR(30)
                NOT NULL,

            consent_granted
                TINYINT(1)
                NOT NULL
                DEFAULT 0,

            granted_at
                DATETIME
                NULL
                DEFAULT NULL,

            withdrawn_at
                DATETIME
                NULL
                DEFAULT NULL,

            acceptance_source
                ENUM(
                    'Survey',
                    'ProfileUpdate'
                )
                NOT NULL
                DEFAULT 'Survey',

            created_at
                DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at
                DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                student_profile_consent_id
            ),

            UNIQUE KEY
                uq_student_profile_consent
                (
                    student_profile_id,
                    consent_key,
                    consent_version
                ),

            KEY
                idx_student_profile_consent_lookup
                (
                    consent_key,
                    consent_granted,
                    granted_at
                ),

            CONSTRAINT
                fk_student_profile_consent_profile

                FOREIGN KEY (
                    student_profile_id
                )

                REFERENCES student_profile (
                    student_profile_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Student sensitive-data consent table ready.";
    echo PHP_EOL;

    $conn->commit();

    echo "Expanded Student profile survey migration completed successfully.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo "Migration failed: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
