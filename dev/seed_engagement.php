<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/dbconnect.php';

echo "<h2>Seeding Engagement...</h2>";

/*
------------------------------------------------
Fetch students
------------------------------------------------
*/

$users = [];

$result = $conn->query("
    SELECT user_id
    FROM user
    WHERE role_id <> 1
");

while ($row = $result->fetch_assoc()) {
    $users[] = $row['user_id'];
}

/*
------------------------------------------------
Announcements
------------------------------------------------
*/

$announcements = [];

$result = $conn->query("
    SELECT announcement_id
    FROM announcements
");

while ($row = $result->fetch_assoc()) {
    $announcements[] = $row['announcement_id'];
}

$reactions = [
    "Like",
    "Love",
    "Care",
    "Wow"
];

/*
====================================================
Announcement Views
====================================================
*/

foreach ($announcements as $announcement) {

    shuffle($users);

    $viewers = rand(5, count($users));

    for ($i = 0; $i < $viewers; $i++) {

        $user = $users[$i];

        $check = $conn->prepare("
            SELECT view_id
            FROM announcement_view
            WHERE announcement_id=?
            AND user_id=?
        ");

        $check->bind_param(
            "ii",
            $announcement,
            $user
        );

        $check->execute();

        if ($check->get_result()->num_rows == 0) {

            $stmt = $conn->prepare("
                INSERT INTO announcement_view
                (
                    announcement_id,
                    user_id
                )
                VALUES
                (?,?)
            ");

            $stmt->bind_param(
                "ii",
                $announcement,
                $user
            );

            $stmt->execute();
        }
    }
}

/*
====================================================
Reactions
====================================================
*/

foreach ($announcements as $announcement) {

    shuffle($users);

    $count = rand(3, 8);

    for ($i = 0; $i < $count; $i++) {

        $user = $users[$i];

        $reaction = $reactions[array_rand($reactions)];

        $check = $conn->prepare("
            SELECT reaction_id
            FROM announcement_reaction
            WHERE announcement_id=?
            AND user_id=?
        ");

        $check->bind_param(
            "ii",
            $announcement,
            $user
        );

        $check->execute();

        if ($check->get_result()->num_rows == 0) {

            $stmt = $conn->prepare("
                INSERT INTO announcement_reaction
                (
                    announcement_id,
                    user_id,
                    reaction
                )
                VALUES
                (?,?,?)
            ");

            $stmt->bind_param(
                "iis",
                $announcement,
                $user,
                $reaction
            );

            $stmt->execute();
        }
    }
}

/*
====================================================
Comments
====================================================
*/

$comments = [

    "Great announcement!",
    "Looking forward to this.",
    "Thank you!",
    "Very helpful information.",
    "Can't wait.",
    "This answered my question.",
    "Nice update.",
    "Awesome!",
    "Very informative.",
    "Thanks Admin."

];

foreach ($announcements as $announcement) {

    shuffle($users);

    $count = rand(2, 6);

    for ($i = 0; $i < $count; $i++) {

        $user = $users[$i];

        $comment = $comments[array_rand($comments)];

        $stmt = $conn->prepare("
            INSERT INTO announcement_comment
            (
                announcement_id,
                user_id,
                comment
            )
            VALUES
            (?,?,?)
        ");

        $stmt->bind_param(
            "iis",
            $announcement,
            $user,
            $comment
        );

        $stmt->execute();
    }
}

/*
====================================================
Survey Answers
====================================================
*/

$questions = [];

$result = $conn->query("
SELECT question_id,question_type
FROM survey_question
");

while ($row = $result->fetch_assoc()) {

    $questions[] = $row;
}

foreach ($questions as $question) {

    foreach ($users as $user) {

        $answer = "";

        switch ($question['question_type']) {

            case "Rating":
                $answer = rand(3, 5);
                break;

            case "Text":
                $texts = [
                    "Very satisfied.",
                    "Needs improvement.",
                    "Excellent.",
                    "Satisfied overall.",
                    "Good experience."
                ];

                $answer = $texts[array_rand($texts)];
                break;

            case "Multiple Choice":

                $choice = $conn->query("
                    SELECT choice_text
                    FROM survey_choice
                    WHERE question_id=" . $question['question_id'] . "
                    ORDER BY RAND()
                    LIMIT 1
                ")->fetch_assoc();

                $answer = $choice['choice_text'];

                break;

            default:

                $answer = "Yes";
        }

        $stmt = $conn->prepare("
            INSERT INTO survey_answer
            (
                question_id,
                user_id,
                answer
            )
            VALUES
            (?,?,?)
        ");

        $stmt->bind_param(
            "iis",
            $question['question_id'],
            $user,
            $answer
        );

        $stmt->execute();
    }
}

echo "<h3>Engagement Seeder Complete.</h3>";
