<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * AcademicStructureSeeder
 *
 * Seeds:
 * - education_level
 * - grade_level
 *
 * Sections are intentionally excluded because
 * official section names are not yet finalized.
 */

require_once __DIR__ . '/../config/dbconnect.php';

mysqli_report(
    MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
);

$conn->set_charset('utf8mb4');

$educationLevels = [
    'Elementary' => [
        'Grade 1',
        'Grade 2',
        'Grade 3',
        'Grade 4',
        'Grade 5',
        'Grade 6'
    ],

    'Junior High' => [
        'Grade 7',
        'Grade 8',
        'Grade 9',
        'Grade 10'
    ],

    'Senior High' => [
        'Grade 11',
        'Grade 12'
    ],

    'College' => [
        '1st Year',
        '2nd Year',
        '3rd Year',
        '4th Year'
    ]
];

try {
    $conn->begin_transaction();

    /* ==========================================
       PREPARED STATEMENTS
    ========================================== */

    $findEducationLevel = $conn->prepare("
        SELECT education_level_id
        FROM education_level
        WHERE education_level_name = ?
        LIMIT 1
    ");

    $insertEducationLevel = $conn->prepare("
        INSERT INTO education_level
        (
            education_level_name,
            status
        )
        VALUES (?, 'Active')
    ");

    $activateEducationLevel = $conn->prepare("
        UPDATE education_level
        SET status = 'Active'
        WHERE education_level_id = ?
    ");

    $findGradeLevel = $conn->prepare("
        SELECT grade_level_id
        FROM grade_level
        WHERE education_level_id = ?
          AND grade_level_name = ?
        LIMIT 1
    ");

    $insertGradeLevel = $conn->prepare("
        INSERT INTO grade_level
        (
            education_level_id,
            grade_level_name,
            status
        )
        VALUES (?, ?, 'Active')
    ");

    $activateGradeLevel = $conn->prepare("
        UPDATE grade_level
        SET status = 'Active'
        WHERE grade_level_id = ?
    ");

    $educationLevelCount = 0;
    $gradeLevelCount = 0;

    /* ==========================================
       SEED EDUCATION AND GRADE LEVELS
    ========================================== */

    foreach (
        $educationLevels as
        $educationLevelName => $gradeLevels
    ) {
        $findEducationLevel->bind_param(
            's',
            $educationLevelName
        );

        $findEducationLevel->execute();

        $educationLevelResult =
            $findEducationLevel
            ->get_result()
            ->fetch_assoc();

        if ($educationLevelResult) {
            $educationLevelId =
                (int) $educationLevelResult['education_level_id'];

            $activateEducationLevel->bind_param(
                'i',
                $educationLevelId
            );

            $activateEducationLevel->execute();
        } else {
            $insertEducationLevel->bind_param(
                's',
                $educationLevelName
            );

            $insertEducationLevel->execute();

            $educationLevelId =
                (int) $conn->insert_id;

            $educationLevelCount++;
        }

        foreach ($gradeLevels as $gradeLevelName) {
            $findGradeLevel->bind_param(
                'is',
                $educationLevelId,
                $gradeLevelName
            );

            $findGradeLevel->execute();

            $gradeLevelResult =
                $findGradeLevel
                ->get_result()
                ->fetch_assoc();

            if ($gradeLevelResult) {
                $gradeLevelId =
                    (int) $gradeLevelResult['grade_level_id'];

                $activateGradeLevel->bind_param(
                    'i',
                    $gradeLevelId
                );

                $activateGradeLevel->execute();

                continue;
            }

            $insertGradeLevel->bind_param(
                'is',
                $educationLevelId,
                $gradeLevelName
            );

            $insertGradeLevel->execute();

            $gradeLevelCount++;
        }
    }

    $conn->commit();

    echo PHP_EOL;
    echo "Academic structure seeding completed.";
    echo PHP_EOL;
    echo "Education levels inserted: ";
    echo $educationLevelCount;
    echo PHP_EOL;
    echo "Grade levels inserted: ";
    echo $gradeLevelCount;
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo PHP_EOL;
    echo "Academic structure seeding failed.";
    echo PHP_EOL;
    echo "Error: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
