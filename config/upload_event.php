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

$title = $_POST['title'] ?? '';
$event_date = $_POST['event_date'] ?? '';

if (empty($title) || empty($event_date)) {
    die("Title and Event Date are required.");
}

$status = "active";

$sql = "INSERT INTO events (title, event_date, created_at, status, user_id)
    VALUES (?, ?, NOW(), ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "sssi",
    $title,
    $event_date,
    $status,
    $user_id
);

if ($stmt->execute()) {
    logActivity($conn, "POST_EVENT", 'posted an event');
    header("Location: ../index.php?page=postings&success=1");
    exit();
} else {
    echo "Execute failed: "
        . $stmt->error;
}
$stmt->close();
$conn->close();
