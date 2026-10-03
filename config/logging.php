<?php


require_once __DIR__
    . '/security.php';

startSecureSession();
require_once "dbconnect.php";

function logActivity(
    $conn,
    $actionName,
    $description,
    ?int $targetUserId = null
) {

    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "SELECT action_id
             FROM actions
             WHERE action_name = ?"
    );

    $stmt->bind_param("s", $actionName);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        return;
    }

    $action_id = $row['action_id'];

    $stmt = $conn->prepare(
        "INSERT INTO activity_log
    (
        description,
        action_id,
        user_id,
        target_user_id
    )

VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "siii",
        $description,
        $action_id,
        $user_id,
        $targetUserId
    );

    $stmt->execute();
}
