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

$conn->set_charset('utf8mb4');

echo PHP_EOL;
echo "Starting unified content engagement migration...";
echo PHP_EOL;

function tableExists(
    mysqli $conn,
    string $tableName
): bool {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
    ");

    $stmt->bind_param(
        's',
        $tableName
    );

    $stmt->execute();

    $row = $stmt
        ->get_result()
        ->fetch_assoc();

    return (int) (
        $row['total']
        ?? 0
    ) > 0;
}

function columnExists(
    mysqli $conn,
    string $tableName,
    string $columnName
): bool {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND column_name = ?
    ");

    $stmt->bind_param(
        'ss',
        $tableName,
        $columnName
    );

    $stmt->execute();

    $row = $stmt
        ->get_result()
        ->fetch_assoc();

    return (int) (
        $row['total']
        ?? 0
    ) > 0;
}

function addInteractionColumns(
    mysqli $conn,
    string $tableName
): void {
    $columns = [
        'allow_reactions' => "
            TINYINT(1)
            NOT NULL
            DEFAULT 1
        ",

        'allow_comments' => "
            TINYINT(1)
            NOT NULL
            DEFAULT 1
        ",

        'require_acknowledgment' => "
            TINYINT(1)
            NOT NULL
            DEFAULT 0
        "
    ];

    foreach (
        $columns as
        $columnName => $definition
    ) {
        if (
            columnExists(
                $conn,
                $tableName,
                $columnName
            )
        ) {
            echo "✓ {$tableName}.{$columnName} already exists.";
            echo PHP_EOL;

            continue;
        }

        $conn->query("
            ALTER TABLE {$tableName}
            ADD COLUMN {$columnName}
            {$definition}
        ");

        echo "✓ {$tableName}.{$columnName} created.";
        echo PHP_EOL;
    }
}

try {
    $conn->begin_transaction();

    addInteractionColumns(
        $conn,
        'events'
    );

    addInteractionColumns(
        $conn,
        'documents'
    );

    if (
        tableExists(
            $conn,
            'survey'
        )
    ) {
        addInteractionColumns(
            $conn,
            'survey'
        );
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS content_view
        (
            view_id INT NOT NULL AUTO_INCREMENT,

            content_type ENUM(
                'announcement',
                'event',
                'document',
                'survey'
            ) NOT NULL,

            content_id INT NOT NULL,

            user_id INT NOT NULL,

            viewed_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (view_id),

            UNIQUE KEY uq_content_view
            (
                content_type,
                content_id,
                user_id
            ),

            KEY idx_content_view_lookup
            (
                content_type,
                content_id
            ),

            KEY idx_content_view_user
            (
                user_id
            ),

            CONSTRAINT fk_content_view_user
                FOREIGN KEY (user_id)
                REFERENCES user (user_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "✓ content_view ready.";
    echo PHP_EOL;

    $conn->query("
        CREATE TABLE IF NOT EXISTS content_reaction
        (
            reaction_id INT NOT NULL AUTO_INCREMENT,

            content_type ENUM(
                'announcement',
                'event',
                'document',
                'survey'
            ) NOT NULL,

            content_id INT NOT NULL,

            user_id INT NOT NULL,

            reaction_type ENUM(
                'Like',
                'Love',
                'Care',
                'Wow'
            ) NOT NULL,

            reacted_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (reaction_id),

            UNIQUE KEY uq_content_reaction
            (
                content_type,
                content_id,
                user_id
            ),

            KEY idx_content_reaction_lookup
            (
                content_type,
                content_id
            ),

            KEY idx_content_reaction_user
            (
                user_id
            ),

            CONSTRAINT fk_content_reaction_user
                FOREIGN KEY (user_id)
                REFERENCES user (user_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "✓ content_reaction ready.";
    echo PHP_EOL;

    $conn->query("
        CREATE TABLE IF NOT EXISTS content_comment
        (
            comment_id INT NOT NULL AUTO_INCREMENT,

            content_type ENUM(
                'announcement',
                'event',
                'document',
                'survey'
            ) NOT NULL,

            content_id INT NOT NULL,

            user_id INT NOT NULL,

            comment TEXT NOT NULL,

            status ENUM(
                'Active',
                'Hidden',
                'Deleted'
            ) NOT NULL
              DEFAULT 'Active',

            created_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (comment_id),

            KEY idx_content_comment_lookup
            (
                content_type,
                content_id,
                status,
                created_at
            ),

            KEY idx_content_comment_user
            (
                user_id
            ),

            CONSTRAINT fk_content_comment_user
                FOREIGN KEY (user_id)
                REFERENCES user (user_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "✓ content_comment ready.";
    echo PHP_EOL;

    $conn->query("
        CREATE TABLE IF NOT EXISTS content_acknowledgment
        (
            acknowledgment_id INT NOT NULL AUTO_INCREMENT,

            content_type ENUM(
                'announcement',
                'event',
                'document',
                'survey'
            ) NOT NULL,

            content_id INT NOT NULL,

            user_id INT NOT NULL,

            acknowledged_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (acknowledgment_id),

            UNIQUE KEY uq_content_acknowledgment
            (
                content_type,
                content_id,
                user_id
            ),

            KEY idx_content_acknowledgment_lookup
            (
                content_type,
                content_id
            ),

            KEY idx_content_acknowledgment_user
            (
                user_id
            ),

            CONSTRAINT fk_content_acknowledgment_user
                FOREIGN KEY (user_id)
                REFERENCES user (user_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "✓ content_acknowledgment ready.";
    echo PHP_EOL;

    $conn->query("
        CREATE TABLE IF NOT EXISTS document_download
        (
            download_id INT NOT NULL AUTO_INCREMENT,

            document_id INT NOT NULL,

            user_id INT NOT NULL,

            downloaded_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (download_id),

            KEY idx_document_download_document
            (
                document_id
            ),

            KEY idx_document_download_user
            (
                user_id
            ),

            CONSTRAINT fk_document_download_document
                FOREIGN KEY (document_id)
                REFERENCES documents (document_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT fk_document_download_user
                FOREIGN KEY (user_id)
                REFERENCES user (user_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "✓ document_download ready.";
    echo PHP_EOL;

    $conn->commit();

    echo "Unified content engagement migration completed.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo "Unified content engagement migration failed.";
    echo PHP_EOL;
    echo "Error: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
