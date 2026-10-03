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
echo "Starting email notification delivery migration...";
echo PHP_EOL;

/* ==========================================
   EMAIL DELIVERY ACTIVATION TIMESTAMP
========================================== */

$columnResult =
    $conn->query("
        SELECT
            COUNT(*) AS total

        FROM information_schema.COLUMNS

        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME =
                'notification_preference'
          AND COLUMN_NAME =
                'email_enabled_at'
    ");

$columnExists =
    (int) (
        $columnResult
            ->fetch_assoc()['total']
        ?? 0
    ) > 0;

if (!$columnExists) {
    $conn->query("
        ALTER TABLE notification_preference

        ADD COLUMN email_enabled_at
            DATETIME
            NULL
            DEFAULT NULL

        AFTER email_enabled
    ");

    echo "Email activation timestamp added.";
    echo PHP_EOL;
} else {
    echo "Email activation timestamp already exists.";
    echo PHP_EOL;
}


/*
 * Existing values remain unchanged. New preference
 * rows start disabled until the user explicitly
 * enables email notifications.
 */
$conn->query("
    ALTER TABLE notification_preference

    MODIFY COLUMN email_enabled
        TINYINT(1)
        NOT NULL
        DEFAULT 0
");

echo "New email preferences now default to disabled.";
echo PHP_EOL;

/*
 * Existing enabled preferences begin receiving only
 * notifications created after this migration runs.
 */
$conn->query("
    UPDATE notification_preference

    SET email_enabled_at =
        NOW()

    WHERE email_enabled = 1
      AND email_enabled_at IS NULL
");

echo "Enabled email preferences activated safely.";
echo PHP_EOL;

/* ==========================================
   EMAIL DELIVERY QUEUE
========================================== */

$conn->query("
    CREATE TABLE IF NOT EXISTS email_delivery
    (
        delivery_id BIGINT
            NOT NULL
            AUTO_INCREMENT,

        notification_id INT
            NOT NULL,

        recipient_email VARCHAR(254)
            NOT NULL,

        delivery_status ENUM(
            'Pending',
            'Sent',
            'Failed',
            'Skipped'
        ) NOT NULL
          DEFAULT 'Pending',

        attempt_count INT
            NOT NULL
            DEFAULT 0,

        last_error VARCHAR(1000)
            NULL
            DEFAULT NULL,

        queued_at DATETIME
            NOT NULL
            DEFAULT CURRENT_TIMESTAMP,

        last_attempt_at DATETIME
            NULL
            DEFAULT NULL,

        sent_at DATETIME
            NULL
            DEFAULT NULL,

        PRIMARY KEY (delivery_id),

        UNIQUE KEY
            uq_email_delivery_notification
            (
                notification_id
            ),

        KEY
            idx_email_delivery_status_queue
            (
                delivery_status,
                queued_at
            ),

        CONSTRAINT
            fk_email_delivery_notification

            FOREIGN KEY (notification_id)
            REFERENCES notification
                (notification_id)

            ON DELETE CASCADE
            ON UPDATE CASCADE
    )
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci
");

echo "Email delivery queue ready.";
echo PHP_EOL;

echo "Email notification delivery migration completed successfully.";
echo PHP_EOL;
