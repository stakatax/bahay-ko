<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

echo "Starting reusable government advisory URL migration..."
    . PHP_EOL;

/* ==========================================
   INDEX LOOKUP
========================================== */

$indexExists =
    static function (
        mysqli $conn,
        string $indexName
    ): bool {
        $stmt =
            $conn->prepare("
                SELECT
                    COUNT(*) AS total

                FROM information_schema.statistics

                WHERE table_schema =
                    DATABASE()

                  AND table_name =
                    'government_advisory'

                  AND index_name = ?
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare government advisory index lookup: '
                    . $conn->error
            );
        }

        $stmt->bind_param(
            's',
            $indexName
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to inspect the government advisory index: '
                    . $error
            );
        }

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return (int) (
            $row['total']
            ?? 0
        ) > 0;
    };

/* ==========================================
   REMOVE URL-ONLY UNIQUE INDEX
========================================== */

$oldIndexName =
    'uq_government_advisory_url_hash';

if (
    $indexExists(
        $conn,
        $oldIndexName
    )
) {
    if (
        !$conn->query("
            ALTER TABLE government_advisory

            DROP INDEX
                uq_government_advisory_url_hash
        ")
    ) {
        throw new RuntimeException(
            'Unable to remove the URL-only government advisory index: '
                . $conn->error
        );
    }

    echo "URL-only uniqueness rule removed."
        . PHP_EOL;
} else {
    echo "URL-only uniqueness rule already removed."
        . PHP_EOL;
}

/* ==========================================
   ADD URL AND REFERENCE UNIQUE INDEX
========================================== */

$newIndexName =
    'uq_government_advisory_url_reference';

if (
    !$indexExists(
        $conn,
        $newIndexName
    )
) {
    if (
        !$conn->query("
            ALTER TABLE government_advisory

            ADD UNIQUE INDEX
                uq_government_advisory_url_reference
                (
                    source_url_hash,
                    external_reference
                )
        ")
    ) {
        throw new RuntimeException(
            'Unable to create the reusable government advisory URL index: '
                . $conn->error
        );
    }

    echo "URL and official-reference uniqueness rule ready."
        . PHP_EOL;
} else {
    echo "URL and official-reference uniqueness rule already ready."
        . PHP_EOL;
}

echo "Reusable government advisory URL migration completed successfully."
    . PHP_EOL;
