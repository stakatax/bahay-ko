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

echo "Starting content review fields migration...\n";

function reviewColumnExists(
    mysqli $conn,
    string $table,
    string $column
): bool {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    $stmt->bind_param(
        'ss',
        $table,
        $column
    );

    $stmt->execute();

    $row = $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();

    return (int) (
        $row['total']
        ?? 0
    ) > 0;
}

function addReviewColumn(
    mysqli $conn,
    string $table,
    string $column,
    string $definition
): void {
    if (
        reviewColumnExists(
            $conn,
            $table,
            $column
        )
    ) {
        echo "• {$table}.{$column} already exists.\n";
        return;
    }

    $conn->query("
        ALTER TABLE `{$table}`
        ADD COLUMN `{$column}` {$definition}
    ");

    echo "✓ {$table}.{$column} created.\n";
}

try {
    $tables = [
        'announcements',
        'events',
        'documents'
    ];

    foreach ($tables as $table) {
        addReviewColumn(
            $conn,
            $table,
            'reviewed_by',
            'INT NULL'
        );

        addReviewColumn(
            $conn,
            $table,
            'reviewed_at',
            'DATETIME NULL'
        );

        addReviewColumn(
            $conn,
            $table,
            'review_notes',
            'TEXT NULL'
        );
    }

    echo "\nContent review fields migration completed.\n";
} catch (Throwable $exception) {
    echo "\nMigration failed: "
        . $exception->getMessage()
        . "\n";

    exit(1);
}
