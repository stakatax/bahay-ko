<?php
header('Content-Type: application/json');

include 'dbconnect.php';

$data = json_decode(file_get_contents("php://input"), true);

$date = $data['date'] ?? null;
$title = $data['title'] ?? null;

if (!$date || !$title) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO events (event_date, title) VALUES (?, ?)");
$stmt->bind_param("ss", $date, $title);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}
?>