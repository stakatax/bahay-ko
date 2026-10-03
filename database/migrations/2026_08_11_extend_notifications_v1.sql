/* ==========================================
   NOTIFICATIONS V1
   Extend existing per-user notifications
========================================== */

ALTER TABLE notification

    ADD COLUMN notification_type
        ENUM(
            'system',
            'content',
            'reminder',
            'workflow'
        )
        NOT NULL
        DEFAULT 'system'
        AFTER user_id,

    ADD COLUMN content_type
        ENUM(
            'announcement',
            'event',
            'document',
            'survey'
        )
        NULL
        AFTER message,

    ADD COLUMN content_id
        INT(11)
        NULL
        AFTER content_type,

    ADD COLUMN read_at
        DATETIME
        NULL
        AFTER is_read,

    ADD COLUMN deduplication_key
        VARCHAR(191)
        NULL
        AFTER read_at,

    ADD INDEX idx_notification_user_unread_created (
        user_id,
        is_read,
        created_at
    ),

    ADD INDEX idx_notification_content (
        content_type,
        content_id
    ),

    ADD UNIQUE INDEX uq_notification_user_deduplication (
        user_id,
        deduplication_key
    );


/* ==========================================
   BACKFILL READ TIMESTAMPS
========================================== */

UPDATE notification

SET read_at =
    COALESCE(
        read_at,
        created_at
    )

WHERE is_read = 1;


/* ==========================================
   ENSURE ACTIVE USERS HAVE PREFERENCES
========================================== */

INSERT INTO notification_preference (
    user_id,
    email_enabled,
    system_enabled
)

SELECT
    user.user_id,
    1,
    1

FROM user

LEFT JOIN notification_preference
    ON notification_preference.user_id =
       user.user_id

WHERE user.status = 'Active'
  AND notification_preference.preference_id
        IS NULL;