<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * AcademicStructureSeeder
 *
 * Seeds and refreshes:
 * - education_level
 * - grade_level
 * - academic_program
 *
 * Sections remain excluded because official section
 * names have not yet been finalized.
 */

require_once __DIR__
    . '/../../config/dbconnect.php';

mysqli_report(
    MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
);

$conn->set_charset('utf8mb4');

/* ==========================================
   EDUCATION AND GRADE LEVELS
========================================== */

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

/* ==========================================
   PROGRAMS AND STRANDS
========================================== */

$academicPrograms = [
    'Senior High' => [
        [
            'program_name' =>
            'Science, Technology, Engineering, and Mathematics',

            'program_code' =>
            'STEM',

            'program_type' =>
            'Strand',

            'description' =>
            'Senior High School strand focused on science, technology, engineering, and mathematics.'
        ],

        [
            'program_name' =>
            'Accountancy, Business, and Management',

            'program_code' =>
            'ABM',

            'program_type' =>
            'Strand',

            'description' =>
            'Senior High School strand focused on business, entrepreneurship, management, and accountancy.'
        ],

        [
            'program_name' =>
            'Humanities and Social Sciences',

            'program_code' =>
            'HUMSS',

            'program_type' =>
            'Strand',

            'description' =>
            'Senior High School strand focused on humanities, communication, social sciences, and public service.'
        ],

        [
            'program_name' =>
            'Technical-Vocational-Livelihood – Information and Communications Technology',

            'program_code' =>
            'TVL-ICT',

            'program_type' =>
            'Strand',

            'description' =>
            'Senior High School strand focused on practical information and communications technology skills.'
        ]
    ],

    'College' => [
        [
            'program_name' =>
            'Bachelor of Elementary Education',

            'program_code' =>
            'BEED',

            'program_type' =>
            'Program',

            'description' =>
            'Undergraduate teacher-education program for elementary-level instruction.'
        ],

        [
            'program_name' =>
            'Bachelor of Secondary Education',

            'program_code' =>
            'BSED',

            'program_type' =>
            'Program',

            'description' =>
            'Undergraduate teacher-education program for secondary-level instruction.'
        ],

        [
            'program_name' =>
            'Bachelor of Technology and Livelihood Education',

            'program_code' =>
            'BTLED',

            'program_type' =>
            'Program',

            'description' =>
            'Undergraduate teacher-education program focused on technology and livelihood education.'
        ],

        [
            'program_name' =>
            'Bachelor of Early Childhood Education',

            'program_code' =>
            'BECE',

            'program_type' =>
            'Program',

            'description' =>
            'Undergraduate teacher-education program focused on early childhood development and learning.'
        ],

        [
            'program_name' =>
            'Bachelor of Science in Criminology',

            'program_code' =>
            'BSCRIM',

            'program_type' =>
            'Program',

            'description' =>
            'Undergraduate program focused on criminology, criminal justice, law enforcement, and public safety.'
        ],

        [
            'program_name' =>
            'Bachelor of Science in Office Administration',

            'program_code' =>
            'BSOAD',

            'program_type' =>
            'Program',

            'description' =>
            'Undergraduate program focused on office systems, administrative management, and professional support services.'
        ],

        [
            'program_name' =>
            'Bachelor of Science in Hospitality Management',

            'program_code' =>
            'BSHM',

            'program_type' =>
            'Program',

            'description' =>
            'Undergraduate program focused on hospitality operations, tourism services, and customer experience management.'
        ],

        [
            'program_name' =>
            'Bachelor of Science in Information Technology',

            'program_code' =>
            'BSIT',

            'program_type' =>
            'Program',

            'description' =>
            'Undergraduate program focused on software development, information systems, networking, and computing technologies.'
        ]
    ]
];

try {
    $conn->begin_transaction();

    /* ==========================================
       EDUCATION LEVEL STATEMENTS
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

    /* ==========================================
       GRADE LEVEL STATEMENTS
    ========================================== */

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

    /* ==========================================
       ACADEMIC PROGRAM STATEMENTS
    ========================================== */

    $findAcademicProgram = $conn->prepare("
        SELECT academic_program_id
        FROM academic_program
        WHERE program_code = ?
        LIMIT 1
    ");

    $insertAcademicProgram = $conn->prepare("
        INSERT INTO academic_program
        (
            education_level_id,
            program_name,
            program_code,
            program_type,
            description,
            status,
            created_at
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            'Active',
            NOW()
        )
    ");

    $updateAcademicProgram = $conn->prepare("
        UPDATE academic_program
        SET
            education_level_id = ?,
            program_name = ?,
            program_type = ?,
            description = ?,
            status = 'Active',
            updated_at = NOW()
        WHERE academic_program_id = ?
    ");

    $educationLevelIds = [];

    $educationLevelInserted = 0;
    $educationLevelRefreshed = 0;

    $gradeLevelInserted = 0;
    $gradeLevelRefreshed = 0;

    $academicProgramInserted = 0;
    $academicProgramRefreshed = 0;

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

            $educationLevelRefreshed++;
        } else {
            $insertEducationLevel->bind_param(
                's',
                $educationLevelName
            );

            $insertEducationLevel->execute();

            $educationLevelId =
                (int) $conn->insert_id;

            $educationLevelInserted++;
        }

        $educationLevelIds[$educationLevelName] = $educationLevelId;

        foreach (
            $gradeLevels as $gradeLevelName
        ) {
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

                $gradeLevelRefreshed++;

                continue;
            }

            $insertGradeLevel->bind_param(
                'is',
                $educationLevelId,
                $gradeLevelName
            );

            $insertGradeLevel->execute();

            $gradeLevelInserted++;
        }
    }

    /* ==========================================
       SEED PROGRAMS AND STRANDS
    ========================================== */

    foreach (
        $academicPrograms as
        $educationLevelName => $programs
    ) {
        $educationLevelId =
            $educationLevelIds[$educationLevelName] ?? 0;

        if ($educationLevelId <= 0) {
            throw new RuntimeException(
                "Education level not found: "
                    . $educationLevelName
            );
        }

        foreach ($programs as $program) {
            $programName =
                trim(
                    $program['program_name']
                );

            $programCode =
                strtoupper(
                    trim(
                        $program['program_code']
                    )
                );

            $programType =
                trim(
                    $program['program_type']
                );

            $description =
                trim(
                    $program['description']
                );

            $findAcademicProgram->bind_param(
                's',
                $programCode
            );

            $findAcademicProgram->execute();

            $programResult =
                $findAcademicProgram
                ->get_result()
                ->fetch_assoc();

            if ($programResult) {
                $academicProgramId =
                    (int) $programResult['academic_program_id'];

                $updateAcademicProgram->bind_param(
                    'isssi',
                    $educationLevelId,
                    $programName,
                    $programType,
                    $description,
                    $academicProgramId
                );

                $updateAcademicProgram->execute();

                $academicProgramRefreshed++;

                continue;
            }

            $insertAcademicProgram->bind_param(
                'issss',
                $educationLevelId,
                $programName,
                $programCode,
                $programType,
                $description
            );

            $insertAcademicProgram->execute();

            $academicProgramInserted++;
        }
    }

    $conn->commit();

    echo PHP_EOL;
    echo "Academic structure seeding completed.";
    echo PHP_EOL;

    echo "Education levels inserted: ";
    echo $educationLevelInserted;
    echo PHP_EOL;

    echo "Education levels refreshed: ";
    echo $educationLevelRefreshed;
    echo PHP_EOL;

    echo "Grade levels inserted: ";
    echo $gradeLevelInserted;
    echo PHP_EOL;

    echo "Grade levels refreshed: ";
    echo $gradeLevelRefreshed;
    echo PHP_EOL;

    echo "Programs and strands inserted: ";
    echo $academicProgramInserted;
    echo PHP_EOL;

    echo "Programs and strands refreshed: ";
    echo $academicProgramRefreshed;
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
