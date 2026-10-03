<?php

require_once __DIR__
    . '/BaseModel.php';

class Notification extends BaseModel
{
    private const NOTIFICATION_TYPES = [
        'system',
        'content',
        'reminder',
        'workflow'
    ];

    private const CONTENT_TYPES = [
        'announcement',
        'event',
        'document',
        'survey'
    ];

    private const NOTIFICATION_CATEGORIES = [
        'content_updates',
        'discussion',
        'engagement',
        'reminders',
        'workflow',
        'account_system'
    ];

    /* ==========================================
       CREATE DEDUPLICATED NOTIFICATION
    ========================================== */

    public function createForUser(
        int $userId,
        string $notificationType,
        string $title,
        string $message,
        ?string $contentType = null,
        ?int $contentId = null,
        ?string $deduplicationKey = null,
        bool $inSystemVisible = true
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid notification user ID.'
            );
        }

        $notificationType =
            strtolower(
                trim($notificationType)
            );

        if (
            !in_array(
                $notificationType,
                self::NOTIFICATION_TYPES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid notification type.'
            );
        }

        $title =
            trim($title);

        $message =
            trim($message);

        if ($title === '') {
            throw new InvalidArgumentException(
                'Notification title is required.'
            );
        }

        if ($message === '') {
            throw new InvalidArgumentException(
                'Notification message is required.'
            );
        }

        if (mb_strlen($title) > 255) {
            $title =
                mb_substr(
                    $title,
                    0,
                    255
                );
        }

        $contentType =
            $this->normalizeContentType(
                $contentType
            );

        if ($contentType === null) {
            $contentId = null;
        } elseif (
            $contentId === null ||
            $contentId <= 0
        ) {
            throw new InvalidArgumentException(
                'A valid content ID is required for content notifications.'
            );
        }

        if ($deduplicationKey !== null) {
            $deduplicationKey =
                trim(
                    $deduplicationKey
                );

            if ($deduplicationKey === '') {
                $deduplicationKey = null;
            } elseif (
                mb_strlen(
                    $deduplicationKey
                ) > 191
            ) {
                $deduplicationKey =
                    mb_substr(
                        $deduplicationKey,
                        0,
                        191
                    );
            }
        }

        $notificationCategory =
            $this->resolveNotificationCategory(
                $notificationType,
                $deduplicationKey
            );

        $categorySystemEnabled =
            $this->isCategorySystemEnabled(
                $userId,
                $notificationCategory
            );

        /*
         * Preserve the caller's existing visibility
         * restriction and additionally apply the
         * recipient's category preference.
         *
         * The notification row is still created when
         * hidden so eligible email and browser delivery
         * can continue independently.
         */
        $inSystemVisibleValue =
            $inSystemVisible &&
            $categorySystemEnabled
            ? 1
            : 0;

        $stmt =
            $this->prepare("
            INSERT INTO notification (
                user_id,
                notification_type,
                title,
                message,
                content_type,
                content_id,
                in_system_visible,
                deduplication_key
            )

            VALUES (
                      ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
            ON DUPLICATE KEY UPDATE notification_id = notification_id
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare notification creation: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'issssiis',
            $userId,
            $notificationType,
            $title,
            $message,
            $contentType,
            $contentId,
            $inSystemVisibleValue,
            $deduplicationKey
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to create notification: '
                    . $error
            );
        }

        /*
         * Zero affected rows means the unique
         * deduplication key already existed.
         */
        $created =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $created;
    }

    /* ==========================================
       SYSTEM NOTIFICATION PREFERENCE
    ========================================== */

    public function isSystemEnabled(
        int $userId
    ): bool {
        if ($userId <= 0) {
            return false;
        }

        $stmt =
            $this->prepare("
            SELECT
                system_enabled

            FROM notification_preference

            WHERE user_id = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare notification preference lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load notification preference: '
                    . $error
            );
        }

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        /*
         * Missing preference rows default to
         * enabled for backward compatibility.
         */
        return $row === null
            ? true
            : !empty($row['system_enabled']);
    }


    public function getPreferences(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid notification preference user ID.'
            );
        }

        $stmt =
            $this->prepare("
SELECT
    email_enabled,
    system_enabled,
    browser_push_enabled

            FROM notification_preference

            WHERE user_id = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare notification preferences: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load notification preferences: '
                    . $error
            );
        }

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        /*
     * Missing preference rows default to enabled
     * to preserve the existing delivery behavior.
     */
        return [
            'email_enabled' =>
            $row === null
                ? false
                : !empty($row['email_enabled']),

            'system_enabled' =>
            $row === null
                ? true
                : !empty($row['system_enabled']),

            'browser_push_enabled' =>
            $row === null
                ? false
                : !empty($row['browser_push_enabled'])
        ];
    }

    public function updateSystemPreference(
        int $userId,
        bool $systemEnabled
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid notification preference user ID.'
            );
        }

        $systemEnabledValue =
            $systemEnabled
            ? 1
            : 0;

        /*
     * email_enabled is preserved when a row exists.
     * New rows receive the schema default of enabled.
     */
        $stmt =
            $this->prepare("
            INSERT INTO notification_preference (
                user_id,
                system_enabled
            )

            VALUES (
                ?,
                ?
            )

            ON DUPLICATE KEY UPDATE
                system_enabled =
                    VALUES(system_enabled)
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare notification preference update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $userId,
            $systemEnabledValue
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update notification preference: '
                    . $error
            );
        }

        $stmt->close();

        return true;
    }

    public function updateEmailPreference(
        int $userId,
        bool $emailEnabled
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid email preference user ID.'
            );
        }

        $emailEnabledValue =
            $emailEnabled
            ? 1
            : 0;

        $stmt =
            $this->prepare("
            INSERT INTO notification_preference
            (
                user_id,
                email_enabled,
                email_enabled_at
            )

            VALUES
            (
                ?,
                ?,
                CASE
                    WHEN ? = 1
                    THEN NOW()
                    ELSE NULL
                END
            )

            ON DUPLICATE KEY UPDATE

                email_enabled_at =
                    CASE

                        WHEN
                            VALUES(email_enabled) = 0

                        THEN NULL

                        WHEN
                            email_enabled = 0
                            OR email_enabled_at
                                IS NULL

                        THEN NOW()

                        ELSE email_enabled_at

                    END,

                email_enabled =
                    VALUES(email_enabled)
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare email preference update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iii',
            $userId,
            $emailEnabledValue,
            $emailEnabledValue
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update email preference: '
                    . $error
            );
        }

        $stmt->close();

        return true;
    }

    public function updateBrowserPushPreference(
        int $userId,
        bool $browserPushEnabled
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid browser push preference user ID.'
            );
        }

        $browserPushEnabledValue =
            $browserPushEnabled
            ? 1
            : 0;

        $stmt =
            $this->prepare("
            INSERT INTO notification_preference
            (
                user_id,
                browser_push_enabled
            )

            VALUES
            (
                ?,
                ?
            )

            ON DUPLICATE KEY UPDATE

                browser_push_enabled =
                    VALUES(
                        browser_push_enabled
                    )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare browser push preference update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $userId,
            $browserPushEnabledValue
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update browser push preference: '
                    . $error
            );
        }

        $stmt->close();

        return true;
    }




    /* ==========================================
       RESOLVE NOTIFICATION CATEGORY
    ========================================== */

    private function resolveNotificationCategory(
        string $notificationType,
        ?string $deduplicationKey
    ): string {
        $normalizedType =
            strtolower(
                trim(
                    $notificationType
                )
            );

        $normalizedKey =
            strtolower(
                trim(
                    (string) $deduplicationKey
                )
            );

        if (
            str_starts_with(
                $normalizedKey,
                'engagement:reply:'
            ) ||
            str_starts_with(
                $normalizedKey,
                'engagement:comment:'
            )
        ) {
            return 'discussion';
        }

        if (
            str_starts_with(
                $normalizedKey,
                'engagement:reaction:'
            ) ||
            str_starts_with(
                $normalizedKey,
                'engagement:acknowledgment:'
            ) ||
            str_starts_with(
                $normalizedKey,
                'engagement:survey_response:'
            )
        ) {
            return 'engagement';
        }

        if ($normalizedType === 'reminder') {
            return 'reminders';
        }

        if ($normalizedType === 'workflow') {
            return 'workflow';
        }

        if (
            in_array(
                $normalizedType,
                [
                    'content',
                    'announcement',
                    'event',
                    'document',
                    'survey'
                ],
                true
            )
        ) {
            return 'content_updates';
        }

        return 'account_system';
    }


    /* ==========================================
       CHECK CATEGORY SYSTEM DELIVERY
    ========================================== */

    private function isCategorySystemEnabled(
        int $userId,
        string $notificationCategory
    ): bool {
        if (
            $userId <= 0 ||
            !in_array(
                $notificationCategory,
                self::NOTIFICATION_CATEGORIES,
                true
            )
        ) {
            return true;
        }

        $stmt =
            $this->prepare("
            SELECT system_enabled

            FROM notification_category_preference

            WHERE user_id = ?
              AND notification_category = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare category system preference lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'is',
            $userId,
            $notificationCategory
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load category system preference: '
                    . $error
            );
        }

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        /*
         * Missing rows default to enabled for backward
         * compatibility with users created before the
         * category-preference migration.
         */
        if (!$row) {
            return true;
        }

        return !empty($row['system_enabled']);
    }



    /* ==========================================
       CATEGORY PREFERENCES
    ========================================== */

    public function getCategoryPreferences(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid category preference user ID.'
            );
        }

        $preferences = [];

        foreach (
            self::NOTIFICATION_CATEGORIES
            as $category
        ) {
            $preferences[$category] = [
                'system_enabled' =>
                true,

                'email_enabled' =>
                true,

                'browser_push_enabled' =>
                true
            ];
        }

        $stmt =
            $this->prepare("
            SELECT
                notification_category,
                system_enabled,
                email_enabled,
                browser_push_enabled

            FROM notification_category_preference

            WHERE user_id = ?

            ORDER BY notification_category ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare category preferences: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load category preferences: '
                    . $error
            );
        }

        $result =
            $stmt->get_result();

        while (
            $row =
            $result->fetch_assoc()
        ) {
            $category =
                strtolower(
                    trim(
                        (string) (
                            $row['notification_category']
                            ?? ''
                        )
                    )
                );

            if (
                !in_array(
                    $category,
                    self::NOTIFICATION_CATEGORIES,
                    true
                )
            ) {
                continue;
            }

            $preferences[$category] = [
                'system_enabled' =>
                !empty($row['system_enabled']),

                'email_enabled' =>
                !empty($row['email_enabled']),

                'browser_push_enabled' =>
                !empty($row['browser_push_enabled'])
            ];
        }

        $stmt->close();

        return $preferences;
    }

    public function updateCategoryPreferences(
        int $userId,
        array $preferences
    ): bool {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid category preference user ID.'
            );
        }

        $actorStatement = $this->prepare(
            "SELECT r.role_prefix FROM user u
             INNER JOIN role r ON r.role_id = u.role_id
             WHERE u.user_id = ? AND u.status = 'Active' LIMIT 1"
        );
        if (!$actorStatement) {
            throw new RuntimeException('Unable to verify notification preference access.');
        }
        try {
            $actorStatement->bind_param('i', $userId);
            if (!$actorStatement->execute()) {
                throw new RuntimeException('Unable to verify notification preference access.');
            }
            $actor = $actorStatement->get_result()->fetch_assoc();
        } finally {
            $actorStatement->close();
        }
        if (!$actor) {
            throw new RuntimeException('An active account is required to update notification preferences.');
        }
        $canManageWorkflow = in_array($actor['role_prefix'], ['Admin', 'Faculty'], true);

        $stmt =
            $this->prepare("
            INSERT INTO
            notification_category_preference
            (
                user_id,
                notification_category,
                system_enabled,
                email_enabled,
                browser_push_enabled
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )

            ON DUPLICATE KEY UPDATE

                system_enabled =
                    VALUES(
                        system_enabled
                    ),

                email_enabled =
                    VALUES(
                        email_enabled
                    ),

                browser_push_enabled =
                    VALUES(
                        browser_push_enabled
                    )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare category preference update: '
                    . $this->conn->error
            );
        }

        $this->conn
            ->begin_transaction();

        try {
            foreach (
                self::NOTIFICATION_CATEGORIES
                as $category
            ) {
                if ($category === 'workflow' && !$canManageWorkflow) {
                    continue;
                }

                $categoryPreference =
                    $preferences[$category]
                    ?? [];

                if (
                    !is_array(
                        $categoryPreference
                    )
                ) {
                    $categoryPreference = [];
                }

                $systemEnabled =
                    !empty($categoryPreference['system_enabled'])
                    ? 1
                    : 0;

                $emailEnabled =
                    !empty($categoryPreference['email_enabled'])
                    ? 1
                    : 0;

                $browserPushEnabled =
                    !empty($categoryPreference['browser_push_enabled'])
                    ? 1
                    : 0;

                $stmt->bind_param(
                    'isiii',
                    $userId,
                    $category,
                    $systemEnabled,
                    $emailEnabled,
                    $browserPushEnabled
                );

                if (!$stmt->execute()) {
                    throw new RuntimeException(
                        'Unable to save the '
                            . $category
                            . ' notification preference: '
                            . $stmt->error
                    );
                }
            }

            $this->conn->commit();

            $stmt->close();

            return true;
        } catch (Throwable $exception) {
            $this->conn->rollback();

            $stmt->close();

            throw $exception;
        }
    }


    /* ==========================================
       CONTENT TYPE VALIDATION
    ========================================== */

    private function normalizeContentType(
        ?string $contentType
    ): ?string {
        if ($contentType === null) {
            return null;
        }

        $contentType =
            strtolower(
                trim($contentType)
            );

        if ($contentType === '') {
            return null;
        }

        if (
            !in_array(
                $contentType,
                self::CONTENT_TYPES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid notification content type.'
            );
        }

        return $contentType;
    }

    /* ==========================================
       USER NOTIFICATION LIST
    ========================================== */

    public function getForUser(
        int $userId,
        int $limit = 50
    ): array {
        if ($userId <= 0) {
            return [];
        }

        $limit =
            max(
                1,
                min(
                    $limit,
                    100
                )
            );

        $stmt =
            $this->prepare("
            SELECT
                notification_id,
                user_id,
                notification_type,
                title,
                message,
                            content_type,
            content_id,
            deduplication_key,
            is_read,
                read_at,
                created_at

            FROM notification

                        WHERE user_id = ?
              AND in_system_visible = 1

            ORDER BY
                created_at DESC,
                notification_id DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare notification list: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $userId,
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load notifications: '
                    . $error
            );
        }

        $items =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        foreach (
            $items
            as &$item
        ) {
            $item =
                $this->normalizeNotification(
                    $item
                );
        }

        unset($item);

        return $items;
    }

    /* ==========================================
       UNREAD COUNT
    ========================================== */

    public function countUnread(
        int $userId
    ): int {
        if ($userId <= 0) {
            return 0;
        }

        $stmt =
            $this->prepare("
            SELECT
                COUNT(*) AS total

            FROM notification

            WHERE user_id = ?
              AND in_system_visible = 1
              AND is_read = 0
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare unread notification count: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to count unread notifications: '
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
        );
    }

    /* ==========================================
       OWNERSHIP-SAFE LOOKUP
    ========================================== */

    public function findForUser(
        int $notificationId,
        int $userId
    ): ?array {
        if (
            $notificationId <= 0 ||
            $userId <= 0
        ) {
            return null;
        }

        $stmt =
            $this->prepare("
            SELECT
                notification_id,
                user_id,
                notification_type,
                title,
                message,
                           content_type,
            content_id,
            deduplication_key,
            is_read,
                read_at,
                created_at

            FROM notification

            WHERE notification_id = ?
              AND user_id = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare notification lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $notificationId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load notification: '
                    . $error
            );
        }

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $row
            ? $this->normalizeNotification(
                $row
            )
            : null;
    }

    /* ==========================================
       MARK ONE AS READ
    ========================================== */

    public function markAsRead(
        int $notificationId,
        int $userId
    ): bool {
        if (
            $notificationId <= 0 ||
            $userId <= 0
        ) {
            return false;
        }

        $stmt =
            $this->prepare("
            UPDATE notification

            SET
                is_read = 1,
                read_at =
                    COALESCE(
                        read_at,
                        NOW()
                    )

            WHERE notification_id = ?
              AND user_id = ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare notification read update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $notificationId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to mark notification as read: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
       MARK ALL AS READ
    ========================================== */

    public function markAllAsRead(
        int $userId
    ): int {
        if ($userId <= 0) {
            return 0;
        }

        $stmt =
            $this->prepare("
            UPDATE notification

            SET
                is_read = 1,
                read_at =
                    COALESCE(
                        read_at,
                        NOW()
                    )

            WHERE user_id = ?
              AND in_system_visible = 1
              AND is_read = 0
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare mark-all-read update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to mark all notifications as read: '
                    . $error
            );
        }

        $updated =
            max(
                0,
                $stmt->affected_rows
            );

        $stmt->close();

        return $updated;
    }

    /* ==========================================
       NORMALIZE DATABASE ROW
    ========================================== */

    private function normalizeNotification(
        array $row
    ): array {
        $row['notification_id'] =
            (int) (
                $row['notification_id']
                ?? 0
            );

        $row['user_id'] =
            (int) (
                $row['user_id']
                ?? 0
            );

        $row['content_id'] =
            !empty($row['content_id'])
            ? (int) $row['content_id']
            : null;

        $row['is_read'] =
            !empty($row['is_read']);

        return $row;
    }
}
