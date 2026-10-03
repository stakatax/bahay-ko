<?php

require_once __DIR__
    . '/BaseModel.php';

class PasswordRecovery extends BaseModel
{
    /* ==========================================
       REQUEST THROTTLING
    ========================================== */

    public function getRecentRequestCounts(
        string $identifierHash,
        string $ipHash,
        int $minutes = 15
    ): array {
        $minutes =
            max(
                1,
                min(
                    1440,
                    $minutes
                )
            );

        $cutoff =
            date(
                'Y-m-d H:i:s',
                time() - ($minutes * 60)
            );

        $stmt =
            $this->prepare("
                SELECT
                    (
                        SELECT COUNT(*)

                        FROM password_reset_request

                        WHERE identifier_hash = ?
                          AND requested_at >= ?
                    ) AS identifier_count,

                    (
                        SELECT COUNT(*)

                        FROM password_reset_request

                        WHERE request_ip_hash = ?
                          AND requested_at >= ?
                    ) AS ip_count
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare password-reset throttling lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssss',
            $identifierHash,
            $cutoff,
            $ipHash,
            $cutoff
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to inspect password-reset request limits: '
                    . $error
            );
        }

        $counts =
            $stmt
            ->get_result()
            ->fetch_assoc()
            ?: [];

        $stmt->close();

        return [
            'identifier_count' =>
            (int) (
                $counts['identifier_count']
                ?? 0
            ),

            'ip_count' =>
            (int) (
                $counts['ip_count']
                ?? 0
            )
        ];
    }

    public function recordRequest(
        ?int $userId,
        string $identifierHash,
        string $ipHash
    ): void {
        $stmt =
            $this->prepare("
                INSERT INTO password_reset_request
                (
                    user_id,
                    identifier_hash,
                    request_ip_hash,
                    requested_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    NOW()
                )
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare the password-reset request log: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iss',
            $userId,
            $identifierHash,
            $ipHash
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to record the password-reset request: '
                    . $error
            );
        }

        $stmt->close();
    }

    /* ==========================================
       TOKEN CREATION
    ========================================== */

    public function replaceActiveToken(
        int $userId,
        string $tokenHash,
        string $ipHash,
        ?string $userAgentHash,
        string $expiresAt
    ): int {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid password-reset user ID.'
            );
        }

        $this->conn->begin_transaction();

        try {
            $invalidate =
                $this->prepare("
                    UPDATE password_reset_token

                    SET invalidated_at =
                        NOW()

                    WHERE user_id = ?
                      AND used_at IS NULL
                      AND invalidated_at IS NULL
                ");

            if (!$invalidate) {
                throw new RuntimeException(
                    'Unable to prepare existing token invalidation: '
                        . $this->conn->error
                );
            }

            $invalidate->bind_param(
                'i',
                $userId
            );

            if (!$invalidate->execute()) {
                $error =
                    $invalidate->error;

                $invalidate->close();

                throw new RuntimeException(
                    'Unable to invalidate existing password-reset tokens: '
                        . $error
                );
            }

            $invalidate->close();

            $insert =
                $this->prepare("
                    INSERT INTO password_reset_token
                    (
                        user_id,
                        token_hash,
                        request_ip_hash,
                        user_agent_hash,
                        expires_at,
                        created_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        NOW()
                    )
                ");

            if (!$insert) {
                throw new RuntimeException(
                    'Unable to prepare the password-reset token: '
                        . $this->conn->error
                );
            }

            $insert->bind_param(
                'issss',
                $userId,
                $tokenHash,
                $ipHash,
                $userAgentHash,
                $expiresAt
            );

            if (!$insert->execute()) {
                $error =
                    $insert->error;

                $insert->close();

                throw new RuntimeException(
                    'Unable to create the password-reset token: '
                        . $error
                );
            }

            $tokenId =
                (int) $this->conn->insert_id;

            $insert->close();

            $this->conn->commit();

            return $tokenId;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }

    public function invalidateToken(
        string $tokenHash
    ): void {
        $stmt =
            $this->prepare("
                UPDATE password_reset_token

                SET invalidated_at =
                    COALESCE(
                        invalidated_at,
                        NOW()
                    )

                WHERE token_hash = ?
                  AND used_at IS NULL
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare password-reset token invalidation: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            's',
            $tokenHash
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to invalidate the password-reset token: '
                    . $error
            );
        }

        $stmt->close();
    }

    /* ==========================================
       TOKEN LOOKUP
    ========================================== */

    public function findValidToken(
        string $tokenHash
    ): ?array {
        $stmt =
            $this->prepare("
                SELECT
                    prt.password_reset_token_id,
                    prt.user_id,
                    prt.expires_at,

                    u.email,
                    u.password AS current_password_hash,
                    u.first_name,
                    u.last_name,
                    u.status

                FROM password_reset_token prt

                INNER JOIN user u
                    ON u.user_id =
                        prt.user_id

                WHERE prt.token_hash = ?
                  AND prt.used_at IS NULL
                  AND prt.invalidated_at IS NULL
                  AND prt.expires_at > NOW()
                  AND u.status = 'Active'

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare password-reset token lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            's',
            $tokenHash
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to validate the password-reset token: '
                    . $error
            );
        }

        $token =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $token ?: null;
    }

    /* ==========================================
       COMPLETE PASSWORD RESET
    ========================================== */

    public function completeReset(
        string $tokenHash,
        string $passwordHash
    ): int {
        if (
            $tokenHash === '' ||
            trim($passwordHash) === ''
        ) {
            throw new InvalidArgumentException(
                'Password-reset credentials are required.'
            );
        }

        $this->conn->begin_transaction();

        try {
            $lookup =
                $this->prepare("
                    SELECT
                        prt.password_reset_token_id,
                        prt.user_id

                    FROM password_reset_token prt

                    INNER JOIN user u
                        ON u.user_id =
                            prt.user_id

                    WHERE prt.token_hash = ?
                      AND prt.used_at IS NULL
                      AND prt.invalidated_at IS NULL
                      AND prt.expires_at > NOW()
                      AND u.status = 'Active'

                    LIMIT 1

                    FOR UPDATE
                ");

            if (!$lookup) {
                throw new RuntimeException(
                    'Unable to prepare password-reset completion lookup: '
                        . $this->conn->error
                );
            }

            $lookup->bind_param(
                's',
                $tokenHash
            );

            if (!$lookup->execute()) {
                $error =
                    $lookup->error;

                $lookup->close();

                throw new RuntimeException(
                    'Unable to validate the password-reset request: '
                        . $error
                );
            }

            $token =
                $lookup
                ->get_result()
                ->fetch_assoc();

            $lookup->close();

            if (!$token) {
                throw new RuntimeException(
                    'This password-reset link is invalid or has expired.'
                );
            }

            $tokenId =
                (int) $token['password_reset_token_id'];

            $userId =
                (int) $token['user_id'];

            $updateUser =
                $this->prepare("
                    UPDATE user

                    SET
                        password = ?,
                        must_change_password = 0,
                        password_changed_at = NOW(),
                        failed_attempts = 0,
                        lock_until = NULL,
                        updated_at = NOW()

                    WHERE user_id = ?
                      AND status = 'Active'

                    LIMIT 1
                ");

            if (!$updateUser) {
                throw new RuntimeException(
                    'Unable to prepare the recovered password update: '
                        . $this->conn->error
                );
            }

            $updateUser->bind_param(
                'si',
                $passwordHash,
                $userId
            );

            if (!$updateUser->execute()) {
                $error =
                    $updateUser->error;

                $updateUser->close();

                throw new RuntimeException(
                    'Unable to update the recovered account password: '
                        . $error
                );
            }

            if ($updateUser->affected_rows !== 1) {
                $updateUser->close();

                throw new RuntimeException(
                    'The account is unavailable for password recovery.'
                );
            }

            $updateUser->close();

            $consume =
                $this->prepare("
                    UPDATE password_reset_token

                    SET used_at =
                        NOW()

                    WHERE password_reset_token_id = ?
                      AND used_at IS NULL
                      AND invalidated_at IS NULL
                ");

            if (!$consume) {
                throw new RuntimeException(
                    'Unable to prepare password-reset token completion: '
                        . $this->conn->error
                );
            }

            $consume->bind_param(
                'i',
                $tokenId
            );

            if (!$consume->execute()) {
                $error =
                    $consume->error;

                $consume->close();

                throw new RuntimeException(
                    'Unable to complete the password-reset token: '
                        . $error
                );
            }

            if ($consume->affected_rows !== 1) {
                $consume->close();

                throw new RuntimeException(
                    'The password-reset token was already used.'
                );
            }

            $consume->close();

            $invalidateOthers =
                $this->prepare("
                    UPDATE password_reset_token

                    SET invalidated_at =
                        NOW()

                    WHERE user_id = ?
                      AND password_reset_token_id <> ?
                      AND used_at IS NULL
                      AND invalidated_at IS NULL
                ");

            if (!$invalidateOthers) {
                throw new RuntimeException(
                    'Unable to prepare remaining token invalidation: '
                        . $this->conn->error
                );
            }

            $invalidateOthers->bind_param(
                'ii',
                $userId,
                $tokenId
            );

            if (!$invalidateOthers->execute()) {
                $error =
                    $invalidateOthers->error;

                $invalidateOthers->close();

                throw new RuntimeException(
                    'Unable to invalidate remaining password-reset tokens: '
                        . $error
                );
            }

            $invalidateOthers->close();

            $this->conn->commit();

            return $userId;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }
}
