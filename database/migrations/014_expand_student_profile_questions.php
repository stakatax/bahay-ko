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
echo "Starting Student profile question expansion...";
echo PHP_EOL;

try {
    $conn->begin_transaction();

    $questions = [
        [
            'electricity_charging_reliability',
            'device',
            'Device Access',
            10,
            'How reliable is your access to electricity or device charging?',
            'Consider your usual situation during school days.',
            'SingleChoice',
            [
                'Always reliable',
                'Usually reliable',
                'Sometimes unavailable',
                'Often unavailable',
                'No reliable access'
            ],
            1,
            0,
            null,
            1,
            50
        ],
        [
            'digital_task_confidence',
            'device',
            'Device Access',
            10,
            'How confident are you when completing common digital school tasks?',
            'Examples include uploading files, joining online activities, and using school platforms.',
            'SingleChoice',
            [
                'Very confident',
                'Confident',
                'Somewhat confident',
                'Need occasional help',
                'Need regular help'
            ],
            0,
            0,
            null,
            1,
            60
        ],
        [
            'daily_internet_availability',
            'connectivity',
            'Internet Connectivity',
            20,
            'During a typical school day, how much internet access do you have?',
            null,
            'SingleChoice',
            [
                'Available throughout the day',
                'Available for several hours',
                'Available for one to two hours',
                'Available for less than one hour',
                'Usually unavailable'
            ],
            1,
            0,
            null,
            1,
            40
        ],
        [
            'connection_disruption_frequency',
            'connectivity',
            'Internet Connectivity',
            20,
            'How often do connection problems interrupt your school activities?',
            null,
            'SingleChoice',
            [
                'Never',
                'Rarely',
                'Sometimes',
                'Often',
                'Almost every time'
            ],
            0,
            0,
            null,
            1,
            50
        ],
        [
            'usual_transportation_method',
            'geography',
            'Geographic Access',
            30,
            'What transportation method do you usually use when going to school?',
            null,
            'SingleChoice',
            [
                'Walking',
                'Bicycle',
                'Private vehicle',
                'Public transportation',
                'School service',
                'Combination of methods',
                'Not applicable'
            ],
            1,
            0,
            null,
            1,
            60
        ],
        [
            'accessible_content_adjustments',
            'accessibility',
            'Accessibility and Support',
            40,
            'Which content adjustments would make school information easier for you to use?',
            'This question is optional. Select every option that applies.',
            'MultipleChoice',
            [
                'Larger text',
                'Higher color contrast',
                'Captions or transcripts',
                'Audio explanation',
                'Simplified written instructions',
                'Keyboard-friendly controls',
                'No adjustment needed',
                'Prefer not to say'
            ],
            0,
            1,
            'accessibility_profile',
            0,
            30
        ],
        [
            'preferred_study_schedule',
            'learning',
            'Learning Environment',
            50,
            'When are you usually most able to focus on schoolwork?',
            null,
            'SingleChoice',
            [
                'Early morning',
                'Morning',
                'Afternoon',
                'Evening',
                'Late evening',
                'Varies depending on the day'
            ],
            1,
            0,
            null,
            1,
            40
        ],
        [
            'collaboration_preference',
            'learning',
            'Learning Environment',
            50,
            'How do you usually prefer to complete learning activities?',
            null,
            'SingleChoice',
            [
                'Independently',
                'With one partner',
                'In a small group',
                'In a larger group',
                'It depends on the activity'
            ],
            0,
            0,
            null,
            1,
            50
        ],
        [
            'academic_workload_pressure',
            'wellbeing',
            'Optional Learning Wellbeing',
            60,
            'How often does your academic workload feel difficult to manage?',
            'This is optional and does not request a medical diagnosis.',
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
            30
        ],
        [
            'school_support_awareness',
            'wellbeing',
            'Optional Learning Wellbeing',
            60,
            'Do you know where to request school support when you need help?',
            null,
            'SingleChoice',
            [
                'Yes',
                'Partly',
                'No',
                'Not sure'
            ],
            0,
            0,
            null,
            1,
            40
        ]
    ];

    $stmt =
        $conn->prepare("
            INSERT INTO student_profile_question
            (
                question_key,
                section_key,
                section_label,
                section_sort_order,
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

                section_sort_order =
                    VALUES(section_sort_order),

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
            $sectionSortOrder,
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

        $stmt->bind_param(
            'sssissssiisii',
            $questionKey,
            $sectionKey,
            $sectionLabel,
            $sectionSortOrder,
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

        $stmt->execute();
    }

    $stmt->close();

    $conn->commit();

    echo count($questions);
    echo " additional questions activated.";
    echo PHP_EOL;
    echo "Student profile question expansion completed successfully.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo "Expansion failed: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
