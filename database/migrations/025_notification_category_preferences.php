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

echo "Starting notification category preference migration..."
    . PHP_EOL;

try {
    $conn->begin_transaction();

    $conn->query("
        CREATE TABLE IF NOT EXISTS
        notification_category_preference
        (
            category_preference_id
                BIGINT NOT NULL AUTO_INCREMENT,

            user_id
                INT NOT NULL,

            notification_category
                ENUM(
                    'content_updates',
                    'discussion',
                    'engagement',
                    'reminders',
                    'workflow',
                    'account_system'
                ) NOT NULL,

            system_enabled
                TINYINT(1) NOT NULL
                DEFAULT 1,

            email_enabled
                TINYINT(1) NOT NULL
                DEFAULT 1,

            browser_push_enabled
                TINYINT(1) NOT NULL
                DEFAULT 1,

            created_at
                DATETIME NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at
                DATETIME DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                category_preference_id
            ),

            UNIQUE KEY
                uq_notification_category_user
                (
                    user_id,
                    notification_category
                ),

            KEY
                idx_notification_category_delivery
                (
                    notification_category,
                    system_enabled,
                    email_enabled,
                    browser_push_enabled
                ),

            CONSTRAINT
                fk_notification_category_user

                FOREIGN KEY (
                    user_id
                )

                REFERENCES user (
                    user_id
                )

                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "Notification category preference table ready."
        . PHP_EOL;

    /*
     * Seed every existing user with all categories
     * enabled. The existing global channel switches
     * remain the master delivery controls.
     */
    $conn->query("
        INSERT IGNORE INTO
        notification_category_preference
        (
            user_id,
            notification_category,
            system_enabled,
            email_enabled,
            browser_push_enabled
        )

        SELECT
            u.user_id,
            categories.notification_category,
            1,
            1,
            1

        FROM user u

        CROSS JOIN
        (
            SELECT
                'content_updates'
                AS notification_category

            UNION ALL

            SELECT
                'discussion'

            UNION ALL

            SELECT
                'engagement'

            UNION ALL

            SELECT
                'reminders'

            UNION ALL

            SELECT
                'workflow'

            UNION ALL

            SELECT
                'account_system'
        ) categories
    ");

    $conn->commit();

    echo "Existing users received default category preferences."
        . PHP_EOL;

    echo "Notification category preference migration completed."
        . PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    fwrite(
        STDERR,
        'Notification category preference migration failed: '
            . $exception->getMessage()
            . PHP_EOL
    );

    exit(1);
}
