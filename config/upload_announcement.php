<?php
require_once "dbconnect.php";
session_start();

$user_id = $_SESSION['user_id'] ?? 1;

$title = $_POST['title'];
$content = $_POST['content'];
$type = "announcement";
$status = "active";

/* ---------- INSERT ---------- */
$sql = "INSERT INTO announcements 
(title, content, type, created_at, status, user_id)
VALUES (?, ?, ?, NOW(), ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $title, $content, $type, $status, $user_id);

$stmt->execute();
header("Location: ../index.php");
?>