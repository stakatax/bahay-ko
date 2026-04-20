<?php
    session_start();
    require_once "dbconnect.php";

    function logActivity($conn, $actionName, $description) {

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
            (description, action_id, user_id)

            VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "sii",
            $description,
            $action_id,
            $user_id
        );

        $stmt->execute();

    }


?>