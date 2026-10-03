<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * Development Academic Seeder
 *
 * WARNING:
 * The programs, strands, and sections in this
 * file are provisional development data.
 *
 * Replace them with official OLSHCO records
 * once confirmed by the school.
 *
 * This seeder is repeatable and will not create
 * duplicate programs or sections.
 */

mysqli_report(
    MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
);

require_once __DIR__ . '/../config/dbconnect.php';

$conn->set_charset('utf8mb4');

/* ==========================================
   PROVISIONAL DEVELOPMENT DATA
========================================== */

$academicPrograms = [
    'Senior High' => [
        [
            'name' =>
            'Science, Technology, Engineering and Mathematics',
            'code' => 'STEM',
            'type' => 'Strand'
        ],
        [
            'name' =>
            'Humanities and Social Sciences',
            'code' => 'HUMSS',
            'type' => 'Strand'
        ],
        [
            'name' =>
            'Accountancy, Business and Management',
            'code' => 'ABM',
            'type' => 'Strand'
        ],
        [
            'name' =>
            'Technical-Vocational-Livelihood',
            'code' => 'TVL',
            'type' => 'Strand'
        ]
    ],

    'College' => [
        [
            'name' =>
            'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
            'type' => 'Program'
        ],
        [
            'name' =>
            'Bachelor of Science in Business Administration',
            'code' => 'BSBA',
            'type' => 'Program'
        ],
        [
            'name' =>
            'Bachelor of Secondary Education',
            'code' => 'BSED',
            'type' => 'Program'
        ],
        [
            'name' =>
            'Bachelor of Elementary Education',
            'code' => 'BEED',
            'type' => 'Program'
        ]
    ]
];

/*
 * Elementary and Junior High sections use no
 * academic_program_id.
 *
 * Senior High and College sections belong to a
 * provisional Strand or Program.
 */
$sections = [
    /* Elementary */

    [
        'education_level' => 'Elementary',
        'grade_level' => 'Grade 1',
        'program_code' => null,
        'section_name' => 'Section A'
    ],
    [
        'education_level' => 'Elementary',
        'grade_level' => 'Grade 2',
        'program_code' => null,
        'section_name' => 'Section A'
    ],
    [
        'education_level' => 'Elementary',
        'grade_level' => 'Grade 3',
        'program_code' => null,
        'section_name' => 'Section A'
    ],
    [
        'education_level' => 'Elementary',
        'grade_level' => 'Grade 4',
        'program_code' => null,
        'section_name' => 'Section A'
    ],
    [
        'education_level' => 'Elementary',
        'grade_level' => 'Grade 5',
        'program_code' => null,
        'section_name' => 'Section A'
    ],
    [
        'education_level' => 'Elementary',
        'grade_level' => 'Grade 6',
        'program_code' => null,
        'section_name' => 'Section A'
    ],

    /* Junior High */

    [
        'education_level' => 'Junior High',
        'grade_level' => 'Grade 7',
        'program_code' => null,
        'section_name' => 'Section A'
    ],
    [
        'education_level' => 'Junior High',
        'grade_level' => 'Grade 8',
        'program_code' => null,
        'section_name' => 'Section A'
    ],
    [
        'education_level' => 'Junior High',
        'grade_level' => 'Grade 9',
        'program_code' => null,
        'section_name' => 'Section A'
    ],
    [
        'education_level' => 'Junior High',
        'grade_level' => 'Grade 10',
        'program_code' => null,
        'section_name' => 'Section A'
    ],

    /* Senior High */

    [
        'education_level' => 'Senior High',
        'grade_level' => 'Grade 11',
        'program_code' => 'STEM',
        'section_name' => 'STEM 11-A'
    ],
    [
        'education_level' => 'Senior High',
        'grade_level' => 'Grade 12',
        'program_code' => 'STEM',
        'section_name' => 'STEM 12-A'
    ],
    [
        'education_level' => 'Senior High',
        'grade_level' => 'Grade 11',
        'program_code' => 'HUMSS',
        'section_name' => 'HUMSS 11-A'
    ],
    [
        'education_level' => 'Senior High',
        'grade_level' => 'Grade 12',
        'program_code' => 'HUMSS',
        'section_name' => 'HUMSS 12-A'
    ],
    [
        'education_level' => 'Senior High',
        'grade_level' => 'Grade 11',
        'program_code' => 'ABM',
        'section_name' => 'ABM 11-A'
    ],
    [
        'education_level' => 'Senior High',
        'grade_level' => 'Grade 12',
        'program_code' => 'ABM',
        'section_name' => 'ABM 12-A'
    ],
    [
        'education_level' => 'Senior High',
        'grade_level' => 'Grade 11',
        'program_code' => 'TVL',
        'section_name' => 'TVL 11-A'
    ],
    [
        'education_level' => 'Senior High',
        'grade_level' => 'Grade 12',
        'program_code' => 'TVL',
        'section_name' => 'TVL 12-A'
    ],

    /* College: BSIT */

    [
        'education_level' => 'College',
        'grade_level' => '1st Year',
        'program_code' => 'BSIT',
        'section_name' => 'BSIT 1A'
    ],
    [
        'education_level' => 'College',
        'grade_level' => '2nd Year',
        'program_code' => 'BSIT',
        'section_name' => 'BSIT 2A'
    ],
    [
        'education_level' => 'College',
        'grade_level' => '3rd Year',
        'program_code' => 'BSIT',
        'section_name' => 'BSIT 3A'
    ],
    [
        'education_level' => 'College',
        'grade_level' => '3rd Year',
        'program_code' => 'BSIT',
        'section_name' => 'BSIT 3D'
    ],
    [
        'education_level' => 'College',
        'grade_level' => '4th Year',
        'program_code' => 'BSIT',
        'section_name' => 'BSIT 4A'
    ],

    /* College: Other provisional programs */

    [
        'education_level' => 'College',
        'grade_level' => '1st Year',
        'program_code' => 'BSBA',
        'section_name' => 'BSBA 1A'
    ],
    [
        'education_level' => 'College',
        'grade_level' => '1st Year',
        'program_code' => 'BSED',
        'section_name' => 'BSED 1A'
    ],
    [
        'education_level' => 'College',
        'grade_level' => '1st Year',
        'program_code' => 'BEED',
        'section_name' => 'BEED 1A'
    ]
];

/* ==========================================
   HELPERS
========================================== */

function findEducationLevelId(
    mysqli $conn,
    string $educationLevelName
): int {
    $stmt = $conn->prepare("
        SELECT education_level_id

        FROM education_level

        WHERE education_level_name = ?
          AND status = 'Active'

        LIMIT 1
    ");

    $stmt->bind_param(
        's',
        $educationLevelName
    );

    $stmt->execute();

    $row = $stmt
        ->get_result()
        ->fetch_assoc();

    if (!$row) {
        throw new Exception(
            "Education level not found: {$educationLevelName}"
        );
    }

    return (int) $row['education_level_id'];
}

function findGradeLevelId(
    mysqli $conn,
    int $educationLevelId,
    string $gradeLevelName
): int {
    $stmt = $conn->prepare("
        SELECT grade_level_id

        FROM grade_level

        WHERE education_level_id = ?
          AND grade_level_name = ?
          AND status = 'Active'

        LIMIT 1
    ");

    $stmt->bind_param(
        'is',
        $educationLevelId,
        $gradeLevelName
    );

    $stmt->execute();

    $row = $stmt
        ->get_result()
        ->fetch_assoc();

    if (!$row) {
        throw new Exception(
            "Grade level not found: {$gradeLevelName}"
        );
    }

    return (int) $row['grade_level_id'];
}

function findAcademicProgramId(
    mysqli $conn,
    int $educationLevelId,
    string $programCode
): ?int {
    $stmt = $conn->prepare("
        SELECT academic_program_id

        FROM academic_program

        WHERE education_level_id = ?
          AND program_code = ?

        LIMIT 1
    ");

    $stmt->bind_param(
        'is',
        $educationLevelId,
        $programCode
    );

    $stmt->execute();

    $row = $stmt
        ->get_result()
        ->fetch_assoc();

    return $row
        ? (int) $row['academic_program_id']
        : null;
}

/* ==========================================
   SEEDING
========================================== */

try {
    $conn->begin_transaction();

    echo PHP_EOL;
    echo "Starting development academic seeding...";
    echo PHP_EOL;

    $insertedPrograms = 0;
    $updatedPrograms = 0;
    $insertedSections = 0;
    $updatedSections = 0;

    /* ======================================
       PROGRAMS AND STRANDS
    ====================================== */

    $findProgram = $conn->prepare("
        SELECT academic_program_id

        FROM academic_program

        WHERE education_level_id = ?
          AND program_code = ?

        LIMIT 1
    ");

    $insertProgram = $conn->prepare("
        INSERT INTO academic_program
        (
            education_level_id,
            program_name,
            program_code,
            program_type,
            status,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'Active',
            NOW()
        )
    ");

    $updateProgram = $conn->prepare("
        UPDATE academic_program

        SET
            program_name = ?,
            program_type = ?,
            status = 'Active'

        WHERE academic_program_id = ?
    ");

    foreach (
        $academicPrograms as
        $educationLevelName => $programs
    ) {
        $educationLevelId =
            findEducationLevelId(
                $conn,
                $educationLevelName
            );

        foreach ($programs as $program) {
            $programCode =
                strtoupper(
                    trim(
                        $program['code']
                    )
                );

            $findProgram->bind_param(
                'is',
                $educationLevelId,
                $programCode
            );

            $findProgram->execute();

            $existingProgram =
                $findProgram
                ->get_result()
                ->fetch_assoc();

            if ($existingProgram) {
                $academicProgramId =
                    (int) $existingProgram['academic_program_id'];

                $updateProgram->bind_param(
                    'ssi',
                    $program['name'],
                    $program['type'],
                    $academicProgramId
                );

                $updateProgram->execute();

                $updatedPrograms++;

                continue;
            }

            $insertProgram->bind_param(
                'isss',
                $educationLevelId,
                $program['name'],
                $programCode,
                $program['type']
            );

            $insertProgram->execute();

            $insertedPrograms++;
        }
    }

    /* ======================================
       SECTIONS
    ====================================== */

    $findSectionWithoutProgram =
        $conn->prepare("
            SELECT section_id

            FROM section

            WHERE grade_level_id = ?
              AND academic_program_id IS NULL
              AND section_name = ?

            LIMIT 1
        ");

    $findSectionWithProgram =
        $conn->prepare("
            SELECT section_id

            FROM section

            WHERE grade_level_id = ?
              AND academic_program_id = ?
              AND section_name = ?

            LIMIT 1
        ");

    $insertSection =
        $conn->prepare("
            INSERT INTO section
            (
                grade_level_id,
                academic_program_id,
                section_name,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'Active'
            )
        ");

    $activateSection =
        $conn->prepare("
            UPDATE section

            SET status = 'Active'

            WHERE section_id = ?
        ");

    foreach ($sections as $section) {
        $educationLevelId =
            findEducationLevelId(
                $conn,
                $section['education_level']
            );

        $gradeLevelId =
            findGradeLevelId(
                $conn,
                $educationLevelId,
                $section['grade_level']
            );

        $academicProgramId = null;

        if (
            !empty($section['program_code'])
        ) {
            $academicProgramId =
                findAcademicProgramId(
                    $conn,
                    $educationLevelId,
                    $section['program_code']
                );

            if (!$academicProgramId) {
                throw new Exception(
                    'Program or Strand not found: '
                        . $section['program_code']
                );
            }
        }

        if ($academicProgramId === null) {
            $findSectionWithoutProgram
                ->bind_param(
                    'is',
                    $gradeLevelId,
                    $section['section_name']
                );

            $findSectionWithoutProgram
                ->execute();

            $existingSection =
                $findSectionWithoutProgram
                ->get_result()
                ->fetch_assoc();
        } else {
            $findSectionWithProgram
                ->bind_param(
                    'iis',
                    $gradeLevelId,
                    $academicProgramId,
                    $section['section_name']
                );

            $findSectionWithProgram
                ->execute();

            $existingSection =
                $findSectionWithProgram
                ->get_result()
                ->fetch_assoc();
        }

        if ($existingSection) {
            $sectionId =
                (int) $existingSection['section_id'];

            $activateSection->bind_param(
                'i',
                $sectionId
            );

            $activateSection->execute();

            $updatedSections++;

            continue;
        }

        $insertSection->bind_param(
            'iis',
            $gradeLevelId,
            $academicProgramId,
            $section['section_name']
        );

        $insertSection->execute();

        $insertedSections++;
    }

    $conn->commit();

    echo "✓ Programs and strands inserted: ";
    echo $insertedPrograms;
    echo PHP_EOL;

    echo "✓ Programs and strands refreshed: ";
    echo $updatedPrograms;
    echo PHP_EOL;

    echo "✓ Sections inserted: ";
    echo $insertedSections;
    echo PHP_EOL;

    echo "✓ Sections refreshed: ";
    echo $updatedSections;
    echo PHP_EOL;

    echo PHP_EOL;
    echo "Development academic seeding completed.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo PHP_EOL;
    echo "Development academic seeding failed.";
    echo PHP_EOL;
    echo "Error: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
