<?php

require_once __DIR__
    . '/BaseModel.php';

class EmailDelivery extends BaseModel
{
    private const MAX_ATTEMPTS = 3;

    /* ==========================================
       QUEUE ELIGIBLE NOTIFICATIONS
    ========================================== */

    public function queueEligible(
        int $limit = 500
    ): int {
        $limit =
            max(
                1,
                min(
                    $limit,
                    1000
                )
            );

        $stmt =
            $this->prepare("
            INSERT IGNORE INTO email_delivery
            (
                notification_id,
                recipient_email
            )

            SELECT
                n.notification_id,
                TRIM(u.email)

            FROM notification n

            INNER JOIN user u
                ON u.user_id =
                   n.user_id

            INNER JOIN notification_preference np
                ON np.user_id =
                   n.user_id

            LEFT JOIN notification_category_preference ncp
                ON ncp.user_id =
                   n.user_id

               AND ncp.notification_category =
                   CASE

                       WHEN
                           n.deduplication_key LIKE
                               'engagement:reply:%'
                           OR n.deduplication_key LIKE
                               'engagement:comment:%'

                       THEN 'discussion'

                       WHEN
                           n.deduplication_key LIKE
                               'engagement:reaction:%'
                           OR n.deduplication_key LIKE
                               'engagement:acknowledgment:%'
                           OR n.deduplication_key LIKE
                               'engagement:survey_response:%'

                       THEN 'engagement'

                       WHEN n.notification_type =
                           'reminder'

                       THEN 'reminders'

                       WHEN n.notification_type =
                           'workflow'

                       THEN 'workflow'

                       WHEN n.notification_type IN
                           (
                               'announcement',
                               'event',
                               'document',
                               'survey'
                           )

                       THEN 'content_updates'

                       ELSE 'account_system'

                   END

            WHERE u.status = 'Active'

              AND u.email IS NOT NULL

              AND TRIM(u.email) <> ''

              AND u.email LIKE '%@%'

              AND np.email_enabled = 1

              AND COALESCE(
                    ncp.email_enabled,
                    1
                  ) = 1

              AND np.email_enabled_at
                    IS NOT NULL

              AND n.created_at >=
                    np.email_enabled_at

              AND NOT EXISTS
              (
                  SELECT 1

                  FROM email_delivery existing

                  WHERE existing.notification_id =
                        n.notification_id
              )

            ORDER BY
                n.notification_id ASC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare email delivery queue: '
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
                'Unable to queue email deliveries: '
                    . $error
            );
        }

        $queued =
            max(
                0,
                $stmt->affected_rows
            );

        $stmt->close();

        return $queued;
    }

    /* ==========================================
       LOAD PENDING/RETRYABLE DELIVERIES
    ========================================== */

    public function getPending(
        int $limit = 100
    ): array {
        $limit =
            max(
                1,
                min(
                    $limit,
                    500
                )
            );

        $stmt =
            $this->prepare("
            SELECT
                ed.delivery_id,
                ed.notification_id,
                ed.recipient_email,
                ed.delivery_status,
                ed.attempt_count,
                ed.last_attempt_at,

                n.user_id,
                n.notification_type,
                n.title,
                n.message,
                n.content_type,
                n.content_id,
                n.deduplication_key,
                n.created_at,

                u.first_name,
                u.middle_name,
                u.last_name,
                u.name_suffix

            FROM email_delivery ed

            INNER JOIN notification n
                ON n.notification_id =
                   ed.notification_id
            INNER JOIN user u
                ON u.user_id =
                   n.user_id

            INNER JOIN notification_preference
                AS pending_np

                ON pending_np.user_id =
                   n.user_id

            LEFT JOIN notification_category_preference
                AS pending_ncp

                ON pending_ncp.user_id =
                   n.user_id

               AND pending_ncp.notification_category =
                   CASE

                       WHEN
                           n.deduplication_key LIKE
                               'engagement:reply:%'
                           OR n.deduplication_key LIKE
                               'engagement:comment:%'

                       THEN 'discussion'

                       WHEN
                           n.deduplication_key LIKE
                               'engagement:reaction:%'
                           OR n.deduplication_key LIKE
                               'engagement:acknowledgment:%'
                           OR n.deduplication_key LIKE
                               'engagement:survey_response:%'

                       THEN 'engagement'

                       WHEN n.notification_type =
                           'reminder'

                       THEN 'reminders'

                       WHEN n.notification_type =
                           'workflow'

                       THEN 'workflow'

                       WHEN n.notification_type IN
                           (
                               'announcement',
                               'event',
                               'document',
                               'survey'
                           )

                       THEN 'content_updates'

                       ELSE 'account_system'

                   END

            WHERE pending_np.email_enabled = 1

              AND COALESCE(
                    pending_ncp.email_enabled,
                    1
                  ) = 1

              AND (
                    ed.delivery_status =
                        'Pending'

                    OR (
                        ed.delivery_status =
                            'Failed'

                        AND ed.attempt_count < ?

                        AND (
                            ed.last_attempt_at
                                IS NULL

                            OR ed.last_attempt_at <=
                                DATE_SUB(
                                    NOW(),
                                    INTERVAL 5 MINUTE
                                )
                        )
                    )
                )

            ORDER BY
                ed.queued_at ASC,
                ed.delivery_id ASC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare pending email deliveries: '
                    . $this->conn->error
            );
        }

        $maximumAttempts =
            self::MAX_ATTEMPTS;

        $stmt->bind_param(
            'ii',
            $maximumAttempts,
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load pending email deliveries: '
                    . $error
            );
        }

        $deliveries =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $deliveries;
    }

    /* ==========================================
       MARK DELIVERY SENT
    ========================================== */

    public function markSent(
        int $deliveryId
    ): bool {
        if ($deliveryId <= 0) {
            return false;
        }

        $stmt =
            $this->prepare("
            UPDATE email_delivery

            SET
                delivery_status =
                    'Sent',

                attempt_count =
                    attempt_count + 1,

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
                'Unable to prepare successful email delivery update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $deliveryId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to mark email delivery sent: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
       MARK DELIVERY FAILED
    ========================================== */

    public function markFailed(
        int $deliveryId,
        string $errorMessage
    ): bool {
        if ($deliveryId <= 0) {
            return false;
        }

        $errorMessage =
            mb_substr(
                trim($errorMessage),
                0,
                1000
            );

        if ($errorMessage === '') {
            $errorMessage =
                'Unknown email delivery failure.';
        }

        $maximumAttempts =
            self::MAX_ATTEMPTS;

        $stmt =
            $this->prepare("
            UPDATE email_delivery

            SET
                attempt_count =
                    attempt_count + 1,

                delivery_status =
                    CASE

                        WHEN
                            attempt_count + 1 >= ?

                        THEN 'Skipped'

                        ELSE 'Failed'

                    END,

                last_error = ?,

                last_attempt_at =
                    NOW()

            WHERE delivery_id = ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare failed email delivery update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'isi',
            $maximumAttempts,
            $errorMessage,
            $deliveryId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to mark email delivery failed: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }
}
