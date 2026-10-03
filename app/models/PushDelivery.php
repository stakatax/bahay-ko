<?php

require_once __DIR__
    . '/BaseModel.php';

class PushDelivery extends BaseModel
{
    /* ==========================================
       QUEUE NEW ELIGIBLE NOTIFICATIONS
    ========================================== */

    public function queueEligible(
        int $limit = 500
    ): int {
        $limit =
            max(
                1,
                min(
                    2000,
                    $limit
                )
            );

        $stmt =
            $this->conn->prepare("
                INSERT IGNORE INTO push_delivery
                (
                    notification_id,
                    subscription_id,
                    delivery_status,
                    attempt_count,
                    queued_at
                )

                SELECT
                    notification.notification_id,
                    push_subscription.subscription_id,
                    'Pending',
                    0,
                    NOW()

                FROM notification

                INNER JOIN notification_preference
                    ON notification_preference.user_id =
                        notification.user_id

                   AND notification_preference.browser_push_enabled =
                        1

                LEFT JOIN notification_category_preference
                    AS category_preference

                    ON category_preference.user_id =
                        notification.user_id

                   AND category_preference.notification_category =
                        CASE

                            WHEN
                                notification.deduplication_key LIKE
                                    'engagement:reply:%'
                                OR notification.deduplication_key LIKE
                                    'engagement:comment:%'

                            THEN 'discussion'

                            WHEN
                                notification.deduplication_key LIKE
                                    'engagement:reaction:%'
                                OR notification.deduplication_key LIKE
                                    'engagement:acknowledgment:%'
                                OR notification.deduplication_key LIKE
                                    'engagement:survey_response:%'

                            THEN 'engagement'

                            WHEN notification.notification_type =
                                'reminder'

                            THEN 'reminders'

                            WHEN notification.notification_type =
                                'workflow'

                            THEN 'workflow'

                            WHEN notification.notification_type IN
                                (
                                    'announcement',
                                    'event',
                                    'document',
                                    'survey'
                                )

                            THEN 'content_updates'

                            ELSE 'account_system'

                        END

                INNER JOIN push_subscription
                    ON push_subscription.user_id =
                        notification.user_id

                   AND push_subscription.subscription_status =
                        'Active'

                WHERE COALESCE(
                        category_preference.browser_push_enabled,
                        1
                      ) = 1

                  AND notification.created_at >=
                    COALESCE(
                        push_subscription.last_used_at,
                        push_subscription.created_at
                    )

                ORDER BY
                    notification.notification_id ASC,
                    push_subscription.subscription_id ASC

                LIMIT ?
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare browser push queue: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to queue browser push deliveries: '
                    . $error
            );
        }

        $queued =
            $stmt->affected_rows;

        $stmt->close();

        return max(
            0,
            $queued
        );
    }

    /* ==========================================
       PENDING DELIVERY BATCH
    ========================================== */

    public function getPending(
        int $limit = 100
    ): array {
        $limit =
            max(
                1,
                min(
                    500,
                    $limit
                )
            );

        $stmt =
            $this->conn->prepare("
                SELECT
                    push_delivery.delivery_id,
                    push_delivery.notification_id,
                    push_delivery.subscription_id,
                    push_delivery.delivery_status,
                    push_delivery.attempt_count,

                    notification.user_id,
                    notification.notification_type,
                    notification.title,
                    notification.message,
                    notification.content_type,
                    notification.content_id,
                    notification.created_at,

                    push_subscription.endpoint,
                    push_subscription.public_key,
                    push_subscription.auth_token,
                    push_subscription.content_encoding

                FROM push_delivery

                INNER JOIN notification
                    ON notification.notification_id =
                        push_delivery.notification_id

                INNER JOIN push_subscription
                    ON push_subscription.subscription_id =
                        push_delivery.subscription_id

                INNER JOIN notification_preference
                    ON notification_preference.user_id =
                        notification.user_id

                LEFT JOIN notification_category_preference
                    AS pending_category_preference

                    ON pending_category_preference.user_id =
                        notification.user_id

                   AND pending_category_preference.notification_category =
                        CASE

                            WHEN
                                notification.deduplication_key LIKE
                                    'engagement:reply:%'
                                OR notification.deduplication_key LIKE
                                    'engagement:comment:%'

                            THEN 'discussion'

                            WHEN
                                notification.deduplication_key LIKE
                                    'engagement:reaction:%'
                                OR notification.deduplication_key LIKE
                                    'engagement:acknowledgment:%'
                                OR notification.deduplication_key LIKE
                                    'engagement:survey_response:%'

                            THEN 'engagement'

                            WHEN notification.notification_type =
                                'reminder'

                            THEN 'reminders'

                            WHEN notification.notification_type =
                                'workflow'

                            THEN 'workflow'

                            WHEN notification.notification_type IN
                                (
                                    'announcement',
                                    'event',
                                    'document',
                                    'survey'
                                )

                            THEN 'content_updates'

                            ELSE 'account_system'

                        END

                WHERE push_subscription.subscription_status =
                        'Active'

                  AND notification_preference.browser_push_enabled =
                        1

                  AND COALESCE(
                        pending_category_preference
                            .browser_push_enabled,
                        1
                      ) = 1

                  AND (
                        push_delivery.delivery_status =
                            'Pending'

                        OR (
                            push_delivery.delivery_status =
                                'Failed'

                            AND push_delivery.attempt_count < 3

                            AND push_delivery.last_attempt_at <=
                                DATE_SUB(
                                    NOW(),
                                    INTERVAL 5 MINUTE
                                )
                        )
                  )

                ORDER BY
                    push_delivery.queued_at ASC,
                    push_delivery.delivery_id ASC

                LIMIT ?
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare pending browser push lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load pending browser push deliveries: '
                    . $error
            );
        }

        $deliveries =
            $stmt->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach (
            $deliveries as &$delivery
        ) {
            foreach (
                [
                    'delivery_id',
                    'notification_id',
                    'subscription_id',
                    'attempt_count',
                    'user_id',
                    'content_id'
                ]
                as $integerField
            ) {
                $delivery[$integerField] =
                    isset(
                        $delivery[$integerField]
                    )
                    ? (int) $delivery[$integerField]
                    : null;
            }
        }

        unset($delivery);

        return $deliveries;
    }

    /* ==========================================
       SUCCESSFUL DELIVERY
    ========================================== */

    public function markSent(
        int $deliveryId,
        ?int $httpStatus = null
    ): void {
        if ($deliveryId <= 0) {
            throw new InvalidArgumentException(
                'Invalid push delivery ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
                UPDATE push_delivery

                SET
                    delivery_status =
                        'Sent',

                    attempt_count =
                        attempt_count + 1,

                    last_http_status =
                        ?,

                    last_error =
                        NULL,

                    last_attempt_at =
                        NOW(),

                    sent_at =
                        NOW()

                WHERE delivery_id = ?
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare successful push delivery update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $httpStatus,
            $deliveryId
        );

        $stmt->execute();
        $stmt->close();
    }

    /* ==========================================
       FAILED OR EXPIRED DELIVERY
    ========================================== */

    public function markFailed(
        int $deliveryId,
        int $subscriptionId,
        ?int $httpStatus,
        string $error,
        bool $expired
    ): void {
        if (
            $deliveryId <= 0 ||
            $subscriptionId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid failed push delivery reference.'
            );
        }

        $error =
            mb_substr(
                trim($error),
                0,
                1000
            );

        $deliveryStatus =
            $expired
            ? 'Expired'
            : 'Failed';

        $subscriptionStatus =
            $expired
            ? 'Expired'
            : 'Active';

        $this->conn->begin_transaction();

        try {
            $deliveryStmt =
                $this->conn->prepare("
                    UPDATE push_delivery

                    SET
                        delivery_status =
                            ?,

                        attempt_count =
                            attempt_count + 1,

                        last_http_status =
                            ?,

                        last_error =
                            ?,

                        last_attempt_at =
                            NOW(),

                        sent_at =
                            NULL

                    WHERE delivery_id = ?
                ");

            if (!$deliveryStmt) {
                throw new RuntimeException(
                    'Unable to prepare failed push delivery update: '
                        . $this->conn->error
                );
            }

            $deliveryStmt->bind_param(
                'sisi',
                $deliveryStatus,
                $httpStatus,
                $error,
                $deliveryId
            );

            $deliveryStmt->execute();
            $deliveryStmt->close();

            $subscriptionStmt =
                $this->conn->prepare("
                    UPDATE push_subscription

                    SET
                        subscription_status =
                            ?,

                        failure_count =
                            failure_count + 1,

                        failed_at =
                            NOW()

                    WHERE subscription_id = ?
                ");

            if (!$subscriptionStmt) {
                throw new RuntimeException(
                    'Unable to prepare failed subscription update: '
                        . $this->conn->error
                );
            }

            $subscriptionStmt->bind_param(
                'si',
                $subscriptionStatus,
                $subscriptionId
            );

            $subscriptionStmt->execute();
            $subscriptionStmt->close();

            $this->conn->commit();
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw $exception;
        }
    }
}
