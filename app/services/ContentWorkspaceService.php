<?php

require_once __DIR__ . '/../models/ContentWorkspace.php';
require_once __DIR__ . '/PublicationTransaction.php';
require_once __DIR__ . '/PublicationNotificationDispatcher.php';

require_once __DIR__ . '/FacultyScopeService.php';

require_once __DIR__
    . '/../models/Announcement.php';

require_once __DIR__
    . '/../models/Event.php';

require_once __DIR__
    . '/../models/Document.php';

require_once __DIR__
    . '/../models/Survey.php';

require_once __DIR__
    . '/NotificationService.php';

class ContentWorkspaceService
{
    private ?PublicationNotificationDispatcher $publicationDispatcher = null;
    private ?FacultyScopeService $facultyScope = null;
    private Announcement $announcement;
    private Event $event;
    private Document $document;
    private Survey $survey;

    private NotificationService $notifications;
    private const WORKFLOW_STATUSES = [
        'draft',
        'pending_review',
        'scheduled',
        'published',
        'rejected',
        'archived'
    ];
    private const CONTENT_TYPES = [
        'announcement',
        'event',
        'document',
        'survey'
    ];

    public function __construct()
    {
        $this->announcement =
            new Announcement();

        $this->event =
            new Event();

        $this->document =
            new Document();

        $this->survey =
            new Survey();

        $this->notifications =
            new NotificationService();
    }

    /* ==========================================
       WORKSPACE DATA
    ========================================== */

    public function getWorkspace(
        string $workflowStatus,
        string $currentRole,
        int $currentUserId
    ): array {
        $workflowStatus =
            $this->validateWorkflowStatus(
                $workflowStatus
            );

        if ($currentUserId <= 0) {
            throw new InvalidArgumentException(
                'A valid authenticated user is required.'
            );
        }

        $currentRole =
            trim($currentRole);

        if (
            !in_array(
                $currentRole,
                [
                    'Admin',
                    'Faculty'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'You are not authorized to access the content workspace.'
            );
        }

        /*
         * Faculty members only see their own content.
         * Administrators can see all content.
         */
        $ownerId =
            $currentRole === 'Faculty'
            ? $currentUserId
            : null;

        $items = [];

        $announcementRows =
            $this->announcement
            ->getByWorkflowStatus(
                $workflowStatus,
                $ownerId,
                250
            );

        foreach (
            $announcementRows
            as $announcement
        ) {
            $items[] =
                $this->normalizeAnnouncement(
                    $announcement
                );
        }

        $eventRows =
            $this->event
            ->getByWorkflowStatus(
                $workflowStatus,
                $ownerId,
                250
            );

        foreach (
            $eventRows
            as $event
        ) {
            $items[] =
                $this->normalizeEvent(
                    $event
                );
        }

        $documentRows =
            $this->document
            ->getByWorkflowStatus(
                $workflowStatus,
                250,
                $ownerId
            );

        foreach (
            $documentRows
            as $document
        ) {
            if (
                $ownerId !== null &&
                (int) (
                    $document['user_id']
                    ?? 0
                ) !== $ownerId
            ) {
                continue;
            }

            $items[] =
                $this->normalizeDocument(
                    $document
                );
        }

        $surveyRows =
            $this->survey
            ->getByWorkflowStatus(
                $workflowStatus,
                $ownerId,
                250
            );

        foreach (
            $surveyRows as $survey
        ) {
            $items[] =
                $this->normalizeSurvey(
                    $survey
                );
        }

        usort(
            $items,
            static function (
                array $first,
                array $second
            ): int {
                $firstDate =
                    strtotime(
                        (string) (
                            $first['created_at']
                            ?? '1970-01-01'
                        )
                    ) ?: 0;

                $secondDate =
                    strtotime(
                        (string) (
                            $second['created_at']
                            ?? '1970-01-01'
                        )
                    ) ?: 0;

                return $secondDate
                    <=> $firstDate;
            }
        );

        return [
            'active_status' =>
            $workflowStatus,

            'items' =>
            $items,

            'counts' =>
            $this->getStatusCounts(
                $currentRole,
                $currentUserId
            ),

            'allowed_statuses' =>
            self::WORKFLOW_STATUSES,

            'supported_types' =>
            self::CONTENT_TYPES
        ];
    }

    /* ==========================================
       STATUS COUNTS
    ========================================== */

    public function getStatusCounts(
        string $currentRole,
        int $currentUserId
    ): array {
        $currentRole = trim($currentRole);
        if ($currentUserId <= 0) {
            throw new InvalidArgumentException('A valid authenticated user is required.');
        }
        if (!in_array($currentRole, ['Admin', 'Faculty'], true)) {
            throw new RuntimeException('You are not authorized to access the content workspace.');
        }
        $totals = (new ContentWorkspace($this->announcement->getDatabaseConnection()))
            ->countByWorkflowStatus($currentRole === 'Faculty' ? $currentUserId : null);
        $counts = array_fill_keys(self::WORKFLOW_STATUSES, 0);
        foreach ($counts as $status => $unused) {
            $counts[$status] = $totals[$status] ?? 0;
        }
        return $counts;
    }
    /* ==========================================
       CONTENT DETAILS
    ========================================== */

    public function findItem(
        string $contentType,
        int $contentId
    ): array {
        $contentType =
            $this->validateContentType(
                $contentType
            );

        if ($contentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid content ID.'
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
        };

        if (!$item) {
            throw new RuntimeException(
                'The requested content could not be found.'
            );
        }

        return match ($contentType) {
            'announcement' =>
            $this->normalizeAnnouncement(
                $item
            ),

            'event' =>
            $this->normalizeEvent(
                $item
            ),

            'document' =>
            $this->normalizeDocument(
                $item
            ),

            'survey' =>
            $this->normalizeSurvey(
                $item
            )
        };
    }

    /* ==========================================
       FACULTY ACTIONS
    ========================================== */

    public function submitForReview(
        string $contentType,
        int $contentId,
        int $currentUserId
    ): void {
        $contentType =
            $this->validateContentType(
                $contentType
            );

        if (
            $contentId <= 0 ||
            $currentUserId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid content or user ID.'
            );
        }

        $model = $this->{$contentType};
        ($this->facultyScope ??= new FacultyScopeService())->assertSavedTargets(
            $model->getTargets($contentId), $currentUserId
        );

        $updated = match ($contentType) {
            'announcement' =>
            $this->announcement
                ->submitForReview(
                    $contentId,
                    $currentUserId
                ),

            'event' =>
            $this->event
                ->submitForReview(
                    $contentId,
                    $currentUserId
                ),

            'document' =>
            $this->document
                ->submitForReview(
                    $contentId,
                    $currentUserId
                ),

            'survey' =>
            $this->survey
                ->submitForReview(
                    $contentId,
                    $currentUserId
                ),
        };

        if (!$updated) {
            throw new RuntimeException(
                'The content could not be submitted. It may no longer be a draft or rejected item.'
            );
        }

        $this->notifyReviewSubmissionSafely(
            $contentType,
            $contentId,
            $currentUserId
        );
    }

    private function notifyReviewSubmissionSafely(
        string $contentType,
        int $contentId,
        int $submitterId
    ): void {
        try {
            /*
         * Reload the item after submission so its
         * pending-review state and author are authoritative.
         */
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
            };

            if (!$item) {
                throw new RuntimeException(
                    'Submitted content could not be reloaded.'
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
                $workflowStatus !==
                'pending_review'
            ) {
                throw new RuntimeException(
                    'Submitted content is not pending review.'
                );
            }

            $authorId =
                (int) (
                    $item['user_id']
                    ?? 0
                );

            if (
                $authorId <= 0 ||
                $authorId !== $submitterId
            ) {
                throw new RuntimeException(
                    'Submitted content author mismatch.'
                );
            }

            $submitterName =
                trim(
                    (string) (
                        $item['author_name']
                        ?? ''
                    )
                );

            $contentTitle =
                trim(
                    (string) (
                        $item['title']
                        ?? ''
                    )
                );

            /*
         * A fresh token allows another notification
         * when rejected content is revised and submitted
         * through a later workflow cycle.
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
                $this->notifications
                ->notifyReviewSubmission(
                    $submitterId,
                    $submitterName,
                    $contentType,
                    $contentId,
                    $contentTitle,
                    $submissionToken
                );

            error_log(
                'Review submission notification result: '
                    . $contentType
                    . ' #'
                    . $contentId
                    . ' | eligible='
                    . (
                        $result['eligible']
                        ?? 0
                    )
                    . ', created='
                    . (
                        $result['created']
                        ?? 0
                    )
                    . ', duplicates='
                    . (
                        $result['duplicates']
                        ?? 0
                    )
                    . ', failed='
                    . (
                        $result['failed']
                        ?? 0
                    )
            );
        } catch (Throwable $exception) {
            /*
         * Notification failure must not undo or
         * misreport a successful Faculty submission.
         */
            error_log(
                'Review submission notification error: '
                    . $contentType
                    . ' #'
                    . $contentId
                    . ' | '
                    . $exception->getMessage()
            );
        }
    }

    public function restoreToDraft(
        string $contentType,
        int $contentId,
        int $currentUserId
    ): void {
        $contentType =
            $this->validateContentType(
                $contentType
            );

        if (
            $contentId <= 0 ||
            $currentUserId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid content or user ID.'
            );
        }

        $updated = match ($contentType) {
            'announcement' =>
            $this->announcement
                ->restoreToDraft(
                    $contentId,
                    $currentUserId
                ),

            'event' =>
            $this->event
                ->restoreToDraft(
                    $contentId,
                    $currentUserId
                ),

            'document' =>
            $this->document
                ->restoreToDraft(
                    $contentId,
                    $currentUserId
                ),

            'survey' =>
            $this->survey
                ->restoreToDraft(
                    $contentId,
                    $currentUserId
                ),
        };

        if (!$updated) {
            throw new RuntimeException(
                'The content could not be restored to draft.'
            );
        }
    }

    /* ==========================================
       ADMIN REVIEW ACTIONS
    ========================================== */

    public function approve(
        string $contentType,
        int $contentId,
        int $reviewerId
    ): void {
        $contentType =
            $this->validateContentType(
                $contentType
            );

        if (
            $contentId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid content or reviewer ID.'
            );
        }

        $model = match ($contentType) {
            'announcement' => $this->announcement,
            'event' => $this->event,
            'document' => $this->document,
            'survey' => $this->survey
        };
        $updated = PublicationTransaction::run(
            $model->getDatabaseConnection(), $contentType,
            fn() => $model->approve($contentId, $reviewerId), $contentId
        );

        if (!$updated) {
            throw new RuntimeException(
                'The content could not be approved. It may no longer be pending review.'
            );
        }

        $this->notifyPublishedContentSafely(
            $contentType,
            $contentId
        );

        $this->notifyWorkflowDecisionSafely(
            $contentType,
            $contentId,
            'approved'
        );
    }

    private function notifyPublishedContentSafely(
        string $contentType,
        int $contentId
    ): void {
        try {
            $result =
                ($this->publicationDispatcher ?? new PublicationNotificationDispatcher())->dispatch(1, $contentType, $contentId);

            error_log(
                'Approved content notification result: '
                    . $contentType
                    . ' #'
                    . $contentId
                    . ' | eligible='
                    . (
                        $result['eligible']
                        ?? 0
                    )
                    . ', created='
                    . (
                        $result['created']
                        ?? 0
                    )
                    . ', duplicates='
                    . (
                        $result['duplicates']
                        ?? 0
                    )
            );
        } catch (Throwable $exception) {
            /*
         * Notification delivery must not undo or
         * misreport an otherwise successful approval.
         */
            error_log(
                'Approved content notification error: '
                    . $contentType
                    . ' #'
                    . $contentId
                    . ' | '
                    . $exception->getMessage()
            );
        }
    }

    private function notifyWorkflowDecisionSafely(
        string $contentType,
        int $contentId,
        string $decision
    ): void {
        try {
            /*
         * Reload the item after approval or rejection.
         * This provides the final workflow status,
         * reviewer note, author, and reviewed_at token.
         */
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
            };

            if (!$item) {
                throw new RuntimeException(
                    'Reviewed content could not be reloaded.'
                );
            }

            $authorId =
                (int) (
                    $item['user_id']
                    ?? 0
                );

            if ($authorId <= 0) {
                throw new RuntimeException(
                    'Reviewed content has no valid author.'
                );
            }

            $contentTitle =
                trim(
                    (string) (
                        $item['title']
                        ?? ''
                    )
                );

            $workflowStatus =
                strtolower(
                    trim(
                        (string) (
                            $item['workflow_status']
                            ?? ''
                        )
                    )
                );

            $reviewNotes =
                trim(
                    (string) (
                        $item['review_notes']
                        ?? ''
                    )
                );

            $reviewedAt =
                trim(
                    (string) (
                        $item['reviewed_at']
                        ?? ''
                    )
                );

            $reviewerId =
                (int) (
                    $item['reviewed_by']
                    ?? 0
                );

            if ($reviewedAt === '') {
                throw new RuntimeException(
                    'Reviewed content has no decision timestamp.'
                );
            }

            /*
         * The timestamp and reviewer distinguish a later
         * review cycle from an earlier approval/rejection.
         */
            $decisionToken =
                $decision
                . '|'
                . $contentType
                . '|'
                . $contentId
                . '|'
                . $reviewerId
                . '|'
                . $reviewedAt;

            $created =
                $this->notifications
                ->notifyWorkflowDecision(
                    $authorId,
                    $contentType,
                    $contentId,
                    $contentTitle,
                    $decision,
                    $workflowStatus,
                    $reviewNotes,
                    $decisionToken
                );

            error_log(
                'Workflow decision notification result: '
                    . $contentType
                    . ' #'
                    . $contentId
                    . ' | decision='
                    . $decision
                    . ' | author='
                    . $authorId
                    . ' | created='
                    . (
                        $created
                        ? '1'
                        : '0'
                    )
            );
        } catch (Throwable $exception) {
            /*
         * A notification failure must never undo or
         * misreport a successful review decision.
         */
            error_log(
                'Workflow decision notification error: '
                    . $contentType
                    . ' #'
                    . $contentId
                    . ' | decision='
                    . $decision
                    . ' | '
                    . $exception->getMessage()
            );
        }
    }

    public function reject(
        string $contentType,
        int $contentId,
        int $reviewerId,
        string $reviewNotes
    ): void {
        $contentType =
            $this->validateContentType(
                $contentType
            );

        $reviewNotes =
            trim($reviewNotes);

        if (
            $contentId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid content or reviewer ID.'
            );
        }

        if ($reviewNotes === '') {
            throw new InvalidArgumentException(
                'A rejection reason is required.'
            );
        }

        $updated = match ($contentType) {
            'announcement' =>
            $this->announcement
                ->reject(
                    $contentId,
                    $reviewerId,
                    $reviewNotes
                ),

            'event' =>
            $this->event
                ->reject(
                    $contentId,
                    $reviewerId,
                    $reviewNotes
                ),

            'document' =>
            $this->document
                ->reject(
                    $contentId,
                    $reviewerId,
                    $reviewNotes
                ),


            'survey' =>
            $this->survey
                ->reject(
                    $contentId,
                    $reviewerId,
                    $reviewNotes
                ),
        };

        if (!$updated) {
            throw new RuntimeException(
                'The content could not be rejected. It may no longer be pending review.'
            );
        }

        $this->notifyWorkflowDecisionSafely(
            $contentType,
            $contentId,
            'rejected'
        );
    }

    public function archive(
        string $contentType,
        int $contentId
    ): void {
        $contentType =
            $this->validateContentType(
                $contentType
            );

        if ($contentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid content ID.'
            );
        }

        $updated = match ($contentType) {
            'announcement' =>
            $this->announcement
                ->archive(
                    $contentId
                ),

            'event' =>
            $this->event
                ->archive(
                    $contentId
                ),

            'document' =>
            $this->document
                ->archive(
                    $contentId
                ),

            'survey' =>
            $this->survey
                ->archive(
                    $contentId
                ),
        };

        if (!$updated) {
            throw new RuntimeException(
                'The content could not be archived.'
            );
        }
    }

    /* ==========================================
       NORMALIZATION
    ========================================== */

    private function normalizeAnnouncement(
        array $item
    ): array {
        return [
            'content_type' =>
            'announcement',

            'content_id' =>
            (int) (
                $item['announcement_id']
                ?? 0
            ),

            'title' =>
            $item['title']
                ?? 'Untitled Announcement',

            'description' =>
            $item['content']
                ?? '',

            'author_id' =>
            (int) (
                $item['user_id']
                ?? 0
            ),

            'author_name' =>
            trim(
                (string) (
                    $item['author_name']
                    ?? ''
                )
            ),

            'workflow_status' =>
            $item['workflow_status']
                ?? 'draft',

            'created_at' =>
            $item['created_at']
                ?? null,

            'published_at' =>
            $item['published_at']
                ?? null,

            'reviewed_at' =>
            $item['reviewed_at']
                ?? null,

            'reviewer_name' =>
            trim(
                (string) (
                    $item['reviewer_name']
                    ?? ''
                )
            ),

            'review_notes' =>
            $item['review_notes']
                ?? null,

            'priority' =>
            $item['priority']
                ?? 'Normal',

            'category' =>
            $item['category']
                ?? 'general',

            'release_mode' =>
            $item['release_mode']
                ?? 'immediate',

            'scheduled_publish_at' =>
            $item['scheduled_publish_at']
                ?? null,

            'calendar_event_id' =>
            !empty($item['calendar_event_id'])
                ? (int) $item['calendar_event_id']
                : null,

            'linked_event_title' =>
            trim(
                (string) (
                    $item['linked_event_title']
                    ?? ''
                )
            ),

            'linked_event_date' =>
            $item['linked_event_date']
                ?? null,

            'image_path' =>
            $item['image_path']
                ?? null,

            'raw' =>
            $item
        ];
    }

    private function normalizeEvent(
        array $item
    ): array {
        return [
            'content_type' =>
            'event',

            'content_id' =>
            (int) (
                $item['event_id']
                ?? 0
            ),

            'title' =>
            $item['title']
                ?? 'Untitled Event',

            'description' =>
            $item['description']
                ?? '',

            'author_id' =>
            (int) (
                $item['user_id']
                ?? 0
            ),

            'author_name' =>
            trim(
                (string) (
                    $item['author_name']
                    ?? ''
                )
            ),

            'workflow_status' =>
            $item['workflow_status']
                ?? 'draft',

            'created_at' =>
            $item['created_at']
                ?? null,

            'reviewed_at' =>
            $item['reviewed_at']
                ?? null,

            'reviewer_name' =>
            trim(
                (string) (
                    $item['reviewer_name']
                    ?? ''
                )
            ),

            'review_notes' =>
            $item['review_notes']
                ?? null,

            'event_date' =>
            $item['event_date']
                ?? null,

            'end_date' =>
            $item['end_date']
                ?? null,

            'location' =>
            $item['location']
                ?? null,

            'image_path' =>
            $item['image_path']
                ?? null,

            'raw' =>
            $item
        ];
    }

    private function normalizeDocument(
        array $item
    ): array {
        return [
            'content_type' =>
            'document',

            'content_id' =>
            (int) (
                $item['document_id']
                ?? 0
            ),

            'title' =>
            $item['file_name']
                ?? 'Untitled Document',

            'description' =>
            strtoupper(
                (string) (
                    $item['file_type']
                    ?? 'FILE'
                )
            ),

            'author_id' =>
            (int) (
                $item['user_id']
                ?? 0
            ),

            'author_name' =>
            trim(
                (string) (
                    $item['author_name']
                    ?? ''
                )
            ),

            'workflow_status' =>
            $item['workflow_status']
                ?? 'draft',

            'created_at' =>
            $item['created_at']
                ?? null,

            'reviewed_at' =>
            $item['reviewed_at']
                ?? null,

            'reviewer_name' =>
            trim(
                (string) (
                    $item['reviewer_name']
                    ?? ''
                )
            ),

            'review_notes' =>
            $item['review_notes']
                ?? null,

            'release_mode' =>
            $item['release_mode']
                ?? 'immediate',

            'scheduled_publish_at' =>
            $item['scheduled_publish_at']
                ?? null,

            'calendar_event_id' =>
            !empty($item['calendar_event_id'])
                ? (int) $item['calendar_event_id']
                : null,

            'linked_event_title' =>
            trim(
                (string) (
                    $item['linked_event_title']
                    ?? ''
                )
            ),

            'linked_event_date' =>
            $item['linked_event_date']
                ?? null,

            'file_name' =>
            $item['file_name']
                ?? '',

            'file_path' =>
            $item['file_path']
                ?? '',

            'file_type' =>
            $item['file_type']
                ?? '',

            'file_size' =>
            (int) (
                $item['file_size']
                ?? 0
            ),

            'image_path' =>
            $item['cover_image_path']
                ?? null,

            'raw' =>
            $item
        ];
    }

    /* ==========================================
       NORMALIZE SURVEY
    ========================================== */

    private function normalizeSurvey(
        array $item
    ): array {
        return [
            'content_type' =>
            'survey',

            'content_id' =>
            (int) (
                $item['survey_id']
                ?? 0
            ),

            'title' =>
            $item['title']
                ?? 'Untitled Survey',

            'description' =>
            $item['description']
                ?? '',

            'author_id' =>
            (int) (
                $item['user_id']
                ?? 0
            ),

            'author_name' =>
            trim(
                (string) (
                    $item['author_name']
                    ?? ''
                )
            ),

            'workflow_status' =>
            $item['workflow_status']
                ?? 'draft',

            'created_at' =>
            $item['created_at']
                ?? null,

            'published_at' =>
            $item['published_at']
                ?? null,

            'reviewed_at' =>
            $item['reviewed_at']
                ?? null,

            'reviewer_name' =>
            trim(
                (string) (
                    $item['reviewer_name']
                    ?? ''
                )
            ),

            'review_notes' =>
            $item['review_notes']
                ?? null,

            'release_mode' =>
            $item['release_mode']
                ?? 'immediate',

            'scheduled_publish_at' =>
            $item['scheduled_publish_at']
                ?? null,

            'calendar_event_id' =>
            !empty($item['calendar_event_id'])
                ? (int) $item['calendar_event_id']
                : null,

            'linked_event_title' =>
            trim(
                (string) (
                    $item['linked_event_title']
                    ?? ''
                )
            ),

            'linked_event_date' =>
            $item['linked_event_date']
                ?? null,

            'opens_at' =>
            $item['opens_at']
                ?? null,

            'closes_at' =>
            $item['closes_at']
                ?? null,

            'response_count' =>
            (int) (
                $item['response_count']
                ?? 0
            ),

            'allow_reactions' =>
            !empty($item['allow_reactions']),

            'allow_comments' =>
            !empty($item['allow_comments']),

            'require_acknowledgment' =>
            !empty($item['require_acknowledgment']),

            'send_notification' =>
            !empty($item['send_notification']),

            'image_path' =>
            null,

            'raw' =>
            $item
        ];
    }



    /* ==========================================
       VALIDATION
    ========================================== */

    private function validateWorkflowStatus(
        string $workflowStatus
    ): string {
        $workflowStatus =
            strtolower(
                trim($workflowStatus)
            );

        if (
            !in_array(
                $workflowStatus,
                self::WORKFLOW_STATUSES,
                true
            )
        ) {
            return 'draft';
        }

        return $workflowStatus;
    }

    private function validateContentType(
        string $contentType
    ): string {
        $contentType =
            strtolower(
                trim($contentType)
            );

        if (
            !in_array(
                $contentType,
                self::CONTENT_TYPES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid content type.'
            );
        }

        return $contentType;
    }
}
