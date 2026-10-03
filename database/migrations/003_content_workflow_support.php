<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../config/dbconnect.php';

mysqli_report(
    MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
);

echo "Starting content workflow migration...\n";

function columnExists(
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

    return (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'] > 0;
}

function indexExists(
    mysqli $conn,
    string $table,
    string $index
): bool {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
    ");

    $stmt->bind_param(
        'ss',
        $table,
        $index
    );

    $stmt->execute();

    return (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'] > 0;
}

function foreignKeyExists(
    mysqli $conn,
    string $table,
    string $constraint
): bool {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND CONSTRAINT_NAME = ?
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ");

    $stmt->bind_param(
        'ss',
        $table,
        $constraint
    );

    $stmt->execute();

    return (int) $stmt
        ->get_result()
        ->fetch_assoc()['total'] > 0;
}

function addColumn(
    mysqli $conn,
    string $table,
    string $column,
    string $definition
): void {
    if (
        columnExists(
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

function addIndex(
    mysqli $conn,
    string $table,
    string $index,
    string $columns
): void {
    if (
        indexExists(
            $conn,
            $table,
            $index
        )
    ) {
        echo "• {$index} already exists.\n";
        return;
    }

    $conn->query("
        CREATE INDEX `{$index}`
        ON `{$table}` ({$columns})
    ");

    echo "✓ {$index} created.\n";
}

try {
    $conn->begin_transaction();

    /* ==========================================
       ANNOUNCEMENTS
    ========================================== */

    $conn->query("
        ALTER TABLE announcements
        MODIFY COLUMN priority
        ENUM(
            'Low',
            'Normal',
            'Medium',
            'High',
            'Important',
            'Urgent',
            'Emergency'
        )
        NULL
        DEFAULT 'Medium'
    ");

    echo "✓ Announcement priority expanded.\n";

    addColumn(
        $conn,
        'announcements',
        'category',
        "VARCHAR(50) NOT NULL
         DEFAULT 'general'
         AFTER `type`"
    );

    addColumn(
        $conn,
        'announcements',
        'workflow_status',
        "ENUM(
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'scheduled',
            'published',
            'archived'
        ) NOT NULL
        DEFAULT 'draft'
        AFTER `status`"
    );

    addColumn(
        $conn,
        'announcements',
        'release_mode',
        "ENUM(
            'immediate',
            'scheduled',
            'calendar'
        ) NOT NULL
        DEFAULT 'immediate'
        AFTER `workflow_status`"
    );

    addColumn(
        $conn,
        'announcements',
        'scheduled_publish_at',
        "DATETIME NULL
         AFTER `release_mode`"
    );

    addColumn(
        $conn,
        'announcements',
        'calendar_event_id',
        "INT NULL
         AFTER `scheduled_publish_at`"
    );

    addColumn(
        $conn,
        'announcements',
        'reviewed_by',
        "INT NULL
         AFTER `calendar_event_id`"
    );

    addColumn(
        $conn,
        'announcements',
        'reviewed_at',
        "DATETIME NULL
         AFTER `reviewed_by`"
    );

    addColumn(
        $conn,
        'announcements',
        'review_notes',
        "TEXT NULL
         AFTER `reviewed_at`"
    );

    addColumn(
        $conn,
        'announcements',
        'allow_reactions',
        "TINYINT(1) NOT NULL
         DEFAULT 1
         AFTER `published_at`"
    );

    addColumn(
        $conn,
        'announcements',
        'allow_comments',
        "TINYINT(1) NOT NULL
         DEFAULT 1
         AFTER `allow_reactions`"
    );

    addColumn(
        $conn,
        'announcements',
        'require_acknowledgment',
        "TINYINT(1) NOT NULL
         DEFAULT 0
         AFTER `allow_comments`"
    );

    addColumn(
        $conn,
        'announcements',
        'send_notification',
        "TINYINT(1) NOT NULL
         DEFAULT 1
         AFTER `require_acknowledgment`"
    );

    /* ==========================================
       EVENTS
    ========================================== */

    $conn->query("
        ALTER TABLE events
        MODIFY COLUMN event_date DATETIME NULL,
        MODIFY COLUMN end_date DATETIME NULL
    ");

    echo "✓ Event dates converted to DATETIME.\n";

    addColumn(
        $conn,
        'events',
        'workflow_status',
        "ENUM(
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'published',
            'archived'
        ) NOT NULL
        DEFAULT 'draft'
        AFTER `status`"
    );

    addColumn(
        $conn,
        'events',
        'reviewed_by',
        "INT NULL
         AFTER `workflow_status`"
    );

    addColumn(
        $conn,
        'events',
        'reviewed_at',
        "DATETIME NULL
         AFTER `reviewed_by`"
    );

    addColumn(
        $conn,
        'events',
        'review_notes',
        "TEXT NULL
         AFTER `reviewed_at`"
    );

    addColumn(
        $conn,
        'events',
        'send_notification',
        "TINYINT(1) NOT NULL
         DEFAULT 1
         AFTER `review_notes`"
    );

    /* ==========================================
       DOCUMENTS
    ========================================== */

    addColumn(
        $conn,
        'documents',
        'workflow_status',
        "ENUM(
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'published',
            'archived'
        ) NOT NULL
        DEFAULT 'draft'
        AFTER `status`"
    );

    addColumn(
        $conn,
        'documents',
        'reviewed_by',
        "INT NULL
         AFTER `workflow_status`"
    );

    addColumn(
        $conn,
        'documents',
        'reviewed_at',
        "DATETIME NULL
         AFTER `reviewed_by`"
    );

    addColumn(
        $conn,
        'documents',
        'review_notes',
        "TEXT NULL
         AFTER `reviewed_at`"
    );

    addColumn(
        $conn,
        'documents',
        'send_notification',
        "TINYINT(1) NOT NULL
         DEFAULT 1
         AFTER `review_notes`"
    );

    /* ==========================================
       SURVEY
    ========================================== */

    addColumn(
        $conn,
        'survey',
        'workflow_status',
        "ENUM(
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'published',
            'archived'
        ) NOT NULL
        DEFAULT 'draft'
        AFTER `status`"
    );

    addColumn(
        $conn,
        'survey',
        'reviewed_by',
        "INT NULL
         AFTER `workflow_status`"
    );

    addColumn(
        $conn,
        'survey',
        'reviewed_at',
        "DATETIME NULL
         AFTER `reviewed_by`"
    );

    addColumn(
        $conn,
        'survey',
        'review_notes',
        "TEXT NULL
         AFTER `reviewed_at`"
    );

    addColumn(
        $conn,
        'survey',
        'allow_comments',
        "TINYINT(1) NOT NULL
         DEFAULT 0
         AFTER `review_notes`"
    );

    addColumn(
        $conn,
        'survey',
        'send_notification',
        "TINYINT(1) NOT NULL
         DEFAULT 1
         AFTER `allow_comments`"
    );

    /* ==========================================
       PROGRAM / STRAND TARGETING
    ========================================== */

    $targetTables = [
        'announcement_target',
        'event_target',
        'document_target',
        'survey_target'
    ];

    foreach ($targetTables as $targetTable) {
        addColumn(
            $conn,
            $targetTable,
            'academic_program_id',
            "INT NULL
             AFTER `education_level_id`"
        );

        addIndex(
            $conn,
            $targetTable,
            "idx_{$targetTable}_program",
            '`academic_program_id`'
        );

        $constraint =
            "fk_{$targetTable}_program";

        if (
            !foreignKeyExists(
                $conn,
                $targetTable,
                $constraint
            )
        ) {
            $conn->query("
                ALTER TABLE `{$targetTable}`
                ADD CONSTRAINT `{$constraint}`
                FOREIGN KEY (`academic_program_id`)
                REFERENCES academic_program(
                    academic_program_id
                )
                ON DELETE SET NULL
                ON UPDATE CASCADE
            ");

            echo "✓ {$constraint} created.\n";
        }
    }

    /* ==========================================
       INDEXES
    ========================================== */

    addIndex(
        $conn,
        'announcements',
        'idx_announcement_workflow_status',
        '`workflow_status`'
    );

    addIndex(
        $conn,
        'announcements',
        'idx_announcement_scheduled_publish',
        '`scheduled_publish_at`'
    );

    addIndex(
        $conn,
        'announcements',
        'idx_announcement_calendar_event',
        '`calendar_event_id`'
    );

    addIndex(
        $conn,
        'events',
        'idx_event_workflow_status',
        '`workflow_status`'
    );

    addIndex(
        $conn,
        'documents',
        'idx_document_workflow_status',
        '`workflow_status`'
    );

    addIndex(
        $conn,
        'survey',
        'idx_survey_workflow_status',
        '`workflow_status`'
    );

    /* ==========================================
       FOREIGN KEYS
    ========================================== */

    if (
        !foreignKeyExists(
            $conn,
            'announcements',
            'fk_announcement_calendar_event'
        )
    ) {
        $conn->query("
            ALTER TABLE announcements
            ADD CONSTRAINT
                fk_announcement_calendar_event
            FOREIGN KEY (
                calendar_event_id
            )
            REFERENCES events(event_id)
            ON DELETE SET NULL
            ON UPDATE CASCADE
        ");

        echo "✓ Announcement calendar-event foreign key created.\n";
    }

    $reviewForeignKeys = [
        [
            'table' => 'announcements',
            'constraint' => 'fk_announcement_reviewer'
        ],
        [
            'table' => 'events',
            'constraint' => 'fk_event_reviewer'
        ],
        [
            'table' => 'documents',
            'constraint' => 'fk_document_reviewer'
        ],
        [
            'table' => 'survey',
            'constraint' => 'fk_survey_reviewer'
        ]
    ];

    foreach (
        $reviewForeignKeys as $foreignKey
    ) {
        if (
            foreignKeyExists(
                $conn,
                $foreignKey['table'],
                $foreignKey['constraint']
            )
        ) {
            continue;
        }

        $conn->query("
            ALTER TABLE `{$foreignKey['table']}`
            ADD CONSTRAINT
                `{$foreignKey['constraint']}`
            FOREIGN KEY (`reviewed_by`)
            REFERENCES user(user_id)
            ON DELETE SET NULL
            ON UPDATE CASCADE
        ");

        echo "✓ {$foreignKey['constraint']} created.\n";
    }

    /* ==========================================
       PRESERVE CURRENT ACTIVE CONTENT
    ========================================== */

    $conn->query("
        UPDATE announcements
        SET
            workflow_status = 'published',
            published_at = COALESCE(
                published_at,
                created_at
            )
        WHERE status = 'active'
          AND workflow_status = 'draft'
    ");

    $conn->query("
        UPDATE events
        SET workflow_status = 'published'
        WHERE status = 'active'
          AND workflow_status = 'draft'
    ");

    $conn->query("
        UPDATE documents
        SET workflow_status = 'published'
        WHERE status = 'active'
          AND workflow_status = 'draft'
    ");

    $conn->query("
        UPDATE survey
        SET workflow_status = 'published'
        WHERE status = 'Published'
          AND workflow_status = 'draft'
    ");

    echo "✓ Existing active content preserved as published.\n";

    $conn->commit();

    echo "\nContent workflow migration completed successfully.\n";
} catch (Throwable $exception) {
    $conn->rollback();

    echo "\nMigration failed: "
        . $exception->getMessage()
        . "\n";

    exit(1);
}
