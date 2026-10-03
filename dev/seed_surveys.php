<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/dbconnect.php';

echo "<h2>Seeding Surveys...</h2>";

$admin = $conn->query("
SELECT user_id
FROM user
WHERE role_id=1
LIMIT 1
")->fetch_assoc();

if (!$admin) {
    die("Admin not found.");
}

$admin_id = $admin['user_id'];

$surveys = [

    [
        "title" => "Student Satisfaction Survey",
        "description" => "Help us improve our services.",
        "questions" => [

            [
                "question" => "How satisfied are you with the school facilities?",
                "type" => "Rating"
            ],

            [
                "question" => "How would you rate your instructors?",
                "type" => "Rating"
            ],

            [
                "question" => "Would you recommend OLSHCO to others?",
                "type" => "Multiple Choice",
                "choices" => [
                    "Yes",
                    "No",
                    "Maybe"
                ]
            ]

        ]

    ],

    [
        "title" => "Website Feedback",
        "description" => "Evaluate the new Digital Hub.",
        "questions" => [

            [
                "question" => "Is the website easy to use?",
                "type" => "Multiple Choice",
                "choices" => [
                    "Very Easy",
                    "Easy",
                    "Average",
                    "Difficult"
                ]
            ],

            [
                "question" => "Which feature do you use most?",
                "type" => "Text"
            ]

        ]

    ],

    [
        "title" => "Library Satisfaction Survey",
        "description" => "Library Improvement Survey",
        "questions" => [

            [
                "question" => "Rate the library resources.",
                "type" => "Rating"
            ],

            [
                "question" => "Rate the study environment.",
                "type" => "Rating"
            ]

        ]

    ]

];

foreach ($surveys as $survey) {

    /*
------------------------------------
Duplicate check
------------------------------------
*/

    $check = $conn->prepare("
SELECT survey_id
FROM survey
WHERE title=?
");

    $check->bind_param("s", $survey['title']);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {

        echo "Skipped {$survey['title']}<br>";
        continue;
    }

    /*
------------------------------------
Survey
------------------------------------
*/

    $stmt = $conn->prepare("

INSERT INTO survey
(
title,
description,
status,
created_at,
user_id
)

VALUES
(
?,
?,
'Published',
NOW(),
?
)

");

    $stmt->bind_param(
        "ssi",
        $survey['title'],
        $survey['description'],
        $admin_id
    );

    $stmt->execute();

    $survey_id = $conn->insert_id;

    /*
------------------------------------
Survey Target
Everyone
------------------------------------
*/

    $target = $conn->prepare("
INSERT INTO survey_target
(
survey_id
)
VALUES
(?)
");

    $target->bind_param(
        "i",
        $survey_id
    );

    $target->execute();

    /*
------------------------------------
Questions
------------------------------------
*/

    foreach ($survey['questions'] as $question) {

        $q = $conn->prepare("

INSERT INTO survey_question
(
survey_id,
question,
question_type
)

VALUES
(
?,
?,
?
)

");

        $q->bind_param(
            "iss",
            $survey_id,
            $question['question'],
            $question['type']
        );

        $q->execute();

        $question_id = $conn->insert_id;

        /*
------------------------------------
Choices
------------------------------------
*/

        if (isset($question['choices'])) {

            foreach ($question['choices'] as $choice) {

                $c = $conn->prepare("

INSERT INTO survey_choice
(
question_id,
choice_text
)

VALUES
(
?,
?
)

");

                $c->bind_param(
                    "is",
                    $question_id,
                    $choice
                );

                $c->execute();
            }
        }
    }

    echo "✔ {$survey['title']}<br>";
}

echo "<hr>";
echo "<h3>Survey Seeder Completed.</h3>";
