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
echo "Starting Student profile personalization migration...";
echo PHP_EOL;

try {
    $conn->begin_transaction();

    /* ==========================================
       INTEREST CATALOG
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS content_interest
        (
            interest_id INT NOT NULL AUTO_INCREMENT,

            interest_name VARCHAR(100)
                NOT NULL,

            interest_slug VARCHAR(100)
                NOT NULL,

            description VARCHAR(500)
                NULL
                DEFAULT NULL,

            status ENUM(
                'Active',
                'Inactive'
            ) NOT NULL
              DEFAULT 'Active',

            sort_order INT
                NOT NULL
                DEFAULT 0,

            created_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (interest_id),

            UNIQUE KEY uq_content_interest_name
            (
                interest_name
            ),

            UNIQUE KEY uq_content_interest_slug
            (
                interest_slug
            ),

            KEY idx_content_interest_directory
            (
                status,
                sort_order,
                interest_name
            )
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Content interest catalog ready.";
    echo PHP_EOL;

    /* ==========================================
       STUDENT PERSONALIZATION PROFILE
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS student_profile
        (
            student_profile_id INT
                NOT NULL
                AUTO_INCREMENT,

            user_id INT
                NOT NULL,

            completion_status ENUM(
                'NotStarted',
                'InProgress',
                'Completed'
            ) NOT NULL
              DEFAULT 'NotStarted',

            personalization_enabled TINYINT(1)
                NOT NULL
                DEFAULT 1,

            profile_version INT
                NOT NULL
                DEFAULT 1,

            completed_at DATETIME
                NULL
                DEFAULT NULL,

            created_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (student_profile_id),

            UNIQUE KEY uq_student_profile_user
            (
                user_id
            ),

            KEY idx_student_profile_completion
            (
                completion_status,
                updated_at
            ),

            CONSTRAINT fk_student_profile_user
                FOREIGN KEY (user_id)
                REFERENCES user (user_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Student personalization profile ready.";
    echo PHP_EOL;

    /* ==========================================
       STUDENT INTEREST RESPONSES
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS student_profile_interest
        (
            student_profile_interest_id INT
                NOT NULL
                AUTO_INCREMENT,

            student_profile_id INT
                NOT NULL,

            interest_id INT
                NOT NULL,

            preference_weight TINYINT
                NOT NULL
                DEFAULT 3,

            selected_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                student_profile_interest_id
            ),

            UNIQUE KEY uq_student_profile_interest
            (
                student_profile_id,
                interest_id
            ),

            KEY idx_student_interest_lookup
            (
                interest_id,
                preference_weight
            ),

            CONSTRAINT fk_student_interest_profile
                FOREIGN KEY (student_profile_id)
                REFERENCES student_profile (
                    student_profile_id
                )
                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT fk_student_interest_catalog
                FOREIGN KEY (interest_id)
                REFERENCES content_interest (
                    interest_id
                )
                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Student interest responses ready.";
    echo PHP_EOL;

    /* ==========================================
       CONTENT TOPIC ASSIGNMENTS
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS content_interest_assignment
        (
            content_interest_assignment_id INT
                NOT NULL
                AUTO_INCREMENT,

            content_type ENUM(
                'announcement',
                'event',
                'document',
                'survey'
            ) NOT NULL,

            content_id INT
                NOT NULL,

            interest_id INT
                NOT NULL,

            assigned_by INT
                NULL
                DEFAULT NULL,

            assigned_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (
                content_interest_assignment_id
            ),

            UNIQUE KEY uq_content_interest_assignment
            (
                content_type,
                content_id,
                interest_id
            ),

            KEY idx_content_interest_content
            (
                content_type,
                content_id
            ),

            KEY idx_content_interest_topic
            (
                interest_id,
                content_type
            ),

            KEY idx_content_interest_assigned_by
            (
                assigned_by
            ),

            CONSTRAINT fk_content_interest_assignment_interest
                FOREIGN KEY (interest_id)
                REFERENCES content_interest (
                    interest_id
                )
                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT fk_content_interest_assignment_user
                FOREIGN KEY (assigned_by)
                REFERENCES user (user_id)
                ON DELETE SET NULL
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Content interest assignments ready.";
    echo PHP_EOL;

    /* ==========================================
       DEFAULT INTEREST CATALOG
    ========================================== */

    $seedStmt = $conn->prepare("
        INSERT INTO content_interest
        (
            interest_name,
            interest_slug,
            description,
            status,
            sort_order
        )
        VALUES
        (
            ?,
            ?,
            ?,
            'Active',
            ?
        )
        ON DUPLICATE KEY UPDATE
            interest_name =
                VALUES(interest_name),

            description =
                VALUES(description),

            status =
                'Active',

            sort_order =
                VALUES(sort_order)
    ");

    $defaultInterests = [
        [
            'Academic Updates',
            'academic-updates',
            'Academic schedules, requirements, examinations, and learning updates.',
            10
        ],
        [
            'Scholarships and Financial Aid',
            'scholarships-financial-aid',
            'Scholarships, grants, financial assistance, and application opportunities.',
            20
        ],
        [
            'School Events',
            'school-events',
            'Campus programs, celebrations, assemblies, and institutional activities.',
            30
        ],
        [
            'Clubs and Organizations',
            'clubs-organizations',
            'Student organizations, clubs, leadership activities, and membership opportunities.',
            40
        ],
        [
            'Sports and Recreation',
            'sports-recreation',
            'Athletics, competitions, recreation, and physical activities.',
            50
        ],
        [
            'Career and College Opportunities',
            'career-college-opportunities',
            'Career guidance, internships, employment, and further-study opportunities.',
            60
        ],
        [
            'Health and Wellness',
            'health-wellness',
            'Physical health, mental wellness, counseling, and wellbeing resources.',
            70
        ],
        [
            'Safety and Emergency',
            'safety-emergency',
            'Emergency notices, safety reminders, suspensions, and urgent advisories.',
            80
        ],
        [
            'Policies and Memoranda',
            'policies-memoranda',
            'Institutional policies, official memoranda, rules, and administrative notices.',
            90
        ],
        [
            'Community and Outreach',
            'community-outreach',
            'Community programs, volunteer activities, outreach, and social initiatives.',
            100
        ]
    ];

    foreach (
        $defaultInterests as $interest
    ) {
        [
            $interestName,
            $interestSlug,
            $description,
            $sortOrder
        ] = $interest;

        $seedStmt->bind_param(
            'sssi',
            $interestName,
            $interestSlug,
            $description,
            $sortOrder
        );

        $seedStmt->execute();
    }

    $seedStmt->close();

    echo "Default Student interests seeded.";
    echo PHP_EOL;

    $conn->commit();

    echo "Student profile personalization migration completed.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo "Student profile personalization migration failed.";
    echo PHP_EOL;

    echo "Error: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
