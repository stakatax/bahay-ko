<?php

require_once __DIR__ . '/BaseModel.php';

class Announcement extends BaseModel
{
    /** One lookup for a visible batch; raw intake records never enter the feed. */
    public function attachGovernmentSources(array $announcements): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', array_column($announcements, 'announcement_id')), static fn(int $id): bool => $id > 0)));
        if ($ids === []) return $announcements;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->prepare("SELECT linked_content_id, source_url
            FROM government_advisory
            WHERE review_status = 'Converted' AND linked_content_type = 'announcement'
              AND linked_content_id IN ($placeholders)
            ORDER BY government_advisory_id ASC");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $sources = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $url = (string) $row['source_url'];
            $parts = parse_url($url);
            if (!filter_var($url, FILTER_VALIDATE_URL) || !is_array($parts)
                || strtolower($parts['scheme'] ?? '') !== 'https'
                || isset($parts['user']) || isset($parts['pass'])) continue;
            $sources[(int) $row['linked_content_id']] ??= $url;
        }
        $stmt->close();
        foreach ($announcements as &$announcement) {
            unset($announcement['government_source_url']);
            $id = (int) ($announcement['announcement_id'] ?? 0);
            if (isset($sources[$id])) $announcement['government_source_url'] = $sources[$id];
        }
        unset($announcement);
        return $announcements;
    }

    /* ==========================================
       CREATE ANNOUNCEMENT

       Supports both:
       - Existing legacy arguments
       - New workflow data array
    ========================================== */

    public function create(
        $dataOrTitle,
        $content = null,
        $imagePath = null,
        $userId = null
    ): int {
        /*
         * Legacy support:
         *
         * create(
         *     $title,
         *     $content,
         *     $imagePath,
         *     $userId
         * )
         */
        if (!is_array($dataOrTitle)) {
            $data = [
                'title' => $dataOrTitle,
                'content' => $content,
                'image_path' => $imagePath,
                'user_id' => $userId,

                'type' => 'announcement',
                'category' => 'general',
                'priority' => 'Medium',

                'workflow_status' => 'published',
                'release_mode' => 'immediate',
                'scheduled_publish_at' => null,
                'calendar_event_id' => null,

                'allow_reactions' => 1,
                'allow_comments' => 1,
                'require_acknowledgment' => 0,
                'send_notification' => 1,

                'published_at' => date('Y-m-d H:i:s'),
                'status' => 'active'
            ];
        } else {
            $data = $dataOrTitle;
        }

        $title = trim(
            (string) ($data['title'] ?? '')
        );

        $announcementContent = trim(
            (string) ($data['content'] ?? '')
        );

        $announcementImagePath =
            $data['image_path']
            ?? null;

        $audioPath =
            $this->normalizeNullableString(
                $data['audio_path']
                    ?? null
            );

        $audioFileName =
            $this->normalizeNullableString(
                $data['audio_file_name']
                    ?? null
            );

        $audioMimeType =
            $this->normalizeNullableString(
                $data['audio_mime_type']
                    ?? null
            );

        $audioFileSize =
            isset($data['audio_file_size']) &&
            $data['audio_file_size'] !== null
            ? (int) $data['audio_file_size']
            : null;

        $audioTranscript =
            $this->normalizeNullableString(
                $data['audio_transcript']
                    ?? null
            );



        if ($audioPath === null) {
            $audioFileName =
                null;

            $audioMimeType =
                null;

            $audioFileSize =
                null;

            $audioTranscript =
                null;
        }

        $announcementUserId =
            (int) ($data['user_id'] ?? 0);

        if ($title === '') {
            throw new InvalidArgumentException(
                'Announcement title is required.'
            );
        }

        if ($announcementContent === '') {
            throw new InvalidArgumentException(
                'Announcement content is required.'
            );
        }

        if ($announcementUserId <= 0) {
            throw new InvalidArgumentException(
                'A valid announcement author is required.'
            );
        }

        if (mb_strlen($title) > 150) {
            throw new InvalidArgumentException(
                'Announcement title cannot exceed 150 characters.'
            );
        }

        if (mb_strlen($announcementContent) > 5000) {
            throw new InvalidArgumentException(
                'Announcement content cannot exceed 5,000 characters.'
            );
        }

        $type = trim(
            (string) ($data['type'] ?? 'announcement')
        );

        $category = trim(
            (string) ($data['category'] ?? 'general')
        );

        $priority = trim(
            (string) ($data['priority'] ?? 'Normal')
        );

        $workflowStatus = trim(
            (string) (
                $data['workflow_status']
                ?? 'draft'
            )
        );

        $releaseMode = trim(
            (string) (
                $data['release_mode']
                ?? 'immediate'
            )
        );

        $scheduledPublishAt =
            $data['scheduled_publish_at']
            ?? null;

        $calendarEventId =
            !empty($data['calendar_event_id'])
            ? (int) $data['calendar_event_id']
            : null;

        $allowReactions =
            !empty($data['allow_reactions'])
            ? 1
            : 0;

        $allowComments =
            !empty($data['allow_comments'])
            ? 1
            : 0;

        $requireAcknowledgment =
            !empty($data['require_acknowledgment'])
            ? 1
            : 0;

        $sendNotification =
            !empty($data['send_notification'])
            ? 1
            : 0;

        $publishedAt =
            $data['published_at']
            ?? null;

        $status = trim(
            (string) ($data['status'] ?? 'active')
        );

        $allowedPriorities = [
            'Low',
            'Normal',
            'Medium',
            'High',
            'Important',
            'Urgent',
            'Emergency'
        ];

        if (
            !in_array(
                $priority,
                $allowedPriorities,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement priority.'
            );
        }

        $allowedWorkflowStatuses = [
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'scheduled',
            'published',
            'archived'
        ];

        if (
            !in_array(
                $workflowStatus,
                $allowedWorkflowStatuses,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement workflow status.'
            );
        }

        $allowedReleaseModes = [
            'immediate',
            'scheduled',
            'calendar'
        ];

        if (
            !in_array(
                $releaseMode,
                $allowedReleaseModes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement release mode.'
            );
        }

        /*
         * Published content must have a publication date.
         */
        if (
            $workflowStatus === 'published' &&
            empty($publishedAt)
        ) {
            $publishedAt =
                date('Y-m-d H:i:s');
        }

        /*
         * Only scheduled releases should store this value.
         */
        if ($releaseMode !== 'scheduled') {
            $scheduledPublishAt = null;
        }

        /*
         * Only calendar-based releases should reference
         * an event.
         */
        if ($releaseMode !== 'calendar') {
            $calendarEventId = null;
        }

        /*
         * Emergency content always requires acknowledgment
         * and notification.
         */
        if ($priority === 'Emergency') {
            $requireAcknowledgment = 1;
            $sendNotification = 1;
        }

        $stmt = $this->conn->prepare("
            INSERT INTO announcements
            (
                title,
                content,
                image_path,
                audio_path,
                audio_file_name,
                audio_mime_type,
                audio_file_size,
                audio_transcript,
                type,
                category,
                priority,
                created_at,
                published_at,
                status,
                workflow_status,
                release_mode,
                scheduled_publish_at,
                calendar_event_id,
                allow_reactions,
                allow_comments,
                require_acknowledgment,
                send_notification,
                user_id
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
                ?,
                ?,
                ?,
                NOW(),
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement insert: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssssssisssssssssiiiiii',
            $title,
            $announcementContent,
            $announcementImagePath,
            $audioPath,
            $audioFileName,
            $audioMimeType,
            $audioFileSize,
            $audioTranscript,
            $type,
            $category,
            $priority,
            $publishedAt,
            $status,
            $workflowStatus,
            $releaseMode,
            $scheduledPublishAt,
            $calendarEventId,
            $allowReactions,
            $allowComments,
            $requireAcknowledgment,
            $sendNotification,
            $announcementUserId
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save announcement: '
                    . $error
            );
        }

        $announcementId =
            (int) $this->conn->insert_id;

        $stmt->close();

        return $announcementId;
    }


    /* ==========================================
       UPDATE ANNOUNCEMENT
    ========================================== */

    public function update(
        int $announcementId,
        array $data,
        int $userId
    ): bool {
        if (
            $announcementId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement or user ID.'
            );
        }

        $title = trim(
            (string) (
                $data['title']
                ?? ''
            )
        );

        $content = trim(
            (string) (
                $data['content']
                ?? ''
            )
        );

        if ($title === '') {
            throw new InvalidArgumentException(
                'Announcement title is required.'
            );
        }

        if ($content === '') {
            throw new InvalidArgumentException(
                'Announcement content is required.'
            );
        }

        if (mb_strlen($title) > 150) {
            throw new InvalidArgumentException(
                'Announcement title cannot exceed 150 characters.'
            );
        }

        if (mb_strlen($content) > 5000) {
            throw new InvalidArgumentException(
                'Announcement content cannot exceed 5,000 characters.'
            );
        }

        $imagePath =
            $data['image_path']
            ?? null;

        $audioPath =
            $this->normalizeNullableString(
                $data['audio_path']
                    ?? null
            );

        $audioFileName =
            $this->normalizeNullableString(
                $data['audio_file_name']
                    ?? null
            );

        $audioMimeType =
            $this->normalizeNullableString(
                $data['audio_mime_type']
                    ?? null
            );

        $audioFileSize =
            isset($data['audio_file_size']) &&
            $data['audio_file_size'] !== null
            ? (int) $data['audio_file_size']
            : null;

        $audioTranscript =
            $this->normalizeNullableString(
                $data['audio_transcript']
                    ?? null
            );



        if ($audioPath === null) {
            $audioFileName =
                null;

            $audioMimeType =
                null;

            $audioFileSize =
                null;

            $audioTranscript =
                null;
        }

        $category = trim(
            (string) (
                $data['category']
                ?? 'general'
            )
        );

        $priority = trim(
            (string) (
                $data['priority']
                ?? 'Normal'
            )
        );

        $workflowStatus = trim(
            (string) (
                $data['workflow_status']
                ?? 'draft'
            )
        );

        $releaseMode = trim(
            (string) (
                $data['release_mode']
                ?? 'immediate'
            )
        );

        $scheduledPublishAt =
            $data['scheduled_publish_at']
            ?? null;

        $calendarEventId =
            !empty($data['calendar_event_id'])
            ? (int) $data['calendar_event_id']
            : null;

        $allowReactions =
            !empty($data['allow_reactions'])
            ? 1
            : 0;

        $allowComments =
            !empty($data['allow_comments'])
            ? 1
            : 0;

        $requireAcknowledgment =
            !empty($data['require_acknowledgment'])
            ? 1
            : 0;

        $sendNotification =
            !empty($data['send_notification'])
            ? 1
            : 0;

        if ($releaseMode !== 'scheduled') {
            $scheduledPublishAt = null;
        }

        if ($releaseMode !== 'calendar') {
            $calendarEventId = null;
        }

        if ($priority === 'Emergency') {
            $requireAcknowledgment = 1;
            $sendNotification = 1;
        }

        $isPublished =
            $workflowStatus ===
            'published';

        $publishedAt =
            $isPublished
            ? date('Y-m-d H:i:s')
            : null;

        $status =
            $isPublished
            ? 'active'
            : 'inactive';

        $stmt =
            $this->conn->prepare("
                UPDATE announcements

                SET
                    title = ?,
                    content = ?,
                    image_path = ?,
                    audio_path = ?,
                    audio_file_name = ?,
                    audio_mime_type = ?,
                    audio_file_size = ?,
                    audio_transcript = ?,
                    category = ?,
                    priority = ?,
                    workflow_status = ?,
                    release_mode = ?,
                    scheduled_publish_at = ?,
                    calendar_event_id = ?,
                    allow_reactions = ?,
                    allow_comments = ?,
                    require_acknowledgment = ?,
                    send_notification = ?,
                    published_at = ?,
                    status = ?,
                    reviewed_by = NULL,
                    reviewed_at = NULL,
                    review_notes = NULL

                WHERE announcement_id = ?
                  AND user_id = ?
                  AND workflow_status IN (
                        'draft',
                        'rejected'
                  )

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssssssissssssiiiiissii',
            $title,
            $content,
            $imagePath,
            $audioPath,
            $audioFileName,
            $audioMimeType,
            $audioFileSize,
            $audioTranscript,
            $category,
            $priority,
            $workflowStatus,
            $releaseMode,
            $scheduledPublishAt,
            $calendarEventId,
            $allowReactions,
            $allowComments,
            $requireAcknowledgment,
            $sendNotification,
            $publishedAt,
            $status,
            $announcementId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update announcement: '
                    . $error
            );
        }

        /*
 * A successful UPDATE may affect zero rows when every
 * submitted value already matches the stored record.
 * Ownership and workflow eligibility were validated
 * before this statement was executed.
 */

        $stmt->close();

        return true;
    }



    /* ==========================================
       SAVE TARGET RECIPIENTS

       Each target entry may contain:
       - role_id
       - department_id
       - education_level_id
       - academic_program_id
       - grade_level_id
       - section_id
    ========================================== */

    public function saveTargets(
        int $announcementId,
        array $targets
    ): void {
        if ($announcementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid announcement ID.'
            );
        }

        /*
         * Remove old targeting rows so this method
         * can also be reused when editing content.
         */
        $deleteStmt =
            $this->conn->prepare("
                DELETE FROM announcement_target
                WHERE announcement_id = ?
            ");

        if (!$deleteStmt) {
            throw new RuntimeException(
                'Unable to prepare target cleanup: '
                    . $this->conn->error
            );
        }

        $deleteStmt->bind_param(
            'i',
            $announcementId
        );

        if (!$deleteStmt->execute()) {
            $error = $deleteStmt->error;

            $deleteStmt->close();

            throw new RuntimeException(
                'Unable to clear announcement targets: '
                    . $error
            );
        }

        $deleteStmt->close();

        if (empty($targets)) {
            return;
        }

        $insertStmt =
            $this->conn->prepare("
                INSERT INTO announcement_target
                (
                    announcement_id,
                    role_id,
                    department_id,
                    education_level_id,
                    academic_program_id,
                    grade_level_id,
                    section_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

        if (!$insertStmt) {
            throw new RuntimeException(
                'Unable to prepare target insert: '
                    . $this->conn->error
            );
        }

        $insertedTargets = [];

        foreach ($targets as $target) {
            $roleId =
                !empty($target['role_id'])
                ? (int) $target['role_id']
                : null;

            $departmentId =
                !empty($target['department_id'])
                ? (int) $target['department_id']
                : null;

            $educationLevelId =
                !empty($target['education_level_id'])
                ? (int) $target['education_level_id']
                : null;

            $academicProgramId =
                !empty($target['academic_program_id'])
                ? (int) $target['academic_program_id']
                : null;

            $gradeLevelId =
                !empty($target['grade_level_id'])
                ? (int) $target['grade_level_id']
                : null;

            $sectionId =
                !empty($target['section_id'])
                ? (int) $target['section_id']
                : null;

            /*
             * Skip a completely empty target.
             */
            if (
                $roleId === null &&
                $departmentId === null &&
                $educationLevelId === null &&
                $academicProgramId === null &&
                $gradeLevelId === null &&
                $sectionId === null
            ) {
                continue;
            }

            /*
             * Prevent duplicate rows within the same request.
             */
            $targetSignature = implode(
                ':',
                [
                    $roleId ?? 0,
                    $departmentId ?? 0,
                    $educationLevelId ?? 0,
                    $academicProgramId ?? 0,
                    $gradeLevelId ?? 0,
                    $sectionId ?? 0
                ]
            );

            if (
                isset(
                    $insertedTargets[$targetSignature]
                )
            ) {
                continue;
            }

            $insertStmt->bind_param(
                'iiiiiii',
                $announcementId,
                $roleId,
                $departmentId,
                $educationLevelId,
                $academicProgramId,
                $gradeLevelId,
                $sectionId
            );

            if (!$insertStmt->execute()) {
                $error =
                    $insertStmt->error;

                $insertStmt->close();

                throw new RuntimeException(
                    'Unable to save announcement target: '
                        . $error
                );
            }

            $insertedTargets[$targetSignature] = true;
        }

        $insertStmt->close();
    }

    /* ==========================================
   GET ANNOUNCEMENT TARGETS
========================================== */

    public function getTargets(
        int $announcementId
    ): array {
        if ($announcementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid announcement ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                at.role_id,
                at.department_id,
                at.education_level_id,
                at.academic_program_id,
                at.grade_level_id,
                at.section_id,
                r.role_prefix

            FROM announcement_target at

            LEFT JOIN role r
                ON r.role_id =
                   at.role_id

            WHERE at.announcement_id = ?

            ORDER BY at.role_id ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement targets: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $announcementId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load announcement targets: '
                    . $error
            );
        }

        $targets =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $targets;
    }

    /* ==========================================
       LOOKUP ROLE IDS
    ========================================== */

    public function getRoleIdsByPrefixes(
        array $rolePrefixes
    ): array {
        $rolePrefixes = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn($role): string =>
                        trim((string) $role),
                        $rolePrefixes
                    ),
                    static fn($role): bool =>
                    $role !== ''
                )
            )
        );

        if (empty($rolePrefixes)) {
            return [];
        }

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($rolePrefixes),
                '?'
            )
        );

        $stmt = $this->conn->prepare("
            SELECT
                role_id,
                role_prefix
            FROM role
            WHERE role_prefix IN ({$placeholders})
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare role lookup: '
                    . $this->conn->error
            );
        }

        $types = str_repeat(
            's',
            count($rolePrefixes)
        );

        $stmt->bind_param(
            $types,
            ...$rolePrefixes
        );

        $stmt->execute();

        $rows = $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        $roleIds = [];

        foreach ($rows as $row) {
            $roleIds[$row['role_prefix']] = (int) $row['role_id'];
        }

        return $roleIds;
    }

    /* ==========================================
       INFORMATION HUB FEED
    ========================================== */

    public function getRecent(
        int $limit = 20,
        bool $includeLegacyEngagement = true
    ): array {
        // Home attaches authoritative unified counts after recipient filtering.
        $engagementColumns = $includeLegacyEngagement ? "
                (
                    SELECT COUNT(*)
                    FROM announcement_view av
                    WHERE av.announcement_id =
                          a.announcement_id
                ) AS view_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction ar
                    WHERE ar.announcement_id =
                          a.announcement_id
                ) AS reaction_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_comment ac
                    WHERE ac.announcement_id =
                          a.announcement_id
                ) AS comment_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_acknowledgment aa
                    WHERE aa.announcement_id =
                          a.announcement_id
                ) AS acknowledgment_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction ar
                    WHERE ar.announcement_id =
                          a.announcement_id
                      AND ar.reaction = 'Like'
                ) AS like_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction ar
                    WHERE ar.announcement_id =
                          a.announcement_id
                      AND ar.reaction = 'Love'
                ) AS love_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction ar
                    WHERE ar.announcement_id =
                          a.announcement_id
                      AND ar.reaction = 'Care'
                ) AS care_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction ar
                    WHERE ar.announcement_id =
                          a.announcement_id
                      AND ar.reaction = 'Wow'
                ) AS wow_count
        " : "0 AS view_count, 0 AS reaction_count, 0 AS comment_count,
                0 AS acknowledgment_count, 0 AS like_count, 0 AS love_count,
                0 AS care_count, 0 AS wow_count";

        $stmt = $this->conn->prepare("
            SELECT
                a.announcement_id,
                a.title,
                a.content,
                a.image_path,
                a.audio_path,
                a.audio_file_name,
                a.audio_mime_type,
                a.audio_file_size,
                a.audio_transcript,
                a.reference_link,
                a.type,
                a.category,
                a.priority,
                a.release_mode,
                a.workflow_status,
                a.scheduled_publish_at,
                a.calendar_event_id,
                a.allow_reactions,
                a.allow_comments,
                a.require_acknowledgment,
                a.send_notification,
                a.created_at,
                a.published_at,
                a.status,
                a.user_id,

                TRIM(
                    CONCAT(
                        COALESCE(
                            u.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            u.last_name,
                            ''
                        )
                    )
                ) AS author_name,

                {$engagementColumns}

            FROM announcements a

            LEFT JOIN user u
                ON u.user_id = a.user_id

            WHERE a.status = 'active'
              AND a.workflow_status = 'published'

            ORDER BY
                CASE a.priority
                    WHEN 'Emergency' THEN 1
                    WHEN 'Urgent' THEN 2
                    WHEN 'Important' THEN 3
                    WHEN 'High' THEN 4
                    WHEN 'Medium' THEN 5
                    WHEN 'Normal' THEN 6
                    WHEN 'Low' THEN 7
                    ELSE 8
                END ASC,

                COALESCE(
                    a.published_at,
                    a.created_at
                ) DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement feed: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $limit
        );

        $stmt->execute();

        $announcements = $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $announcements;
    }

    /* ==========================================
   COUNT PUBLISHED ANNOUNCEMENTS
========================================== */

    public function countPublished(): int
    {
        $stmt =
            $this->conn->prepare("
            SELECT COUNT(*) AS total
            FROM announcements
            WHERE status = 'active'
              AND workflow_status = 'published'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare published announcement count: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to count published announcements: '
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
       FIND PUBLISHED ANNOUNCEMENT
    ========================================== */

    public function findById(
        int $announcementId
    ): ?array {
        $stmt = $this->conn->prepare("
            SELECT
                a.announcement_id,
                a.title,
                a.content,
                a.image_path,
                a.audio_path,
                a.audio_file_name,
                a.audio_mime_type,
                a.audio_file_size,
                a.audio_transcript,
                a.reference_link,
                a.type,
                a.category,
                a.priority,
                a.release_mode,
                a.workflow_status,
                a.allow_reactions,
                a.allow_comments,
                a.require_acknowledgment,
                a.send_notification,
                a.created_at,
                a.published_at,
                a.status,
                a.user_id,

                TRIM(
                    CONCAT(
                        COALESCE(
                            u.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            u.last_name,
                            ''
                        )
                    )
                ) AS author_name

            FROM announcements a

            LEFT JOIN user u
                ON u.user_id = a.user_id

            WHERE a.announcement_id = ?
              AND a.status = 'active'
              AND a.workflow_status = 'published'

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement details: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $announcementId
        );

        $stmt->execute();

        $announcement = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $announcement ?: null;
    }

    /* ==========================================
       RECORD UNIQUE VIEW
    ========================================== */

    public function recordView(
        int $announcementId,
        int $userId
    ): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO announcement_view
            (
                announcement_id,
                user_id,
                viewed_at
            )

            SELECT ?, ?, NOW()

            WHERE NOT EXISTS (
                SELECT 1
                FROM announcement_view
                WHERE announcement_id = ?
                  AND user_id = ?
            )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement view: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iiii',
            $announcementId,
            $userId,
            $announcementId,
            $userId
        );

        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    /* ==========================================
       ENGAGEMENT
    ========================================== */

    public function getEngagement(
        int $announcementId,
        int $userId
    ): array {
        $stmt = $this->conn->prepare("
            SELECT
                (
                    SELECT COUNT(*)
                    FROM announcement_view
                    WHERE announcement_id = ?
                ) AS view_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_comment
                    WHERE announcement_id = ?
                ) AS comment_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction
                    WHERE announcement_id = ?
                ) AS reaction_count,

                (
                    SELECT reaction
                    FROM announcement_reaction
                    WHERE announcement_id = ?
                      AND user_id = ?
                    LIMIT 1
                ) AS user_reaction,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction
                    WHERE announcement_id = ?
                      AND reaction = 'Like'
                ) AS like_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction
                    WHERE announcement_id = ?
                      AND reaction = 'Love'
                ) AS love_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction
                    WHERE announcement_id = ?
                      AND reaction = 'Care'
                ) AS care_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_reaction
                    WHERE announcement_id = ?
                      AND reaction = 'Wow'
                ) AS wow_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_acknowledgment
                    WHERE announcement_id = ?
                ) AS acknowledgment_count,

                (
                    SELECT COUNT(*)
                    FROM announcement_acknowledgment
                    WHERE announcement_id = ?
                      AND user_id = ?
                ) AS user_acknowledged
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare engagement data: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iiiiiiiiiiii',
            $announcementId,
            $announcementId,
            $announcementId,
            $announcementId,
            $userId,
            $announcementId,
            $announcementId,
            $announcementId,
            $announcementId,
            $announcementId,
            $announcementId,
            $userId
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load engagement data: '
                    . $error
            );
        }

        $engagement = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$engagement) {
            $engagement = [];
        }

        $engagement['view_count'] =
            (int) (
                $engagement['view_count']
                ?? 0
            );

        $engagement['comment_count'] =
            (int) (
                $engagement['comment_count']
                ?? 0
            );

        $engagement['reaction_count'] =
            (int) (
                $engagement['reaction_count']
                ?? 0
            );

        $engagement['acknowledgment_count'] =
            (int) (
                $engagement['acknowledgment_count']
                ?? 0
            );

        $engagement['user_acknowledged'] =
            (int) (
                $engagement['user_acknowledged']
                ?? 0
            ) > 0;

        $engagement['reaction_breakdown'] = [
            'Like' => (int) (
                $engagement['like_count']
                ?? 0
            ),

            'Love' => (int) (
                $engagement['love_count']
                ?? 0
            ),

            'Care' => (int) (
                $engagement['care_count']
                ?? 0
            ),

            'Wow' => (int) (
                $engagement['wow_count']
                ?? 0
            )
        ];

        return $engagement;
    }

    /* ==========================================
       INTERACTION SETTINGS
    ========================================== */

    private function getInteractionSettings(
        int $announcementId
    ): array {
        $stmt = $this->conn->prepare("
            SELECT
                allow_reactions,
                allow_comments,
                require_acknowledgment
            FROM announcements
            WHERE announcement_id = ?
              AND status = 'active'
              AND workflow_status = 'published'
            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to load interaction settings: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $announcementId
        );

        $stmt->execute();

        $settings = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$settings) {
            throw new RuntimeException(
                'Announcement not found.'
            );
        }

        return $settings;
    }

    /* ==========================================
       REACTIONS
    ========================================== */

    public function react(
        int $announcementId,
        int $userId,
        string $reaction
    ): ?string {
        $settings =
            $this->getInteractionSettings(
                $announcementId
            );

        if (
            empty($settings['allow_reactions'])
        ) {
            throw new RuntimeException(
                'Reactions are disabled for this announcement.'
            );
        }

        $allowedReactions = [
            'Like',
            'Love',
            'Care',
            'Wow'
        ];

        if (
            !in_array(
                $reaction,
                $allowedReactions,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid reaction.'
            );
        }

        $existingStmt =
            $this->conn->prepare("
                SELECT
                    reaction_id,
                    reaction
                FROM announcement_reaction
                WHERE announcement_id = ?
                  AND user_id = ?
                LIMIT 1
            ");

        if (!$existingStmt) {
            throw new RuntimeException(
                'Unable to prepare reaction lookup: '
                    . $this->conn->error
            );
        }

        $existingStmt->bind_param(
            'ii',
            $announcementId,
            $userId
        );

        $existingStmt->execute();

        $existing = $existingStmt
            ->get_result()
            ->fetch_assoc();

        $existingStmt->close();

        if ($existing) {
            if (
                $existing['reaction'] ===
                $reaction
            ) {
                $deleteStmt =
                    $this->conn->prepare("
                        DELETE FROM announcement_reaction
                        WHERE announcement_id = ?
                          AND user_id = ?
                    ");

                if (!$deleteStmt) {
                    throw new RuntimeException(
                        'Unable to prepare reaction removal.'
                    );
                }

                $deleteStmt->bind_param(
                    'ii',
                    $announcementId,
                    $userId
                );

                $deleteStmt->execute();
                $deleteStmt->close();

                return null;
            }

            $updateStmt =
                $this->conn->prepare("
                    UPDATE announcement_reaction
                    SET
                        reaction = ?,
                        created_at = NOW()
                    WHERE announcement_id = ?
                      AND user_id = ?
                ");

            if (!$updateStmt) {
                throw new RuntimeException(
                    'Unable to prepare reaction update.'
                );
            }

            $updateStmt->bind_param(
                'sii',
                $reaction,
                $announcementId,
                $userId
            );

            $updateStmt->execute();
            $updateStmt->close();

            return $reaction;
        }

        $insertStmt =
            $this->conn->prepare("
                INSERT INTO announcement_reaction
                (
                    announcement_id,
                    user_id,
                    reaction,
                    created_at
                )
                VALUES (?, ?, ?, NOW())
            ");

        if (!$insertStmt) {
            throw new RuntimeException(
                'Unable to prepare reaction insert.'
            );
        }

        $insertStmt->bind_param(
            'iis',
            $announcementId,
            $userId,
            $reaction
        );

        $insertStmt->execute();
        $insertStmt->close();

        return $reaction;
    }

    /* ==========================================
       COMMENTS
    ========================================== */

    public function addComment(
        int $announcementId,
        int $userId,
        string $comment
    ): bool {
        $settings =
            $this->getInteractionSettings(
                $announcementId
            );

        if (
            empty($settings['allow_comments'])
        ) {
            throw new RuntimeException(
                'Comments are disabled for this announcement.'
            );
        }

        $comment = trim($comment);

        if ($comment === '') {
            throw new InvalidArgumentException(
                'Comment cannot be empty.'
            );
        }

        if (mb_strlen($comment) > 1000) {
            throw new InvalidArgumentException(
                'Comment must not exceed 1,000 characters.'
            );
        }

        $stmt = $this->conn->prepare("
            INSERT INTO announcement_comment
            (
                announcement_id,
                user_id,
                comment,
                created_at
            )
            VALUES (?, ?, ?, NOW())
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare comment insert: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iis',
            $announcementId,
            $userId,
            $comment
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save comment: '
                    . $error
            );
        }

        $stmt->close();

        return true;
    }

    public function getComments(
        int $announcementId
    ): array {
        $stmt = $this->conn->prepare("
            SELECT
                ac.comment_id,
                ac.comment,
                ac.created_at,
                ac.user_id,

                TRIM(
                    CONCAT(
                        COALESCE(
                            u.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            u.last_name,
                            ''
                        )
                    )
                ) AS user_name,

                u.profile_photo,
                r.role_prefix

            FROM announcement_comment ac

            INNER JOIN user u
                ON u.user_id = ac.user_id

            LEFT JOIN role r
                ON r.role_id = u.role_id

            WHERE ac.announcement_id = ?

            ORDER BY ac.created_at ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare comment list: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $announcementId
        );

        $stmt->execute();

        $comments = $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $comments;
    }

    /* ==========================================
       ACKNOWLEDGMENT
    ========================================== */

    public function acknowledge(
        int $announcementId,
        int $userId
    ): bool {
        $this->getInteractionSettings(
            $announcementId
        );

        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO announcement_acknowledgment
            (
                announcement_id,
                user_id,
                acknowledged_at
            )
            VALUES (?, ?, NOW())
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare acknowledgment: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $announcementId,
            $userId
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save acknowledgment: '
                    . $error
            );
        }

        $stmt->close();

        return true;
    }

    /* ==========================================
   WORKFLOW LIST
========================================== */

    public function getByWorkflowStatus(
        string $workflowStatus,
        ?int $userId = null,
        int $limit = 100
    ): array {
        $allowedWorkflowStatuses = [
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'scheduled',
            'published',
            'archived'
        ];

        if (
            !in_array(
                $workflowStatus,
                $allowedWorkflowStatuses,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement workflow status.'
            );
        }

        $limit = max(
            1,
            min(
                $limit,
                250
            )
        );

        $sql = "
        SELECT
            a.announcement_id,
            a.title,
            a.content,
            a.image_path,
            a.reference_link,
            a.type,
            a.category,
            a.priority,

          a.release_mode,
a.scheduled_publish_at,
a.calendar_event_id,

linked_event.title
    AS linked_event_title,

linked_event.event_date
    AS linked_event_date,

            a.allow_reactions,
            a.allow_comments,
            a.require_acknowledgment,
            a.send_notification,

            a.created_at,
            a.published_at,
            a.status,
            a.workflow_status,

            a.reviewed_by,
            a.reviewed_at,
            a.review_notes,

            a.user_id,

            TRIM(
                CONCAT(
                    COALESCE(
                        creator.first_name,
                        ''
                    ),
                    ' ',
                    COALESCE(
                        creator.last_name,
                        ''
                    )
                )
            ) AS author_name,

            TRIM(
                CONCAT(
                    COALESCE(
                        reviewer.first_name,
                        ''
                    ),
                    ' ',
                    COALESCE(
                        reviewer.last_name,
                        ''
                    )
                )
            ) AS reviewer_name

        FROM announcements a

        LEFT JOIN user creator
            ON creator.user_id =
               a.user_id

       LEFT JOIN user reviewer
    ON reviewer.user_id =
       a.reviewed_by

LEFT JOIN events linked_event
    ON linked_event.event_id =
       a.calendar_event_id

WHERE a.workflow_status = ?
    ";

        if (
            $userId !== null &&
            $userId > 0
        ) {
            $sql .= "
            AND a.user_id = ?
        ";
        }

        $sql .= "
        ORDER BY a.created_at DESC
        LIMIT ?
    ";

        $stmt =
            $this->conn->prepare(
                $sql
            );

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement workflow list: '
                    . $this->conn->error
            );
        }

        if (
            $userId !== null &&
            $userId > 0
        ) {
            $stmt->bind_param(
                'sii',
                $workflowStatus,
                $userId,
                $limit
            );
        } else {
            $stmt->bind_param(
                'si',
                $workflowStatus,
                $limit
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load announcement workflow list: '
                    . $error
            );
        }

        $announcements =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $announcements;
    }

    /* ==========================================
   FIND ANNOUNCEMENT FOR WORKSPACE
========================================== */

    public function findWorkflowItemById(
        int $announcementId
    ): ?array {
        if ($announcementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid announcement ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                a.announcement_id,
                a.title,
                a.content,
                a.image_path,
                a.audio_path,
                a.audio_file_name,
                a.audio_mime_type,
                a.audio_file_size,
                a.audio_transcript,
                a.reference_link,
                a.type,
                a.category,
                a.priority,

                a.release_mode,
                a.scheduled_publish_at,
                a.calendar_event_id,

                a.allow_reactions,
                a.allow_comments,
                a.require_acknowledgment,
                a.send_notification,

                a.created_at,
                a.published_at,
                a.status,
                a.workflow_status,

                a.reviewed_by,
                a.reviewed_at,
                a.review_notes,

                a.user_id,

                TRIM(
                    CONCAT(
                        COALESCE(
                            creator.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            creator.last_name,
                            ''
                        )
                    )
                ) AS author_name,

                TRIM(
                    CONCAT(
                        COALESCE(
                            reviewer.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            reviewer.last_name,
                            ''
                        )
                    )
                ) AS reviewer_name

            FROM announcements a

            LEFT JOIN user creator
                ON creator.user_id =
                   a.user_id

            LEFT JOIN user reviewer
                ON reviewer.user_id =
                   a.reviewed_by

            WHERE a.announcement_id = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement workspace details: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $announcementId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load announcement workspace details: '
                    . $error
            );
        }

        $announcement =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $announcement ?: null;
    }

    /* ==========================================
   SUBMIT ANNOUNCEMENT FOR REVIEW
========================================== */

    public function submitForReview(
        int $announcementId,
        int $userId
    ): bool {
        if (
            $announcementId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement or user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE announcements

            SET
                workflow_status =
                    'pending_review',

                status =
                    'inactive',

                published_at =
                    NULL,

                reviewed_by =
                    NULL,

                reviewed_at =
                    NULL,

                review_notes =
                    NULL

            WHERE announcement_id = ?
              AND user_id = ?
              AND workflow_status IN (
                    'draft',
                    'rejected'
              )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement review submission: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $announcementId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to submit announcement for review: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   APPROVE ANNOUNCEMENT
========================================== */

    public function approve(
        int $announcementId,
        int $reviewerId
    ): bool {
        if (
            $announcementId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement or reviewer ID.'
            );
        }

        $announcement =
            $this->findWorkflowItemById(
                $announcementId
            );

        if (
            !$announcement ||
            (
                $announcement['workflow_status']
                ?? ''
            ) !== 'pending_review'
        ) {
            return false;
        }

        $releaseMode =
            strtolower(
                trim(
                    (string) (
                        $announcement['release_mode']
                        ?? 'immediate'
                    )
                )
            );

        $workflowStatus =
            $releaseMode === 'scheduled'
            ? 'scheduled'
            : 'published';

        $status =
            $workflowStatus === 'published'
            ? 'active'
            : 'inactive';

        $publishedAt =
            $workflowStatus === 'published'
            ? date('Y-m-d H:i:s')
            : null;

        $stmt =
            $this->conn->prepare("
            UPDATE announcements

            SET
                workflow_status = ?,

                status = ?,

                published_at = ?,

                reviewed_by = ?,

                reviewed_at = NOW(),

                review_notes = NULL

            WHERE announcement_id = ?
              AND workflow_status =
                  'pending_review'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement approval: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'sssii',
            $workflowStatus,
            $status,
            $publishedAt,
            $reviewerId,
            $announcementId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to approve announcement: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   REJECT ANNOUNCEMENT
========================================== */

    public function reject(
        int $announcementId,
        int $reviewerId,
        string $reviewNotes
    ): bool {
        if (
            $announcementId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement or reviewer ID.'
            );
        }

        $reviewNotes =
            trim(
                $reviewNotes
            );

        if ($reviewNotes === '') {
            throw new InvalidArgumentException(
                'A rejection reason is required.'
            );
        }

        if (
            mb_strlen(
                $reviewNotes
            ) > 1000
        ) {
            throw new InvalidArgumentException(
                'The rejection reason cannot exceed 1,000 characters.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE announcements

            SET
                workflow_status =
                    'rejected',

                status =
                    'inactive',

                published_at =
                    NULL,

                reviewed_by =
                    ?,

                reviewed_at =
                    NOW(),

                review_notes =
                    ?

            WHERE announcement_id = ?
              AND workflow_status =
                  'pending_review'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement rejection: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'isi',
            $reviewerId,
            $reviewNotes,
            $announcementId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to reject announcement: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   ARCHIVE ANNOUNCEMENT
========================================== */

    public function archive(
        int $announcementId
    ): bool {
        if ($announcementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid announcement ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE announcements

            SET
                workflow_status =
                    'archived',

                status =
                    'inactive'

            WHERE announcement_id = ?
              AND workflow_status IN (
                    'published',
                    'scheduled'
              )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement archive: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $announcementId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to archive announcement: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   RESTORE ANNOUNCEMENT TO DRAFT
========================================== */

    public function restoreToDraft(
        int $announcementId,
        int $userId
    ): bool {
        if (
            $announcementId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement or user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE announcements

            SET
                workflow_status =
                    'draft',

                status =
                    'inactive',

                published_at =
                    NULL,

                reviewed_by =
                    NULL,

                reviewed_at =
                    NULL,

                review_notes =
                    NULL

            WHERE announcement_id = ?
              AND user_id = ?
              AND workflow_status IN (
                    'rejected',
                    'archived'
              )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare announcement draft restoration: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $announcementId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to restore announcement to draft: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
       NULLABLE STRING NORMALIZATION
    ========================================== */

    private function normalizeNullableString(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            is_array($value) ||
            is_object($value)
        ) {
            return null;
        }

        $normalized =
            trim(
                (string) $value
            );

        return $normalized !== ''
            ? $normalized
            : null;
    }
}
