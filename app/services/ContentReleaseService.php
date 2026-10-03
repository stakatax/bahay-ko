<?php

require_once __DIR__ . '/PublicationNotificationDispatcher.php';

require_once __DIR__
    . '/NotificationService.php';

class ContentReleaseService
{
    private ?PublicationNotificationDispatcher $publicationDispatcher = null;
    private mysqli $conn;

    public function __construct(?mysqli $connection = null)
    {
        if ($connection !== null) {
            $this->conn = $connection;
            return;
        }
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
       PROCESS ALL DUE CONTENT
    ========================================== */

    /** Cheap request fallback; the scheduled worker still uses the full processing path. */
    public function processPendingReleasesIfDue(): array
    {
        $checks = [];
        foreach (['events', 'announcements', 'documents', 'survey'] as $table) {
            $checks[] = "EXISTS(SELECT 1 FROM {$table} WHERE workflow_status = 'scheduled'
                AND release_mode = 'scheduled' AND scheduled_publish_at <= NOW())";
        }
        foreach (['announcements', 'documents', 'survey'] as $table) {
            $checks[] = "EXISTS(SELECT 1 FROM {$table} c INNER JOIN events e ON e.event_id = c.calendar_event_id
                WHERE c.workflow_status = 'scheduled' AND c.release_mode = 'calendar'
                AND e.workflow_status = 'published' AND e.status = 'active' AND e.event_date <= NOW())";
        }
        $checks[] = "EXISTS(SELECT 1 FROM publication_notification_outbox
            WHERE (delivery_status = 'Pending' AND available_at <= NOW())
               OR (delivery_status = 'Processing' AND locked_until <= NOW()))";
        $result = $this->conn->query('SELECT (' . implode(' OR ', $checks) . ') AS due');
        if (!$result) { throw new RuntimeException('Unable to check pending publication work.'); }
        $due = (bool) $result->fetch_assoc()['due'];
        $result->free();
        if ($due) { return $this->processPendingReleases(); }
        return [
            'scheduled_events' => 0, 'scheduled_announcements' => 0, 'scheduled_documents' => 0,
            'calendar_announcements' => 0, 'calendar_documents' => 0,
            'scheduled_surveys' => 0, 'calendar_surveys' => 0, 'total_released' => 0,
            'notifications' => ['eligible' => 0, 'created' => 0, 'duplicates' => 0, 'completed' => 0, 'cancelled' => 0, 'failed' => 0]
        ];
    }
    public function processPendingReleases(): array
    {
        if (!$this->conn->begin_transaction()) throw new RuntimeException('Unable to start scheduled publication.');

        try {
            /*
         * Lock and remember scheduled content that is
         * genuinely due during this transaction.
         */
            $scheduledEventIds =
                $this->collectDueScheduledIds(
                    'events',
                    'event_id'
                );

            $scheduledAnnouncementIds =
                $this->collectDueScheduledIds(
                    'announcements',
                    'announcement_id'
                );

            $scheduledDocumentIds =
                $this->collectDueScheduledIds(
                    'documents',
                    'document_id'
                );

            $scheduledSurveyIds =
                $this->collectDueScheduledIds(
                    'survey',
                    'survey_id'
                );

            /*
         * Events go first because calendar-based content
         * may depend on an Event released here.
         */
            $releasedEvents =
                $this->releaseScheduledEvents($scheduledEventIds);

            $releasedAnnouncements =
                $this->releaseScheduledAnnouncements($scheduledAnnouncementIds);

            $releasedDocuments =
                $this->releaseScheduledDocuments($scheduledDocumentIds);

            $releasedSurveys =
                $this->releaseScheduledSurveys($scheduledSurveyIds);

            /*
         * Collect calendar content after scheduled Events
         * have become published within this transaction.
         */
            $calendarAnnouncementIds =
                $this->collectDueCalendarIds(
                    'announcements',
                    'announcement_id'
                );

            $calendarDocumentIds =
                $this->collectDueCalendarIds(
                    'documents',
                    'document_id'
                );

            $calendarSurveyIds =
                $this->collectDueCalendarIds(
                    'survey',
                    'survey_id'
                );

            $calendarAnnouncements =
                $this->releaseCalendarAnnouncements($calendarAnnouncementIds);

            $calendarDocuments =
                $this->releaseCalendarDocuments($calendarDocumentIds);

            $calendarSurveys =
                $this->releaseCalendarSurveys($calendarSurveyIds);

            $outbox = new PublicationNotificationOutbox($this->conn);
            $releasedContent = [
                    'event' =>
                    $scheduledEventIds,

                    'announcement' =>
                    array_merge(
                        $scheduledAnnouncementIds,
                        $calendarAnnouncementIds
                    ),

                    'document' =>
                    array_merge(
                        $scheduledDocumentIds,
                        $calendarDocumentIds
                    ),

                    'survey' =>
                    array_merge(
                        $scheduledSurveyIds,
                        $calendarSurveyIds
                    )
                ];
            foreach ($releasedContent as $type => $ids) {
                foreach (array_unique($ids) as $id) $outbox->enqueue($type, (int)$id);
            }
            if (!$this->conn->commit()) throw new RuntimeException('Unable to commit scheduled publication.');

            // Delivery failure must not misreport the already committed publication.
            try {
                $notificationResult = ($this->publicationDispatcher ?? new PublicationNotificationDispatcher())->dispatch(25);
            } catch (Throwable $exception) {
                error_log('Publication dispatch unavailable: '.get_class($exception).' (code '.(int)$exception->getCode().').');
                $notificationResult = ['eligible'=>0, 'created'=>0, 'duplicates'=>0, 'failed'=>1];
            }

            return [
                'scheduled_events' =>
                $releasedEvents,

                'scheduled_announcements' =>
                $releasedAnnouncements,

                'scheduled_documents' =>
                $releasedDocuments,

                'calendar_announcements' =>
                $calendarAnnouncements,

                'calendar_documents' =>
                $calendarDocuments,

                'scheduled_surveys' =>
                $releasedSurveys,

                'calendar_surveys' =>
                $calendarSurveys,

                'total_released' =>
                $releasedEvents
                    + $releasedAnnouncements
                    + $releasedDocuments
                    + $releasedSurveys
                    + $calendarAnnouncements
                    + $calendarDocuments
                    + $calendarSurveys,

                'notifications' =>
                $notificationResult
            ];
        } catch (Throwable $exception) {
            $this->conn->rollback();

            throw new RuntimeException(
                'Unable to process scheduled content: '
                    . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private function collectDueScheduledIds(
        string $table,
        string $idColumn
    ): array {
        $allowedTables = [
            'announcements' =>
            'announcement_id',

            'events' =>
            'event_id',

            'documents' =>
            'document_id',

            'survey' =>
            'survey_id'
        ];

        if (
            !isset($allowedTables[$table]) ||
            $allowedTables[$table] !== $idColumn
        ) {
            throw new InvalidArgumentException(
                'Invalid scheduled-content source.'
            );
        }

        $result =
            $this->conn->query("
            SELECT
                {$idColumn}

            FROM {$table}

            WHERE workflow_status =
                    'scheduled'

              AND release_mode =
                    'scheduled'

              AND scheduled_publish_at
                    IS NOT NULL

              AND scheduled_publish_at
                    <= NOW()

            FOR UPDATE
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to collect scheduled '
                    . $table
                    . ': '
                    . $this->conn->error
            );
        }

        $ids = [];

        while (
            $row =
            $result->fetch_assoc()
        ) {
            $contentId =
                (int) (
                    $row[$idColumn]
                    ?? 0
                );

            if ($contentId > 0) {
                $ids[] =
                    $contentId;
            }
        }

        return $ids;
    }

    private function collectDueCalendarIds(
        string $table,
        string $idColumn
    ): array {
        $allowedTables = [
            'announcements' =>
            'announcement_id',

            'documents' =>
            'document_id',

            'survey' =>
            'survey_id'
        ];

        if (
            !isset($allowedTables[$table]) ||
            $allowedTables[$table] !== $idColumn
        ) {
            throw new InvalidArgumentException(
                'Invalid calendar-content source.'
            );
        }

        $contentAlias =
            $table === 'announcements'
            ? 'a'
            : (
                $table === 'documents'
                ? 'd'
                : 's'
            );

        $result =
            $this->conn->query("
            SELECT
                {$contentAlias}.{$idColumn}
                    AS content_id

            FROM {$table} {$contentAlias}

            INNER JOIN events e
                ON e.event_id =
                   {$contentAlias}.calendar_event_id

            WHERE {$contentAlias}.workflow_status =
                    'scheduled'

              AND {$contentAlias}.release_mode =
                    'calendar'

              AND {$contentAlias}.calendar_event_id
                    IS NOT NULL

              AND e.workflow_status =
                    'published'

              AND e.status =
                    'active'

              AND e.event_date
                    <= NOW()

            FOR UPDATE
        ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to collect calendar '
                    . $table
                    . ': '
                    . $this->conn->error
            );
        }

        $ids = [];

        while (
            $row =
            $result->fetch_assoc()
        ) {
            $contentId =
                (int) (
                    $row['content_id']
                    ?? 0
                );

            if ($contentId > 0) {
                $ids[] =
                    $contentId;
            }
        }

        return $ids;
    }

    /* ==========================================
       SCHEDULED ANNOUNCEMENTS
    ========================================== */

    private function releaseScheduledAnnouncements(array $ids): int
    {
        if ($ids === []) return 0;
        $idList = implode(',', array_map('intval', $ids));
        $stmt = $this->conn->prepare("
            UPDATE announcements

            SET
                workflow_status =
                    'published',

                status =
                    'active',

                published_at =
                    NOW()

            WHERE announcement_id IN ({$idList}) AND workflow_status =
                    'scheduled'

              AND release_mode =
                    'scheduled'

              AND scheduled_publish_at
                    IS NOT NULL

              AND scheduled_publish_at
                    <= NOW()
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare scheduled announcement release: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to release scheduled announcements: '
                    . $error
            );
        }

        $released =
            max(
                0,
                $stmt->affected_rows
            );

        $stmt->close();

        return $released;
    }

    /* ==========================================
       SCHEDULED EVENTS
    ========================================== */

    private function releaseScheduledEvents(array $ids): int
    {
        if ($ids === []) return 0;
        $idList = implode(',', array_map('intval', $ids));
        $stmt = $this->conn->prepare("
            UPDATE events

            SET
                workflow_status =
                    'published',

                status =
                    'active'

            WHERE event_id IN ({$idList}) AND workflow_status =
                    'scheduled'

              AND release_mode =
                    'scheduled'

              AND scheduled_publish_at
                    IS NOT NULL

              AND scheduled_publish_at
                    <= NOW()
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare scheduled event release: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to release scheduled events: '
                    . $error
            );
        }

        $released =
            max(
                0,
                $stmt->affected_rows
            );

        $stmt->close();

        return $released;
    }

    /* ==========================================
       SCHEDULED DOCUMENTS
    ========================================== */

    private function releaseScheduledDocuments(array $ids): int
    {
        if ($ids === []) return 0;
        $idList = implode(',', array_map('intval', $ids));
        $stmt = $this->conn->prepare("
            UPDATE documents

            SET
                workflow_status =
                    'published',

                status =
                    'active'

            WHERE document_id IN ({$idList}) AND workflow_status =
                    'scheduled'

              AND release_mode =
                    'scheduled'

              AND scheduled_publish_at
                    IS NOT NULL

              AND scheduled_publish_at
                    <= NOW()
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare scheduled document release: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to release scheduled documents: '
                    . $error
            );
        }

        $released =
            max(
                0,
                $stmt->affected_rows
            );

        $stmt->close();

        return $released;
    }

    /* ==========================================
   SCHEDULED SURVEYS
========================================== */

    private function releaseScheduledSurveys(array $ids): int
    {
        if ($ids === []) return 0;
        $idList = implode(',', array_map('intval', $ids));
        $stmt = $this->conn->prepare("
        UPDATE survey

        SET
            workflow_status =
                'published',

            status =
                'Published',

            published_at =
                NOW()

        WHERE survey_id IN ({$idList}) AND workflow_status =
                'scheduled'

          AND release_mode =
                'scheduled'

          AND scheduled_publish_at
                IS NOT NULL

          AND scheduled_publish_at
                <= NOW()
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare scheduled survey release: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to release scheduled surveys: '
                    . $error
            );
        }

        $released =
            max(
                0,
                $stmt->affected_rows
            );

        $stmt->close();

        return $released;
    }

    /* ==========================================
   CALENDAR-BASED SURVEYS
========================================== */

    private function releaseCalendarSurveys(array $ids): int
    {
        if ($ids === []) return 0;
        $idList = implode(',', array_map('intval', $ids));
        $stmt = $this->conn->prepare("
        UPDATE survey s

        INNER JOIN events e
            ON e.event_id =
               s.calendar_event_id

        SET
            s.workflow_status =
                'published',

            s.status =
                'Published',

            s.published_at =
                NOW()

        WHERE s.survey_id IN ({$idList}) AND s.workflow_status =
                'scheduled'

          AND s.release_mode =
                'calendar'

          AND s.calendar_event_id
                IS NOT NULL

          AND e.workflow_status =
                'published'

          AND e.status =
                'active'

          AND e.event_date
                <= NOW()
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare calendar survey release: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to release calendar surveys: '
                    . $error
            );
        }

        $released =
            max(
                0,
                $stmt->affected_rows
            );

        $stmt->close();

        return $released;
    }

    /* ==========================================
       CALENDAR-BASED ANNOUNCEMENTS
    ========================================== */

    private function releaseCalendarAnnouncements(array $ids): int
    {
        if ($ids === []) return 0;
        $idList = implode(',', array_map('intval', $ids));
        $stmt = $this->conn->prepare("
            UPDATE announcements a

            INNER JOIN events e
                ON e.event_id =
                   a.calendar_event_id

            SET
                a.workflow_status =
                    'published',

                a.status =
                    'active',

                a.published_at =
                    NOW()

            WHERE a.announcement_id IN ({$idList}) AND a.workflow_status =
                    'scheduled'

              AND a.release_mode =
                    'calendar'

              AND a.calendar_event_id
                    IS NOT NULL

              AND e.workflow_status =
                    'published'

              AND e.status =
                    'active'

              AND e.event_date
                    <= NOW()
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare calendar announcement release: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to release calendar announcements: '
                    . $error
            );
        }

        $released =
            max(
                0,
                $stmt->affected_rows
            );

        $stmt->close();

        return $released;
    }

    /* ==========================================
       CALENDAR-BASED DOCUMENTS
    ========================================== */

    private function releaseCalendarDocuments(array $ids): int
    {
        if ($ids === []) return 0;
        $idList = implode(',', array_map('intval', $ids));
        $stmt = $this->conn->prepare("
            UPDATE documents d

            INNER JOIN events e
                ON e.event_id =
                   d.calendar_event_id

            SET
                d.workflow_status =
                    'published',

                d.status =
                    'active'

            WHERE d.document_id IN ({$idList}) AND d.workflow_status =
                    'scheduled'

              AND d.release_mode =
                    'calendar'

              AND d.calendar_event_id
                    IS NOT NULL

              AND e.workflow_status =
                    'published'

              AND e.status =
                    'active'

              AND e.event_date
                    <= NOW()
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare calendar document release: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to release calendar documents: '
                    . $error
            );
        }

        $released =
            max(
                0,
                $stmt->affected_rows
            );

        $stmt->close();

        return $released;
    }
}
