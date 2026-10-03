<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/dbconnect.php';

echo "<h2>Seeding Documents...</h2>";

$admin = $conn->query("
    SELECT user_id
    FROM user
    WHERE role_id = 1
    LIMIT 1
")->fetch_assoc();

if (!$admin) {
    die("Admin account not found.");
}

$admin_id = $admin['user_id'];

$documents = [

    ["Student Handbook", "Student_Handbook.pdf", "application/pdf", 254893],
    ["Enrollment Guidelines", "Enrollment_Guidelines.pdf", "application/pdf", 183221],
    ["Academic Calendar", "Academic_Calendar.pdf", "application/pdf", 124155],
    ["Scholarship Guidelines", "Scholarship_Guidelines.pdf", "application/pdf", 198221],
    ["Library Manual", "Library_Manual.pdf", "application/pdf", 156112],
    ["Faculty Handbook", "Faculty_Handbook.pdf", "application/pdf", 312448],
    ["Research Manual", "Research_Manual.pdf", "application/pdf", 241552],
    ["OJT Guidelines", "OJT_Guidelines.pdf", "application/pdf", 165881],
    ["Clearance Form", "Clearance_Form.pdf", "application/pdf", 112335],
    ["Graduation Requirements", "Graduation_Requirements.pdf", "application/pdf", 175004]

];

foreach ($documents as $doc) {

    $displayName = $doc[0];
    $fileName    = $doc[1];
    $fileType    = $doc[2];
    $fileSize    = $doc[3];

    /*
    ----------------------------------
    Skip duplicates
    ----------------------------------
    */

    $check = $conn->prepare("
        SELECT document_id
        FROM documents
        WHERE file_name = ?
    ");

    $check->bind_param("s", $fileName);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {

        echo "Skipped {$fileName}<br>";
        continue;
    }

    /*
    ----------------------------------
    Fake upload path
    ----------------------------------
    */

    $path = "Assets/uploads/" . $fileName;

    /*
    ----------------------------------
    Insert document
    ----------------------------------
    */

    $stmt = $conn->prepare("

        INSERT INTO documents
        (
            file_name,
            file_path,
            file_type,
            file_size,
            status,
            created_at,
            user_id
        )

        VALUES

        (
            ?,
            ?,
            ?,
            ?,
            'active',
            NOW(),
            ?
        )

    ");

    $stmt->bind_param(

        "sssii",

        $fileName,
        $path,
        $fileType,
        $fileSize,
        $admin_id

    );

    $stmt->execute();

    $document_id = $conn->insert_id;

    /*
    ----------------------------------
    Visible to everyone
    ----------------------------------
    */

    $target = $conn->prepare("

        INSERT INTO document_target
        (
            document_id
        )

        VALUES
        (?)

    ");

    $target->bind_param(
        "i",
        $document_id
    );

    $target->execute();

    echo "✔ {$displayName}<br>";
}

echo "<hr>";
echo "<h3>Document Seeder Completed.</h3>";
