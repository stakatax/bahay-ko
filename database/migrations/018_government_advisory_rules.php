<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

echo "Starting government advisory rule migration..."
    . PHP_EOL;

if (
    !$conn->query("
        CREATE TABLE IF NOT EXISTS government_advisory_rule
        (
            government_advisory_rule_id
                INT NOT NULL AUTO_INCREMENT,

            rule_name
                VARCHAR(150) NOT NULL,

            match_field
                ENUM(
                    'Title',
                    'Content',
                    'CombinedText',
                    'AgencyCategory',
                    'SourceHost'
                ) NOT NULL,

            match_value
                VARCHAR(150) NOT NULL,

            advisory_type
                VARCHAR(40) DEFAULT NULL,

            geographic_scope
                VARCHAR(40) DEFAULT NULL,

            score_adjustment
                SMALLINT NOT NULL DEFAULT 0,

            priority_order
                INT NOT NULL DEFAULT 100,

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
                government_advisory_rule_id
            ),

            UNIQUE KEY
                uq_government_advisory_rule_name
                (
                    rule_name
                ),

            KEY
                idx_government_advisory_rule_engine
                (
                    status,
                    priority_order,
                    match_field
                ),

            KEY
                fk_government_advisory_rule_creator
                (
                    created_by
                ),

            CONSTRAINT
                fk_government_advisory_rule_creator

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
    ")
) {
    throw new RuntimeException(
        'Unable to create government advisory rule table: '
            . $conn->error
    );
}

echo "Government advisory rule table ready."
    . PHP_EOL;

$rules = [
    [
        'Issuance directory penalty',
        'Title',
        'documents & issuances',
        null,
        null,
        -45,
        5
    ],

    [
        'Education agency',
        'AgencyCategory',
        'Education',
        'EducationPolicy',
        null,
        25,
        10
    ],
    [
        'Emergency agency',
        'AgencyCategory',
        'WeatherEmergency',
        'Emergency',
        null,
        25,
        10
    ],
    [
        'Class suspension',
        'CombinedText',
        'class suspension',
        'ClassSuspension',
        null,
        45,
        20
    ],
    [
        'Suspension of classes',
        'CombinedText',
        'suspension of classes',
        'ClassSuspension',
        null,
        45,
        20
    ],
    [
        'No classes',
        'CombinedText',
        'no classes',
        'ClassSuspension',
        null,
        35,
        25
    ],
    [
        'Non-working day',
        'CombinedText',
        'non-working day',
        'Holiday',
        null,
        35,
        30
    ],
    [
        'Holiday',
        'CombinedText',
        'holiday',
        'Holiday',
        null,
        30,
        30
    ],
    [
        'Education',
        'CombinedText',
        'education',
        'EducationPolicy',
        null,
        20,
        40
    ],
    [
        'School',
        'CombinedText',
        'school',
        'EducationPolicy',
        null,
        15,
        40
    ],
    [
        'Student',
        'CombinedText',
        'student',
        'EducationPolicy',
        null,
        12,
        45
    ],
    [
        'Scholarship',
        'CombinedText',
        'scholarship',
        'Scholarship',
        null,
        25,
        35
    ],
    [
        'Tropical cyclone',
        'CombinedText',
        'tropical cyclone',
        'Weather',
        null,
        35,
        20
    ],
    [
        'Typhoon',
        'CombinedText',
        'typhoon',
        'Weather',
        null,
        35,
        20
    ],
    [
        'Heavy rainfall',
        'CombinedText',
        'heavy rainfall',
        'Weather',
        null,
        30,
        25
    ],
    [
        'Heat index',
        'CombinedText',
        'heat index',
        'Weather',
        null,
        30,
        25
    ],
    [
        'Flood',
        'CombinedText',
        'flood',
        'Emergency',
        null,
        30,
        25
    ],
    [
        'Earthquake',
        'CombinedText',
        'earthquake',
        'Emergency',
        null,
        35,
        20
    ],
    [
        'Health advisory',
        'CombinedText',
        'health advisory',
        'HealthSafety',
        null,
        30,
        25
    ],
    [
        'Dengue',
        'CombinedText',
        'dengue',
        'HealthSafety',
        null,
        25,
        30
    ],
    [
        'Guimba scope',
        'CombinedText',
        'guimba',
        null,
        'Municipality',
        25,
        15
    ],
    [
        'Nueva Ecija scope',
        'CombinedText',
        'nueva ecija',
        null,
        'Province',
        20,
        15
    ],
    [
        'Nationwide scope',
        'CombinedText',
        'nationwide',
        null,
        'Nationwide',
        10,
        50
    ],
    [
        'Procurement penalty',
        'CombinedText',
        'procurement',
        null,
        null,
        -25,
        80
    ],
    [
        'Vacancy penalty',
        'CombinedText',
        'vacancy',
        null,
        null,
        -20,
        80
    ]
];

$stmt =
    $conn->prepare("
        INSERT INTO government_advisory_rule
        (
            rule_name,
            match_field,
            match_value,
            advisory_type,
            geographic_scope,
            score_adjustment,
            priority_order,
            status,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'Active',
            NOW()
        )

        ON DUPLICATE KEY UPDATE
            match_field =
                VALUES(match_field),

            match_value =
                VALUES(match_value),

            advisory_type =
                VALUES(advisory_type),

            geographic_scope =
                VALUES(geographic_scope),

            score_adjustment =
                VALUES(score_adjustment),

            priority_order =
                VALUES(priority_order)
    ");

if (!$stmt) {
    throw new RuntimeException(
        'Unable to prepare government advisory rules: '
            . $conn->error
    );
}

foreach ($rules as $rule) {
    [
        $ruleName,
        $matchField,
        $matchValue,
        $advisoryType,
        $geographicScope,
        $scoreAdjustment,
        $priorityOrder
    ] = $rule;

    $stmt->bind_param(
        'sssssii',
        $ruleName,
        $matchField,
        $matchValue,
        $advisoryType,
        $geographicScope,
        $scoreAdjustment,
        $priorityOrder
    );

    if (!$stmt->execute()) {
        $error =
            $stmt->error;

        $stmt->close();

        throw new RuntimeException(
            'Unable to store government advisory rule: '
                . $error
        );
    }
}

$stmt->close();

echo count($rules)
    . " government advisory rules ready."
    . PHP_EOL;

echo "Government advisory rule migration completed successfully."
    . PHP_EOL;
