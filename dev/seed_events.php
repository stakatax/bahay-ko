<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once "../config/dbconnect.php";

echo "<h2>Seeding Events...</h2>";

$admin = $conn->query("
    SELECT user_id
    FROM user
    WHERE role_id = 1
    LIMIT 1
")->fetch_assoc();

if (!$admin) {
    die("No admin found.");
}

$admin_id = $admin['user_id'];

$events = [

    [
        "title" => "Orientation Day",
        "date" => "2026-08-15",
        "location" => "Main Auditorium",
        "description" => "Welcome program for all freshmen."
    ],

    [
        "title" => "College Week",
        "date" => "2026-09-20",
        "location" => "OLSHCO Grounds",
        "description" => "Week-long celebration with competitions."
    ],

    [
        "title" => "Foundation Day",
        "date" => "2026-10-10",
        "location" => "Campus Grounds",
        "description" => "Annual foundation celebration."
    ],

    [
        "title" => "Research Symposium",
        "date" => "2026-11-18",
        "location" => "AVR",
        "description" => "Presentation of student research."
    ],

    [
        "title" => "Christmas Program",
        "date" => "2026-12-18",
        "location" => "Gymnasium",
        "description" => "Christmas celebration."
    ],

    [
        "title" => "Leadership Seminar",
        "date" => "2027-01-12",
        "location" => "Conference Hall",
        "description" => "Training for student leaders."
    ],

    [
        "title" => "Intramurals Opening",
        "date" => "2027-02-05",
        "location" => "Sports Complex",
        "description" => "Opening ceremony."
    ],

    [
        "title" => "Career Fair",
        "date" => "2027-03-08",
        "location" => "College Lobby",
        "description" => "Partner companies visit OLSHCO."
    ],

    [
        "title" => "Recognition Day",
        "date" => "2027-04-01",
        "location" => "Gymnasium",
        "description" => "Recognition of outstanding students."
    ],

    [
        "title" => "Graduation Practice",
        "date" => "2027-05-20",
        "location" => "Gymnasium",
        "description" => "Mandatory graduation rehearsal."
    ]

];

foreach ($events as $event) {

    $stmt = $conn->prepare("
INSERT INTO events
(title,
description,
event_date,
location,
status,
created_at,
user_id)
VALUES
(?,?,?,?, 'active',NOW(),?)
");

    $stmt->bind_param(
        "ssssi",
        $event['title'],
        $event['description'],
        $event['date'],
        $event['location'],
        $admin_id
    );

    $stmt->execute();

    $event_id = $conn->insert_id;

    /*
Everyone can see event
*/

    $conn->query("
INSERT INTO event_target
(event_id)
VALUES
($event_id)
");

    echo "✔ {$event['title']}<br>";
}

echo "<h3>Done.</h3>";
