<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

echo "Starting calendar holiday migration..."
    . PHP_EOL;

$conn->query("
    CREATE TABLE IF NOT EXISTS calendar_holiday
    (
        calendar_holiday_id
            INT NOT NULL AUTO_INCREMENT,

        holiday_date
            DATE NOT NULL,

        title
            VARCHAR(150) NOT NULL,

        holiday_type
            ENUM(
                'Regular',
                'SpecialNonWorking',
                'SpecialWorking',
                'Local',
                'School'
            ) NOT NULL,

        scope
            ENUM(
                'Nationwide',
                'Local',
                'School'
            ) NOT NULL DEFAULT 'Nationwide',

        description
            VARCHAR(500) DEFAULT NULL,

        proclamation_reference
            VARCHAR(150) DEFAULT NULL,

        status
            ENUM(
                'Active',
                'Inactive'
            ) NOT NULL DEFAULT 'Active',

        created_by
            INT DEFAULT NULL,

        created_at
            DATETIME NOT NULL
            DEFAULT CURRENT_TIMESTAMP,

        updated_at
            DATETIME DEFAULT NULL
            ON UPDATE CURRENT_TIMESTAMP,

        PRIMARY KEY (
            calendar_holiday_id
        ),

        UNIQUE KEY
            uq_calendar_holiday
            (
                holiday_date,
                title,
                scope
            ),

        KEY
            idx_calendar_holiday_directory
            (
                holiday_date,
                status,
                scope
            ),

        CONSTRAINT
            fk_calendar_holiday_creator

            FOREIGN KEY (
                created_by
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

echo "Calendar holiday table ready."
    . PHP_EOL;

$holidays = [
    [
        '2026-01-01',
        "New Year's Day",
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-02-17',
        'Chinese New Year',
        'SpecialNonWorking',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-02-25',
        'EDSA People Power Revolution Anniversary',
        'SpecialWorking',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-03-20',
        "Eid'l Fitr (Feast of Ramadhan)",
        'Regular',
        'Proclamation No. 1189, s. 2026'
    ],
    [
        '2026-04-02',
        'Maundy Thursday',
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-04-03',
        'Good Friday',
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-04-04',
        'Black Saturday',
        'SpecialNonWorking',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-04-09',
        'Araw ng Kagitingan',
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-05-01',
        'Labor Day',
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-05-27',
        "Eid'l Adha (Feast of Sacrifice)",
        'Regular',
        'Proclamation No. 1264, s. 2026'
    ],
    [
        '2026-06-12',
        'Independence Day',
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-08-21',
        'Ninoy Aquino Day',
        'SpecialNonWorking',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-08-31',
        'National Heroes Day',
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-11-01',
        "All Saints' Day",
        'SpecialNonWorking',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-11-02',
        "All Souls' Day",
        'SpecialNonWorking',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-11-30',
        'Bonifacio Day',
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-12-08',
        'Feast of the Immaculate Conception of Mary',
        'SpecialNonWorking',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-12-24',
        'Christmas Eve',
        'SpecialNonWorking',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-12-25',
        'Christmas Day',
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-12-30',
        'Rizal Day',
        'Regular',
        'Proclamation No. 1006, s. 2025'
    ],
    [
        '2026-12-31',
        'Last Day of the Year',
        'SpecialNonWorking',
        'Proclamation No. 1006, s. 2025'
    ]
];

$stmt =
    $conn->prepare("
        INSERT INTO calendar_holiday
        (
            holiday_date,
            title,
            holiday_type,
            scope,
            description,
            proclamation_reference,
            status,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            'Nationwide',
            'Official Philippine holiday.',
            ?,
            'Active',
            NOW()
        )

        ON DUPLICATE KEY UPDATE
            holiday_type =
                VALUES(holiday_type),

            proclamation_reference =
                VALUES(
                    proclamation_reference
                ),

            status =
                'Active',

            updated_at =
                NOW()
    ");

if (!$stmt) {
    throw new RuntimeException(
        'Unable to prepare calendar holidays: '
            . $conn->error
    );
}

foreach ($holidays as $holiday) {
    [
        $holidayDate,
        $title,
        $holidayType,
        $reference
    ] = $holiday;

    $stmt->bind_param(
        'ssss',
        $holidayDate,
        $title,
        $holidayType,
        $reference
    );

    if (!$stmt->execute()) {
        $error =
            $stmt->error;

        $stmt->close();

        throw new RuntimeException(
            'Unable to store calendar holiday: '
                . $error
        );
    }
}

$stmt->close();

echo count($holidays)
    . " nationwide holidays stored."
    . PHP_EOL;

echo "Calendar holiday migration completed successfully."
    . PHP_EOL;
