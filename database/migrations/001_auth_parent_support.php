<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * Authentication and Parent Support Migration
 *
 * Changes:
 * 1. Allows non-student users to have no Student ID.
 * 2. Expands account statuses for approval workflow.
 * 3. Adds account approval metadata.
 * 4. Creates the Parent-to-Student relationship table.
 *
 * Existing Active accounts remain Active.
 */

mysqli_report(
    MYSQLI_REPORT_ERROR |
        MYSQLI_REPORT_STRICT
);

require_once __DIR__
    . '/../../config/dbconnect.php';

$conn->set_charset('utf8mb4');

function columnExists(
    mysqli $conn,
    string $tableName,
    string $columnName
): bool {
    $databaseResult = $conn->query(
        'SELECT DATABASE() AS database_name'
    );

    $databaseName =
        $databaseResult
            ->fetch_assoc()['database_name']
        ?? '';

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ?
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    $stmt->bind_param(
        'sss',
        $databaseName,
        $tableName,
        $columnName
    );

    $stmt->execute();

    $result =
        $stmt->get_result()->fetch_assoc();

    return (int) ($result['total'] ?? 0) > 0;
}

function tableExists(
    mysqli $conn,
    string $tableName
): bool {
    $databaseResult = $conn->query(
        'SELECT DATABASE() AS database_name'
    );

    $databaseName =
        $databaseResult
            ->fetch_assoc()['database_name']
        ?? '';

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ?
          AND TABLE_NAME = ?
    ");

    $stmt->bind_param(
        'ss',
        $databaseName,
        $tableName
    );

    $stmt->execute();

    $result =
        $stmt->get_result()->fetch_assoc();

    return (int) ($result['total'] ?? 0) > 0;
}

try {
    echo PHP_EOL;
    echo "Starting authentication migration...";
    echo PHP_EOL;

    /* ==========================================
       STUDENT ID
    ========================================== */

    $conn->query("
        ALTER TABLE user
        MODIFY studID VARCHAR(50) NULL
    ");

    echo "✓ Student ID now supports NULL values.";
    echo PHP_EOL;

    /* ==========================================
       ACCOUNT STATUS
    ========================================== */

    $conn->query("
        ALTER TABLE user
        MODIFY status ENUM(
            'Pending',
            'Active',
            'Inactive',
            'Rejected'
        ) NOT NULL DEFAULT 'Pending'
    ");

    echo "✓ Account approval statuses updated.";
    echo PHP_EOL;

    /* ==========================================
       APPROVAL METADATA
    ========================================== */

    if (
        !columnExists(
            $conn,
            'user',
            'approved_by'
        )
    ) {
        $conn->query("
            ALTER TABLE user
            ADD COLUMN approved_by INT NULL
            AFTER status
        ");

        echo "✓ approved_by column created.";
        echo PHP_EOL;
    } else {
        echo "• approved_by already exists.";
        echo PHP_EOL;
    }

    if (
        !columnExists(
            $conn,
            'user',
            'approved_at'
        )
    ) {
        $conn->query("
            ALTER TABLE user
            ADD COLUMN approved_at DATETIME NULL
            AFTER approved_by
        ");

        echo "✓ approved_at column created.";
        echo PHP_EOL;
    } else {
        echo "• approved_at already exists.";
        echo PHP_EOL;
    }

    /* ==========================================
       USER APPROVAL FOREIGN KEY
    ========================================== */

    $approvalConstraintExists =
        $conn->query("
            SELECT COUNT(*) AS total
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user'
              AND CONSTRAINT_NAME =
                  'fk_user_approved_by'
        ")
        ->fetch_assoc();

    if (
        (int) (
            $approvalConstraintExists['total']
            ?? 0
        ) === 0
    ) {
        $conn->query("
            ALTER TABLE user
            ADD CONSTRAINT fk_user_approved_by
                FOREIGN KEY (approved_by)
                REFERENCES user(user_id)
                ON DELETE SET NULL
                ON UPDATE CASCADE
        ");

        echo "✓ User approval foreign key created.";
        echo PHP_EOL;
    } else {
        echo "• User approval foreign key already exists.";
        echo PHP_EOL;
    }

    /* ==========================================
       PARENT-STUDENT RELATIONSHIP
    ========================================== */

    if (
        !tableExists(
            $conn,
            'parent_student'
        )
    ) {
        $conn->query("
            CREATE TABLE parent_student
            (
                parent_student_id
                    INT AUTO_INCREMENT PRIMARY KEY,

                parent_user_id
                    INT NOT NULL,

                student_user_id
                    INT NOT NULL,

                relationship
                    VARCHAR(50) NOT NULL,

                status
                    ENUM(
                        'Pending',
                        'Verified',
                        'Rejected'
                    )
                    NOT NULL
                    DEFAULT 'Pending',

                verified_by
                    INT NULL,

                verified_at
                    DATETIME NULL,

                created_at
                    DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at
                    DATETIME NULL
                    DEFAULT NULL
                    ON UPDATE CURRENT_TIMESTAMP,

                UNIQUE KEY uq_parent_student (
                    parent_user_id,
                    student_user_id
                ),

                KEY idx_parent_student_parent (
                    parent_user_id
                ),

                KEY idx_parent_student_student (
                    student_user_id
                ),

                KEY idx_parent_student_status (
                    status
                ),

                CONSTRAINT fk_parent_student_parent
                    FOREIGN KEY (
                        parent_user_id
                    )
                    REFERENCES user(user_id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,

                CONSTRAINT fk_parent_student_student
                    FOREIGN KEY (
                        student_user_id
                    )
                    REFERENCES user(user_id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,

                CONSTRAINT fk_parent_student_verifier
                    FOREIGN KEY (
                        verified_by
                    )
                    REFERENCES user(user_id)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            )
            ENGINE = InnoDB
            DEFAULT CHARSET = utf8mb4
            COLLATE = utf8mb4_general_ci
        ");

        echo "✓ parent_student table created.";
        echo PHP_EOL;
    } else {
        echo "• parent_student table already exists.";
        echo PHP_EOL;
    }

    /* ==========================================
       PRESERVE EXISTING ACCOUNTS
    ========================================== */

    $conn->query("
        UPDATE user
        SET status = 'Active'
        WHERE status IS NULL
           OR status = ''
    ");

    echo "✓ Existing accounts preserved.";
    echo PHP_EOL;

    echo PHP_EOL;
    echo "Authentication migration completed successfully.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    echo PHP_EOL;
    echo "Authentication migration failed.";
    echo PHP_EOL;
    echo "Error: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
