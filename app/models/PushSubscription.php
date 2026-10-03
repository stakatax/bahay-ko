<?php

require_once __DIR__
    . '/BaseModel.php';
require_once __DIR__ . '/../../config/push-endpoint.php';

class PushSubscription extends BaseModel
{
    /* ==========================================
       SAVE OR REFRESH SUBSCRIPTION
    ========================================== */

    public function save(
        int $userId,
        array $subscription,
        ?string $userAgent = null,
        ?string $deviceLabel = null
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid push subscription user ID.'
            );
        }

        $endpoint = validateBrowserPushEndpoint($subscription['endpoint'] ?? '');

        $keys =
            is_array(
                $subscription['keys']
                    ?? null
            )
            ? $subscription['keys']
            : [];

        validateBrowserPushKeys($keys);

        $publicKey =
            trim(
                (string) (
                    $keys['p256dh']
                    ?? ''
                )
            );

        $authToken =
            trim(
                (string) (
                    $keys['auth']
                    ?? ''
                )
            );

        $contentEncoding =
            trim(
                (string) (
                    $subscription['contentEncoding']
                    ?? 'aes128gcm'
                )
            );

        if (
            $publicKey === '' ||
            $authToken === ''
        ) {
            throw new InvalidArgumentException(
                'The browser push encryption keys are missing.'
            );
        }

        if (
            !in_array(
                $contentEncoding,
                [
                    'aes128gcm',
                    'aesgcm'
                ],
                true
            )
        ) {
            $contentEncoding =
                'aes128gcm';
        }

        $endpointHash =
            hash(
                'sha256',
                $endpoint
            );

        $userAgent =
            $userAgent !== null
            ? mb_substr(
                trim($userAgent),
                0,
                500
            )
            : null;

        $deviceLabel =
            $deviceLabel !== null
            ? mb_substr(
                trim($deviceLabel),
                0,
                100
            )
            : null;

        $this->conn->begin_transaction();

        try {
            /*
             * A browser subscription can move to a
             * different logged-in account. Delete its
             * old ownership and delivery history first.
             */
            $existingStmt =
                $this->conn->prepare("
                    SELECT
                        subscription_id,
                        user_id

                    FROM push_subscription

                    WHERE endpoint_hash = ?

                    LIMIT 1

                    FOR UPDATE
                ");

            if (!$existingStmt) {
                throw new RuntimeException(
                    'Unable to prepare push subscription lookup: '
                        . $this->conn->error
                );
            }

            $existingStmt->bind_param(
                's',
                $endpointHash
            );

            $existingStmt->execute();

            $existing =
                $existingStmt
                ->get_result()
                ->fetch_assoc();

            $existingStmt->close();

            if (
                $existing &&
                (int) $existing['user_id']
                !== $userId
            ) {
                $subscriptionId =
                    (int) (
                        $existing['subscription_id']
                        ?? 0
                    );

                $deleteStmt =
                    $this->conn->prepare("
                        DELETE FROM push_subscription

                        WHERE subscription_id = ?
                    ");

                if (!$deleteStmt) {
                    throw new RuntimeException(
                        'Unable to prepare previous push subscription cleanup: '
                            . $this->conn->error
                    );
                }

                $deleteStmt->bind_param(
                    'i',
                    $subscriptionId
                );

                $deleteStmt->execute();
                $deleteStmt->close();
            }

            $stmt =
                $this->conn->prepare("
                    INSERT INTO push_subscription
                    (
                        user_id,
                        endpoint_hash,
                        endpoint,
                        public_key,
                        auth_token,
                        content_encoding,
                        user_agent,
                        device_label,
                        subscription_status,
                        failure_count,
                        last_used_at,
                        failed_at,
                        created_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Active',
                        0,
                        NOW(),
                        NULL,
                        NOW()
                    )

                    ON DUPLICATE KEY UPDATE

                        user_id =
                            VALUES(user_id),

                        endpoint =
                            VALUES(endpoint),

                        public_key =
                            VALUES(public_key),

                        auth_token =
                            VALUES(auth_token),

                        content_encoding =
                            VALUES(content_encoding),

                        user_agent =
                            VALUES(user_agent),

                        device_label =
                            VALUES(device_label),

                        subscription_status =
                            'Active',

                        failure_count =
                            0,

                        last_used_at =
                            NOW(),

                        failed_at =
                            NULL
                ");

            if (!$stmt) {
                throw new RuntimeException(
                    'Unable to prepare push subscription save: '
                        . $this->conn->error
                );
            }

            $stmt->bind_param(
                'isssssss',
                $userId,
                $endpointHash,
                $endpoint,
                $publicKey,
                $authToken,
                $contentEncoding,
                $userAgent,
                $deviceLabel
            );

            $stmt->execute();
            $stmt->close();

            $saved =
                $this->findActiveByEndpoint(
                    $userId,
                    $endpoint
                );

            if (!$saved) {
                throw new RuntimeException(
                    'The browser push subscription could not be restored.'
                );
            }

            $this->conn->commit();

            return $saved;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }

    /* ==========================================
       ACTIVE SUBSCRIPTIONS
    ========================================== */

    public function getActiveByUserId(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [];
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    subscription_id,
                    user_id,
                    endpoint,
                    public_key,
                    auth_token,
                    content_encoding,
                    user_agent,
                    device_label,
                    subscription_status,
                    failure_count,
                    last_used_at,
                    created_at,
                    updated_at

                FROM push_subscription

                WHERE user_id = ?
                  AND subscription_status = 'Active'

                ORDER BY
                    last_used_at DESC,
                    subscription_id DESC
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare active push subscription lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        $stmt->execute();

        $subscriptions =
            $stmt->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach (
            $subscriptions as &$subscription
        ) {
            $subscription['subscription_id'] =
                (int) $subscription['subscription_id'];

            $subscription['user_id'] =
                (int) $subscription['user_id'];

            $subscription['failure_count'] =
                (int) $subscription['failure_count'];
        }

        unset($subscription);

        return $subscriptions;
    }

    public function findActiveByEndpoint(
        int $userId,
        string $endpoint
    ): ?array {
        if (
            $userId <= 0 ||
            trim($endpoint) === ''
        ) {
            return null;
        }

        $endpointHash =
            hash(
                'sha256',
                trim($endpoint)
            );

        $stmt =
            $this->conn->prepare("
                SELECT
                    subscription_id,
                    user_id,
                    endpoint,
                    public_key,
                    auth_token,
                    content_encoding,
                    user_agent,
                    device_label,
                    subscription_status,
                    failure_count,
                    last_used_at,
                    created_at,
                    updated_at

                FROM push_subscription

                WHERE user_id = ?
                  AND endpoint_hash = ?
                  AND subscription_status = 'Active'

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare push subscription verification: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'is',
            $userId,
            $endpointHash
        );

        $stmt->execute();

        $subscription =
            $stmt->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$subscription) {
            return null;
        }

        $subscription['subscription_id'] =
            (int) $subscription['subscription_id'];

        $subscription['user_id'] =
            (int) $subscription['user_id'];

        $subscription['failure_count'] =
            (int) $subscription['failure_count'];

        return $subscription;
    }

    /* ==========================================
       REVOKE SUBSCRIPTION
    ========================================== */

    public function revoke(
        int $userId,
        string $endpoint
    ): bool {
        if (
            $userId <= 0 ||
            trim($endpoint) === ''
        ) {
            return false;
        }

        $endpointHash =
            hash(
                'sha256',
                trim($endpoint)
            );

        $stmt =
            $this->conn->prepare("
                UPDATE push_subscription

                SET
                    subscription_status =
                        'Revoked',

                    updated_at =
                        NOW()

                WHERE user_id = ?
                  AND endpoint_hash = ?
                  AND subscription_status = 'Active'
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare push subscription revocation: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'is',
            $userId,
            $endpointHash
        );

        $stmt->execute();

        $revoked =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $revoked;
    }
}
