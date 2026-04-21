<?php
    require_once "dbconnect.php";
    require_once "logging.php";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        die("Unauthorized user");
    }

    $title = $_POST['title'];
    $content = $_POST['content'];
    $type = "announcement";
    $status = "active";

    /* ---------- INSERT ---------- */
    $sql = "INSERT INTO announcements 
    (title, content, type, created_at, status, user_id)
    VALUES (?, ?, ?, NOW(), ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssssi",
        $title,
        $content,
        $type,
        $status,
        $user_id
    );

    if ($stmt->execute()) {
        logActivity($conn, "POST_ANNOUNCEMENT", 'posted announcement');
        header("Location: ../index.php?page=postings&success=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
?>