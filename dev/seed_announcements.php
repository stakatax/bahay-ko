<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/dbconnect.php';

echo "<h2>Seeding Announcements...</h2>";

$adminID = 1;

$announcements = [

    [
        "Enrollment for First Semester",
        "Enrollment for the First Semester is now officially open. Students are encouraged to complete their enrollment before the deadline.",
        4
    ],

    [
        "Midterm Examination Schedule",
        "The official midterm examination schedule has been released. Please check your respective department bulletin.",
        4
    ],

    [
        "NSTP Orientation",
        "All first year college students are required to attend the NSTP Orientation this Friday.",
        4
    ],

    [
        "Foundation Day Celebration",
        "OLSHCO Foundation Day activities will begin next Monday. Everyone is encouraged to participate.",
        0
    ],

    [
        "Elementary Parent Orientation",
        "Parents of Elementary students are invited to attend the Parent Orientation this Saturday.",
        1
    ],

    [
        "Junior High Intramurals",
        "The Junior High School Intramurals will officially begin next week.",
        2
    ],

    [
        "Senior High Research Defense",
        "Research defense schedules for Grade 12 students are now available.",
        3
    ],

    [
        "Scholarship Application",
        "Qualified students may now submit scholarship requirements before August 20.",
        4
    ],

    [
        "Class Suspension Advisory",
        "Classes are suspended tomorrow due to severe weather conditions.",
        0
    ],

    [
        "Graduation Clearance",
        "Graduating students may now process their graduation clearance.",
        4

    ]

];

foreach ($announcements as $item) {

    list($title, $content, $department) = $item;

    /*
    -----------------------------------
    Skip duplicates
    -----------------------------------
    */

    $check = $conn->prepare("
        SELECT announcement_id
        FROM announcements
        WHERE title=?
    ");

    $check->bind_param("s", $title);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {

        echo "Skipped {$title}<br>";
        continue;
    }

    /*
    -----------------------------------
    Insert announcement
    -----------------------------------
    */

    $stmt = $conn->prepare("

        INSERT INTO announcements
        (
            title,
            content,
            type,
            status,
            created_at,
            user_id
        )

        VALUES

        (
            ?,
            ?,
            'announcement',
            'active',
            NOW(),
            ?
        )

    ");

    $stmt->bind_param(
        "ssi",
        $title,
        $content,
        $adminID
    );

    $stmt->execute();

    $announcementID = $conn->insert_id;

    /*
    -----------------------------------
    Target Audience
    -----------------------------------
    */

    if ($department == 0) {

        /*
            Everyone
        */

        for ($i = 1; $i <= 4; $i++) {

            $target = $conn->prepare("

                INSERT INTO announcement_target
                (
                    announcement_id,
                    department_id
                )

                VALUES
                (?,?)

            ");

            $target->bind_param(
                "ii",
                $announcementID,
                $i
            );

            $target->execute();
        }
    } else {

        $target = $conn->prepare("

            INSERT INTO announcement_target
            (
                announcement_id,
                department_id
            )

            VALUES
            (?,?)

        ");

        $target->bind_param(
            "ii",
            $announcementID,
            $department
        );

        $target->execute();
    }

    echo "Created {$title}<br>";
}

echo "<hr>";
echo "<h3>Announcement Seeder Complete.</h3>";
