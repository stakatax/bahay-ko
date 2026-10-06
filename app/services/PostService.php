<?php

require_once __DIR__ . '/DocumentUploadValidator.php';
require_once __DIR__ . '/GovernmentAdvisoryIntakeService.php';
require_once __DIR__ . '/PublicationTransaction.php';
require_once __DIR__ . '/PublicationNotificationDispatcher.php';

require_once __DIR__ . '/FacultyScopeService.php';

require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/ContentAudienceService.php';
require_once __DIR__ . '/../models/Announcement.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/ContentEngagement.php';
require_once __DIR__
    . '/../models/ContentInterest.php';
require_once __DIR__ . '/../models/Survey.php';
require_once __DIR__
    . '/NotificationService.php';
require_once __DIR__
    . '/../models/StudentProfile.php';
require_once __DIR__
    . '/ContentRedundancyService.php';

class PostService
{
    private ?PublicationNotificationDispatcher $publicationDispatcher = null;
    private ?GovernmentAdvisoryIntakeService $advisories = null;
    private ?FacultyScopeService $facultyScope = null;
    private Event $event;
    private Announcement $announcement;
    private Document $document;
    private Survey $survey;
    private ContentEngagement $engagement;
    private ContentInterest $contentInterest;

    private StudentProfile $studentProfile;

    private ?ContentRedundancyService $redundancy = null;

    private ?NotificationService $notifications = null;
    public function __construct(?mysqli $connection = null)
    {
        $this->event =
            new Event($connection);

        $this->announcement =
            new Announcement($connection);

        $this->document =
            new Document($connection);

        $this->survey =
            new Survey($connection);

        $this->engagement =
            new ContentEngagement($connection);


        $this->contentInterest =
            new ContentInterest($connection);

        $this->studentProfile =
            new StudentProfile($connection);
    }


    private function getNotifications(): NotificationService
    {
        return $this->notifications ??= new NotificationService();
    }

    private function getRedundancy(): ContentRedundancyService
    {
        return $this->redundancy ??= new ContentRedundancyService();
    }

    /* ==========================================
       CONTENT TOPICS
    ========================================== */

    public function getContentInterests(): array
    {
        return $this->contentInterest
            ->getDisplayInterests();
    }


    /* ==========================================
   CONTENT REDUNDANCY PREFLIGHT
========================================== */

    public function assessContentRedundancy(
        array $data
    ): array {
        return $this->getRedundancy()
            ->assessSubmission(
                $data
            );
    }


    /* ==========================================
       INFORMATION HUB
    ========================================== */

    public function getNewsFeed(): array
    {
        $userId = (int) (
            $_SESSION['user_id']
            ?? 0
        );


        $announcements =
            $this->announcement->getRecent(
                100,
                false
            );



        /*
 * Load the published Event candidate set here.
 * The shared recipient filter below performs
 * the final role/academic authorization and
 * includes verified linked-Student profiles
 * for Parent accounts.
 */
        $events =
            $this->event->getRecent(
                100,
                [],
                true
            );



        $documents =
            $this->document->getRecent(
                100
            );




        $surveys =
            $this->survey->getRecent(
                100
            );



        /* ==========================================
   APPLY RECIPIENT VISIBILITY
========================================== */

        $visible = (new ContentAudienceService(new ContentAudience($this->announcement->getDatabaseConnection())))->filterSetsForUser([
            'announcement' => $announcements,
            'event' => $events,
            'document' => $documents,
            'survey' => $surveys
        ], $userId, true);
        $announcements = $this->announcement->attachGovernmentSources($visible['announcement']);
        $events = $visible['event'];
        $documents = $visible['document'];
        $surveys = $visible['survey'];

        $respondedSurveyIds = $this->survey->getRespondedSurveyIds(array_column($surveys, 'survey_id'), $userId);
        foreach ($surveys as &$surveyItem) {
            $surveyId =
                (int) (
                    $surveyItem['survey_id']
                    ?? 0
                );

            $surveyItem['user_responded'] =
                isset($respondedSurveyIds[$surveyId]);
        }

        unset($surveyItem);


        /* ==========================================
   PERSONALIZED VISIBLE COUNTS
========================================== */

        $totalAnnouncementCount =
            count($announcements);

        $totalEventCount =
            count($events);

        $totalDocumentCount =
            count($documents);

        $totalSurveyCount =
            count($surveys);

        $feedEngagement = $this->engagement->getEngagementSets([
            'announcement' => array_column($announcements, 'announcement_id'),
            'event' => array_column($events, 'event_id'),
            'document' => array_column($documents, 'document_id'),
            'survey' => array_column($surveys, 'survey_id')
        ], $userId);
        $announcements = $this->attachEngagement('announcement', 'announcement_id', $announcements, $userId, $feedEngagement['announcement']);
        $events = $this->attachEngagement('event', 'event_id', $events, $userId, $feedEngagement['event']);
        $documents = $this->attachEngagement('document', 'document_id', $documents, $userId, $feedEngagement['document']);
        $surveys = $this->attachEngagement('survey', 'survey_id', $surveys, $userId, $feedEngagement['survey']);

        $feedLabels = (new ContentAudience($this->announcement->getDatabaseConnection()))->getLabelSets([
            'announcement' => array_column($announcements, 'announcement_id'),
            'event' => array_column($events, 'event_id'),
            'document' => array_column($documents, 'document_id'),
            'survey' => array_column($surveys, 'survey_id')
        ]);
        $announcements = $this->attachTargetTags('announcement', 'announcement_id', $announcements, $feedLabels['announcement']);
        $events = $this->attachTargetTags('event', 'event_id', $events, $feedLabels['event']);
        $documents = $this->attachTargetTags('document', 'document_id', $documents, $feedLabels['document']);
        $surveys = $this->attachTargetTags('survey', 'survey_id', $surveys, $feedLabels['survey']);

        $feedTopics = $this->contentInterest->getAssignmentSets([
            'announcement' => array_column($announcements, 'announcement_id'),
            'event' => array_column($events, 'event_id'),
            'document' => array_column($documents, 'document_id'),
            'survey' => array_column($surveys, 'survey_id')
        ]);
        $announcements = $this->attachContentInterests('announcement', 'announcement_id', $announcements, $feedTopics['announcement']);
        $events = $this->attachContentInterests('event', 'event_id', $events, $feedTopics['event']);
        $documents = $this->attachContentInterests('document', 'document_id', $documents, $feedTopics['document']);
        $surveys = $this->attachContentInterests('survey', 'survey_id', $surveys, $feedTopics['survey']);
        $currentUserInterestWeights =
            $this->getCurrentUserInterestWeights();
        require_once __DIR__ . '/FeedPreference.php';
        $learnedTopicScores = FeedPreference::topics([$announcements, $events, $documents, $surveys]);

        return [
            'announcements' =>
            $announcements,

            'events' =>
            $events,

            'documents' =>
            $documents,

            'surveys' =>
            $surveys,

            'total_announcement_count' =>
            $totalAnnouncementCount,

            'total_event_count' =>
            $totalEventCount,

            'total_document_count' =>
            $totalDocumentCount,

            'total_survey_count' =>
            $totalSurveyCount,

            'current_user_interest_weights' =>
            $currentUserInterestWeights,

            'learned_topic_scores' => $learnedTopicScores,
        ];
    }


    /* ==========================================
   AUTHENTICATED HOME SUMMARY
========================================== */

    public function getHomeSummary(): array
    {
        /*
     * Keep the Home query intentionally small.
     * Full engagement, tags, interests, comments,
     * and survey response data belong to the
     * Information Hub rather than the landing page.
     */
        $announcements =
            $this->announcement
            ->getRecent(
                20
            );

        $announcements =
            $this->filterByCurrentUserTargets(
                'announcement',
                'announcement_id',
                $announcements
            );

        /*
     * Upcoming events are already ordered by
     * event_date ascending. Load a wider candidate
     * set before filtering because the earliest
     * events may target a different audience.
     */
        $events =
            $this->event
            ->getUpcoming(
                100
            );

        $events =
            $this->filterByCurrentUserTargets(
                'event',
                'event_id',
                $events
            );

        return [
            'priority_announcement' =>
            $announcements[0]
                ?? null,

            'next_event' =>
            $events[0]
                ?? null
        ];
    }



    private function attachEngagement(
        string $contentType,
        string $idColumn,
        array $items,
        int $userId,
        ?array $engagementMap = null
    ): array {
        $engagementMap ??= $this->engagement->getEngagementBatch($contentType, array_column($items, $idColumn), $userId);
        foreach ($items as &$item) {
            $contentId = (int) (
                $item[$idColumn]
                ?? 0
            );

            if ($contentId <= 0) {
                continue;
            }

            $engagement = $engagementMap[$contentId];

            $item = array_merge(
                $item,
                $engagement
            );

            $breakdown =
                $engagement['reaction_breakdown']
                ?? [];

            $item['upvote_count'] = (int) ($breakdown['Upvote'] ?? 0);
            $item['downvote_count'] = (int) ($breakdown['Downvote'] ?? 0);

        }

        unset($item);

        return $items;
    }


    private function getCurrentUserInterestWeights(): array
    {
        $currentRole =
            trim(
                (string) (
                    $_SESSION['role']
                    ?? ''
                )
            );

        $userId =
            (int) (
                $_SESSION['user_id']
                ?? 0
            );

        if (
            $currentRole !== 'Student' ||
            $userId <= 0
        ) {
            return [];
        }

        $interests = $this->studentProfile->getPersonalizationInterests($userId);
        $interestWeights = [];

        foreach ($interests as $interest) {
            $interestId =
                (int) (
                    $interest['interest_id']
                    ?? 0
                );

            $preferenceWeight =
                max(
                    1,
                    min(
                        5,
                        (int) (
                            $interest['preference_weight']
                            ?? 1
                        )
                    )
                );

            if ($interestId > 0) {
                $interestWeights[$interestId] =
                    $preferenceWeight;
            }
        }

        return $interestWeights;
    }

    private function attachContentInterests(
        string $contentType,
        string $idColumn,
        array $items,
        ?array $assignmentMap = null
    ): array {
        if ($items === []) {
            return $items;
        }

        $contentIds = [];

        foreach ($items as $item) {
            $contentId =
                (int) (
                    $item[$idColumn]
                    ?? 0
                );

            if ($contentId > 0) {
                $contentIds[] =
                    $contentId;
            }
        }

        $assignmentMap ??=
            $this->contentInterest
            ->getAssignmentMap(
                $contentType,
                $contentIds
            );

        foreach ($items as &$item) {
            $contentId =
                (int) (
                    $item[$idColumn]
                    ?? 0
                );

            $assignments =
                $assignmentMap[$contentId]
                ?? [];

            $item['interest_assignments'] =
                $assignments;

            $item['interest_ids'] =
                array_values(
                    array_map(
                        static fn(
                            array $assignment
                        ): int =>
                        (int) (
                            $assignment['interest_id']
                            ?? 0
                        ),
                        $assignments
                    )
                );
        }

        unset($item);

        return $items;
    }

    private function attachTargetTags(
        string $contentType,
        string $idColumn,
        array $items,
        ?array $targetMap = null
    ): array {
        if ($items === []) { return $items; }
        $targetMap ??= (new ContentAudience($this->announcement->getDatabaseConnection()))->getLabelSets([
            $contentType => array_column($items, $idColumn)
        ])[$contentType];
        foreach ($items as &$item) {
            $item['target_tags'] = $targetMap[(int) ($item[$idColumn] ?? 0)] ?? [];
        }
        unset($item);
        return $items;
    }

    /* ==========================================
   FILTER CONTENT BY CURRENT USER TARGET
========================================== */

    private function filterByCurrentUserTargets(
        string $contentType,
        string $idColumn,
        array $items
    ): array {
        return (new ContentAudienceService())->filterForUser(
            $contentType,
            $idColumn,
            $items,
            (int) ($_SESSION['user_id'] ?? 0),
            true
        );
    }

    /* ==========================================
       POSTING FORM OPTIONS
    ========================================== */

    public function getDepartments(): array
    {
        require __DIR__
            . '/../../config/dbconnect.php';

        $result = $conn->query("
        SELECT
            d.department_id,
            d.department_name

        FROM department d

        WHERE d.status = 'Active'

        ORDER BY
            d.department_name ASC
    ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load departments.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    public function getEducationLevels(): array
    {
        require __DIR__
            . '/../../config/dbconnect.php';

        $result = $conn->query("
        SELECT
            el.education_level_id,
            el.department_id,
            el.education_level_name

        FROM education_level el

        INNER JOIN department d
            ON d.department_id =
                el.department_id
           AND d.status = 'Active'

        WHERE el.status = 'Active'

        ORDER BY
            d.department_name ASC,

            FIELD(
                el.education_level_name,
                'Elementary',
                'Junior High',
                'Senior High',
                'College'
            ),

            el.education_level_name ASC
    ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load education levels.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    public function getAcademicPrograms(): array
    {
        require __DIR__
            . '/../../config/dbconnect.php';

        $result = $conn->query("
        SELECT
            ap.academic_program_id,
            ap.education_level_id,
            ap.program_name,
            ap.program_code,
            ap.program_type

        FROM academic_program ap

        INNER JOIN education_level el
            ON el.education_level_id =
                ap.education_level_id
           AND el.status = 'Active'

        INNER JOIN department d
            ON d.department_id =
                el.department_id
           AND d.status = 'Active'

        WHERE ap.status = 'Active'

        ORDER BY
            ap.education_level_id ASC,
            ap.program_type ASC,
            ap.program_code ASC
    ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load programs and strands.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    public function getGradeLevels(): array
    {
        require __DIR__
            . '/../../config/dbconnect.php';

        $result = $conn->query("
        SELECT
            gl.grade_level_id,
            gl.education_level_id,
            gl.grade_level_name

        FROM grade_level gl

        INNER JOIN education_level el
            ON el.education_level_id =
                gl.education_level_id
           AND el.status = 'Active'

        INNER JOIN department d
            ON d.department_id =
                el.department_id
           AND d.status = 'Active'

        WHERE gl.status = 'Active'

        ORDER BY
            gl.education_level_id ASC,
            gl.grade_level_id ASC
    ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load grade levels.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    public function getSections(): array
    {
        require __DIR__
            . '/../../config/dbconnect.php';

        $result = $conn->query("
        SELECT
            s.section_id,
            s.grade_level_id,
            s.academic_program_id,
            s.section_name

        FROM section s

        INNER JOIN grade_level gl
            ON gl.grade_level_id =
                s.grade_level_id
           AND gl.status = 'Active'

        INNER JOIN education_level el
            ON el.education_level_id =
                gl.education_level_id
           AND el.status = 'Active'

        INNER JOIN department d
            ON d.department_id =
                el.department_id
           AND d.status = 'Active'

        LEFT JOIN academic_program ap
            ON ap.academic_program_id =
                s.academic_program_id

        WHERE s.status = 'Active'
          AND (
                s.academic_program_id IS NULL
                OR (
                    ap.academic_program_id IS NOT NULL
                    AND ap.status = 'Active'
                    AND ap.education_level_id =
                        gl.education_level_id
                )
              )

        ORDER BY
            gl.education_level_id ASC,
            s.grade_level_id ASC,
            s.academic_program_id ASC,
            s.section_name ASC
    ");

        if (!$result) {
            throw new RuntimeException(
                'Unable to load sections.'
            );
        }

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    public function getUpcomingEvents(): array
    {
        return $this->event->getUpcoming(
            100
        );
    }

    /* ==========================================
   LOAD CONTENT FOR EDIT
========================================== */

    public function getEditableContent(
        string $contentType,
        int $contentId,
        int $userId
    ): array {
        $contentType =
            strtolower(
                trim(
                    $contentType
                )
            );

        if (
            $contentId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid content or user ID.'
            );
        }

        if (
            !in_array(
                $contentType,
                [
                    'announcement',
                    'event',
                    'document',
                    'survey'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Editing this content type is not supported yet.'
            );
        }

        $item = match ($contentType) {
            'announcement' =>
            $this->announcement
                ->findWorkflowItemById(
                    $contentId
                ),

            'event' =>
            $this->event
                ->findWorkflowItemById(
                    $contentId
                ),

            'document' =>
            $this->document
                ->findWorkflowItemById(
                    $contentId
                ),

            'survey' =>
            $this->survey
                ->findWorkflowItemById(
                    $contentId
                ),

            default =>
            null
        };

        if (!$item) {
            throw new RuntimeException(
                'The '
                    . $contentType
                    . ' could not be found.'
            );
        }

        if (
            (int) (
                $item['user_id']
                ?? 0
            ) !== $userId
        ) {
            throw new RuntimeException(
                'You are not allowed to edit this '
                    . $contentType
                    . '.'
            );
        }

        $workflowStatus =
            strtolower(
                trim(
                    (string) (
                        $item['workflow_status']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $workflowStatus,
                [
                    'draft',
                    'rejected'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only draft or rejected '
                    . $contentType
                    . ' content can be edited.'
            );
        }

        if (
            $contentType === 'survey' &&
            (int) (
                $item['response_count']
                ?? 0
            ) > 0
        ) {
            throw new RuntimeException(
                'A survey with submitted responses cannot be edited.'
            );
        }

        $item['targets'] =
            match ($contentType) {
                'announcement' =>
                $this->announcement
                    ->getTargets(
                        $contentId
                    ),

                'event' =>
                $this->event
                    ->getTargets(
                        $contentId
                    ),

                'document' =>
                $this->document
                    ->getTargets(
                        $contentId
                    ),

                'survey' =>
                $this->survey
                    ->getTargets(
                        $contentId
                    ),

                default =>
                []
            };

        $item['interest_assignments'] =
            $this->contentInterest
            ->getAssignments(
                $contentType,
                $contentId
            );

        $item['interest_ids'] =
            array_values(
                array_map(
                    static fn(
                        array $assignment
                    ): int =>
                    (int) (
                        $assignment['interest_id']
                        ?? 0
                    ),
                    $item['interest_assignments']
                )
            );

        return $item;
    }
    /* ==========================================
       CREATE CONTENT
    ========================================== */

    public function create(
        array $data,
        array $files,
        int $userId
    ): int {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'A valid authenticated user is required.'
            );
        }

        require_once __DIR__
            . '/../../config/logging.php';

        global $conn;

        $postType =
            strtolower(
                trim(
                    (string) (
                        $data['post_type']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $postType,
                [
                    'announcement',
                    'event',
                    'document'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid post type.'
            );
        }
        if (array_key_exists('government_advisory_id', $data) || array_key_exists('government_advisory_url', $data)) {
            $this->advisories ??= new GovernmentAdvisoryIntakeService(new GovernmentAdvisory($this->announcement->getDatabaseConnection()));
            $data['government_advisory_id'] = $this->advisories->validateAnnouncementAttachment($data, $userId);
        }
        $redundancyAssessment =
            $this->getRedundancy()
            ->enforceSubmission(
                $data
            );

        $contentInterestIds =
            $this->normalizeContentInterestIds(
                $data['content_interest_ids']
                    ?? []
            );

        $userName =
            trim(
                (string) (
                    $_SESSION['name']
                    ?? 'Unknown user'
                )
            );

        $editId =
            (int) (
                $data['edit_id']
                ?? 0
            );

        if ($editId > 0) {
            $contentId =
                match ($postType) {
                    'announcement' =>
                    $this->updateAnnouncement(
                        $editId,
                        $data,
                        $files,
                        $userId,
                        $userName,
                        $conn
                    ),

                    'event' =>
                    $this->updateEvent(
                        $editId,
                        $data,
                        $files,
                        $userId,
                        $userName,
                        $conn
                    ),

                    'document' =>
                    $this->updateDocument(
                        $editId,
                        $data,
                        $files,
                        $userId,
                        $userName,
                        $conn
                    ),

                    default =>
                    throw new InvalidArgumentException(
                        'Editing this content type is not supported yet.'
                    )
                };
        } else {
            $contentId =
                match ($postType) {
                    'announcement' =>
                    $this->createAnnouncement(
                        $data,
                        $files,
                        $userId,
                        $userName,
                        $conn
                    ),

                    'event' =>
                    $this->createEvent(
                        $data,
                        $files,
                        $userId,
                        $userName,
                        $conn
                    ),

                    'document' =>
                    $this->createDocument(
                        $data,
                        $files,
                        $userId,
                        $userName,
                        $conn
                    ),

                    default =>
                    throw new InvalidArgumentException(
                        'Invalid post type.'
                    )
                };
        }

        if ($contentId <= 0) {
            throw new RuntimeException(
                'The saved content could not be identified.'
            );
        }

        $this->contentInterest
            ->replaceAssignments(
                $postType,
                $contentId,
                $contentInterestIds,
                $userId
            );

        if (
            !empty($redundancyAssessment['override_reason'])
        ) {
            $matchedRecords =
                array_map(
                    static function (
                        array $match
                    ): string {
                        return (
                            $match['content_type']
                            ?? 'content'
                        )
                            . ' #'
                            . (
                                (int) (
                                    $match['content_id']
                                    ?? 0
                                )
                            );
                    },
                    $redundancyAssessment['matches']
                        ?? []
                );

            logActivity(
                $conn,
                'OVERRIDE_CONTENT_REDUNDANCY',
                'Saved '
                    . $postType
                    . ' #'
                    . $contentId
                    . ' after reviewing similar records'
                    . (
                        $matchedRecords !== []
                        ? ': '
                        . implode(
                            ', ',
                            $matchedRecords
                        )
                        : ''
                    )
                    . '. Reason: '
                    . $redundancyAssessment['override_reason']
            );
        }

        return $contentId;
    }

    private function normalizeContentInterestIds(
        mixed $interestIds
    ): array {
        if (!is_array($interestIds)) {
            $interestIds = [
                $interestIds
            ];
        }

        $interestIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(
                                mixed $interestId
                            ): int =>
                            (int) $interestId,
                            $interestIds
                        ),
                        static fn(
                            int $interestId
                        ): bool =>
                        $interestId > 0
                    )
                )
            );

        if ($interestIds === []) {
            throw new InvalidArgumentException(
                'Select at least one content topic.'
            );
        }

        $activeInterestIds = [];

        foreach (
            $this->contentInterest
                ->getActiveInterests()
            as $interest
        ) {
            $activeInterestIds[(int) (
                $interest['interest_id']
                ?? 0
            )] = true;
        }

        foreach (
            $interestIds as $interestId
        ) {
            if (
                !isset(
                    $activeInterestIds[$interestId]
                )
            ) {
                throw new InvalidArgumentException(
                    'One or more selected content topics are inactive or unavailable.'
                );
            }
        }

        return $interestIds;
    }

    /* ==========================================
   SAFE RICH TEXT
========================================== */

    private function sanitizeRichText(
        string $html
    ): string {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $previousErrors =
            libxml_use_internal_errors(
                true
            );

        $wrapperId =
            'rich-text-root';

        $document->loadHTML(
            '<?xml encoding="UTF-8">'
                . '<div id="'
                . $wrapperId
                . '">'
                . $html
                . '</div>',
            LIBXML_HTML_NOIMPLIED |
                LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        libxml_use_internal_errors(
            $previousErrors
        );

        $root =
            $document->getElementById(
                $wrapperId
            );

        if (!$root) {
            return '';
        }

        $allowedTags = [
            'p',
            'br',
            'strong',
            'em',
            'u',
            'ul',
            'ol',
            'li',
            'a'
        ];

        $sanitizeNode =
            function (
                DOMNode $node
            ) use (
                &$sanitizeNode,
                $allowedTags
            ): void {
                $children = [];

                foreach (
                    $node->childNodes
                    as $child
                ) {
                    $children[] =
                        $child;
                }

                foreach (
                    $children
                    as $child
                ) {
                    if (
                        $child
                        instanceof
                        DOMElement
                    ) {
                        $tag =
                            strtolower(
                                $child->tagName
                            );

                        if (
                            !in_array(
                                $tag,
                                $allowedTags,
                                true
                            )
                        ) {
                            /*
                         * Keep the text/content,
                         * remove only the unsafe tag.
                         */
                            $sanitizeNode($child);

                            while (
                                $child
                                ->firstChild
                            ) {
                                $child
                                    ->parentNode
                                    ?->insertBefore(
                                        $child
                                            ->firstChild,
                                        $child
                                    );
                            }

                            $child
                                ->parentNode
                                ?->removeChild(
                                    $child
                                );

                            continue;
                        }

                        /*
                     * Preserve href only for links.
                     * All other attributes are removed.
                     */
                        $safeHref = null;

                        if (
                            $tag === 'a' &&
                            $child
                            ->hasAttribute(
                                'href'
                            )
                        ) {
                            $candidate =
                                trim(
                                    $child
                                        ->getAttribute(
                                            'href'
                                        )
                                );

                            if (
                                preg_match(
                                    '/^(https?:\/\/|mailto:)/i',
                                    $candidate
                                )
                            ) {
                                $safeHref =
                                    $candidate;
                            }
                        }

                        while (
                            $child
                            ->attributes
                            ->length > 0
                        ) {
                            $attribute =
                                $child
                                ->attributes
                                ->item(0);

                            if ($attribute) {
                                $child
                                    ->removeAttributeNode(
                                        $attribute
                                    );
                            }
                        }

                        if (
                            $safeHref !== null
                        ) {
                            $child
                                ->setAttribute(
                                    'href',
                                    $safeHref
                                );

                            $child
                                ->setAttribute(
                                    'target',
                                    '_blank'
                                );

                            $child
                                ->setAttribute(
                                    'rel',
                                    'noopener noreferrer'
                                );
                        }
                    }

                    $sanitizeNode(
                        $child
                    );
                }
            };

        $sanitizeNode(
            $root
        );

        $output = '';

        foreach (
            $root->childNodes
            as $child
        ) {
            $output .=
                $document->saveHTML(
                    $child
                );
        }

        return trim(
            $output
        );
    }

    /* ==========================================
       CREATE ANNOUNCEMENT
    ========================================== */

    private function createAnnouncement(
        array $data,
        array $files,
        int $userId,
        string $userName,
        $conn
    ): int {
        $targets = $this->buildTargets($data);

        $title = trim(
            (string) (
                $data['announcement_title']
                ?? ''
            )
        );

        $content =
            $this->sanitizeRichText(
                (string) (
                    $data['announcement_content']
                    ?? ''
                )
            );

        if (
            $title === '' ||
            $content === ''
        ) {
            throw new InvalidArgumentException(
                'Announcement title and content are required.'
            );
        }
        $imagePath =
            $this->handleOptionalImage(
                $files['announcement_image']
                    ?? null,
                'posts'
            );

        $audioUpload =
            null;

        try {
            $audioUpload =
                $this->storeAnnouncementAudio(
                    $files['announcement_audio']
                        ?? null
                );

            $audioTranscript =
                trim(
                    (string) (
                        $data['announcement_audio_transcript']
                        ?? ''
                    )
                );



            if (
                mb_strlen(
                    $audioTranscript,
                    'UTF-8'
                ) > 10000
            ) {
                throw new InvalidArgumentException(
                    'The audio transcript cannot exceed 10,000 characters.'
                );
            }

            if ($audioUpload === null) {
                $audioTranscript =
                    null;
            }
            $workflowAction =
                $this->resolveWorkflowAction(
                    $data
                );

            $releaseMode =
                $this->resolveReleaseMode(
                    $data
                );


            $scheduledPublishAt =
                $this->resolveScheduledPublishAt(
                    $data,
                    $releaseMode
                );

            $calendarEventId =
                $this->resolveCalendarEventId(
                    $data,
                    $releaseMode
                );

            $workflowStatus =
                $this->resolveContentWorkflowStatus(
                    $workflowAction,
                    $releaseMode
                );

            $priority = trim(
                (string) (
                    $data['announcement_priority']
                    ?? 'Normal'
                )
            );

            $category = strtolower(
                trim(
                    (string) (
                        $data['announcement_category']
                        ?? 'general'
                    )
                )
            );

            $allowComments =
                !empty($data['allow_comments']);

            $allowReactions =
                !empty($data['allow_reactions']);

            $requireAcknowledgment =
                !empty($data['require_acknowledgment']);

            $sendNotification =
                !empty($data['send_notification']);

            if (
                $priority === 'Emergency' ||
                $category === 'emergency'
            ) {
                $priority =
                    'Emergency';

                $requireAcknowledgment =
                    true;

                $sendNotification =
                    true;
            }

            $isPublished =
                $workflowStatus ===
                'published';

            $announcementData = [
                'title' =>
                $title,

                'content' =>
                $content,

                'image_path' =>
                $imagePath,

                'audio_path' =>
                $audioUpload['path']
                    ?? null,

                'audio_file_name' =>
                $audioUpload['file_name']
                    ?? null,

                'audio_mime_type' =>
                $audioUpload['mime_type']
                    ?? null,

                'audio_file_size' =>
                $audioUpload['file_size']
                    ?? null,

                'audio_transcript' =>
                $audioTranscript,

                'user_id' =>
                $userId,

                'type' =>
                'announcement',

                'category' =>
                $category,

                'priority' =>
                $priority,

                'workflow_status' =>
                $workflowStatus,

                'release_mode' =>
                $releaseMode,

                'scheduled_publish_at' =>
                $scheduledPublishAt,

                'calendar_event_id' =>
                $calendarEventId,

                'allow_comments' =>
                $allowComments,

                'allow_reactions' =>
                $allowReactions,

                'require_acknowledgment' =>
                $requireAcknowledgment,

                'send_notification' =>
                $sendNotification,

                'published_at' =>
                $isPublished
                    ? date(
                        'Y-m-d H:i:s'
                    )
                    : null,

                'status' =>
                $isPublished
                    ? 'active'
                    : 'inactive'
            ];

            $announcementId = PublicationTransaction::run(
                $this->announcement->getDatabaseConnection(), 'announcement',
                function () use ($announcementData, $targets, $data, $userId) {
                    $announcementId =
                        $this->announcement->create(
                            $announcementData
                        );

                    if ($announcementId <= 0) {
                        throw new RuntimeException(
                            'The announcement could not be saved.'
                        );
                    }

                    $this->announcement->saveTargets(
                        $announcementId,
                        $targets
                    );
                    $advisoryId = (int) ($data['government_advisory_id'] ?? 0);
                    if ($advisoryId > 0) {
                        (new GovernmentAdvisory($this->announcement->getDatabaseConnection()))
                            ->linkAnnouncement($advisoryId, $announcementId, $userId);
                    }
                    return $announcementId;
                }
            );

            if ($workflowStatus === 'published') {
                $this->notifyPublishedContentSafely(
                    'announcement',
                    $announcementId
                );
            }



            if (
                $workflowStatus ===
                'pending_review'
            ) {
                $this->notifyReviewSubmissionSafely(
                    'announcement',
                    $announcementId,
                    $title,
                    $userId,
                    $userName
                );
            }

            logActivity(
                $conn,
                'POST_ANNOUNCEMENT',
                $this->buildActivityMessage(
                    $userName,
                    'announcement',
                    $workflowStatus
                )
            );

            return $announcementId;
        } catch (Throwable $exception) {
            $this->deleteUploadedFile(
                $imagePath
            );

            $this->deleteUploadedFile(
                $audioUpload['path']
                    ?? null
            );

            throw $exception;
        }
    }



    /* ==========================================
   UPDATE ANNOUNCEMENT
========================================== */

    private function updateAnnouncement(
        int $announcementId,
        array $data,
        array $files,
        int $userId,
        string $userName,
        $conn
    ): int {
        $targets = $this->buildTargets($data);

        if (
            $announcementId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement or user ID.'
            );
        }

        $existing =
            $this->announcement
            ->findWorkflowItemById(
                $announcementId
            );

        if (!$existing) {
            throw new RuntimeException(
                'The announcement could not be found.'
            );
        }

        if (
            (int) (
                $existing['user_id']
                ?? 0
            ) !== $userId
        ) {
            throw new RuntimeException(
                'You are not allowed to edit this announcement.'
            );
        }

        $existingWorkflowStatus =
            strtolower(
                trim(
                    (string) (
                        $existing['workflow_status']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $existingWorkflowStatus,
                [
                    'draft',
                    'rejected'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only draft or rejected announcements can be edited.'
            );
        }

        $title =
            trim(
                (string) (
                    $data['announcement_title']
                    ?? ''
                )
            );

        $content =
            $this->sanitizeRichText(
                (string) (
                    $data['announcement_content']
                    ?? ''
                )
            );

        if (
            $title === '' ||
            $content === ''
        ) {
            throw new InvalidArgumentException(
                'Announcement title and content are required.'
            );
        }

        $oldImagePath =
            $existing['image_path']
            ?? null;

        $newImagePath =
            $this->handleOptionalImage(
                $files['announcement_image']
                    ?? null,
                'posts'
            );

        $imagePath =
            $newImagePath
            ?: $oldImagePath;

        $oldAudioPath =
            $existing['audio_path']
            ?? null;

        $oldAudioFileName =
            $existing['audio_file_name']
            ?? null;

        $oldAudioMimeType =
            $existing['audio_mime_type']
            ?? null;

        $oldAudioFileSize =
            isset(
                $existing['audio_file_size']
            )
            ? (int) $existing['audio_file_size']
            : null;

        $oldAudioTranscript =
            trim(
                (string) (
                    $existing['audio_transcript']
                    ?? ''
                )
            );

        $removeAudio =
            !empty($data['remove_announcement_audio']);

        $newAudioUpload =
            null;

        try {
            $newAudioUpload =
                $this->storeAnnouncementAudio(
                    $files['announcement_audio']
                        ?? null
                );

            $submittedAudioTranscript =
                trim(
                    (string) (
                        $data['announcement_audio_transcript']
                        ?? ''
                    )
                );

            if ($newAudioUpload !== null) {
                $audioPath =
                    $newAudioUpload['path'];

                $audioFileName =
                    $newAudioUpload['file_name'];

                $audioMimeType =
                    $newAudioUpload['mime_type'];

                $audioFileSize =
                    $newAudioUpload['file_size'];

                $audioTranscript =
                    $submittedAudioTranscript;
            } elseif ($removeAudio) {
                $audioPath =
                    null;

                $audioFileName =
                    null;

                $audioMimeType =
                    null;

                $audioFileSize =
                    null;

                $audioTranscript =
                    null;
            } else {
                $audioPath =
                    $oldAudioPath;

                $audioFileName =
                    $oldAudioFileName;

                $audioMimeType =
                    $oldAudioMimeType;

                $audioFileSize =
                    $oldAudioFileSize;

                $audioTranscript =
                    $submittedAudioTranscript !== ''
                    ? $submittedAudioTranscript
                    : $oldAudioTranscript;
            }



            if (
                mb_strlen(
                    (string) $audioTranscript,
                    'UTF-8'
                ) > 10000
            ) {
                throw new InvalidArgumentException(
                    'The audio transcript cannot exceed 10,000 characters.'
                );
            }

            $workflowAction =
                $this->resolveWorkflowAction(
                    $data
                );

            $releaseMode =
                $this->resolveReleaseMode(
                    $data
                );

            $scheduledPublishAt =
                $this->resolveScheduledPublishAt(
                    $data,
                    $releaseMode
                );

            $calendarEventId =
                $this->resolveCalendarEventId(
                    $data,
                    $releaseMode
                );

            $workflowStatus =
                $this->resolveContentWorkflowStatus(
                    $workflowAction,
                    $releaseMode
                );

            $priority =
                trim(
                    (string) (
                        $data['announcement_priority']
                        ?? 'Normal'
                    )
                );

            $category =
                strtolower(
                    trim(
                        (string) (
                            $data['announcement_category']
                            ?? 'general'
                        )
                    )
                );

            $allowComments =
                !empty($data['allow_comments']);

            $allowReactions =
                !empty($data['allow_reactions']);

            $requireAcknowledgment =
                !empty($data['require_acknowledgment']);

            $sendNotification =
                !empty($data['send_notification']);

            if (
                $priority === 'Emergency' ||
                $category === 'emergency'
            ) {
                $priority =
                    'Emergency';

                $requireAcknowledgment =
                    true;

                $sendNotification =
                    true;
            }

            $announcementData = [
                'title' =>
                $title,

                'content' =>
                $content,

                'image_path' =>
                $imagePath,

                'audio_path' =>
                $audioPath,

                'audio_file_name' =>
                $audioFileName,

                'audio_mime_type' =>
                $audioMimeType,

                'audio_file_size' =>
                $audioFileSize,

                'audio_transcript' =>
                $audioTranscript,

                'category' =>
                $category,

                'priority' =>
                $priority,

                'workflow_status' =>
                $workflowStatus,

                'release_mode' =>
                $releaseMode,

                'scheduled_publish_at' =>
                $scheduledPublishAt,

                'calendar_event_id' =>
                $calendarEventId,

                'allow_comments' =>
                $allowComments,

                'allow_reactions' =>
                $allowReactions,

                'require_acknowledgment' =>
                $requireAcknowledgment,

                'send_notification' =>
                $sendNotification
            ];

            $announcementId = PublicationTransaction::run(
                $this->announcement->getDatabaseConnection(), 'announcement',
                function () use ($announcementData, $targets, $announcementId, $userId) {
                    $updated =
                        $this->announcement->update(
                            $announcementId,
                            $announcementData,
                            $userId
                        );

                    if (!$updated) {
                        throw new RuntimeException(
                            'The announcement could not be updated.'
                        );
                    }

                    $this->announcement->saveTargets(
                        $announcementId,
                        $targets
                    );
                    return $announcementId;
                }
            );

            if ($workflowStatus === 'published') {
                $this->notifyPublishedContentSafely(
                    'announcement',
                    $announcementId
                );
            }

            if (
                $workflowStatus ===
                'pending_review'
            ) {
                $this->notifyReviewSubmissionSafely(
                    'announcement',
                    $announcementId,
                    $title,
                    $userId,
                    $userName
                );
            }

            if (
                $newImagePath &&
                $oldImagePath &&
                $newImagePath !== $oldImagePath
            ) {
                $this->deleteUploadedFile(
                    $oldImagePath
                );
            }

            if (
                $oldAudioPath &&
                $audioPath !== $oldAudioPath
            ) {
                $this->deleteUploadedFile(
                    $oldAudioPath
                );
            }

            logActivity(
                $conn,
                'UPDATE_POST',
                $userName
                    . ' updated an announcement draft.'
            );

            return $announcementId;
        } catch (Throwable $exception) {
            if ($newImagePath) {
                $this->deleteUploadedFile(
                    $newImagePath
                );
            }

            if ($newAudioUpload !== null) {
                $this->deleteUploadedFile(
                    $newAudioUpload['path']
                        ?? null
                );
            }

            throw $exception;
        }
    }

    /* ==========================================
   UPDATE EVENT
========================================== */

    private function updateEvent(
        int $eventId,
        array $data,
        array $files,
        int $userId,
        string $userName,
        $conn
    ): int {
        $targets = $this->buildTargets($data);

        if (
            $eventId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid event or user ID.'
            );
        }

        $existing =
            $this->event
            ->findWorkflowItemById(
                $eventId
            );

        if (!$existing) {
            throw new RuntimeException(
                'The event could not be found.'
            );
        }

        if (
            (int) (
                $existing['user_id']
                ?? 0
            ) !== $userId
        ) {
            throw new RuntimeException(
                'You are not allowed to edit this event.'
            );
        }

        $existingWorkflowStatus =
            strtolower(
                trim(
                    (string) (
                        $existing['workflow_status']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $existingWorkflowStatus,
                [
                    'draft',
                    'rejected'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only draft or rejected events can be edited.'
            );
        }

        $title =
            trim(
                (string) (
                    $data['event_title']
                    ?? ''
                )
            );

        $description =
            $this->sanitizeRichText(
                (string) (
                    $data['event_description']
                    ?? ''
                )
            );

        $location =
            trim(
                (string) (
                    $data['event_location']
                    ?? ''
                )
            );

        $eventDate =
            trim(
                (string) (
                    $data['event_date']
                    ?? ''
                )
            );

        $endDate =
            trim(
                (string) (
                    $data['event_end_date']
                    ?? ''
                )
            );

        if (
            $title === '' ||
            $description === '' ||
            $eventDate === ''
        ) {
            throw new InvalidArgumentException(
                'Event title, description, and start date are required.'
            );
        }

        $oldImagePath =
            $existing['image_path']
            ?? null;

        $newImagePath =
            $this->handleOptionalImage(
                $files['event_image']
                    ?? null,
                'events'
            );

        $imagePath =
            $newImagePath
            ?: $oldImagePath;

        try {
            $workflowAction =
                $this->resolveWorkflowAction(
                    $data
                );

            $releaseMode =
                $this->resolveReleaseMode(
                    $data
                );

            $scheduledPublishAt =
                $this->resolveScheduledPublishAt(
                    $data,
                    $releaseMode
                );

            $calendarEventId =
                $this->resolveCalendarEventId(
                    $data,
                    $releaseMode
                );

            $workflowStatus =
                $this->resolveContentWorkflowStatus(
                    $workflowAction,
                    $releaseMode
                );

            $allowComments =
                !empty($data['allow_comments']);

            $allowReactions =
                !empty($data['allow_reactions']);

            $requireAcknowledgment =
                !empty($data['require_acknowledgment']);

            $sendNotification =
                !empty($data['send_notification']);

            $eventData = [
                'title' =>
                $title,

                'description' =>
                $description,

                'location' =>
                $location,

                'image_path' =>
                $imagePath,

                'event_date' =>
                $eventDate,

                'end_date' =>
                $endDate,

                'workflow_status' =>
                $workflowStatus,

                'release_mode' =>
                $releaseMode,

                'scheduled_publish_at' =>
                $scheduledPublishAt,

                'calendar_event_id' =>
                $calendarEventId,

                'allow_comments' =>
                $allowComments,

                'allow_reactions' =>
                $allowReactions,

                'require_acknowledgment' =>
                $requireAcknowledgment,

                'send_notification' =>
                $sendNotification
            ];

            $eventId = PublicationTransaction::run(
                $this->event->getDatabaseConnection(), 'event',
                function () use ($eventData, $targets, $eventId, $userId) {
                    $updated =
                        $this->event->update(
                            $eventId,
                            $eventData,
                            $userId
                        );

                    if (!$updated) {
                        throw new RuntimeException(
                            'The event could not be updated.'
                        );
                    }

                    $this->event->saveTargets(
                        $eventId,
                        $targets
                    );
                    return $eventId;
                }
            );

            if ($workflowStatus === 'published') {
                $this->notifyPublishedContentSafely(
                    'event',
                    $eventId
                );
            }

            if (
                $workflowStatus ===
                'pending_review'
            ) {
                $this->notifyReviewSubmissionSafely(
                    'event',
                    $eventId,
                    $title,
                    $userId,
                    $userName
                );
            }

            if (
                $newImagePath &&
                $oldImagePath &&
                $newImagePath !== $oldImagePath
            ) {
                $this->deleteUploadedFile(
                    $oldImagePath
                );
            }

            logActivity(
                $conn,
                'UPDATE_POST',
                $userName
                    . ' updated an event draft.'
            );

            return $eventId;
        } catch (Throwable $exception) {
            if ($newImagePath) {
                $this->deleteUploadedFile(
                    $newImagePath
                );
            }

            throw $exception;
        }
    }

    /* ==========================================
       CREATE EVENT
    ========================================== */

    private function createEvent(
        array $data,
        array $files,
        int $userId,
        string $userName,
        $conn
    ): int {
        $targets = $this->buildTargets($data);

        $title = trim(
            (string) (
                $data['event_title']
                ?? ''
            )
        );

        $description =
            $this->sanitizeRichText(
                (string) (
                    $data['event_description']
                    ?? ''
                )
            );

        $location = trim(
            (string) (
                $data['event_location']
                ?? ''
            )
        );

        $eventDate =
            $this->normalizeDateTime(
                (string) (
                    $data['event_date']
                    ?? ''
                )
            );

        $endDate =
            $this->normalizeDateTime(
                (string) (
                    $data['event_end_date']
                    ?? ''
                ),
                true
            );

        if (
            $title === '' ||
            $description === '' ||
            !$eventDate
        ) {
            throw new InvalidArgumentException(
                'Event title, description, and date are required.'
            );
        }

        $eventTimestamp =
            strtotime(
                $eventDate
            );

        if (
            $eventTimestamp === false ||
            $eventTimestamp < time()
        ) {
            throw new InvalidArgumentException(
                'The event start date cannot be in the past.'
            );
        }

        if ($endDate !== null) {
            $endTimestamp =
                strtotime(
                    $endDate
                );

            if (
                $endTimestamp === false ||
                $endTimestamp <=
                $eventTimestamp
            ) {
                throw new InvalidArgumentException(
                    'The event end date must be later than the start date.'
                );
            }
        }

        $imagePath =
            $this->handleOptionalImage(
                $files['event_image']
                    ?? null,
                'posts'
            );

        try {
            $workflowAction =
                $this->resolveWorkflowAction(
                    $data
                );

            $releaseMode =
                $this->resolveReleaseMode(
                    $data
                );

            if ($releaseMode === 'calendar') {
                throw new InvalidArgumentException(
                    'Calendar-based release is not available for events.'
                );
            }


            $scheduledPublishAt =
                $this->resolveScheduledPublishAt(
                    $data,
                    $releaseMode
                );

            $calendarEventId =
                $this->resolveCalendarEventId(
                    $data,
                    $releaseMode
                );

            $workflowStatus =
                $this->resolveContentWorkflowStatus(
                    $workflowAction,
                    $releaseMode
                );

            $eventData = [

                'title' =>
                $title,

                'description' =>
                $description,

                'location' =>
                $location !== ''
                    ? $location
                    : null,

                'event_date' =>
                $eventDate,

                'end_date' =>
                $endDate,

                'image_path' =>
                $imagePath,

                'user_id' =>
                $userId,

                'workflow_status' =>
                $workflowStatus,

                'release_mode' =>
                $releaseMode,

                'scheduled_publish_at' =>
                $scheduledPublishAt,

                'calendar_event_id' =>
                $calendarEventId,

                'send_notification' =>
                !empty($data['send_notification']),

                'allow_reactions' =>
                !empty($data['allow_reactions']),

                'allow_comments' =>
                !empty($data['allow_comments']),

                'require_acknowledgment' =>
                !empty($data['require_acknowledgment'])
            ];

            $eventId = PublicationTransaction::run(
                $this->event->getDatabaseConnection(), 'event',
                function () use ($eventData, $targets) {
                    $eventId =
                        $this->event->create(
                            $eventData
                        );

                    if ($eventId <= 0) {
                        throw new RuntimeException(
                            'The event could not be saved.'
                        );
                    }

                    $this->event->saveTargets(
                        $eventId,
                        $targets
                    );
                    return $eventId;
                }
            );

            if ($workflowStatus === 'published') {
                $this->notifyPublishedContentSafely(
                    'event',
                    $eventId
                );
            }

            if (
                $workflowStatus ===
                'pending_review'
            ) {
                $this->notifyReviewSubmissionSafely(
                    'event',
                    $eventId,
                    $title,
                    $userId,
                    $userName
                );
            }

            logActivity(
                $conn,
                'POST_EVENT',
                $this->buildActivityMessage(
                    $userName,
                    'event',
                    $workflowStatus
                )
            );

            return $eventId;
        } catch (Throwable $exception) {
            $this->deleteUploadedFile(
                $imagePath
            );

            throw $exception;
        }
    }

    /* ==========================================
   UPDATE DOCUMENT
========================================== */

    private function updateDocument(
        int $documentId,
        array $data,
        array $files,
        int $userId,
        string $userName,
        $conn
    ): int {
        $targets = $this->buildTargets($data);

        if (
            $documentId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid document or user ID.'
            );
        }

        $existing =
            $this->document
            ->findWorkflowItemById(
                $documentId
            );

        if (!$existing) {
            throw new RuntimeException(
                'The document could not be found.'
            );
        }

        if (
            (int) (
                $existing['user_id']
                ?? 0
            ) !== $userId
        ) {
            throw new RuntimeException(
                'You are not allowed to edit this document.'
            );
        }

        $existingWorkflowStatus =
            strtolower(
                trim(
                    (string) (
                        $existing['workflow_status']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $existingWorkflowStatus,
                [
                    'draft',
                    'rejected'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only draft or rejected documents can be edited.'
            );
        }

        /* ======================================
       DOCUMENT INFORMATION
    ======================================= */

        $title = trim(
            (string) (
                $data['document_title']
                ?? ''
            )
        );

        $description = trim(
            (string) (
                $data['document_description']
                ?? ''
            )
        );

        if (
            $title === '' ||
            $description === ''
        ) {
            throw new InvalidArgumentException(
                'Document title and description are required.'
            );
        }

        /* ======================================
       EXISTING FILES
    ======================================= */

        $oldDocumentPath =
            trim(
                (string) (
                    $existing['file_path']
                    ?? ''
                )
            );

        $oldCoverPath =
            trim(
                (string) (
                    $existing['cover_image_path']
                    ?? ''
                )
            );

        if ($oldDocumentPath === '') {
            throw new RuntimeException(
                'The existing document file could not be found.'
            );
        }

        $newDocumentUpload = null;
        $newCoverPath = null;

        try {
            /* ==================================
           OPTIONAL REPLACEMENT DOCUMENT
        =================================== */

            $documentFile =
                $files['document_file']
                ?? null;

            $hasNewDocument =
                is_array($documentFile) &&
                isset($documentFile['error']) &&
                (int) $documentFile['error'] !==
                UPLOAD_ERR_NO_FILE;

            if ($hasNewDocument) {
                $newDocumentUpload =
                    $this->storeDocumentUpload(
                        $documentFile
                    );
            }

            /* ==================================
           OPTIONAL REPLACEMENT COVER
        =================================== */

            $newCoverPath =
                $this->handleOptionalImage(
                    $files['document_cover']
                        ?? null,
                    'document-covers'
                );

            /* ==================================
           RESOLVE DOCUMENT FILE
        =================================== */

            $documentFileName =
                $newDocumentUpload !== null
                ? (
                    $newDocumentUpload['file_name']
                    ?? ''
                )
                : (
                    $existing['file_name']
                    ?? ''
                );

            $documentFilePath =
                $newDocumentUpload !== null
                ? (
                    $newDocumentUpload['file_path']
                    ?? ''
                )
                : $oldDocumentPath;

            $documentFileType =
                $newDocumentUpload !== null
                ? (
                    $newDocumentUpload['file_type']
                    ?? ''
                )
                : (
                    $existing['file_type']
                    ?? ''
                );

            $documentFileSize =
                $newDocumentUpload !== null
                ? (int) (
                    $newDocumentUpload['file_size']
                    ?? 0
                )
                : (int) (
                    $existing['file_size']
                    ?? 0
                );

            $documentCoverPath =
                $newCoverPath
                ?: (
                    $oldCoverPath !== ''
                    ? $oldCoverPath
                    : null
                );




            /* ==================================
           WORKFLOW SETTINGS
        =================================== */

            $workflowAction =
                $this->resolveWorkflowAction(
                    $data
                );

            $releaseMode =
                $this->resolveReleaseMode(
                    $data
                );

            $scheduledPublishAt =
                $this->resolveScheduledPublishAt(
                    $data,
                    $releaseMode
                );

            $calendarEventId =
                $this->resolveCalendarEventId(
                    $data,
                    $releaseMode
                );

            $workflowStatus =
                $this->resolveContentWorkflowStatus(
                    $workflowAction,
                    $releaseMode
                );

            /* ==================================
           COMPLETE UPDATE DATA
        =================================== */

            $documentData = [
                'title' =>
                $title,

                'description' =>
                $description,

                'file_name' =>
                $documentFileName,

                'file_path' =>
                $documentFilePath,

                'file_type' =>
                $documentFileType,

                'file_size' =>
                $documentFileSize,

                'cover_image_path' =>
                $documentCoverPath,

                'workflow_status' =>
                $workflowStatus,

                'release_mode' =>
                $releaseMode,

                'scheduled_publish_at' =>
                $scheduledPublishAt,

                'calendar_event_id' =>
                $calendarEventId,

                'send_notification' =>
                !empty($data['send_notification']),

                'allow_reactions' =>
                !empty($data['allow_reactions']),

                'allow_comments' =>
                !empty($data['allow_comments']),

                'require_acknowledgment' =>
                !empty($data['require_acknowledgment'])
            ];

            $documentId = PublicationTransaction::run(
                $this->document->getDatabaseConnection(), 'document',
                function () use ($documentData, $targets, $documentId, $userId) {
                    $updated =
                        $this->document->update(
                            $documentId,
                            $documentData,
                            $userId
                        );

                    if (!$updated) {
                        throw new RuntimeException(
                            'The document could not be updated.'
                        );
                    }

                    /* ==================================
                   REPLACE DOCUMENT TARGETS
                =================================== */

                    $this->document->saveTargets(
                        $documentId,
                        $targets
                    );
                    return $documentId;
                }
            );

            if ($workflowStatus === 'published') {
                $this->notifyPublishedContentSafely(
                    'document',
                    $documentId
                );
            }

            if (
                $workflowStatus ===
                'pending_review'
            ) {
                $this->notifyReviewSubmissionSafely(
                    'document',
                    $documentId,
                    $title,
                    $userId,
                    $userName
                );
            }

            /* ==================================
   DELETE REPLACED OLD FILES
=================================== */

            if (
                $newDocumentUpload !== null &&
                $oldDocumentPath !== '' &&
                (
                    $newDocumentUpload['file_path']
                    ?? ''
                ) !== $oldDocumentPath
            ) {
                $this->deleteUploadedFile(
                    $oldDocumentPath
                );
            }

            if (
                $newCoverPath &&
                $oldCoverPath !== '' &&
                $newCoverPath !== $oldCoverPath
            ) {
                $this->deleteUploadedFile(
                    $oldCoverPath
                );
            }

            logActivity(
                $conn,
                'UPDATE_POST',
                $userName
                    . ' updated a document draft.'
            );

            return $documentId;
        } catch (Throwable $exception) {
            if ($newDocumentUpload !== null) {
                $this->deleteUploadedFile(
                    $newDocumentUpload['file_path']
                        ?? null
                );
            }

            if ($newCoverPath) {
                $this->deleteUploadedFile(
                    $newCoverPath
                );
            }

            throw $exception;
        }
    }

    /* ==========================================
       CREATE DOCUMENT
    ========================================== */

    private function createDocument(
        array $data,
        array $files,
        int $userId,
        string $userName,
        $conn
    ): int {
        $targets = $this->buildTargets($data);


        $title = trim(
            (string) (
                $data['document_title']
                ?? ''
            )
        );

        $description = trim(
            (string) (
                $data['document_description']
                ?? ''
            )
        );

        if (
            $title === '' ||
            $description === ''
        ) {
            throw new InvalidArgumentException(
                'Document title and description are required.'
            );
        }


        $coverPath =
            $this->handleOptionalImage(
                $files['document_cover']
                    ?? null,
                'document-covers'
            );

        $uploadedDocumentPath = null;

        try {
            $workflowAction =
                $this->resolveWorkflowAction(
                    $data
                );

            $releaseMode =
                $this->resolveReleaseMode(
                    $data
                );

            $scheduledPublishAt =
                $this->resolveScheduledPublishAt(
                    $data,
                    $releaseMode
                );

            $calendarEventId =
                $this->resolveCalendarEventId(
                    $data,
                    $releaseMode
                );

            $workflowStatus =
                $this->resolveContentWorkflowStatus(
                    $workflowAction,
                    $releaseMode
                );

            $documentUpload =
                $this->storeDocumentUpload(
                    $files['document_file']
                        ?? null
                );

            $uploadedDocumentPath =
                $documentUpload['file_path']
                ?? null;

            $documentData = [
                'title' =>
                $title,

                'description' =>
                $description,

                'file_name' =>
                $documentUpload['file_name'],

                'file_path' =>
                $documentUpload['file_path'],

                'file_type' =>
                $documentUpload['file_type'],

                'file_size' =>
                $documentUpload['file_size'],

                'cover_image_path' =>
                $coverPath,

                'user_id' =>
                $userId,

                'workflow_status' =>
                $workflowStatus,

                'release_mode' =>
                $releaseMode,

                'scheduled_publish_at' =>
                $scheduledPublishAt,

                'calendar_event_id' =>
                $calendarEventId,

                'send_notification' =>
                !empty($data['send_notification']),

                'allow_reactions' =>
                !empty($data['allow_reactions']),

                'allow_comments' =>
                !empty($data['allow_comments']),

                'require_acknowledgment' =>
                !empty($data['require_acknowledgment'])
            ];

            $documentId = PublicationTransaction::run(
                $this->document->getDatabaseConnection(), 'document',
                function () use ($documentData, $targets) {
                    $documentId =
                        $this->document->create(
                            $documentData
                        );

                    if ($documentId <= 0) {
                        throw new RuntimeException(
                            'The document could not be saved.'
                        );
                    }

                    $this->document->saveTargets(
                        $documentId,
                        $targets
                    );
                    return $documentId;
                }
            );

            if ($workflowStatus === 'published') {
                $this->notifyPublishedContentSafely(
                    'document',
                    $documentId
                );
            }

            if (
                $workflowStatus ===
                'pending_review'
            ) {
                $this->notifyReviewSubmissionSafely(
                    'document',
                    $documentId,
                    $title,
                    $userId,
                    $userName
                );
            }

            logActivity(
                $conn,
                'UPLOAD_DOCUMENT',
                $this->buildActivityMessage(
                    $userName,
                    'document',
                    $workflowStatus
                )
            );

            return $documentId;
        } catch (Throwable $exception) {
            $this->deleteUploadedFile(
                $coverPath
            );

            $this->deleteUploadedFile(
                $uploadedDocumentPath
            );

            throw $exception;
        }
    }

    /* ==========================================
   SAFE PUBLISHED CONTENT NOTIFICATION
========================================== */

    private function notifyPublishedContentSafely(
        string $contentType,
        int $contentId
    ): void {
        try {
            $result =
                ($this->publicationDispatcher ?? new PublicationNotificationDispatcher())->dispatch(1, $contentType, $contentId);

            error_log(
                'Notification delivery completed for '
                    . $contentType
                    . ' '
                    . $contentId
                    . ': '
                    . json_encode(
                        $result,
                        JSON_UNESCAPED_SLASHES
                    )
            );
        } catch (Throwable $exception) {
            /*
         * Notification failure must not make
         * successfully saved content appear failed.
         * The deduplication key permits a safe retry.
         */
            error_log(
                'Notification delivery failed for '
                    . $contentType
                    . ' '
                    . $contentId
                    . ': '
                    . $exception->getMessage()
            );
        }
    }


    /* ==========================================
   SAFE REVIEW SUBMISSION NOTIFICATION
========================================== */

    private function notifyReviewSubmissionSafely(
        string $contentType,
        int $contentId,
        string $contentTitle,
        int $submitterId,
        string $submitterName
    ): void {
        try {
            if (
                $contentId <= 0 ||
                $submitterId <= 0
            ) {
                throw new InvalidArgumentException(
                    'Invalid review-submission notification data.'
                );
            }

            /*
         * A unique token identifies this submission cycle.
         * A later revision and resubmission receives a new
         * token and therefore a new Admin notification.
         */
            $submissionToken =
                $contentType
                . '|'
                . $contentId
                . '|'
                . $submitterId
                . '|'
                . sprintf(
                    '%.6F',
                    microtime(true)
                );

            $result =
                $this->getNotifications()
                ->notifyReviewSubmission(
                    $submitterId,
                    $submitterName,
                    $contentType,
                    $contentId,
                    $contentTitle,
                    $submissionToken
                );

            error_log(
                'Direct review submission notification result: '
                    . $contentType
                    . ' #'
                    . $contentId
                    . ' | '
                    . json_encode(
                        $result,
                        JSON_UNESCAPED_SLASHES
                    )
            );
        } catch (Throwable $exception) {
            /*
         * Notification failure must not make a
         * successfully submitted post appear failed.
         */
            error_log(
                'Direct review submission notification error: '
                    . $contentType
                    . ' #'
                    . $contentId
                    . ' | '
                    . $exception->getMessage()
            );
        }
    }

    /* ==========================================
       WORKFLOW
    ========================================== */

    private function resolveWorkflowAction(
        array $data
    ): string {
        $workflowAction = strtolower(
            trim(
                (string) (
                    $data['workflow_action']
                    ?? 'draft'
                )
            )
        );

        $allowedActions = [
            'draft',
            'submit_review',
            'publish'
        ];

        if (
            !in_array(
                $workflowAction,
                $allowedActions,
                true
            )
        ) {
            $workflowAction =
                'draft';
        }

        $currentRole =
            $_SESSION['role']
            ?? 'Guest';

        if (
            $currentRole !== 'Admin' &&
            $workflowAction === 'publish'
        ) {
            return 'submit_review';
        }

        return $workflowAction;
    }

    private function resolveStandardWorkflowStatus(
        string $workflowAction
    ): string {
        return match ($workflowAction) {
            'publish' =>
            'published',

            'submit_review' =>
            'pending_review',

            default =>
            'draft'
        };
    }

    private function resolveContentWorkflowStatus(
        string $workflowAction,
        string $releaseMode
    ): string {
        if (
            $workflowAction === 'publish' &&
            in_array(
                $releaseMode,
                [
                    'scheduled',
                    'calendar'
                ],
                true
            )
        ) {
            return 'scheduled';
        }

        return $this->resolveStandardWorkflowStatus(
            $workflowAction
        );
    }

    /* ==========================================
       RELEASE SETTINGS
    ========================================== */

    private function resolveReleaseMode(
        array $data
    ): string {
        $releaseMode = strtolower(
            trim(
                (string) (
                    $data['release_mode']
                    ?? 'immediate'
                )
            )
        );

        $allowedModes = [
            'immediate',
            'scheduled',
            'calendar'
        ];

        if (
            !in_array(
                $releaseMode,
                $allowedModes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid release mode.'
            );
        }

        return $releaseMode;
    }

    private function resolveScheduledPublishAt(
        array $data,
        string $releaseMode
    ): ?string {
        if (
            $releaseMode !==
            'scheduled'
        ) {
            return null;
        }

        $scheduledPublishAt =
            $this->normalizeDateTime(
                (string) (
                    $data['scheduled_publish_at']
                    ?? ''
                ),
                true
            );

        if ($scheduledPublishAt === null) {
            throw new InvalidArgumentException(
                'Scheduled release date is required.'
            );
        }

        $scheduledTimestamp =
            strtotime(
                $scheduledPublishAt
            );

        if (
            $scheduledTimestamp === false ||
            $scheduledTimestamp <= time()
        ) {
            throw new InvalidArgumentException(
                'The scheduled release must be in the future.'
            );
        }

        return $scheduledPublishAt;
    }

    private function resolveCalendarEventId(
        array $data,
        string $releaseMode
    ): ?int {
        if (
            $releaseMode !==
            'calendar'
        ) {
            return null;
        }

        $calendarEventId =
            (int) (
                $data['calendar_event_id']
                ?? 0
            );

        if ($calendarEventId <= 0) {
            throw new InvalidArgumentException(
                'Select a calendar event for calendar-based release.'
            );
        }

        $event =
            $this->event->findById(
                $calendarEventId
            );

        if (!$event) {
            throw new InvalidArgumentException(
                'The selected calendar event is unavailable.'
            );
        }

        $eventStatus =
            strtolower(
                trim(
                    (string) (
                        $event['status']
                        ?? ''
                    )
                )
            );

        $eventWorkflowStatus =
            strtolower(
                trim(
                    (string) (
                        $event['workflow_status']
                        ?? ''
                    )
                )
            );

        $eventDate =
            trim(
                (string) (
                    $event['event_date']
                    ?? ''
                )
            );

        if (
            $eventStatus !== 'active' ||
            $eventWorkflowStatus !== 'published'
        ) {
            throw new InvalidArgumentException(
                'The selected event is no longer eligible for calendar-based release.'
            );
        }

        if ($eventDate === '') {
            throw new InvalidArgumentException(
                'The selected event does not have a valid start date.'
            );
        }

        $eventTimestamp =
            strtotime(
                $eventDate
            );

        if ($eventTimestamp === false) {
            throw new InvalidArgumentException(
                'The selected event has an invalid start date.'
            );
        }

        if ($eventTimestamp <= time()) {
            throw new InvalidArgumentException(
                'The selected event has already started and can no longer be used for calendar-based release.'
            );
        }

        return $calendarEventId;
    }
    /* ==========================================
       TARGET RECIPIENTS
    ========================================== */

    private function buildTargets(
        array $data
    ): array {
        $data = ($this->facultyScope ??= new FacultyScopeService())->prepareSubmission(
            $data, (int) ($_SESSION['user_id'] ?? 0)
        );

        $rolePrefixes =
            $data['target_roles']
            ?? [];

        if (!is_array($rolePrefixes)) {
            $rolePrefixes = [
                $rolePrefixes
            ];
        }

        $rolePrefixes =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(
                                mixed $role
                            ): string =>
                            trim(
                                (string) $role
                            ),
                            $rolePrefixes
                        ),
                        static fn(
                            string $role
                        ): bool =>
                        $role !== ''
                    )
                )
            );

        if ($rolePrefixes === []) {
            throw new InvalidArgumentException(
                'Select at least one target recipient group.'
            );
        }

        $roleIdMap =
            $this->announcement
            ->getRoleIdsByPrefixes(
                $rolePrefixes
            );

        $missingRoles =
            array_diff(
                $rolePrefixes,
                array_keys(
                    $roleIdMap
                )
            );

        if ($missingRoles !== []) {
            throw new RuntimeException(
                'These recipient roles are not configured in the database: '
                    . implode(
                        ', ',
                        $missingRoles
                    )
            );
        }

        $audienceMode =
            strtolower(
                trim(
                    (string) (
                        $data['audience_scope']
                        ?? 'schoolwide'
                    )
                )
            );

        if (
            !in_array(
                $audienceMode,
                [
                    'schoolwide',
                    'custom'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Select a valid audience scope.'
            );
        }

        $rawScopes = [];

        if ($audienceMode === 'schoolwide') {
            $rawScopes[] = [];
        } else {
            $submittedScopes =
                $data['audience_scopes']
                ?? [];

            if (is_array($submittedScopes)) {
                $rawScopes =
                    array_values(
                        array_filter(
                            $submittedScopes,
                            'is_array'
                        )
                    );
            }

            /*
         * Backward compatibility for the existing
         * single-scope posting form.
         */
            if ($rawScopes === []) {
                $rawScopes[] = [
                    'department_id' =>
                    $data['target_department']
                        ?? null,

                    'education_level_id' =>
                    $data['target_education_level']
                        ?? null,

                    'academic_program_id' =>
                    $data['target_program']
                        ?? null,

                    'grade_level_id' =>
                    $data['target_grade_level']
                        ?? null,

                    'section_id' =>
                    $data['target_section']
                        ?? null
                ];
            }
        }

        $scopes = [];

        $scopeKeys = [];

        foreach ($rawScopes as $rawScope) {
            $departmentId =
                $this->nullablePositiveInt(
                    $rawScope['department_id']
                        ?? null
                );

            $educationLevelId =
                $this->nullablePositiveInt(
                    $rawScope['education_level_id']
                        ?? null
                );

            $academicProgramId =
                $this->nullablePositiveInt(
                    $rawScope['academic_program_id']
                        ?? null
                );

            $gradeLevelId =
                $this->nullablePositiveInt(
                    $rawScope['grade_level_id']
                        ?? null
                );

            $sectionId =
                $this->nullablePositiveInt(
                    $rawScope['section_id']
                        ?? null
                );

            if ($audienceMode === 'schoolwide') {
                $departmentId = null;
                $educationLevelId = null;
                $academicProgramId = null;
                $gradeLevelId = null;
                $sectionId = null;
            }

            $this->validateAcademicAudienceScope(
                $departmentId,
                $educationLevelId,
                $academicProgramId,
                $gradeLevelId,
                $sectionId
            );

            $scopeKey =
                implode(
                    ':',
                    [
                        $departmentId ?? 0,
                        $educationLevelId ?? 0,
                        $academicProgramId ?? 0,
                        $gradeLevelId ?? 0,
                        $sectionId ?? 0
                    ]
                );

            if (
                isset(
                    $scopeKeys[$scopeKey]
                )
            ) {
                continue;
            }

            $scopeKeys[$scopeKey] = true;

            $scopes[] = [
                'department_id' =>
                $departmentId,

                'education_level_id' =>
                $educationLevelId,

                'academic_program_id' =>
                $academicProgramId,

                'grade_level_id' =>
                $gradeLevelId,

                'section_id' =>
                $sectionId
            ];
        }

        if ($scopes === []) {
            throw new InvalidArgumentException(
                'Add at least one audience group.'
            );
        }

        $this->validateAcademicScopeOverlap(
            $scopes
        );

        /*
    |------------------------------------------------------
    | ROLE × SCOPE TARGET ROWS
    |------------------------------------------------------
    */

        $targets = [];

        $targetKeys = [];

        foreach ($rolePrefixes as $rolePrefix) {
            $roleId =
                (int) (
                    $roleIdMap[$rolePrefix]
                    ?? 0
                );

            foreach ($scopes as $scope) {
                $targetKey =
                    implode(
                        ':',
                        [
                            $roleId,
                            $scope['department_id']
                                ?? 0,
                            $scope['education_level_id']
                                ?? 0,
                            $scope['academic_program_id']
                                ?? 0,
                            $scope['grade_level_id']
                                ?? 0,
                            $scope['section_id']
                                ?? 0
                        ]
                    );

                if (
                    isset(
                        $targetKeys[$targetKey]
                    )
                ) {
                    continue;
                }

                $targetKeys[$targetKey] = true;

                $targets[] = [
                    'role_id' =>
                    $roleId,

                    'department_id' =>
                    $scope['department_id'],

                    'education_level_id' =>
                    $scope['education_level_id'],

                    'academic_program_id' =>
                    $scope['academic_program_id'],

                    'grade_level_id' =>
                    $scope['grade_level_id'],

                    'section_id' =>
                    $scope['section_id']
                ];
            }
        }

        return $targets;
    }


    private function validateAcademicAudienceScope(
        ?int $departmentId,
        ?int $educationLevelId,
        ?int $academicProgramId,
        ?int $gradeLevelId,
        ?int $sectionId
    ): void {
        if (
            $departmentId === null &&
            $educationLevelId === null &&
            $academicProgramId === null &&
            $gradeLevelId === null &&
            $sectionId === null
        ) {
            return;
        }

        require __DIR__
            . '/../../config/dbconnect.php';

        $departmentValue =
            $departmentId ?? 0;

        $educationValue =
            $educationLevelId ?? 0;

        $programValue =
            $academicProgramId ?? 0;

        $gradeValue =
            $gradeLevelId ?? 0;

        $sectionValue =
            $sectionId ?? 0;

        $stmt = $conn->prepare("
        SELECT
            d.department_id

        FROM department d

        LEFT JOIN education_level el
            ON el.department_id =
                d.department_id
           AND el.status = 'Active'

        LEFT JOIN academic_program ap
            ON ap.education_level_id =
                el.education_level_id
           AND ap.status = 'Active'

        LEFT JOIN grade_level gl
            ON gl.education_level_id =
                el.education_level_id
           AND gl.status = 'Active'

        LEFT JOIN section s
            ON s.grade_level_id =
                gl.grade_level_id
           AND s.status = 'Active'

        LEFT JOIN academic_program sap
            ON sap.academic_program_id =
                s.academic_program_id
           AND sap.status = 'Active'

        WHERE d.status = 'Active'

          AND (
                ? = 0 OR
                d.department_id = ?
          )

          AND (
                ? = 0 OR
                el.education_level_id = ?
          )

          AND (
                ? = 0 OR
                ap.academic_program_id = ?
          )

          AND (
                ? = 0 OR
                gl.grade_level_id = ?
          )

          AND (
                ? = 0 OR
                s.section_id = ?
          )

          AND (
                s.section_id IS NULL OR
                s.academic_program_id IS NULL OR
                sap.academic_program_id IS NOT NULL
          )

          AND (
                ? = 0 OR
                ? = 0 OR
                s.academic_program_id = ?
          )

        LIMIT 1
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to validate the selected academic target: '
                    . $conn->error
            );
        }

        $stmt->bind_param(
            'iiiiiiiiiiiii',
            $departmentValue,
            $departmentValue,
            $educationValue,
            $educationValue,
            $programValue,
            $programValue,
            $gradeValue,
            $gradeValue,
            $sectionValue,
            $sectionValue,
            $sectionValue,
            $programValue,
            $programValue
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to validate the selected academic target: '
                    . $error
            );
        }

        $isValid =
            $stmt
            ->get_result()
            ->fetch_assoc() !== null;

        $stmt->close();

        if (!$isValid) {
            throw new InvalidArgumentException(
                'A selected academic target is inactive, unavailable, or does not belong to the chosen hierarchy.'
            );
        }
    }

    private function validateAcademicScopeOverlap(
        array $scopes
    ): void {
        $scopeCount =
            count(
                $scopes
            );

        for (
            $firstIndex = 0;
            $firstIndex < $scopeCount;
            $firstIndex++
        ) {
            for (
                $secondIndex =
                    $firstIndex + 1;
                $secondIndex < $scopeCount;
                $secondIndex++
            ) {
                $first =
                    $scopes[$firstIndex];

                $second =
                    $scopes[$secondIndex];

                $fields = [
                    'department_id',
                    'education_level_id',
                    'academic_program_id',
                    'grade_level_id',
                    'section_id'
                ];

                $firstContainsSecond =
                    true;

                $secondContainsFirst =
                    true;

                foreach ($fields as $field) {
                    $firstValue =
                        $first[$field]
                        ?? null;

                    $secondValue =
                        $second[$field]
                        ?? null;

                    if (
                        $firstValue !== null &&
                        $firstValue !==
                        $secondValue
                    ) {
                        $firstContainsSecond =
                            false;
                    }

                    if (
                        $secondValue !== null &&
                        $secondValue !==
                        $firstValue
                    ) {
                        $secondContainsFirst =
                            false;
                    }
                }

                if (
                    $firstContainsSecond ||
                    $secondContainsFirst
                ) {
                    throw new InvalidArgumentException(
                        'The selected academic targets overlap. Remove the duplicate or broader target.'
                    );
                }
            }
        }
    }

    private function nullablePositiveInt(
        mixed $value
    ): ?int {
        $number =
            (int) $value;

        return $number > 0
            ? $number
            : null;
    }

    /* ==========================================
       DATE HELPERS
    ========================================== */

    private function normalizeDateTime(
        string $value,
        bool $nullable = false
    ): string|null|false {
        $value = trim(
            $value
        );

        if ($value === '') {
            return $nullable
                ? null
                : false;
        }

        $timestamp =
            strtotime(
                $value
            );

        if ($timestamp === false) {
            throw new InvalidArgumentException(
                'Invalid date and time.'
            );
        }

        return date(
            'Y-m-d H:i:s',
            $timestamp
        );
    }

    /* ==========================================
       IMAGE UPLOAD
    ========================================== */

    private function handleOptionalImage(
        ?array $image,
        string $folder
    ): ?string {
        if (
            !$image ||
            !isset($image['error']) ||
            $image['error'] ===
            UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        if (
            $image['error'] !==
            UPLOAD_ERR_OK
        ) {
            throw new RuntimeException(
                'Image upload failed.'
            );
        }

        if (
            (int) $image['size'] >
            5 * 1024 * 1024
        ) {
            throw new InvalidArgumentException(
                'Image must not exceed 5 MB.'
            );
        }

        $allowedTypes = [
            'image/jpeg' =>
            'jpg',

            'image/png' =>
            'png',

            'image/webp' =>
            'webp'
        ];

        $fileInfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );

        $mimeType =
            $fileInfo->file(
                $image['tmp_name']
            );

        if (
            !isset(
                $allowedTypes[$mimeType]
            )
        ) {
            throw new InvalidArgumentException(
                'Only JPG, PNG, and WEBP images are allowed.'
            );
        }

        $safeFolder =
            preg_replace(
                '/[^a-zA-Z0-9_-]/',
                '',
                $folder
            );

        if ($safeFolder === '') {
            throw new RuntimeException(
                'Invalid upload folder.'
            );
        }

        $directory =
            __DIR__
            . "/../../Assets/uploads/{$safeFolder}/";

        if (
            !is_dir($directory) &&
            !mkdir(
                $directory,
                0775,
                true
            ) &&
            !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Unable to create upload directory.'
            );
        }

        $fileName =
            'image_'
            . bin2hex(
                random_bytes(12)
            )
            . '.'
            . $allowedTypes[$mimeType];

        $absolutePath =
            $directory
            . $fileName;

        if (
            !move_uploaded_file(
                $image['tmp_name'],
                $absolutePath
            )
        ) {
            throw new RuntimeException(
                'Unable to save uploaded image.'
            );
        }

        return
            "Assets/uploads/{$safeFolder}/{$fileName}";
    }

    /* ==========================================
       ANNOUNCEMENT AUDIO UPLOAD
    ========================================== */

    private function storeAnnouncementAudio(
        ?array $audio
    ): ?array {
        if (
            !$audio ||
            !isset($audio['error']) ||
            $audio['error'] ===
            UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        if (
            $audio['error'] !==
            UPLOAD_ERR_OK
        ) {
            throw new RuntimeException(
                'Audio upload failed.'
            );
        }

        $fileSize =
            (int) (
                $audio['size']
                ?? 0
            );

        if (
            $fileSize <= 0 ||
            $fileSize >
            15 * 1024 * 1024
        ) {
            throw new InvalidArgumentException(
                'Audio must be a non-empty file not exceeding 15 MB.'
            );
        }

        $temporaryPath =
            (string) (
                $audio['tmp_name']
                ?? ''
            );

        if (
            $temporaryPath === '' ||
            !is_uploaded_file(
                $temporaryPath
            )
        ) {
            throw new RuntimeException(
                'The uploaded audio file is invalid.'
            );
        }

        $allowedTypes = [
            'audio/mpeg' =>
            'mp3',

            'audio/mp4' =>
            'm4a',

            'audio/x-m4a' =>
            'm4a',

            'audio/wav' =>
            'wav',

            'audio/x-wav' =>
            'wav',

            'audio/webm' =>
            'webm',

            'video/webm' =>
            'webm'
        ];

        $fileInfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );

        $mimeType =
            (string) $fileInfo->file(
                $temporaryPath
            );

        if (
            !isset(
                $allowedTypes[$mimeType]
            )
        ) {
            throw new InvalidArgumentException(
                'Only MP3, M4A, WAV, and WebM audio files are allowed.'
            );
        }

        $directory =
            __DIR__
            . '/../../Assets/uploads/announcement-audio/';

        if (
            !is_dir($directory) &&
            !mkdir(
                $directory,
                0775,
                true
            ) &&
            !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Unable to create the announcement audio directory.'
            );
        }

        $storedFileName =
            'audio_'
            . bin2hex(
                random_bytes(16)
            )
            . '.'
            . $allowedTypes[$mimeType];

        $absolutePath =
            $directory
            . $storedFileName;

        if (
            !move_uploaded_file(
                $temporaryPath,
                $absolutePath
            )
        ) {
            throw new RuntimeException(
                'Unable to save the uploaded announcement audio.'
            );
        }

        $originalFileName =
            basename(
                (string) (
                    $audio['name']
                    ?? 'Audio broadcast'
                )
            );

        $originalFileName =
            preg_replace(
                '/[\x00-\x1F\x7F]/u',
                '',
                $originalFileName
            )
            ?? 'Audio broadcast';

        $originalFileName =
            mb_substr(
                trim(
                    $originalFileName
                ),
                0,
                255,
                'UTF-8'
            );

        return [
            'path' =>
            'Assets/uploads/announcement-audio/'
                . $storedFileName,

            'file_name' =>
            $originalFileName !== ''
                ? $originalFileName
                : 'Audio broadcast',

            'mime_type' =>
            $mimeType,

            'file_size' =>
            $fileSize
        ];
    }


    /* ==========================================
       DOCUMENT UPLOAD
    ========================================== */

    private function storeDocumentUpload(
        ?array $file
    ): array {
        $validated = DocumentUploadValidator::validate($file);
        $originalName = $validated['file_name'];
        $extension = $validated['file_type'];

        $directory =
            __DIR__
            . '/../../Assets/uploads/documents/';

        if (
            !is_dir($directory) &&
            !mkdir(
                $directory,
                0775,
                true
            ) &&
            !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Unable to create document directory.'
            );
        }

        $storedName =
            'document_'
            . bin2hex(
                random_bytes(12)
            )
            . '.'
            . $extension;

        $absolutePath =
            $directory
            . $storedName;

        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $absolutePath
            )
        ) {
            throw new RuntimeException(
                'Unable to save document.'
            );
        }

        return [
            'file_name' =>
            $originalName,

            'file_path' =>
            'Assets/uploads/documents/'
                . $storedName,

            'file_type' =>
            $extension,

            'file_size' =>
            $validated['file_size']
        ];
    }

    /* ==========================================
       FILE CLEANUP
    ========================================== */

    private function deleteUploadedFile(
        ?string $relativePath
    ): void {
        if (
            $relativePath === null ||
            trim($relativePath) === ''
        ) {
            return;
        }

        $normalizedPath =
            str_replace(
                [
                    '/',
                    '\\'
                ],
                DIRECTORY_SEPARATOR,
                $relativePath
            );

        $absolutePath =
            __DIR__
            . '/../../'
            . ltrim(
                $normalizedPath,
                DIRECTORY_SEPARATOR
            );

        if (is_file($absolutePath)) {
            @unlink(
                $absolutePath
            );
        }
    }

    /* ==========================================
       ACTIVITY LOG MESSAGE
    ========================================== */

    private function buildActivityMessage(
        string $userName,
        string $contentType,
        string $workflowStatus
    ): string {
        $action = match ($workflowStatus) {
            'published' =>
            'published',

            'scheduled' =>
            'scheduled',

            'pending_review' =>
            'submitted for review',

            default =>
            'saved as draft'
        };

        return sprintf(
            '%s %s a %s',
            $userName,
            $action,
            $contentType
        );
    }
}
