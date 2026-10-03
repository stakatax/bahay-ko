<?php

require_once __DIR__ . '/BaseModel.php';

class Event extends BaseModel
{
    /* ==========================================
       CREATE EVENT

       Supports:
       1. Legacy positional arguments
       2. New workflow-ready data array
    ========================================== */

    public function create(
        $dataOrTitle,
        $description = null,
        $location = null,
        $eventDate = null,
        $endDate = null,
        $imagePath = null,
        $userId = null
    ): int {
        if (!is_array($dataOrTitle)) {
            $data = [
                'title' =>
                $dataOrTitle,

                'description' =>
                $description,

                'location' =>
                $location,

                'event_date' =>
                $eventDate,

                'end_date' =>
                $endDate,

                'image_path' =>
                $imagePath,

                'user_id' =>
                $userId,

                'workflow_status' =>
                'published',

                'release_mode' =>
                'immediate',

                'scheduled_publish_at' =>
                null,

                'calendar_event_id' =>
                null,

                'send_notification' =>
                1,

                'allow_reactions' =>
                1,

                'allow_comments' =>
                1,

                'require_acknowledgment' =>
                0
            ];
        } else {
            $data = $dataOrTitle;
        }

        $title = trim(
            (string) (
                $data['title']
                ?? ''
            )
        );

        $eventDescription = trim(
            (string) (
                $data['description']
                ?? ''
            )
        );

        $eventLocation = trim(
            (string) (
                $data['location']
                ?? ''
            )
        );

        $eventDateValue = trim(
            (string) (
                $data['event_date']
                ?? ''
            )
        );

        $endDateValue = trim(
            (string) (
                $data['end_date']
                ?? ''
            )
        );

        $eventImagePath =
            !empty($data['image_path'])
            ? trim(
                (string) $data['image_path']
            )
            : null;

        $eventUserId =
            (int) (
                $data['user_id']
                ?? 0
            );
        $workflowStatus = strtolower(
            trim(
                (string) (
                    $data['workflow_status']
                    ?? 'draft'
                )
            )
        );

        $releaseMode = strtolower(
            trim(
                (string) (
                    $data['release_mode']
                    ?? 'immediate'
                )
            )
        );

        $scheduledPublishAt =
            !empty($data['scheduled_publish_at'])
            ? trim(
                (string) $data['scheduled_publish_at']
            )
            : null;

        $calendarEventId =
            !empty($data['calendar_event_id'])
            ? (int) $data['calendar_event_id']
            : null;

        $sendNotification =
            !empty($data['send_notification'])
            ? 1
            : 0;

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

        /* ======================================
       VALIDATION
    ======================================= */

        if ($title === '') {
            throw new InvalidArgumentException(
                'Event title is required.'
            );
        }

        if ($eventDescription === '') {
            throw new InvalidArgumentException(
                'Event description is required.'
            );
        }

        if ($eventDateValue === '') {
            throw new InvalidArgumentException(
                'Event start date is required.'
            );
        }

        if ($eventUserId <= 0) {
            throw new InvalidArgumentException(
                'A valid event creator is required.'
            );
        }

        if (mb_strlen($title) > 100) {
            throw new InvalidArgumentException(
                'Event title cannot exceed 100 characters.'
            );
        }

        if (
            mb_strlen(
                $eventDescription
            ) > 5000
        ) {
            throw new InvalidArgumentException(
                'Event description cannot exceed 5,000 characters.'
            );
        }

        if (
            mb_strlen(
                $eventLocation
            ) > 255
        ) {
            throw new InvalidArgumentException(
                'Event location cannot exceed 255 characters.'
            );
        }

        $eventTimestamp =
            strtotime(
                $eventDateValue
            );

        if ($eventTimestamp === false) {
            throw new InvalidArgumentException(
                'Invalid event start date.'
            );
        }

        $eventDateValue = date(
            'Y-m-d H:i:s',
            $eventTimestamp
        );

        if ($endDateValue !== '') {
            $endTimestamp =
                strtotime(
                    $endDateValue
                );

            if ($endTimestamp === false) {
                throw new InvalidArgumentException(
                    'Invalid event end date.'
                );
            }

            if (
                $endTimestamp <=
                $eventTimestamp
            ) {
                throw new InvalidArgumentException(
                    'The event end date must be later than the start date.'
                );
            }

            $endDateValue = date(
                'Y-m-d H:i:s',
                $endTimestamp
            );
        } else {
            $endDateValue = null;
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
                'Invalid event workflow status.'
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
                'Invalid event release mode.'
            );
        }

        if (
            $releaseMode === 'scheduled' &&
            $scheduledPublishAt === null
        ) {
            throw new InvalidArgumentException(
                'Scheduled release date is required.'
            );
        }

        if (
            $releaseMode === 'calendar' &&
            (
                $calendarEventId === null ||
                $calendarEventId <= 0
            )
        ) {
            throw new InvalidArgumentException(
                'Calendar event reference is required.'
            );
        }

        $status =
            $workflowStatus === 'published'
            ? 'active'
            : 'inactive';

        /* ======================================
       INSERT EVENT
    ======================================= */

        $stmt = $this->conn->prepare("
        INSERT INTO events
(
    title,
    description,
    location,
    image_path,
    event_date,
    end_date,
    created_at,
    status,
    workflow_status,
    release_mode,
    scheduled_publish_at,
    calendar_event_id,
    send_notification,
    allow_reactions,
    allow_comments,
    require_acknowledgment,
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
    ?
)
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event insert: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssssssssssiiiiii',
            $title,
            $eventDescription,
            $eventLocation,
            $eventImagePath,
            $eventDateValue,
            $endDateValue,
            $status,
            $workflowStatus,
            $releaseMode,
            $scheduledPublishAt,
            $calendarEventId,
            $sendNotification,
            $allowReactions,
            $allowComments,
            $requireAcknowledgment,
            $eventUserId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save event: '
                    . $error
            );
        }

        $eventId =
            (int) $this->conn->insert_id;

        $stmt->close();

        return $eventId;
    }

    /* ==========================================
   UPDATE EVENT
========================================== */

    public function update(
        int $eventId,
        array $data,
        int $userId
    ): bool {
        if (
            $eventId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid event or user ID.'
            );
        }

        $title = trim(
            (string) (
                $data['title']
                ?? ''
            )
        );

        $description = trim(
            (string) (
                $data['description']
                ?? ''
            )
        );

        $location = trim(
            (string) (
                $data['location']
                ?? ''
            )
        );

        $imagePath =
            $data['image_path']
            ?? null;

        $eventDate = trim(
            (string) (
                $data['event_date']
                ?? ''
            )
        );

        $endDate = trim(
            (string) (
                $data['end_date']
                ?? ''
            )
        );

        $workflowStatus = strtolower(
            trim(
                (string) (
                    $data['workflow_status']
                    ?? 'draft'
                )
            )
        );

        $releaseMode = strtolower(
            trim(
                (string) (
                    $data['release_mode']
                    ?? 'immediate'
                )
            )
        );

        $scheduledPublishAt =
            !empty($data['scheduled_publish_at'])
            ? trim(
                (string) $data['scheduled_publish_at']
            )
            : null;

        $calendarEventId =
            !empty($data['calendar_event_id'])
            ? (int) $data['calendar_event_id']
            : null;

        $sendNotification =
            !empty($data['send_notification'])
            ? 1
            : 0;

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

        if ($title === '') {
            throw new InvalidArgumentException(
                'Event title is required.'
            );
        }

        if ($description === '') {
            throw new InvalidArgumentException(
                'Event description is required.'
            );
        }

        if ($eventDate === '') {
            throw new InvalidArgumentException(
                'Event start date is required.'
            );
        }

        if (mb_strlen($title) > 100) {
            throw new InvalidArgumentException(
                'Event title cannot exceed 100 characters.'
            );
        }

        if (mb_strlen($description) > 5000) {
            throw new InvalidArgumentException(
                'Event description cannot exceed 5,000 characters.'
            );
        }

        if (mb_strlen($location) > 255) {
            throw new InvalidArgumentException(
                'Event location cannot exceed 255 characters.'
            );
        }

        $eventTimestamp =
            strtotime(
                $eventDate
            );

        if ($eventTimestamp === false) {
            throw new InvalidArgumentException(
                'Invalid event start date.'
            );
        }

        $eventDate = date(
            'Y-m-d H:i:s',
            $eventTimestamp
        );

        if ($endDate !== '') {
            $endTimestamp =
                strtotime(
                    $endDate
                );

            if ($endTimestamp === false) {
                throw new InvalidArgumentException(
                    'Invalid event end date.'
                );
            }

            if (
                $endTimestamp <=
                $eventTimestamp
            ) {
                throw new InvalidArgumentException(
                    'The event end date must be later than the start date.'
                );
            }

            $endDate = date(
                'Y-m-d H:i:s',
                $endTimestamp
            );
        } else {
            $endDate = null;
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
                'Invalid event workflow status.'
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
                'Invalid event release mode.'
            );
        }

        if (
            $releaseMode === 'scheduled' &&
            $scheduledPublishAt === null
        ) {
            throw new InvalidArgumentException(
                'Scheduled release date is required.'
            );
        }

        if (
            $releaseMode === 'calendar' &&
            (
                $calendarEventId === null ||
                $calendarEventId <= 0
            )
        ) {
            throw new InvalidArgumentException(
                'Calendar event reference is required.'
            );
        }

        if ($releaseMode !== 'scheduled') {
            $scheduledPublishAt = null;
        }

        if ($releaseMode !== 'calendar') {
            $calendarEventId = null;
        }

        $status =
            $workflowStatus === 'published'
            ? 'active'
            : 'inactive';

        $stmt =
            $this->conn->prepare("
            UPDATE events

            SET
                title = ?,
                description = ?,
                location = ?,
                image_path = ?,
                event_date = ?,
                end_date = ?,
                status = ?,
                workflow_status = ?,
                release_mode = ?,
                scheduled_publish_at = ?,
                calendar_event_id = ?,
                send_notification = ?,
                allow_reactions = ?,
                allow_comments = ?,
                require_acknowledgment = ?,
                reviewed_by = NULL,
                reviewed_at = NULL,
                review_notes = NULL

            WHERE event_id = ?
              AND user_id = ?
              AND workflow_status IN (
                    'draft',
                    'rejected'
              )

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssssssssssiiiiiii',
            $title,
            $description,
            $location,
            $imagePath,
            $eventDate,
            $endDate,
            $status,
            $workflowStatus,
            $releaseMode,
            $scheduledPublishAt,
            $calendarEventId,
            $sendNotification,
            $allowReactions,
            $allowComments,
            $requireAcknowledgment,
            $eventId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update event: '
                    . $error
            );
        }

        /*
 * A successful update may affect zero rows when the
 * submitted Event values already match the database.
 */

        $stmt->close();

        return true;
    }

    /* ==========================================
       SAVE EVENT TARGETS

       Accepted target fields:
       - role_id
       - department_id
       - education_level_id
       - academic_program_id
       - grade_level_id
       - section_id
    ========================================== */

    public function saveTargets(
        int $eventId,
        array $targets
    ): void {
        if ($eventId <= 0) {
            throw new InvalidArgumentException(
                'Invalid event ID.'
            );
        }

        $deleteStmt =
            $this->conn->prepare("
                DELETE FROM event_target
                WHERE event_id = ?
            ");

        if (!$deleteStmt) {
            throw new RuntimeException(
                'Unable to prepare event target cleanup: '
                    . $this->conn->error
            );
        }

        $deleteStmt->bind_param(
            'i',
            $eventId
        );

        if (!$deleteStmt->execute()) {
            $error =
                $deleteStmt->error;

            $deleteStmt->close();

            throw new RuntimeException(
                'Unable to clear event targets: '
                    . $error
            );
        }

        $deleteStmt->close();

        if (empty($targets)) {
            return;
        }

        $insertStmt =
            $this->conn->prepare("
                INSERT INTO event_target
                (
                    event_id,
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
                'Unable to prepare event target insert: '
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

            $signature = implode(
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
                    $insertedTargets[$signature]
                )
            ) {
                continue;
            }

            $insertStmt->bind_param(
                'iiiiiii',
                $eventId,
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
                    'Unable to save event target: '
                        . $error
                );
            }

            $insertedTargets[$signature] = true;
        }

        $insertStmt->close();
    }

    /* ==========================================
   GET EVENT TARGETS
========================================== */

    /* ==========================================
   GET EVENT TARGETS
========================================== */

    public function getTargets(
        int $eventId
    ): array {
        if ($eventId <= 0) {
            throw new InvalidArgumentException(
                'Invalid event ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
        SELECT
            et.role_id,
            et.department_id,
            et.education_level_id,
            et.academic_program_id,
            et.grade_level_id,
            et.section_id,
            r.role_prefix

        FROM event_target et

        LEFT JOIN role r
            ON r.role_id =
               et.role_id

        WHERE et.event_id = ?

        ORDER BY et.target_id ASC
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event targets: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $eventId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load event targets: '
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
       CALENDAR EVENTS BY YEAR
    ========================================== */

    public function getByYear(
        int $year,
        array $audience = [],
        bool $includeAll = false
    ): mysqli_result {
        if (
            $year < 2000 ||
            $year > 2200
        ) {
            throw new InvalidArgumentException(
                'Invalid calendar year.'
            );
        }

        $startDate =
            sprintf(
                '%04d-01-01 00:00:00',
                $year
            );

        $endDate =
            sprintf(
                '%04d-12-31 23:59:59',
                $year
            );

        $includeAllValue =
            $includeAll
            ? 1
            : 0;

        $roleId =
            max(
                0,
                (int) (
                    $audience['role_id']
                    ?? 0
                )
            );

        $departmentId =
            max(
                0,
                (int) (
                    $audience['department_id']
                    ?? 0
                )
            );

        $educationLevelId =
            max(
                0,
                (int) (
                    $audience['education_level_id']
                    ?? 0
                )
            );

        $academicProgramId =
            max(
                0,
                (int) (
                    $audience['academic_program_id']
                    ?? 0
                )
            );

        $gradeLevelId =
            max(
                0,
                (int) (
                    $audience['grade_level_id']
                    ?? 0
                )
            );

        $sectionId =
            max(
                0,
                (int) (
                    $audience['section_id']
                    ?? 0
                )
            );

        $stmt =
            $this->conn->prepare("
            SELECT
                e.event_id,
                e.title,
                e.description,
                e.location,
                e.image_path,
                e.event_date,
                e.end_date,

                e.allow_reactions,
                e.allow_comments,
                e.require_acknowledgment,

                e.created_at,
                e.status,
                e.workflow_status,
                e.send_notification,
                e.user_id,

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

            FROM events e

            LEFT JOIN user u
                ON u.user_id =
                   e.user_id

            WHERE e.status = 'active'
              AND e.workflow_status = 'published'
              AND e.event_date BETWEEN ? AND ?

              AND (
                    ? = 1

                    OR EXISTS (
                        SELECT 1

                        FROM event_target et

                        WHERE et.event_id =
                              e.event_id

                          AND (
                                et.role_id IS NULL
                                OR et.role_id = ?
                              )

                          AND (
                                et.department_id IS NULL
                                OR et.department_id = ?
                              )

                          AND (
                                et.education_level_id IS NULL
                                OR et.education_level_id = ?
                              )

                          AND (
                                et.academic_program_id IS NULL
                                OR et.academic_program_id = ?
                              )

                          AND (
                                et.grade_level_id IS NULL
                                OR et.grade_level_id = ?
                              )

                          AND (
                                et.section_id IS NULL
                                OR et.section_id = ?
                              )
                    )
                  )

            ORDER BY
                e.event_date ASC,
                e.event_id ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare audience-aware yearly events: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssiiiiiii',
            $startDate,
            $endDate,
            $includeAllValue,
            $roleId,
            $departmentId,
            $educationLevelId,
            $academicProgramId,
            $gradeLevelId,
            $sectionId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load audience-aware yearly events: '
                    . $error
            );
        }

        /*
     * The result remains buffered by mysqli
     * after get_result(), so callers can iterate it.
     */
        return $stmt->get_result();
    }

    /* ==========================================
   COUNT PUBLISHED EVENTS
========================================== */

    public function countPublished(): int
    {
        $stmt =
            $this->conn->prepare("
            SELECT COUNT(*) AS total
            FROM events
            WHERE status = 'active'
              AND workflow_status = 'published'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare published event count: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to count published events: '
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
   RECENT PUBLISHED EVENTS
========================================== */

    public function getRecent(
        int $limit = 10,
        array $audience = [],
        bool $includeAll = false
    ): array {
        $limit =
            max(
                1,
                min(
                    $limit,
                    100
                )
            );

        $includeAllValue =
            $includeAll
            ? 1
            : 0;

        $roleId =
            max(
                0,
                (int) (
                    $audience['role_id']
                    ?? 0
                )
            );

        $departmentId =
            max(
                0,
                (int) (
                    $audience['department_id']
                    ?? 0
                )
            );

        $educationLevelId =
            max(
                0,
                (int) (
                    $audience['education_level_id']
                    ?? 0
                )
            );

        $academicProgramId =
            max(
                0,
                (int) (
                    $audience['academic_program_id']
                    ?? 0
                )
            );

        $gradeLevelId =
            max(
                0,
                (int) (
                    $audience['grade_level_id']
                    ?? 0
                )
            );

        $sectionId =
            max(
                0,
                (int) (
                    $audience['section_id']
                    ?? 0
                )
            );

        $stmt =
            $this->conn->prepare("
            SELECT
                e.event_id,
                e.title,
                e.description,
                e.location,
                e.image_path,
                e.event_date,
                e.end_date,

                e.allow_reactions,
                e.allow_comments,
                e.require_acknowledgment,
                e.send_notification,

                e.created_at,
                e.status,
                e.workflow_status,
                e.user_id,

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

            FROM events e

            LEFT JOIN user u
                ON u.user_id =
                   e.user_id

            WHERE e.status = 'active'
              AND e.workflow_status = 'published'

              AND (
                    ? = 1

                    OR EXISTS (
                        SELECT 1

                        FROM event_target et

                        WHERE et.event_id =
                              e.event_id

                          AND (
                                et.role_id IS NULL
                                OR et.role_id = ?
                              )

                          AND (
                                et.department_id IS NULL
                                OR et.department_id = ?
                              )

                          AND (
                                et.education_level_id IS NULL
                                OR et.education_level_id = ?
                              )

                          AND (
                                et.academic_program_id IS NULL
                                OR et.academic_program_id = ?
                              )

                          AND (
                                et.grade_level_id IS NULL
                                OR et.grade_level_id = ?
                              )

                          AND (
                                et.section_id IS NULL
                                OR et.section_id = ?
                              )
                    )
                  )

            ORDER BY
                e.created_at DESC,
                e.event_id DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare audience-aware recent events: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iiiiiiii',
            $includeAllValue,
            $roleId,
            $departmentId,
            $educationLevelId,
            $academicProgramId,
            $gradeLevelId,
            $sectionId,
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load audience-aware recent events: '
                    . $error
            );
        }

        $events =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $events;
    }

    /* ==========================================
       UPCOMING EVENTS FOR SELECT FIELDS
    ========================================== */

    public function getUpcoming(
        int $limit = 100
    ): array {
        $limit = max(
            1,
            min(
                $limit,
                250
            )
        );

        $stmt = $this->conn->prepare("
            SELECT
                event_id,
                title,
                event_date,
                end_date,
                location
            FROM events
            WHERE status = 'active'
              AND workflow_status = 'published'
              AND event_date > NOW()
            ORDER BY event_date ASC
            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare upcoming events: '
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
                'Unable to load upcoming events: '
                    . $error
            );
        }

        $events = $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $events;
    }

    /* ==========================================
       FIND PUBLISHED EVENT
    ========================================== */

    public function findById(
        int $eventId
    ): ?array {
        $stmt = $this->conn->prepare("

  SELECT
    e.event_id,
    e.title,
    e.description,
    e.location,
    e.image_path,
    e.event_date,
    e.end_date,
    e.created_at,
    e.status,
    e.workflow_status,
    e.send_notification,
    e.allow_reactions,
    e.allow_comments,
    e.require_acknowledgment,
    e.user_id,

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

            FROM events e

            LEFT JOIN user u
                ON u.user_id = e.user_id

            WHERE e.event_id = ?
              AND e.status = 'active'
              AND e.workflow_status = 'published'

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event details: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $eventId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load event details: '
                    . $error
            );
        }

        $event = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $event ?: null;
    }

    /* ==========================================
   WORKFLOW LIST
========================================== */

    public function getByWorkflowStatus(
        string $workflowStatus,
        ?int $userId = null,
        int $limit = 100
    ): array {
        $allowedStatuses = [
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
                $allowedStatuses,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid event workflow status.'
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
            e.event_id,
            e.title,
            e.description,
            e.location,
            e.image_path,
            e.event_date,
            e.end_date,

            e.allow_reactions,
            e.allow_comments,
            e.require_acknowledgment,
            e.send_notification,

            e.created_at,
            e.status,
            e.workflow_status,

            e.reviewed_by,
            e.reviewed_at,
            e.review_notes,

            e.user_id,

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

        FROM events e

        LEFT JOIN user creator
            ON creator.user_id =
               e.user_id

        LEFT JOIN user reviewer
            ON reviewer.user_id =
               e.reviewed_by

        WHERE e.workflow_status = ?
    ";

        if (
            $userId !== null &&
            $userId > 0
        ) {
            $sql .= "
            AND e.user_id = ?
        ";
        }

        $sql .= "
        ORDER BY e.created_at DESC
        LIMIT ?
    ";

        $stmt =
            $this->conn->prepare(
                $sql
            );

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event workflow list: '
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
                'Unable to load event workflow list: '
                    . $error
            );
        }

        $events =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $events;
    }

    /* ==========================================
   FIND EVENT FOR WORKSPACE
========================================== */

    public function findWorkflowItemById(
        int $eventId
    ): ?array {
        if ($eventId <= 0) {
            throw new InvalidArgumentException(
                'Invalid event ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                e.event_id,
                e.title,
                e.description,
                e.location,
                e.image_path,
                e.event_date,
                e.end_date,

                e.allow_reactions,
                e.allow_comments,
                e.require_acknowledgment,
                e.send_notification,

                e.created_at,
                e.status,
                e.workflow_status,

                e.reviewed_by,
                e.reviewed_at,
                e.review_notes,

                e.user_id,

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

            FROM events e

            LEFT JOIN user creator
                ON creator.user_id =
                   e.user_id

            LEFT JOIN user reviewer
                ON reviewer.user_id =
                   e.reviewed_by

            WHERE e.event_id = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event workspace details: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $eventId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load event workspace details: '
                    . $error
            );
        }

        $event =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $event ?: null;
    }

    /* ==========================================
   SUBMIT EVENT FOR REVIEW
========================================== */

    public function submitForReview(
        int $eventId,
        int $userId
    ): bool {
        if (
            $eventId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid event or user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE events

            SET
                workflow_status =
                    'pending_review',

                status =
                    'inactive',

                reviewed_by =
                    NULL,

                reviewed_at =
                    NULL,

                review_notes =
                    NULL

            WHERE event_id = ?
              AND user_id = ?
              AND workflow_status IN (
                    'draft',
                    'rejected'
              )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event review submission: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $eventId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to submit event for review: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   APPROVE AND PUBLISH EVENT
========================================== */

    public function approve(
        int $eventId,
        int $reviewerId
    ): bool {
        if (
            $eventId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid event or reviewer ID.'
            );
        }

        $lookup =
            $this->conn->prepare("
            SELECT
                release_mode

            FROM events

            WHERE event_id = ?
              AND workflow_status =
                  'pending_review'

            LIMIT 1
        ");

        if (!$lookup) {
            throw new RuntimeException(
                'Unable to prepare Event approval lookup: '
                    . $this->conn->error
            );
        }

        $lookup->bind_param(
            'i',
            $eventId
        );

        if (!$lookup->execute()) {
            $error =
                $lookup->error;

            $lookup->close();

            throw new RuntimeException(
                'Unable to load the Event for approval: '
                    . $error
            );
        }

        $event =
            $lookup
            ->get_result()
            ->fetch_assoc();

        $lookup->close();

        if (!$event) {
            return false;
        }

        $releaseMode =
            strtolower(
                trim(
                    (string) (
                        $event['release_mode']
                        ?? 'immediate'
                    )
                )
            );

        $workflowStatus =
            in_array(
                $releaseMode,
                [
                    'scheduled',
                    'calendar'
                ],
                true
            )
            ? 'scheduled'
            : 'published';

        $status =
            $workflowStatus === 'published'
            ? 'active'
            : 'inactive';

        $stmt =
            $this->conn->prepare("
            UPDATE events

            SET
                workflow_status = ?,
                status = ?,
                reviewed_by = ?,
                reviewed_at = NOW(),
                review_notes = NULL

            WHERE event_id = ?
              AND workflow_status =
                  'pending_review'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Event approval: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssii',
            $workflowStatus,
            $status,
            $reviewerId,
            $eventId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to approve Event: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }
    /* ==========================================
   REJECT EVENT
========================================== */

    public function reject(
        int $eventId,
        int $reviewerId,
        string $reviewNotes
    ): bool {
        if (
            $eventId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid event or reviewer ID.'
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
            UPDATE events

            SET
                workflow_status =
                    'rejected',

                status =
                    'inactive',

                reviewed_by =
                    ?,

                reviewed_at =
                    NOW(),

                review_notes =
                    ?

            WHERE event_id = ?
              AND workflow_status =
                  'pending_review'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event rejection: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'isi',
            $reviewerId,
            $reviewNotes,
            $eventId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to reject event: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   ARCHIVE EVENT
========================================== */

    public function archive(
        int $eventId
    ): bool {
        if ($eventId <= 0) {
            throw new InvalidArgumentException(
                'Invalid event ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE events

            SET
                workflow_status =
                    'archived',

                status =
                    'inactive'

            WHERE event_id = ?
              AND workflow_status =
                  'published'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event archive: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $eventId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to archive event: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   RESTORE EVENT TO DRAFT
========================================== */

    public function restoreToDraft(
        int $eventId,
        int $userId
    ): bool {
        if (
            $eventId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid event or user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE events

            SET
                workflow_status =
                    'draft',

                status =
                    'inactive',

                reviewed_by =
                    NULL,

                reviewed_at =
                    NULL,

                review_notes =
                    NULL

            WHERE event_id = ?
              AND user_id = ?
              AND workflow_status IN (
                    'rejected',
                    'archived'
              )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare event draft restoration: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $eventId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to restore event to draft: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }
}
