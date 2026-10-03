<?php
require_once __DIR__ . '/ContentAudienceService.php';
require_once __DIR__ . '/../models/StudentProfileCycle.php';

require_once __DIR__
    . '/../models/Notification.php';

class NotificationService
{
    private Notification $notification;

    private mysqli $conn;

    private const CONTENT_CONFIG = [
        'announcement' => [
            'table' =>
            'announcements',

            'id_column' =>
            'announcement_id',

            'target_table' =>
            'announcement_target',

            'target_foreign_key' =>
            'announcement_id',

            'title_label' =>
            'New Announcement',

            'message_label' =>
            'A new announcement has been published for you.'
        ],

        'event' => [
            'table' =>
            'events',

            'id_column' =>
            'event_id',

            'target_table' =>
            'event_target',

            'target_foreign_key' =>
            'event_id',

            'title_label' =>
            'New Event',

            'message_label' =>
            'A new school event has been published for you.'
        ],

        'document' => [
            'table' =>
            'documents',

            'id_column' =>
            'document_id',

            'target_table' =>
            'document_target',

            'target_foreign_key' =>
            'document_id',

            'title_label' =>
            'New Document',

            'message_label' =>
            'A new document has been published for you.'
        ],

        'survey' => [
            'table' =>
            'survey',

            'id_column' =>
            'survey_id',

            'target_table' =>
            'survey_target',

            'target_foreign_key' =>
            'survey_id',

            'title_label' =>
            'New Survey',

            'message_label' =>
            'A new survey is available for your response.'
        ]
    ];

    public function __construct(?mysqli $connection = null)
    {
        if ($connection !== null) {
            $this->conn = $connection;
            $this->notification = new Notification($connection);
            return;
        }
        $this->notification =
            new Notification();

        require __DIR__
            . '/../../config/dbconnect.php';

        if (
            !isset($conn) ||
            !($conn instanceof mysqli)
        ) {
            throw new RuntimeException(
                'Database connection is unavailable.'
            );
        }

        $this->conn = $conn;
    }

    /* ==========================================
       USER NOTIFICATION CENTER
    ========================================== */

    public function getForUser(
        int $userId,
        int $limit = 50
    ): array {
        $this->validateUserId(
            $userId
        );

        $items =
            $this->notification
            ->getForUser(
                $userId,
                $limit
            );

        foreach (
            $items
            as &$item
        ) {
            $item['action_url'] =
                $this->buildActionUrl(
                    $item
                );
        }

        unset($item);

        return $items;
    }

    public function countUnread(
        int $userId
    ): int {
        $this->validateUserId(
            $userId
        );

        return $this->notification
            ->countUnread(
                $userId
            );
    }

    public function getPreferences(
        int $userId
    ): array {
        $this->validateUserId(
            $userId
        );

        $preferences =
            $this->notification
            ->getPreferences(
                $userId
            );

        $preferences['categories'] =
            $this->notification
            ->getCategoryPreferences(
                $userId
            );

        return $preferences;
    }

    public function updateSystemPreference(
        int $userId,
        bool $systemEnabled
    ): array {
        $this->validateUserId(
            $userId
        );

        $this->notification
            ->updateSystemPreference(
                $userId,
                $systemEnabled
            );

        return $this->notification
            ->getPreferences(
                $userId
            );
    }

    public function updateCategoryPreferences(
        int $userId,
        array $preferences
    ): array {
        $this->validateUserId(
            $userId
        );

        $updated =
            $this->notification
            ->updateCategoryPreferences(
                $userId,
                $preferences
            );

        if (!$updated) {
            throw new RuntimeException(
                'Notification categories could not be updated.'
            );
        }

        return $this->getPreferences(
            $userId
        );
    }

    public function updateEmailPreference(
        int $userId,
        bool $emailEnabled
    ): array {
        $this->validateUserId(
            $userId
        );

        $this->notification
            ->updateEmailPreference(
                $userId,
                $emailEnabled
            );

        return $this->notification
            ->getPreferences(
                $userId
            );
    }

    public function updateBrowserPushPreference(
        int $userId,
        bool $browserPushEnabled
    ): array {
        $this->validateUserId(
            $userId
        );

        $this->notification
            ->updateBrowserPushPreference(
                $userId,
                $browserPushEnabled
            );

        return $this->notification
            ->getPreferences(
                $userId
            );
    }

    /* ==========================================
       OPEN OWNED NOTIFICATION
    ========================================== */

    public function open(
        int $notificationId,
        int $userId
    ): string {
        $this->validateUserId(
            $userId
        );

        if ($notificationId <= 0) {
            throw new InvalidArgumentException(
                'Invalid notification ID.'
            );
        }

        $item =
            $this->notification
            ->findForUser(
                $notificationId,
                $userId
            );

        if (!$item) {
            throw new RuntimeException(
                'Notification not found.'
            );
        }

        $this->notification
            ->markAsRead(
                $notificationId,
                $userId
            );

        return $this->buildActionUrl(
            $item
        );
    }

    public function markAllAsRead(
        int $userId
    ): int {
        $this->validateUserId(
            $userId
        );

        return $this->notification
            ->markAllAsRead(
                $userId
            );
    }

    /* ==========================================
       STUDENT PROFILE CYCLE ASSIGNMENT
    ========================================== */

    public function recoverStudentProfileNotifications(int $limit = 100, ?int $cycleId = null): array
    {
        $summary = ['eligible'=>0, 'created'=>0, 'duplicates'=>0, 'failed'=>0];
        $rows = (new StudentProfileCycle($this->conn))->getMissingAssignmentNotifications($limit, $cycleId);
        foreach ($rows as $row) {
            $result = $this->notifyStudentProfileCycleAssigned(
                [(int) $row['user_id']], (int) $row['student_profile_cycle_id'],
                $row['cycle_name'], $row['due_at'], $row['opens_at']
            );
            foreach ($summary as $key => $value) $summary[$key] += (int) ($result[$key] ?? 0);
        }
        return $summary;
    }

    public function notifyStudentProfileCycleAssigned(
        array $studentUserIds,
        int $cycleId,
        string $cycleName,
        ?string $dueAt = null,
        ?string $opensAt = null
    ): array {
        if ($cycleId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Student profile cycle ID.'
            );
        }

        $cycleName =
            trim($cycleName);

        if ($cycleName === '') {
            throw new InvalidArgumentException(
                'Student profile cycle name is required.'
            );
        }

        $studentUserIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'intval',
                            $studentUserIds
                        ),
                        static fn(
                            int $userId
                        ): bool =>
                        $userId > 0
                    )
                )
            );

        $notificationTitle =
            'Student profile update required';

        $notificationMessage =
            'You have been assigned to complete '
            . $cycleName
            . '.';

        $dueAt =
            $dueAt !== null
            ? trim($dueAt)
            : '';

        if ($dueAt !== '') {
            $dueTimestamp =
                strtotime($dueAt);

            if ($dueTimestamp !== false) {
                $notificationMessage .=
                    ' Please complete it by '
                    . date(
                        'F j, Y',
                        $dueTimestamp
                    )
                    . '.';
            }
        }

        if ($opensAt && ($opensTimestamp = strtotime($opensAt)) !== false && $opensTimestamp > time()) {
            $notificationMessage .= ' Opens on ' . date('F j, Y, g:i A', $opensTimestamp) . '.';
        }
        $notificationMessage .=
            ' Open your Student Profile to review and submit your responses.';

        $deduplicationKey =
            'student-profile-cycle-assigned:cycle:'
            . $cycleId;

        $created = 0;
        $duplicates = 0;
        $failed = 0;

        foreach (
            $studentUserIds
            as $studentUserId
        ) {
            try {
                $wasCreated =
                    $this->notification
                    ->createForUser(
                        $studentUserId,
                        'reminder',
                        $notificationTitle,
                        $notificationMessage,
                        null,
                        null,
                        $deduplicationKey
                    );

                if ($wasCreated) {
                    $created++;
                } else {
                    $duplicates++;
                }
            } catch (Throwable $exception) {
                $failed++;

                error_log(
                    'Student profile cycle notification error: cycle #'
                        . $cycleId
                        . ' | Student user #'
                        . $studentUserId
                        . ' | '
                        . $exception->getMessage()
                );
            }
        }

        return [
            'eligible' =>
            count($studentUserIds),

            'created' =>
            $created,

            'duplicates' =>
            $duplicates,

            'failed' =>
            $failed
        ];
    }

    /* ==========================================
   REGISTRATION SUBMISSION DELIVERY
========================================== */

    public function notifyRegistrationSubmitted(
        int $applicantId,
        string $applicantName,
        string $roleType
    ): array {
        $this->validateUserId(
            $applicantId
        );

        $applicantName =
            trim($applicantName);

        if ($applicantName === '') {
            $applicantName =
                'A new applicant';
        }

        $roleType =
            ucfirst(
                strtolower(
                    trim($roleType)
                )
            );

        if (
            !in_array(
                $roleType,
                [
                    'Student',
                    'Parent'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid registration role.'
            );
        }

        /*
     * Notify only active Administrators who
     * have in-system notifications enabled.
     */
        $stmt =
            $this->conn->prepare("
        SELECT
            u.user_id

        FROM user u

        INNER JOIN role r
            ON r.role_id =
               u.role_id

        LEFT JOIN notification_preference np
            ON np.user_id =
               u.user_id

        WHERE u.status =
                'Active'

          AND r.role_prefix =
                'Admin'

          AND COALESCE(
                np.system_enabled,
                1
              ) = 1

        ORDER BY
            u.user_id ASC
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare registration notification recipients: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load registration notification recipients: '
                    . $error
            );
        }

        $result =
            $stmt->get_result();

        $adminIds = [];

        while (
            $row =
            $result->fetch_assoc()
        ) {
            $adminId =
                (int) (
                    $row['user_id']
                    ?? 0
                );

            if ($adminId > 0) {
                $adminIds[] =
                    $adminId;
            }
        }

        $stmt->close();

        $adminIds =
            array_values(
                array_unique(
                    $adminIds
                )
            );

        $notificationTitle =
            $roleType
            . ' registration awaiting review';

        $notificationMessage =
            $applicantName
            . ' submitted a '
            . strtolower($roleType)
            . ' account registration for Administrator review.';

        $deduplicationKey =
            'registration-submitted:user:'
            . $applicantId;

        $created = 0;
        $duplicates = 0;
        $failed = 0;

        foreach (
            $adminIds
            as $adminId
        ) {
            try {
                $wasCreated =
                    $this->notification
                    ->createForUser(
                        $adminId,
                        'system',
                        $notificationTitle,
                        $notificationMessage,
                        null,
                        null,
                        $deduplicationKey
                    );

                if ($wasCreated) {
                    $created++;
                } else {
                    $duplicates++;
                }
            } catch (Throwable $exception) {
                $failed++;

                error_log(
                    'Registration notification error: applicant #'
                        . $applicantId
                        . ' | Admin #'
                        . $adminId
                        . ' | '
                        . $exception->getMessage()
                );
            }
        }

        return [
            'eligible' =>
            count($adminIds),

            'created' =>
            $created,

            'duplicates' =>
            $duplicates,

            'failed' =>
            $failed
        ];
    }


    /* ==========================================
   REGISTRATION DECISION DELIVERY
========================================== */

    public function notifyRegistrationDecision(
        int $applicantId,
        string $applicantName,
        string $decision,
        ?string $reviewNotes = null
    ): bool {
        $this->validateUserId(
            $applicantId
        );

        $applicantName =
            trim($applicantName);

        if ($applicantName === '') {
            $applicantName =
                'Applicant';
        }

        $decision =
            strtolower(
                trim($decision)
            );

        if (
            !in_array(
                $decision,
                [
                    'approved',
                    'rejected'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid registration decision.'
            );
        }

        $reviewNotes =
            trim(
                (string) $reviewNotes
            );

        if ($decision === 'approved') {
            $title =
                'Your account was approved';

            $message =
                'Hello '
                . $applicantName
                . ', your registration was approved. '
                . 'You may now sign in to the OLSHCO Digital Hub.';

            if ($reviewNotes !== '') {
                $message .=
                    ' Administrator note: '
                    . $reviewNotes;
            }
        } else {
            $title =
                'Your registration was rejected';

            $message =
                'Hello '
                . $applicantName
                . ', your account registration was rejected.';

            if ($reviewNotes !== '') {
                $message .=
                    ' Reason: '
                    . $reviewNotes;
            }
        }

        return $this->notification
            ->createForUser(
                $applicantId,
                'system',
                $title,
                $message,
                null,
                null,
                'registration-'
                    . $decision
                    . ':user:'
                    . $applicantId
            );
    }


    /* ==========================================
   REVIEW SUBMISSION DELIVERY
========================================== */

    public function notifyReviewSubmission(
        int $submitterId,
        string $submitterName,
        string $contentType,
        int $contentId,
        string $contentTitle,
        string $submissionToken
    ): array {
        $this->validateUserId(
            $submitterId
        );

        $contentType =
            $this->validateContentType(
                $contentType
            );

        if ($contentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid review-submission content ID.'
            );
        }

        $submitterName =
            trim(
                $submitterName
            );

        if ($submitterName === '') {
            $submitterName =
                'A Faculty member';
        }

        $contentTitle =
            trim(
                $contentTitle
            );

        if ($contentTitle === '') {
            $contentTitle =
                ucfirst(
                    $contentType
                )
                . ' #'
                . $contentId;
        }

        $submissionToken =
            trim(
                $submissionToken
            );

        if ($submissionToken === '') {
            throw new InvalidArgumentException(
                'A review-submission token is required.'
            );
        }

        $contentLabel =
            match ($contentType) {
                'announcement' =>
                'Announcement',

                'event' =>
                'Event',

                'document' =>
                'Document',

                'survey' =>
                'Survey'
            };

        /*
     * Only active Admin accounts with in-system
     * notifications enabled receive review notices.
     */
        $stmt =
            $this->conn->prepare("
            SELECT
                u.user_id

            FROM user u

            INNER JOIN role r
                ON r.role_id =
                   u.role_id

            LEFT JOIN notification_preference np
                ON np.user_id =
                   u.user_id

            WHERE u.status =
                    'Active'

              AND r.role_prefix =
                    'Admin'

              AND u.user_id <> ?

              AND COALESCE(
                    np.system_enabled,
                    1
                  ) = 1

            ORDER BY
                u.user_id ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Admin notification recipients: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $submitterId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load Admin notification recipients: '
                    . $error
            );
        }

        $result =
            $stmt->get_result();

        $adminIds = [];

        while (
            $row =
            $result->fetch_assoc()
        ) {
            $adminId =
                (int) (
                    $row['user_id']
                    ?? 0
                );

            if ($adminId > 0) {
                $adminIds[] =
                    $adminId;
            }
        }

        $stmt->close();

        $adminIds =
            array_values(
                array_unique(
                    $adminIds
                )
            );

        $notificationTitle =
            $contentLabel
            . ' awaiting review';

        $notificationMessage =
            $submitterName
            . ' submitted the '
            . strtolower($contentLabel)
            . ' “'
            . $contentTitle
            . '” for administrative review.';

        $deduplicationKey =
            'workflow-submitted:'
            . $contentType
            . ':'
            . $contentId
            . ':'
            . sha1(
                $submissionToken
            );

        $summary = [
            'eligible' =>
            count($adminIds),

            'created' => 0,
            'duplicates' => 0,
            'failed' => 0
        ];

        foreach (
            $adminIds
            as $adminId
        ) {
            try {
                $created =
                    $this->notification
                    ->createForUser(
                        $adminId,
                        'workflow',
                        $notificationTitle,
                        $notificationMessage,
                        $contentType,
                        $contentId,
                        $deduplicationKey
                    );

                if ($created) {
                    $summary['created']++;
                } else {
                    $summary['duplicates']++;
                }
            } catch (Throwable $exception) {
                $summary['failed']++;

                error_log(
                    'Admin review notification error: '
                        . $contentType
                        . ' #'
                        . $contentId
                        . ' | Admin #'
                        . $adminId
                        . ' | '
                        . $exception->getMessage()
                );
            }
        }

        return $summary;
    }

    /* ==========================================
   WORKFLOW DECISION DELIVERY
========================================== */

    public function notifyWorkflowDecision(
        int $authorId,
        string $contentType,
        int $contentId,
        string $contentTitle,
        string $decision,
        string $workflowStatus,
        ?string $reviewNotes,
        string $decisionToken
    ): bool {
        $this->validateUserId(
            $authorId
        );

        $contentType =
            $this->validateContentType(
                $contentType
            );

        if ($contentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid workflow notification content ID.'
            );
        }

        $contentTitle =
            trim(
                $contentTitle
            );

        if ($contentTitle === '') {
            $contentTitle =
                ucfirst(
                    $contentType
                )
                . ' #'
                . $contentId;
        }

        $decision =
            strtolower(
                trim(
                    $decision
                )
            );

        if (
            !in_array(
                $decision,
                [
                    'approved',
                    'rejected'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid workflow decision.'
            );
        }

        $workflowStatus =
            strtolower(
                trim(
                    $workflowStatus
                )
            );

        $reviewNotes =
            trim(
                (string) $reviewNotes
            );

        $decisionToken =
            trim(
                $decisionToken
            );

        if ($decisionToken === '') {
            throw new InvalidArgumentException(
                'A workflow decision token is required.'
            );
        }

        /*
     * Workflow notices obey the same user-controlled
     * in-system notification preference.
     */
        if (
            !$this->notification
                ->isSystemEnabled(
                    $authorId
                )
        ) {
            return false;
        }

        $contentLabel =
            match ($contentType) {
                'announcement' =>
                'Announcement',

                'event' =>
                'Event',

                'document' =>
                'Document',

                'survey' =>
                'Survey'
            };

        if ($decision === 'rejected') {
            $notificationTitle =
                $contentLabel
                . ' requires revision';

            $notificationMessage =
                'Your '
                . strtolower($contentLabel)
                . ' “'
                . $contentTitle
                . '” was returned for revision.';

            if ($reviewNotes !== '') {
                $notificationMessage .=
                    ' Reviewer note: '
                    . $reviewNotes;
            }
        } elseif ($workflowStatus === 'scheduled') {
            $notificationTitle =
                $contentLabel
                . ' approved and scheduled';

            $notificationMessage =
                'Your '
                . strtolower($contentLabel)
                . ' “'
                . $contentTitle
                . '” was approved and is waiting for its scheduled release.';
        } else {
            $notificationTitle =
                $contentLabel
                . ' approved';

            $notificationMessage =
                'Your '
                . strtolower($contentLabel)
                . ' “'
                . $contentTitle
                . '” was approved and published.';
        }

        $deduplicationKey =
            'workflow-'
            . $decision
            . ':'
            . $contentType
            . ':'
            . $contentId
            . ':'
            . sha1(
                $decisionToken
            );

        return $this->notification
            ->createForUser(
                $authorId,
                'workflow',
                $notificationTitle,
                $notificationMessage,
                $contentType,
                $contentId,
                $deduplicationKey
            );
    }

    /* ==========================================
       PUBLISHED CONTENT DELIVERY
    ========================================== */

    public function notifyPublishedContent(
        string $contentType,
        int $contentId
    ): array {
        $contentType =
            $this->validateContentType(
                $contentType
            );

        if ($contentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid notification content ID.'
            );
        }

        $content =
            $this->findPublishedContent(
                $contentType,
                $contentId
            );

        if (!$content) {
            return [
                'eligible' => 0,
                'created' => 0,
                'duplicates' => 0
            ];
        }

        if (
            empty($content['send_notification'])
        ) {
            return [
                'eligible' => 0,
                'created' => 0,
                'duplicates' => 0
            ];
        }

        $recipients =
            $this->resolveRecipientIds(
                $contentType,
                $contentId,
                (int) (
                    $content['user_id']
                    ?? 0
                )
            );

        $config =
            self::CONTENT_CONFIG[$contentType];

        $contentTitle =
            trim(
                (string) (
                    $content['title']
                    ?? ''
                )
            );

        $message =
            $config['message_label'];

        if ($contentTitle !== '') {
            $message .=
                ' “'
                . $contentTitle
                . '”';
        }

        $deduplicationKey =
            'content-published:'
            . $contentType
            . ':'
            . $contentId;

        $created = 0;
        $duplicates = 0;

        foreach (
            $recipients
            as $recipient
        ) {
            $recipientId =
                (int) (
                    $recipient['user_id']
                    ?? 0
                );

            if ($recipientId <= 0) {
                continue;
            }

            $inSystemVisible =
                !empty($recipient['in_system_visible']);

            $wasCreated =
                $this->notification
                ->createForUser(
                    $recipientId,
                    'content',
                    $config['title_label'],
                    $message,
                    $contentType,
                    $contentId,
                    $deduplicationKey,
                    $inSystemVisible
                );

            if ($wasCreated) {
                $created++;
            } else {
                $duplicates++;
            }
        }

        return [
            'eligible' =>
            count($recipients),

            'created' =>
            $created,

            'duplicates' =>
            $duplicates
        ];
    }

    /* ==========================================
   UPCOMING EVENT REMINDERS
========================================== */

    public function processUpcomingEventReminders(): array
    {
        $result =
            $this->conn->query("
            SELECT
                event_id,
                title,
                location,
                event_date,
                user_id

            FROM events

            WHERE workflow_status =
                    'published'

              AND status =
                    'active'

             AND send_notification = 1

/*
 * Do not send a reminder immediately after the
 * same Event's publication notification.
 */
AND NOT EXISTS (
    SELECT 1

    FROM notification recent_publication

    WHERE recent_publication.notification_type =
            'content'

      AND recent_publication.content_type =
            'event'

      AND recent_publication.content_id =
            events.event_id

      AND recent_publication.created_at >
            DATE_SUB(
                NOW(),
                INTERVAL 60 MINUTE
            )
)

AND event_date > NOW()

              AND event_date <=
                    DATE_ADD(
                        NOW(),
                        INTERVAL 24 HOUR
                    )

            ORDER BY
                event_date ASC,
                event_id ASC
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load upcoming Event reminders: '
                    . $this->conn->error
            );
        }

        $events =
            $result->fetch_all(
                MYSQLI_ASSOC
            );

        $summary = [
            'events' =>
            count($events),

            'eligible' => 0,
            'created' => 0,
            'duplicates' => 0,
            'failed' => 0
        ];

        foreach (
            $events
            as $event
        ) {
            $eventId =
                (int) (
                    $event['event_id']
                    ?? 0
                );

            if ($eventId <= 0) {
                continue;
            }

            $authorId =
                (int) (
                    $event['user_id']
                    ?? 0
                );

            $recipients =
                $this->resolveRecipientIds(
                    'event',
                    $eventId,
                    $authorId
                );

            $summary['eligible'] +=
                count($recipients);

            $eventTitle =
                trim(
                    (string) (
                        $event['title']
                        ?? 'Upcoming Event'
                    )
                );

            $eventDate =
                trim(
                    (string) (
                        $event['event_date']
                        ?? ''
                    )
                );

            $eventLocation =
                trim(
                    (string) (
                        $event['location']
                        ?? ''
                    )
                );

            $formattedDate =
                $eventDate !== ''
                ? date(
                    'M d, Y g:i A',
                    strtotime(
                        $eventDate
                    )
                )
                : 'the scheduled time';

            $notificationTitle =
                'Upcoming Event reminder';

            $notificationMessage =
                '"'
                . $eventTitle
                . '" begins on '
                . $formattedDate
                . '.';

            if ($eventLocation !== '') {
                $notificationMessage .=
                    ' Location: '
                    . $eventLocation
                    . '.';
            }

            /*
         * One 24-hour reminder per recipient and Event.
         * Repeated page requests safely become duplicates.
         */
            $deduplicationKey =
                'event-reminder-24h:event:'
                . $eventId;

            foreach (
                $recipients
                as $recipient
            ) {
                $recipientId =
                    (int) (
                        $recipient['user_id']
                        ?? 0
                    );

                if ($recipientId <= 0) {
                    continue;
                }

                $inSystemVisible =
                    !empty($recipient['in_system_visible']);
                try {
                    $created =
                        $this->notification
                        ->createForUser(
                            $recipientId,
                            'reminder',
                            $notificationTitle,
                            $notificationMessage,
                            'event',
                            $eventId,
                            $deduplicationKey,
                            $inSystemVisible
                        );

                    if ($created) {
                        $summary['created']++;
                    } else {
                        $summary['duplicates']++;
                    }
                } catch (Throwable $exception) {
                    $summary['failed']++;

                    error_log(
                        'Event reminder notification error: '
                            . 'Event #'
                            . $eventId
                            . ' | User #'
                            . $recipientId
                            . ' | '
                            . $exception->getMessage()
                    );
                }
            }
        }

        return $summary;
    }

    /* ==========================================
       PUBLISHED CONTENT LOOKUP
    ========================================== */

    private function findPublishedContent(
        string $contentType,
        int $contentId
    ): ?array {
        $config =
            self::CONTENT_CONFIG[$contentType];

        /*
         * Table and column names come only
         * from the internal whitelist.
         */
        $table =
            $config['table'];

        $idColumn =
            $config['id_column'];

        $stmt =
            $this->conn->prepare("
            SELECT
                {$idColumn} AS content_id,
                title,
                workflow_status,
                status,
                send_notification,
                user_id

            FROM {$table}

            WHERE {$idColumn} = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare notification content lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $contentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load notification content: '
                    . $error
            );
        }

        $content =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$content) {
            return null;
        }

        $workflowStatus =
            strtolower(
                trim(
                    (string) (
                        $content['workflow_status']
                        ?? ''
                    )
                )
            );

        $status =
            strtolower(
                trim(
                    (string) (
                        $content['status']
                        ?? ''
                    )
                )
            );

        if (
            $workflowStatus !== 'published' ||
            !in_array(
                $status,
                [
                    'active',
                    'published'
                ],
                true
            )
        ) {
            return null;
        }

        return $content;
    }

    /* ==========================================
       REVERSE TARGET RECIPIENT MATCHING
    ========================================== */

    private function resolveRecipientIds(
        string $contentType,
        int $contentId,
        int $authorUserId
    ): array {
        $config =
            self::CONTENT_CONFIG[$contentType];

        /*
         * Dynamic identifiers are read only
         * from the internal content whitelist.
         */
        $targetTable =
            $config['target_table'];

        $foreignKey =
            $config['target_foreign_key'];

        $stmt =
            $this->conn->prepare("
            SELECT DISTINCT
                u.user_id, r.role_prefix,

                COALESCE(
                    np.system_enabled,
                    1
                ) AS in_system_visible

            FROM user u
            INNER JOIN role r ON r.role_id=u.role_id

            LEFT JOIN notification_preference np
                ON np.user_id =
                   u.user_id

            WHERE u.status = 'Active'

              AND u.user_id <> ?
               AND (
                    COALESCE(
                        np.system_enabled,
                        1
                    ) = 1

                    OR COALESCE(
                        np.browser_push_enabled,
                        0
                    ) = 1

                    OR (
                        COALESCE(
                            np.email_enabled,
                            0
                        ) = 1

                        AND np.email_enabled_at
                            IS NOT NULL
                    )
                  )

              AND (
                    r.role_prefix = 'Parent' OR NOT EXISTS (
                        SELECT 1

                        FROM {$targetTable}
                            all_targets

                        WHERE all_targets.{$foreignKey} = ?
                    )

                    OR EXISTS (
                        SELECT 1

                        FROM {$targetTable} target

                        WHERE target.{$foreignKey} = ?

                          AND (
                                COALESCE(
                                    target.role_id,
                                    0
                                ) = 0

                                OR target.role_id =
                                   u.role_id
                              )

                          AND (
                                COALESCE(
                                    target.department_id,
                                    0
                                ) = 0

                                OR target.department_id =
                                   u.department_id
                              )

                          AND (
                                COALESCE(
                                    target.education_level_id,
                                    0
                                ) = 0

                                OR target.education_level_id =
                                   u.education_level_id
                              )

                          AND (
                                COALESCE(
                                    target.academic_program_id,
                                    0
                                ) = 0

                                OR target.academic_program_id =
                                   u.academic_program_id
                              )

                          AND (
                                COALESCE(
                                    target.grade_level_id,
                                    0
                                ) = 0

                                OR target.grade_level_id =
                                   u.grade_level_id
                              )

                          AND (
                                COALESCE(
                                    target.section_id,
                                    0
                                ) = 0

                                OR target.section_id =
                                   u.section_id
                              )
                    )
                  )

            ORDER BY
                u.user_id ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare notification recipient resolution: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'iii',
            $authorUserId,
            $contentId,
            $contentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to resolve notification recipients: '
                    . $error
            );
        }

        $result =
            $stmt->get_result();

        $recipients = [];
        $audience = new ContentAudienceService(new ContentAudience($this->conn));

        while (
            $row =
            $result->fetch_assoc()
        ) {
            $userId =
                (int) (
                    $row['user_id']
                    ?? 0
                );

            if ($userId <= 0) {
                continue;
            }

            if ($row['role_prefix'] === 'Parent' && $audience->filterForUser($contentType, $contentType . '_id',
                [[$contentType . '_id' => $contentId]], $userId) === []) { continue; }

            $recipients[$userId] = [
                'user_id' =>
                $userId,

                'in_system_visible' =>
                !empty($row['in_system_visible'])
            ];
        }

        $stmt->close();

        return array_values(
            $recipients
        );
    }

    /* ==========================================
   CONTENT ENGAGEMENT NOTIFICATION
========================================== */

    public function notifyContentEngagement(
        int $recipientId,
        int $actorId,
        string $action,
        string $contentType,
        int $contentId,
        string $contentTitle,
        string $interactionKey,
        ?string $reaction = null
    ): bool {
        if (
            $recipientId <= 0 ||
            $actorId <= 0 ||
            $recipientId === $actorId
        ) {
            return false;
        }

        $contentType =
            $this->validateContentType(
                $contentType
            );

        if ($contentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid engagement content ID.'
            );
        }

        $action =
            strtolower(
                trim($action)
            );

        $allowedActions = [
            'reaction',
            'comment',
            'reply',
            'acknowledgment',
            'survey_response'
        ];

        if (
            !in_array(
                $action,
                $allowedActions,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid engagement notification action.'
            );
        }

        $interactionKey =
            trim(
                $interactionKey
            );

        if ($interactionKey === '') {
            throw new InvalidArgumentException(
                'An interaction key is required.'
            );
        }

        /*
     * Load the actor name while ensuring the
     * recipient remains active and permits
     * in-system notifications.
     */
        $stmt =
            $this->conn->prepare("
        SELECT
            recipient.user_id,

            TRIM(
                CONCAT(
                    COALESCE(
                        actor.first_name,
                        ''
                    ),
                    ' ',
                    COALESCE(
                        actor.last_name,
                        ''
                    )
                )
            ) AS actor_name

        FROM user recipient

        INNER JOIN user actor
            ON actor.user_id = ?

        LEFT JOIN notification_preference np
            ON np.user_id =
               recipient.user_id

        WHERE recipient.user_id = ?
          AND recipient.status = 'Active'

          AND COALESCE(
                np.system_enabled,
                1
              ) = 1

        LIMIT 1
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare engagement notification recipient: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $actorId,
            $recipientId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load engagement notification recipient: '
                    . $error
            );
        }

        $recipient =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$recipient) {
            return false;
        }

        $actorName =
            trim(
                (string) (
                    $recipient['actor_name']
                    ?? ''
                )
            );

        if ($actorName === '') {
            $actorName =
                'A user';
        }

        $contentTitle =
            trim(
                $contentTitle
            );

        if ($contentTitle === '') {
            $contentTitle =
                ucfirst(
                    $contentType
                )
                . ' #'
                . $contentId;
        }

        $contentLabel =
            match ($contentType) {
                'announcement' =>
                'announcement',

                'event' =>
                'event',

                'document' =>
                'document',

                'survey' =>
                'survey'
            };

        $notificationTitle =
            'Content activity';

        $notificationMessage =
            $actorName
            . ' interacted with your '
            . $contentLabel
            . ': '
            . $contentTitle
            . '.';

        switch ($action) {
            case 'reaction':
                $reaction =
                    trim(
                        (string) $reaction
                    );

                $notificationTitle =
                    'New reaction';

                $notificationMessage =
                    $actorName
                    . ' reacted'
                    . (
                        $reaction !== ''
                        ? ' with '
                        . $reaction
                        : ''
                    )
                    . ' to your '
                    . $contentLabel
                    . ': '
                    . $contentTitle
                    . '.';
                break;

            case 'comment':
                $notificationTitle =
                    'New comment';

                $notificationMessage =
                    $actorName
                    . ' commented on your '
                    . $contentLabel
                    . ': '
                    . $contentTitle
                    . '.';
                break;

            case 'reply':
                $notificationTitle =
                    'New reply';

                $notificationMessage =
                    $actorName
                    . ' replied to your comment on '
                    . $contentTitle
                    . '.';
                break;

            case 'acknowledgment':
                $notificationTitle =
                    'Content acknowledged';

                $notificationMessage =
                    $actorName
                    . ' acknowledged your '
                    . $contentLabel
                    . ': '
                    . $contentTitle
                    . '.';
                break;

            case 'survey_response':
                $notificationTitle =
                    'New survey response';

                /*
             * Do not disclose the respondent or
             * any submitted answer in the notice.
             */
                $notificationMessage =
                    'A participant submitted a response to your survey: '
                    . $contentTitle
                    . '.';
                break;
        }

        $deduplicationKey =
            'engagement:'
            . $action
            . ':recipient:'
            . $recipientId
            . ':actor:'
            . $actorId
            . ':'
            . $contentType
            . ':'
            . $contentId
            . ':'
            . $interactionKey;

        return $this->notification
            ->createForUser(
                $recipientId,
                'content',
                $notificationTitle,
                $notificationMessage,
                $contentType,
                $contentId,
                $deduplicationKey,
                true
            );
    }

    /* ==========================================
       SECURE INTERNAL ACTION URL
    ========================================== */

    private function buildActionUrl(
        array $notification
    ): string {
        $notificationType =
            strtolower(
                trim(
                    (string) (
                        $notification['notification_type']
                        ?? ''
                    )
                )
            );

        $contentType =
            strtolower(
                trim(
                    (string) (
                        $notification['content_type']
                        ?? ''
                    )
                )
            );

        $contentId =
            (int) (
                $notification['content_id']
                ?? 0
            );


        $deduplicationKey =
            trim(
                (string) (
                    $notification['deduplication_key']
                    ?? ''
                )
            );

        if ($notificationType === 'reminder'
            && preg_match('/^student-profile-cycle-assigned:cycle:[1-9][0-9]*$/', $deduplicationKey)) {
            return 'index.php?page=student_profile_survey';
        }

        /*
 * Registration notifications open the exact
 * applicant in the Account Approvals page.
 */
        if (
            $notificationType === 'system' &&
            preg_match(
                '/^registration-submitted:user:(\d+)$/',
                $deduplicationKey,
                $matches
            ) === 1
        ) {
            $registrationUserId =
                (int) (
                    $matches[1]
                    ?? 0
                );

            if ($registrationUserId > 0) {
                return 'index.php?'
                    . http_build_query([
                        'page' =>
                        'account_approvals',

                        'user_id' =>
                        $registrationUserId
                    ]);
            }
        }

        /*
 * Approved applicants can open their account
 * notification after signing in.
 */
        if (
            $notificationType === 'system' &&
            preg_match(
                '/^registration-approved:user:(\d+)$/',
                $deduplicationKey
            ) === 1
        ) {
            return 'index.php?page=account_profile';
        }

        /*
     * Workflow notifications are intended for
     * content authors and reviewers. Rejected,
     * draft, and scheduled content may not exist
     * in the public Information Hub.
     */
        if (
            $notificationType === 'workflow' &&
            in_array(
                $contentType,
                [
                    'announcement',
                    'event',
                    'document',
                    'survey'
                ],
                true
            ) &&
            $contentId > 0
        ) {
            return 'index.php?'
                . http_build_query([
                    'page' =>
                    'content_workspace',

                    'open_type' =>
                    $contentType,

                    'open_id' =>
                    $contentId
                ]);
        }

        /*
 * Survey response notifications are intended
 * for the Survey author and open the results
 * page instead of the participation form.
 */
        if (
            $contentType === 'survey' &&
            $contentId > 0 &&
            str_starts_with(
                $deduplicationKey,
                'engagement:survey_response:'
            )
        ) {
            return 'index.php?'
                . http_build_query([
                    'page' =>
                    'survey_results',

                    'survey_id' =>
                    $contentId
                ]);
        }

        /*
     * Published Survey notifications open the
     * Survey participation page directly.
     */
        if (
            $contentType === 'survey' &&
            $contentId > 0
        ) {
            return 'index.php?'
                . http_build_query([
                    'page' =>
                    'survey_participate',

                    'survey_id' =>
                    $contentId
                ]);
        }

        /*
     * Published Announcement, Event, and Document
     * notifications open the matching Hub item.
     */
        if (
            in_array(
                $contentType,
                [
                    'announcement',
                    'event',
                    'document'
                ],
                true
            ) &&
            $contentId > 0
        ) {
            return 'index.php?'
                . http_build_query([
                    'page' =>
                    'news',

                    'open_type' =>
                    $contentType,

                    'open_id' =>
                    $contentId
                ]);
        }

        /* Notices without a related content item stay in Notifications. */
        return 'index.php?page=notifications';
    }
    /* ==========================================
       VALIDATION
    ========================================== */

    private function validateContentType(
        string $contentType
    ): string {
        $contentType =
            strtolower(
                trim($contentType)
            );

        if (
            !array_key_exists(
                $contentType,
                self::CONTENT_CONFIG
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid notification content type.'
            );
        }

        return $contentType;
    }

    private function validateUserId(
        int $userId
    ): void {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid notification user ID.'
            );
        }
    }
}
