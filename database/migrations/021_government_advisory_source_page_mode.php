<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

echo "Starting government advisory source-page mode migration..."
    . PHP_EOL;

/* ==========================================
   COLUMN LOOKUP
========================================== */

$stmt =
    $conn->prepare("
        SELECT
            COUNT(*) AS total

        FROM information_schema.columns

        WHERE table_schema =
            DATABASE()

          AND table_name =
            'government_advisory'

          AND column_name =
            'source_page_mode'
    ");

if (!$stmt) {
    throw new RuntimeException(
        'Unable to prepare source-page mode column lookup: '
            . $conn->error
    );
}

if (!$stmt->execute()) {
    $error =
        $stmt->error;

    $stmt->close();

    throw new RuntimeException(
        'Unable to inspect the source-page mode column: '
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

/* ==========================================
   ADD SOURCE-PAGE MODE
========================================== */

if (!$columnExists) {
    if (
        !$conn->query("
            ALTER TABLE government_advisory

            ADD COLUMN source_page_mode
                ENUM(
                    'Specific',
                    'Reusable'
                )
                NOT NULL
                DEFAULT 'Specific'

            AFTER source_url_hash
        ")
    ) {
        throw new RuntimeException(
            'Unable to add the government advisory source-page mode: '
                . $conn->error
        );
    }

    echo "Government advisory source-page mode column ready."
        . PHP_EOL;
} else {
    echo "Government advisory source-page mode column already ready."
        . PHP_EOL;
}

/* ==========================================
   IDENTIFY EXISTING REUSED URLS
========================================== */

if (
    !$conn->query("
        UPDATE government_advisory ga

        INNER JOIN
        (
            SELECT
                source_url_hash

            FROM government_advisory

            GROUP BY
                source_url_hash

            HAVING COUNT(*) > 1
        ) reused_url
            ON reused_url.source_url_hash =
                ga.source_url_hash

        SET ga.source_page_mode =
            'Reusable'
    ")
) {
    throw new RuntimeException(
        'Unable to classify existing reusable government source pages: '
            . $conn->error
    );
}

echo "Existing reused government source pages classified."
    . PHP_EOL;

echo "Government advisory source-page mode migration completed successfully."
    . PHP_EOL;
