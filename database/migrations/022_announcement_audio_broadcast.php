<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

echo "Starting announcement audio broadcast migration..."
    . PHP_EOL;

$columns = [
    'audio_path' => [
        'definition' =>
        'VARCHAR(500) NULL',

        'after' =>
        'image_path'
    ],

    'audio_file_name' => [
        'definition' =>
        'VARCHAR(255) NULL',

        'after' =>
        'audio_path'
    ],

    'audio_mime_type' => [
        'definition' =>
        'VARCHAR(100) NULL',

        'after' =>
        'audio_file_name'
    ],

    'audio_file_size' => [
        'definition' =>
        'BIGINT UNSIGNED NULL',

        'after' =>
        'audio_mime_type'
    ],

    'audio_transcript' => [
        'definition' =>
        'TEXT NULL',

        'after' =>
        'audio_file_size'
    ]
];

foreach (
    $columns
    as $columnName => $column
) {
    $stmt =
        $conn->prepare("
            SELECT
                COUNT(*) AS total

            FROM information_schema.columns

            WHERE table_schema =
                DATABASE()

              AND table_name =
                'announcements'

              AND column_name = ?
        ");

    if (!$stmt) {
        throw new RuntimeException(
            'Unable to prepare announcement audio column lookup: '
                . $conn->error
        );
    }

    $stmt->bind_param(
        's',
        $columnName
    );

    if (!$stmt->execute()) {
        $error =
            $stmt->error;

        $stmt->close();

        throw new RuntimeException(
            'Unable to inspect announcement audio column '
                . $columnName
                . ': '
                . $error
        );
    }

    $columnExists =
        (int) (
            $stmt
                ->get_result()
                ->fetch_assoc()['total']
            ?? 0
        ) > 0;

    $stmt->close();

    if ($columnExists) {
        echo "Announcement column {$columnName} already ready."
            . PHP_EOL;

        continue;
    }

    $definition =
        $column['definition'];

    $afterColumn =
        $column['after'];

    if (
        !$conn->query("
            ALTER TABLE announcements

            ADD COLUMN `{$columnName}`
                {$definition}

            AFTER `{$afterColumn}`
        ")
    ) {
        throw new RuntimeException(
            'Unable to add announcement audio column '
                . $columnName
                . ': '
                . $conn->error
        );
    }

    echo "Announcement column {$columnName} ready."
        . PHP_EOL;
}

echo "Announcement audio broadcast migration completed successfully."
    . PHP_EOL;
