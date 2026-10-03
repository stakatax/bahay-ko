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
echo "Starting Terms, Privacy, and consent migration...";
echo PHP_EOL;

try {
    $conn->begin_transaction();

    /* ==========================================
       LEGAL DOCUMENT VERSIONS
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS legal_document_version
        (
            legal_document_version_id INT
                NOT NULL
                AUTO_INCREMENT,

            document_type ENUM(
                'Terms',
                'Privacy'
            ) NOT NULL,

            version VARCHAR(30)
                NOT NULL,

            title VARCHAR(150)
                NOT NULL,

            effective_at DATETIME
                NOT NULL,

            status ENUM(
                'Draft',
                'Active',
                'Retired'
            ) NOT NULL
              DEFAULT 'Draft',

            created_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME
                NULL
                DEFAULT NULL
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (
                legal_document_version_id
            ),

            UNIQUE KEY uq_legal_document_version
            (
                document_type,
                version
            ),

            KEY idx_legal_document_active
            (
                document_type,
                status,
                effective_at
            )
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Legal document version table ready.";
    echo PHP_EOL;

    /* ==========================================
       USER LEGAL ACCEPTANCE
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS user_legal_acceptance
        (
            user_legal_acceptance_id INT
                NOT NULL
                AUTO_INCREMENT,

            user_id INT
                NOT NULL,

            legal_document_version_id INT
                NOT NULL,

            accepted_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            acceptance_source ENUM(
                'Registration',
                'Reconsent'
            ) NOT NULL
              DEFAULT 'Registration',

            user_agent VARCHAR(500)
                NULL
                DEFAULT NULL,

            created_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (
                user_legal_acceptance_id
            ),

            UNIQUE KEY uq_user_legal_acceptance
            (
                user_id,
                legal_document_version_id
            ),

            KEY idx_user_legal_acceptance_user
            (
                user_id,
                accepted_at
            ),

            KEY idx_user_legal_acceptance_document
            (
                legal_document_version_id,
                accepted_at
            ),

            CONSTRAINT fk_user_legal_acceptance_user
                FOREIGN KEY (user_id)
                REFERENCES user (user_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE,

            CONSTRAINT fk_user_legal_acceptance_document
                FOREIGN KEY (
                    legal_document_version_id
                )
                REFERENCES legal_document_version (
                    legal_document_version_id
                )
                ON DELETE RESTRICT
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "User legal acceptance table ready.";
    echo PHP_EOL;

    /* ==========================================
       OPTIONAL USER CONSENT
    ========================================== */

    $conn->query("
        CREATE TABLE IF NOT EXISTS user_optional_consent
        (
            user_optional_consent_id INT
                NOT NULL
                AUTO_INCREMENT,

            user_id INT
                NOT NULL,

            consent_type ENUM(
                'SensitiveSurveyData'
            ) NOT NULL,

            consent_version VARCHAR(30)
                NOT NULL,

            granted TINYINT(1)
                NOT NULL
                DEFAULT 0,

            responded_at DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            withdrawn_at DATETIME
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
                user_optional_consent_id
            ),

            UNIQUE KEY uq_user_optional_consent
            (
                user_id,
                consent_type,
                consent_version
            ),

            KEY idx_optional_consent_lookup
            (
                consent_type,
                consent_version,
                granted
            ),

            CONSTRAINT fk_user_optional_consent_user
                FOREIGN KEY (user_id)
                REFERENCES user (user_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci
    ");

    echo "Optional consent table ready.";
    echo PHP_EOL;

    /* ==========================================
       INITIAL TERMS VERSION
    ========================================== */

    $conn->query("
        INSERT INTO legal_document_version
        (
            document_type,
            version,
            title,
            effective_at,
            status
        )
        VALUES
        (
            'Terms',
            '1.0',
            'OLSHCO Digital Hub Terms and Conditions',
            NOW(),
            'Active'
        )
        ON DUPLICATE KEY UPDATE
            title = VALUES(title)
    ");

    /* ==========================================
       INITIAL PRIVACY VERSION
    ========================================== */

    $conn->query("
        INSERT INTO legal_document_version
        (
            document_type,
            version,
            title,
            effective_at,
            status
        )
        VALUES
        (
            'Privacy',
            '1.0',
            'OLSHCO Digital Hub Privacy Notice',
            NOW(),
            'Active'
        )
        ON DUPLICATE KEY UPDATE
            title = VALUES(title)
    ");

    echo "Initial Terms and Privacy versions ready.";
    echo PHP_EOL;

    $conn->commit();

    echo "Terms, Privacy, and consent migration completed.";
    echo PHP_EOL;
} catch (Throwable $exception) {
    $conn->rollback();

    echo "Terms, Privacy, and consent migration failed.";
    echo PHP_EOL;

    echo "Error: ";
    echo $exception->getMessage();
    echo PHP_EOL;

    exit(1);
}
