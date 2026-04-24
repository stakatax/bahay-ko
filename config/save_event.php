<?php
session_start();
require_once "dbconnect.php";

// 1. Siguraduhin na ang user ay logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Session expired. Please log in again."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$date = $data['date'] ?? null;
$title = $data['title'] ?? null;

if (!$date || !$title) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

// 2. Gamitin ang dynamic $user_id mula sa session
$stmt = $conn->prepare("INSERT INTO events (event_date, title, status, user_id) VALUES (?, ?, 'active', ?)");
$stmt->bind_param("ssi", $date, $title, $user_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}
