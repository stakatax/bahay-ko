<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/dbconnect.php';

echo "<h2>Seeding Notifications...</h2>";

$notifications = [

    [
        "title" => "Welcome to OLSHCO Digital Hub",
        "message" => "Welcome! Explore announcements, events, surveys and documents."
    ],

    [
        "title" => "New Announcement",
        "message" => "A new school announcement has been posted."
    ],

    [
        "title" => "Upcoming Event",
        "message" => "Don't forget to check the upcoming events."
    ],

    [
        "title" => "Survey Available",
        "message" => "A new survey is waiting for your response."
    ],

    [
        "title" => "Document Uploaded",
        "message" => "A new document has been uploaded."
    ]

];

$users = $conn->query("
SELECT user_id
FROM user
");

while ($user = $users->fetch_assoc()) {

    foreach ($notifications as $notification) {

        /*
        Avoid duplicate notifications
        */

        $check = $conn->prepare("
            SELECT notification_id
            FROM notification
            WHERE user_id=?
            AND title=?
        ");

        $check->bind_param(
            "is",
            $user['user_id'],
            $notification['title']
        );

        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            continue;
        }

        $stmt = $conn->prepare("

            INSERT INTO notification
            (
                user_id,
                title,
                message,
                is_read,
                created_at
            )

            VALUES
            (
                ?,
                ?,
                ?,
                0,
                NOW()
            )

        ");

        $stmt->bind_param(
            "iss",
            $user['user_id'],
            $notification['title'],
            $notification['message']
        );

        $stmt->execute();
    }
}

echo "<h3>Notifications Seeded Successfully.</h3>";
