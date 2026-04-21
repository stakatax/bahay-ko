<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once "dbconnect.php";
    require_once "logging.php";

    if (!isset($_SESSION['user_id'])) {
        die("User not logged in.");
    }

    $user_id = $_SESSION['user_id'];

    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== 0) {
        die("Document upload is required.");
    }

    $uploadDir = __DIR__ . "/../Assets/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = basename($_FILES["attachment"]["name"]);
    $fileTmp = $_FILES["attachment"]["tmp_name"];


    $fileName = time() . "_" . $originalName;
    $targetFile = $uploadDir . $fileName;
    $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

    $allowedTypes = ['pdf', 'doc', 'docx'];
    if (!in_array(strtolower($fileType), $allowedTypes)) {
        die("Only PDF, DOC, DOCX files are allowed.");
    }

    if (!move_uploaded_file($fileTmp, $targetFile)) {
        die("Failed to upload file.");
    }

    $status = "active";
    $sql = "INSERT INTO documents ( file_name, file_type, created_at, status, user_id)
    VALUES (?, ?, NOW(), ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "sssi",
        $fileName,
        $fileType,
        $status,
        $user_id
    );

    if ($stmt->execute()) {
        logActivity($conn, "POST_ANNOUNCEMENT", 'posted announcement');
        header("Location: ../index.php?page=postings&success=1");
        exit();
    } else {
        echo "Execute failed: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
?>