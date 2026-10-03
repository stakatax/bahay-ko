<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

echo "Starting content redundancy audit migration..."
    . PHP_EOL;

$stmt =
    $conn->prepare("
        INSERT INTO actions
        (
            action_name
        )
        VALUES
        (
            'OVERRIDE_CONTENT_REDUNDANCY'
        )

        ON DUPLICATE KEY UPDATE
            action_name =
                VALUES(action_name)
    ");

if (!$stmt) {
    throw new RuntimeException(
        'Unable to prepare redundancy action migration: '
            . $conn->error
    );
}

if (!$stmt->execute()) {
    $error =
        $stmt->error;

    $stmt->close();

    throw new RuntimeException(
        'Unable to store redundancy audit action: '
            . $error
    );
}

$stmt->close();

echo "Content redundancy audit action ready."
    . PHP_EOL;

echo "Content redundancy audit migration completed successfully."
    . PHP_EOL;
