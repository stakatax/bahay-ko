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
echo "Starting Student profile section-order migration...";
echo PHP_EOL;

try {
    $conn->begin_transaction();

    $columnResult =
        $conn->query("
            SHOW COLUMNS

            FROM student_profile_question

            LIKE 'section_sort_order'
        ");

    if ($columnResult->num_rows === 0) {
        $conn->query("
            ALTER TABLE student_profile_question

            ADD COLUMN section_sort_order
                INT
                NOT NULL
                DEFAULT 0
                AFTER section_label
        ");

        echo "Section sort order added.";
        echo PHP_EOL;
    }

    $conn->query("
        UPDATE student_profile_question

        SET section_sort_order =
            CASE section_key
                WHEN 'device'
                    THEN 10

                WHEN 'connectivity'
                    THEN 20

                WHEN 'geography'
                    THEN 30

                WHEN 'accessibility'
                    THEN 40

                WHEN 'learning'
                    THEN 50

                WHEN 'wellbeing'
                    THEN 60

                ELSE 999
            END
    ");

    $indexResult =
        $conn->query("
            SHOW INDEX

            FROM student_profile_question

            WHERE Key_name =
                'idx_student_profile_question_form'
        ");

    if ($indexResult->num_rows === 0) {
        $conn->query("
            ALTER TABLE student_profile_question

            ADD INDEX
                idx_student_profile_question_form
                (
                    survey_version,
                    status,
                    section_sort_order,
                    sort_order
                )
        ");
    }

    $conn->commit();

    echo "Student profile section-order migration completed successfully.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo "Migration failed: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
