<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

mysqli_report(
    MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
);

$conn->set_charset(
    'utf8mb4'
);

echo PHP_EOL;
echo "Starting notification channel visibility migration...";
echo PHP_EOL;

/* ==========================================
   IN-SYSTEM DELIVERY VISIBILITY
========================================== */

$columnResult =
    $conn->query("
        SELECT
            COUNT(*) AS total

        FROM information_schema.COLUMNS

        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'notification'
          AND COLUMN_NAME = 'in_system_visible'
    ");

$columnExists =
    (int) (
        $columnResult
            ->fetch_assoc()['total']
        ?? 0
    ) > 0;

if (!$columnExists) {
    $conn->query("
        ALTER TABLE notification

        ADD COLUMN in_system_visible
            TINYINT(1)
            NOT NULL
            DEFAULT 1

        AFTER content_id
    ");

    echo "In-system visibility column added.";
    echo PHP_EOL;
} else {
    echo "In-system visibility column already exists.";
    echo PHP_EOL;
}

/* ==========================================
   NOTIFICATION CENTER INDEX
========================================== */

$indexResult =
    $conn->query("
        SELECT
            COUNT(*) AS total

        FROM information_schema.STATISTICS

        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'notification'
          AND INDEX_NAME =
                'idx_notification_user_visible_unread'
    ");

$indexExists =
    (int) (
        $indexResult
            ->fetch_assoc()['total']
        ?? 0
    ) > 0;

if (!$indexExists) {
    $conn->query("
        ALTER TABLE notification

        ADD INDEX
            idx_notification_user_visible_unread
            (
                user_id,
                in_system_visible,
                is_read,
                created_at
            )
    ");

    echo "Notification-center visibility index added.";
    echo PHP_EOL;
} else {
    echo "Notification-center visibility index already exists.";
    echo PHP_EOL;
}

echo "Notification channel visibility migration completed successfully.";
echo PHP_EOL;
