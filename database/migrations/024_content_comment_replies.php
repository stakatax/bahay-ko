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

echo "Starting content comment reply migration..."
    . PHP_EOL;

function replyColumnExists(
    mysqli $conn,
    string $column
): bool {
    $stmt =
        $conn->prepare("
            SELECT COUNT(*) AS total

            FROM information_schema.COLUMNS

            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'content_comment'
              AND COLUMN_NAME = ?
        ");

    $stmt->bind_param(
        's',
        $column
    );

    $stmt->execute();

    $exists =
        (int) (
            $stmt
                ->get_result()
                ->fetch_assoc()['total']
            ?? 0
        ) > 0;

    $stmt->close();

    return $exists;
}

function replyIndexExists(
    mysqli $conn,
    string $index
): bool {
    $stmt =
        $conn->prepare("
            SELECT COUNT(*) AS total

            FROM information_schema.STATISTICS

            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'content_comment'
              AND INDEX_NAME = ?
        ");

    $stmt->bind_param(
        's',
        $index
    );

    $stmt->execute();

    $exists =
        (int) (
            $stmt
                ->get_result()
                ->fetch_assoc()['total']
            ?? 0
        ) > 0;

    $stmt->close();

    return $exists;
}

function replyForeignKeyExists(
    mysqli $conn,
    string $constraint
): bool {
    $stmt =
        $conn->prepare("
            SELECT COUNT(*) AS total

            FROM information_schema.TABLE_CONSTRAINTS

            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'content_comment'
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

    $stmt->bind_param(
        's',
        $constraint
    );

    $stmt->execute();

    $exists =
        (int) (
            $stmt
                ->get_result()
                ->fetch_assoc()['total']
            ?? 0
        ) > 0;

    $stmt->close();

    return $exists;
}

try {
    if (
        !replyColumnExists(
            $conn,
            'parent_comment_id'
        )
    ) {
        $conn->query("
            ALTER TABLE content_comment

            ADD COLUMN parent_comment_id
                INT NULL

            AFTER user_id
        ");

        echo "parent_comment_id created."
            . PHP_EOL;
    } else {
        echo "parent_comment_id already exists."
            . PHP_EOL;
    }

    if (
        !replyIndexExists(
            $conn,
            'idx_content_comment_parent'
        )
    ) {
        $conn->query("
            CREATE INDEX
                idx_content_comment_parent

            ON content_comment (
                parent_comment_id
            )
        ");

        echo "Reply index created."
            . PHP_EOL;
    } else {
        echo "Reply index already exists."
            . PHP_EOL;
    }

    if (
        !replyForeignKeyExists(
            $conn,
            'fk_content_comment_parent'
        )
    ) {
        $conn->query("
            ALTER TABLE content_comment

            ADD CONSTRAINT
                fk_content_comment_parent

            FOREIGN KEY (
                parent_comment_id
            )

            REFERENCES content_comment (
                comment_id
            )

            ON DELETE CASCADE
            ON UPDATE CASCADE
        ");

        echo "Reply foreign key created."
            . PHP_EOL;
    } else {
        echo "Reply foreign key already exists."
            . PHP_EOL;
    }

    echo "Content comment reply migration completed."
        . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "Migration failed: "
            . $exception->getMessage()
            . PHP_EOL
    );

    exit(1);
}
