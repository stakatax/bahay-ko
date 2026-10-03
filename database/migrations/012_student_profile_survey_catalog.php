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
echo "Starting Student profile survey catalog migration...";
echo PHP_EOL;

try {
    $conn->begin_transaction();

    /* ==========================================
       VERSIONED CONSENT DEFINITIONS
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
            student_profile_consent_definition
        (
            student_profile_consent_definition_id
                INT
                NOT NULL
                AUTO_INCREMENT,

            consent_key
                VARCHAR(100)
                NOT NULL,

            consent_version
                VARCHAR(30)
                NOT NULL,

            title
                VARCHAR(150)
                NOT NULL,

            consent_statement
                TEXT
                NOT NULL,

            status
                ENUM(
                    'Draft',
                    'Active',
                    'Retired'
                )
                NOT NULL
                DEFAULT 'Draft',

            effective_at
                DATETIME
                NOT NULL,

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
                student_profile_consent_definition_id
            ),

            UNIQUE KEY
                uq_student_profile_consent_definition
                (
                    consent_key,
                    consent_version
                ),

            KEY
                idx_student_profile_consent_active
                (
                    status,
                    effective_at,
                    consent_key
                )
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    $consentDefinitions = [
        [
            'location_profile',
            '1.0',
            'Geographic Profile Consent',
            'I voluntarily allow OLSHCO Digital Hub to process my general province, municipality or city, barangay, travel time, and school-access information to understand student access conditions. The survey does not request my exact home address or live GPS location.'
        ],
        [
            'accessibility_profile',
            '1.0',
            'Accessibility Support Consent',
            'I voluntarily allow OLSHCO Digital Hub to process the accessibility and assistive-support information I choose to provide so the school can understand general support needs. I may select Prefer not to say.'
        ],
        [
            'wellbeing_profile',
            '1.0',
            'Learning Wellbeing Consent',
            'I voluntarily allow OLSHCO Digital Hub to process the optional learning-related wellbeing information I choose to provide. The survey does not request a medical diagnosis, and I may decline or select Prefer not to say.'
        ]
    ];

    $consentStmt =
        $conn->prepare("
            INSERT INTO
                student_profile_consent_definition
            (
                consent_key,
                consent_version,
                title,
                consent_statement,
                status,
                effective_at
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

            ON DUPLICATE KEY UPDATE
                title =
                    VALUES(title),

                consent_statement =
                    VALUES(consent_statement),

                status =
                    'Active'
        ");

    foreach (
        $consentDefinitions
        as $definition
    ) {
        [
            $consentKey,
            $consentVersion,
            $consentTitle,
            $consentStatement
        ] = $definition;

        $consentStmt->bind_param(
            'ssss',
            $consentKey,
            $consentVersion,
            $consentTitle,
            $consentStatement
        );

        $consentStmt->execute();
    }

    $consentStmt->close();

    echo "Sensitive-data consent definitions ready.";
    echo PHP_EOL;

    /* ==========================================
       QUESTION CATALOG
    ========================================== */

    $questions = [
        [
            'primary_device',
            'device',
            'Device Access',
            'What device do you primarily use for school-related digital activities?',
            'Choose the device you use most often.',
            'SingleChoice',
            [
                'Smartphone',
                'Laptop',
                'Desktop computer',
                'Tablet',
                'Shared computer',
                'No regular device'
            ],
            1,
            0,
            null,
            1,
            10
        ],
        [
            'device_ownership',
            'device',
            'Device Access',
            'How do you access your primary device?',
            null,
            'SingleChoice',
            [
                'Personally owned',
                'Shared with family',
                'Borrowed',
                'School-provided',
                'No regular access'
            ],
            1,
            0,
            null,
            1,
            20
        ],
        [
            'device_condition',
            'device',
            'Device Access',
            'What is the current condition of your primary device?',
            null,
            'SingleChoice',
            [
                'Excellent',
                'Good',
                'Fair',
                'Poor',
                'Not applicable'
            ],
            1,
            0,
            null,
            1,
            30
        ],
        [
            'device_access_frequency',
            'device',
            'Device Access',
            'How often can you access a suitable device for schoolwork?',
            null,
            'SingleChoice',
            [
                'Any time I need it',
                'Most days',
                'Only on limited days',
                'Rarely',
                'No access'
            ],
            1,
            0,
            null,
            1,
            40
        ],
        [
            'internet_connection_type',
            'connectivity',
            'Internet Connectivity',
            'What is your main way of connecting to the internet?',
            null,
            'SingleChoice',
            [
                'Home fiber or DSL',
                'Mobile data',
                'Prepaid Wi-Fi',
                'Public Wi-Fi',
                'School internet',
                'No regular connection'
            ],
            1,
            0,
            null,
            1,
            10
        ],
        [
            'internet_reliability',
            'connectivity',
            'Internet Connectivity',
            'How reliable is your internet connection for school activities?',
            null,
            'SingleChoice',
            [
                'Reliable',
                'Usually reliable',
                'Intermittent',
                'Often unavailable',
                'No regular connection'
            ],
            1,
            0,
            null,
            1,
            20
        ],
        [
            'internet_affordability',
            'connectivity',
            'Internet Connectivity',
            'How often does internet or mobile-data cost limit your school participation?',
            null,
            'SingleChoice',
            [
                'Never',
                'Rarely',
                'Sometimes',
                'Often',
                'Always'
            ],
            1,
            0,
            null,
            1,
            30
        ],
        [
            'province',
            'geography',
            'Geographic Access',
            'What province do you currently live in?',
            'Do not enter your exact home address.',
            'ShortText',
            null,
            0,
            1,
            'location_profile',
            1,
            10
        ],
        [
            'municipality_city',
            'geography',
            'Geographic Access',
            'What municipality or city do you currently live in?',
            'Do not enter your exact home address.',
            'ShortText',
            null,
            0,
            1,
            'location_profile',
            1,
            20
        ],
        [
            'barangay',
            'geography',
            'Geographic Access',
            'What barangay do you currently live in?',
            'Do not enter your street, house number, or exact address.',
            'ShortText',
            null,
            0,
            1,
            'location_profile',
            1,
            30
        ],
        [
            'school_travel_time',
            'geography',
            'Geographic Access',
            'How long does it usually take you to travel to school?',
            null,
            'SingleChoice',
            [
                'Less than 15 minutes',
                '15 to 30 minutes',
                '31 to 60 minutes',
                'More than 1 hour',
                'Not applicable'
            ],
            1,
            0,
            null,
            1,
            40
        ],
        [
            'school_access_barriers',
            'geography',
            'Geographic Access',
            'What conditions sometimes make it difficult to reach or participate in school?',
            'Select every option that applies.',
            'MultipleChoice',
            [
                'Transportation availability',
                'Transportation cost',
                'Distance',
                'Road or weather conditions',
                'Mobility limitations',
                'Family responsibilities',
                'None',
                'Prefer not to say'
            ],
            0,
            1,
            'location_profile',
            1,
            50
        ],
        [
            'accessibility_support_needs',
            'accessibility',
            'Accessibility and Support',
            'Which accessibility or learning supports would help you participate?',
            'This question is optional. Select every option that applies.',
            'MultipleChoice',
            [
                'Visual support',
                'Hearing support',
                'Mobility support',
                'Reading or comprehension support',
                'Extended task time',
                'Assistive technology',
                'Other school support',
                'None',
                'Prefer not to say'
            ],
            0,
            1,
            'accessibility_profile',
            0,
            10
        ],
        [
            'assistive_technology',
            'accessibility',
            'Accessibility and Support',
            'Do you currently use assistive technology for learning?',
            null,
            'SingleChoice',
            [
                'Yes',
                'No',
                'Not sure',
                'Prefer not to say'
            ],
            0,
            1,
            'accessibility_profile',
            0,
            20
        ],
        [
            'study_environment',
            'learning',
            'Learning Environment',
            'Where do you usually complete schoolwork outside the classroom?',
            null,
            'SingleChoice',
            [
                'Quiet room at home',
                'Shared room at home',
                'School facility',
                'Library or study center',
                'Workplace',
                'Other location'
            ],
            1,
            0,
            null,
            1,
            10
        ],
        [
            'quiet_study_access',
            'learning',
            'Learning Environment',
            'How often do you have access to a quiet place for studying?',
            null,
            'SingleChoice',
            [
                'Always',
                'Often',
                'Sometimes',
                'Rarely',
                'Never'
            ],
            1,
            0,
            null,
            1,
            20
        ],
        [
            'preferred_learning_modes',
            'learning',
            'Learning Environment',
            'Which learning formats help you understand lessons best?',
            'Select up to three.',
            'MultipleChoice',
            [
                'Written explanations',
                'Images and diagrams',
                'Recorded video',
                'Audio explanations',
                'Live demonstrations',
                'Practice activities',
                'Group discussion'
            ],
            1,
            0,
            null,
            1,
            30
        ],
        [
            'learning_wellbeing_barrier',
            'wellbeing',
            'Optional Learning Wellbeing',
            'How often does your current wellbeing make it difficult to focus on schoolwork?',
            'No diagnosis is requested. You may decline to answer.',
            'SingleChoice',
            [
                'Never',
                'Rarely',
                'Sometimes',
                'Often',
                'Always',
                'Prefer not to say'
            ],
            0,
            1,
            'wellbeing_profile',
            0,
            10
        ],
        [
            'requested_school_support',
            'wellbeing',
            'Optional Learning Wellbeing',
            'Is there any general support you would like the school to consider?',
            'Do not include medical records or highly private details.',
            'LongText',
            null,
            0,
            1,
            'wellbeing_profile',
            0,
            20
        ]
    ];

    $questionStmt =
        $conn->prepare("
            INSERT INTO student_profile_question
            (
                question_key,
                section_key,
                section_label,
                question_text,
                help_text,
                response_type,
                options_json,
                is_required,
                is_sensitive,
                consent_key,
                analytics_enabled,
                survey_version,
                status,
                sort_order
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
                ?,
                ?,
                ?,
                ?,
                1,
                'Active',
                ?
            )

            ON DUPLICATE KEY UPDATE
                section_key =
                    VALUES(section_key),

                section_label =
                    VALUES(section_label),

                question_text =
                    VALUES(question_text),

                help_text =
                    VALUES(help_text),

                response_type =
                    VALUES(response_type),

                options_json =
                    VALUES(options_json),

                is_required =
                    VALUES(is_required),

                is_sensitive =
                    VALUES(is_sensitive),

                consent_key =
                    VALUES(consent_key),

                analytics_enabled =
                    VALUES(analytics_enabled),

                status =
                    'Active',

                sort_order =
                    VALUES(sort_order)
        ");

    foreach ($questions as $question) {
        [
            $questionKey,
            $sectionKey,
            $sectionLabel,
            $questionText,
            $helpText,
            $responseType,
            $options,
            $isRequired,
            $isSensitive,
            $consentKey,
            $analyticsEnabled,
            $sortOrder
        ] = $question;

        $optionsJson =
            $options === null
            ? null
            : json_encode(
                $options,
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            );

        $questionStmt->bind_param(
            'sssssssiisii',
            $questionKey,
            $sectionKey,
            $sectionLabel,
            $questionText,
            $helpText,
            $responseType,
            $optionsJson,
            $isRequired,
            $isSensitive,
            $consentKey,
            $analyticsEnabled,
            $sortOrder
        );

        $questionStmt->execute();
    }

    $questionStmt->close();

    $conn->commit();

    echo count($questions);
    echo " Student profile questions activated.";
    echo PHP_EOL;
    echo "Student profile survey catalog migration completed successfully.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo "Migration failed: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
