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
echo "Starting browser push notification migration...";
echo PHP_EOL;

$columnExists =
    static function (
        mysqli $conn,
        string $table,
        string $column
    ): bool {
        $stmt =
            $conn->prepare("
                SELECT COUNT(*) AS total

                FROM information_schema.COLUMNS

                WHERE TABLE_SCHEMA =
                    DATABASE()

                  AND TABLE_NAME = ?

                  AND COLUMN_NAME = ?
            ");

        $stmt->bind_param(
            'ss',
            $table,
            $column
        );

        $stmt->execute();

        $row =
            $stmt->get_result()
            ->fetch_assoc();

        $stmt->close();

        return (int) (
            $row['total']
            ?? 0
        ) > 0;
    };

try {
    /* ==========================================
       BROWSER PUSH PREFERENCE
    ========================================== */

    if (
        !$columnExists(
            $conn,
            'notification_preference',
            'browser_push_enabled'
        )
    ) {
        $conn->query("
            ALTER TABLE notification_preference

            ADD COLUMN browser_push_enabled
                TINYINT(1)
                NOT NULL
                DEFAULT 0

            AFTER system_enabled
        ");

        echo "Browser push preference added.";
        echo PHP_EOL;
    } else {
        echo "Browser push preference already exists.";
        echo PHP_EOL;
    }

    /* ==========================================
       BROWSER SUBSCRIPTIONS
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
            push_subscription
        (
            subscription_id BIGINT
                NOT NULL
                AUTO_INCREMENT,

            user_id INT
                NOT NULL,

            endpoint_hash CHAR(64)
                NOT NULL,

            endpoint TEXT
                NOT NULL,

            public_key VARCHAR(255)
                NOT NULL,

            auth_token VARCHAR(255)
                NOT NULL,

            content_encoding VARCHAR(50)
                NOT NULL
                DEFAULT 'aes128gcm',

            user_agent VARCHAR(500)
                NULL
                DEFAULT NULL,

            device_label VARCHAR(100)
                NULL
                DEFAULT NULL,

            subscription_status ENUM(
                'Active',
                'Expired',
                'Revoked'
            ) NOT NULL
              DEFAULT 'Active',

            failure_count INT
                NOT NULL
                DEFAULT 0,

            last_used_at DATETIME
                NULL
                DEFAULT NULL,

            failed_at DATETIME
                NULL
                DEFAULT NULL,

            created_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                subscription_id
            ),

            UNIQUE KEY
                uq_push_subscription_endpoint
            (
                endpoint_hash
            ),

            KEY
                idx_push_subscription_user_status
            (
                user_id,
                subscription_status
            ),

            CONSTRAINT
                fk_push_subscription_user

                FOREIGN KEY (
                    user_id
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Push subscription table ready.";
    echo PHP_EOL;

    /* ==========================================
       DELIVERY QUEUE AND HISTORY
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS
            push_delivery
        (
            delivery_id BIGINT
                NOT NULL
                AUTO_INCREMENT,

            notification_id INT
                NOT NULL,

            subscription_id BIGINT
                NOT NULL,

            delivery_status ENUM(
                'Pending',
                'Sent',
                'Failed',
                'Expired',
                'Skipped'
            ) NOT NULL
              DEFAULT 'Pending',

            attempt_count INT
                NOT NULL
                DEFAULT 0,

            last_http_status SMALLINT
                UNSIGNED
                NULL
                DEFAULT NULL,

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

            PRIMARY KEY (
                delivery_id
            ),

            UNIQUE KEY
                uq_push_delivery_notification_subscription
            (
                notification_id,
                subscription_id
            ),

            KEY
                idx_push_delivery_status_queue
            (
                delivery_status,
                queued_at
            ),

            KEY
                idx_push_delivery_subscription
            (
                subscription_id
            ),

            CONSTRAINT
                fk_push_delivery_notification

                FOREIGN KEY (
                    notification_id
                )

                REFERENCES notification (
                    notification_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT
                fk_push_delivery_subscription

                FOREIGN KEY (
                    subscription_id
                )

                REFERENCES push_subscription (
                    subscription_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Push delivery table ready.";
    echo PHP_EOL;

    /* ==========================================
       ENSURE USERS HAVE PREFERENCE ROWS
    ========================================== */

    $conn->query("
        INSERT IGNORE INTO
            notification_preference
        (
            user_id,
            email_enabled,
            system_enabled,
            browser_push_enabled
        )

        SELECT
            user_id,
            1,
            1,
            0

        FROM user

        WHERE status = 'Active'
    ");

    echo "Notification preferences verified.";
    echo PHP_EOL;

    echo "Browser push migration completed successfully.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    echo "Browser push migration failed: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
