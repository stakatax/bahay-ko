<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../../config/dbconnect.php';

echo "Starting password recovery migration..."
    . PHP_EOL;

/* ==========================================
   PASSWORD RESET REQUEST THROTTLING
========================================== */

if (
    !$conn->query("
        CREATE TABLE IF NOT EXISTS
            password_reset_request
        (
            password_reset_request_id
                BIGINT UNSIGNED
                NOT NULL
                AUTO_INCREMENT,

            user_id
                INT(11)
                NULL,

            identifier_hash
                CHAR(64)
                CHARACTER SET ascii
                COLLATE ascii_bin
                NOT NULL,

            request_ip_hash
                CHAR(64)
                CHARACTER SET ascii
                COLLATE ascii_bin
                NOT NULL,

            requested_at
                DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (
                password_reset_request_id
            ),

            KEY idx_password_reset_identifier (
                identifier_hash,
                requested_at
            ),

            KEY idx_password_reset_ip (
                request_ip_hash,
                requested_at
            ),

            KEY idx_password_reset_request_user (
                user_id,
                requested_at
            ),

            CONSTRAINT
                fk_password_reset_request_user

                FOREIGN KEY (
                    user_id
                )

                REFERENCES user (
                    user_id
                )

                ON UPDATE CASCADE
                ON DELETE SET NULL
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_general_ci
    ")
) {
    throw new RuntimeException(
        'Unable to create the password-reset request table: '
            . $conn->error
    );
}

echo "Password-reset request throttling table ready."
    . PHP_EOL;

/* ==========================================
   PASSWORD RESET TOKENS
========================================== */

if (
    !$conn->query("
        CREATE TABLE IF NOT EXISTS
            password_reset_token
        (
            password_reset_token_id
                BIGINT UNSIGNED
                NOT NULL
                AUTO_INCREMENT,

            user_id
                INT(11)
                NOT NULL,

            token_hash
                CHAR(64)
                CHARACTER SET ascii
                COLLATE ascii_bin
                NOT NULL,

            request_ip_hash
                CHAR(64)
                CHARACTER SET ascii
                COLLATE ascii_bin
                NOT NULL,

            user_agent_hash
                CHAR(64)
                CHARACTER SET ascii
                COLLATE ascii_bin
                NULL,

            expires_at
                DATETIME
                NOT NULL,

            used_at
                DATETIME
                NULL,

            invalidated_at
                DATETIME
                NULL,

            created_at
                DATETIME
                NOT NULL
                DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (
                password_reset_token_id
            ),

            UNIQUE KEY uq_password_reset_token_hash (
                token_hash
            ),

            KEY idx_password_reset_token_user (
                user_id,
                created_at
            ),

            KEY idx_password_reset_token_state (
                user_id,
                used_at,
                invalidated_at,
                expires_at
            ),

            CONSTRAINT
                fk_password_reset_token_user

                FOREIGN KEY (
                    user_id
                )

                REFERENCES user (
                    user_id
                )

                ON UPDATE CASCADE
                ON DELETE CASCADE
        )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_general_ci
    ")
) {
    throw new RuntimeException(
        'Unable to create the password-reset token table: '
            . $conn->error
    );
}

echo "Password-reset token table ready."
    . PHP_EOL;

/* ==========================================
   PASSWORD RECOVERY AUDIT ACTIONS
========================================== */

if (
    !$conn->query("
        INSERT IGNORE INTO actions
        (
            action_name
        )
        VALUES
            (
                'REQUEST_PASSWORD_RESET'
            ),
            (
                'COMPLETE_PASSWORD_RESET'
            )
    ")
) {
    throw new RuntimeException(
        'Unable to prepare password-recovery audit actions: '
            . $conn->error
    );
}

echo "Password recovery audit actions ready."
    . PHP_EOL;

echo "Password recovery migration completed successfully."
    . PHP_EOL;
