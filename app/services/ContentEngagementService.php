<?php

require_once __DIR__
    . '/../models/ContentEngagement.php';

require_once __DIR__
    . '/../models/Announcement.php';

require_once __DIR__
    . '/../models/Event.php';

require_once __DIR__
    . '/../models/Document.php';

require_once __DIR__
    . '/NotificationService.php';

require_once __DIR__ . '/ContentAudienceService.php';

require_once __DIR__ . '/RequestRateLimitService.php';

class ContentEngagementService
{
    private ContentEngagement $engagement;
    private ?RequestRateLimitService $rateLimits = null;

    private ContentAudienceService $audience;

    private Announcement $announcement;

    private Event $event;

    private Document $document;

    private NotificationService $notificationService;

    private array $allowedReactions = [
        'Like',
        'Love',
        'Care',
        'Wow'
    ];

    public function __construct()
    {
        $this->engagement =
            new ContentEngagement();

        $this->audience = new ContentAudienceService();

        $this->announcement =
            new Announcement();

        $this->event =
            new Event();

        $this->document =
            new Document();

        $this->notificationService =
            new NotificationService();
    }

    /* ==========================================
       OPEN CONTENT
    ========================================== */

    public function open(
        string $contentType,
        int $contentId,
        int $userId
    ): array {
        $contentType =
            $this->normalizeContentType(
                $contentType
            );

        $this->validateIdentifiers(
            $contentId,
            $userId
        );

        ($this->rateLimits ??= new RequestRateLimitService(
            new RequestRateLimit($this->engagement->getDatabaseConnection())
        ))->engagement('open', $userId);

        $content =
            $this->findContent(
                $contentType,
                $contentId,
                $userId
            );

        if (!$content) {
            throw new RuntimeException(
                'Content not found.'
            );
        }

        if ($contentType === 'announcement') {
            $content = $this->announcement->attachGovernmentSources([$content])[0];
        }

        $this->engagement->recordView(
            $contentType,
            $contentId,
            $userId
        );

        return [
            'content_type' =>
            $contentType,

            'content' =>
            $content,

            'engagement' =>
            $this->engagement
                ->getEngagement(
                    $contentType,
                    $contentId,
                    $userId
                ),

            'comments' =>
            $this->engagement
                ->getComments(
                    $contentType,
                    $contentId
                ),

            'settings' =>
            $this->getInteractionSettings(
                $contentType,
                $content
            )
        ];
    }

    /* ==========================================
       REACTION
    ========================================== */

    public function react(
        string $contentType,
        int $contentId,
        int $userId,
        string $reaction
    ): array {
        $contentType =
            $this->normalizeContentType(
                $contentType
            );

        $this->validateIdentifiers(
            $contentId,
            $userId
        );

        $reaction =
            trim($reaction);

        if (
            !in_array(
                $reaction,
                $this->allowedReactions,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid reaction type.'
            );
        }

        ($this->rateLimits ??= new RequestRateLimitService(
            new RequestRateLimit($this->engagement->getDatabaseConnection())
        ))->engagement('react', $userId);

        $content =
            $this->findContent(
                $contentType,
                $contentId,
                $userId
            );

        if (!$content) {
            throw new RuntimeException(
                'Content not found.'
            );
        }

        $settings =
            $this->getInteractionSettings(
                $contentType,
                $content
            );

        if (!$settings['allow_reactions']) {
            throw new RuntimeException(
                'Reactions are disabled for this content.'
            );
        }

        $affectedRows =
            $this->engagement->react(
                $contentType,
                $contentId,
                $userId,
                $reaction
            );

        $reactionChanged =
            $affectedRows > 0;

        if ($reactionChanged) {
            $contentAuthorId =
                (int) (
                    $content['user_id']
                    ?? 0
                );

            $contentTitle =
                trim(
                    (string) (
                        $content['title']
                        ?? $content['file_name']
                        ?? ''
                    )
                );

            try {
                $this->notificationService
                    ->notifyContentEngagement(
                        $contentAuthorId,
                        $userId,
                        'reaction',
                        $contentType,
                        $contentId,
                        $contentTitle,

                        /*
                 * One notification for each
                 * reaction type selected by
                 * this user on this content.
                 */
                        strtolower(
                            $reaction
                        ),

                        $reaction
                    );
            } catch (Throwable $exception) {
                /*
         * The reaction remains successful even
         * if notification delivery encounters
         * an independent problem.
         */
                error_log(
                    'Reaction notification error: '
                        . $exception->getMessage()
                );
            }
        }

        return [

            'reaction_changed' =>
            $reactionChanged,

            'selected_reaction' =>
            $reaction,

            'engagement' =>
            $this->engagement
                ->getEngagement(
                    $contentType,
                    $contentId,
                    $userId
                )
        ];
    }

    /* ==========================================
       COMMENT
    ========================================== */

    public function comment(
        string $contentType,
        int $contentId,
        int $userId,
        string $comment,
        ?int $parentCommentId = null
    ): array {
        $contentType =
            $this->normalizeContentType(
                $contentType
            );

        $this->validateIdentifiers(
            $contentId,
            $userId
        );

        $comment =
            trim($comment);

        if ($comment === '') {
            throw new InvalidArgumentException(
                'Comment cannot be empty.'
            );
        }



        if (
            mb_strlen(
                $comment
            ) > 1000
        ) {
            throw new InvalidArgumentException(
                'Comment must not exceed 1000 characters.'
            );
        }

        ($this->rateLimits ??= new RequestRateLimitService(
            new RequestRateLimit($this->engagement->getDatabaseConnection())
        ))->engagement('comment', $userId);

        $content =
            $this->findContent(
                $contentType,
                $contentId,
                $userId
            );

        if (!$content) {
            throw new RuntimeException(
                'Content not found.'
            );
        }

        $settings =
            $this->getInteractionSettings(
                $contentType,
                $content
            );

        if (!$settings['allow_comments']) {
            throw new RuntimeException(
                'Comments are disabled for this content.'
            );
        }

        $parentComment =
            null;

        if (

            $parentCommentId !== null &&
            $parentCommentId > 0
        ) {
            $parentComment =
                $this->engagement
                ->getActiveComment(
                    $parentCommentId
                );

            if (!$parentComment) {
                throw new InvalidArgumentException(
                    'The comment being replied to is unavailable.'
                );
            }

            if (
                strtolower(
                    (string) (
                        $parentComment['content_type']
                        ?? ''
                    )
                ) !== $contentType ||
                (int) (
                    $parentComment['content_id']
                    ?? 0
                ) !== $contentId
            ) {
                throw new InvalidArgumentException(
                    'The reply does not belong to this content.'
                );
            }
        } else {
            $parentCommentId =
                null;
        }

        $commentId =
            $this->engagement
            ->comment(
                $contentType,
                $contentId,
                $userId,
                $comment,
                $parentCommentId
            );

        if ($commentId <= 0) {
            throw new RuntimeException(
                'Comment could not be saved.'
            );
        }

        $contentAuthorId =
            (int) (
                $content['user_id']
                ?? 0
            );

        $contentTitle =
            trim(
                (string) (
                    $content['title']
                    ?? $content['file_name']
                    ?? ''
                )
            );

        $replyRecipientId =
            is_array(
                $parentComment
            )
            ? (
                (int) (
                    $parentComment['user_id']
                    ?? 0
                )
            )
            : 0;

        try {
            /*
     * When the content author is also the
     * directly replied-to commenter, send one
     * reply notification instead of two notices.
     */
            $authorAction =
                (
                    $replyRecipientId > 0 &&
                    $replyRecipientId ===
                    $contentAuthorId
                )
                ? 'reply'
                : 'comment';

            $this->notificationService
                ->notifyContentEngagement(
                    $contentAuthorId,
                    $userId,
                    $authorAction,
                    $contentType,
                    $contentId,
                    $contentTitle,
                    (string) $commentId
                );

            /*
     * Notify the directly replied-to commenter
     * when that user is different from the
     * content author. Self-notifications are
     * rejected by NotificationService.
     */
            if (
                $replyRecipientId > 0 &&
                $replyRecipientId !==
                $contentAuthorId
            ) {
                $this->notificationService
                    ->notifyContentEngagement(
                        $replyRecipientId,
                        $userId,
                        'reply',
                        $contentType,
                        $contentId,
                        $contentTitle,
                        (string) $commentId
                    );
            }
        } catch (Throwable $exception) {
            /*
     * Keep the saved comment successful if
     * notification delivery independently fails.
     */
            error_log(
                'Comment notification error: '
                    . $exception->getMessage()
            );
        }

        return [
            'engagement' =>
            $this->engagement
                ->getEngagement(
                    $contentType,
                    $contentId,
                    $userId
                ),

            'comments' =>
            $this->engagement
                ->getComments(
                    $contentType,
                    $contentId
                )
        ];
    }
    /* ==========================================
       ACKNOWLEDGMENT
    ========================================== */

    public function acknowledge(
        string $contentType,
        int $contentId,
        int $userId
    ): array {
        $contentType =
            $this->normalizeContentType(
                $contentType
            );

        $this->validateIdentifiers(
            $contentId,
            $userId
        );

        ($this->rateLimits ??= new RequestRateLimitService(
            new RequestRateLimit($this->engagement->getDatabaseConnection())
        ))->engagement('acknowledge', $userId);

        $content =
            $this->findContent(
                $contentType,
                $contentId,
                $userId
            );

        if (!$content) {
            throw new RuntimeException(
                'Content not found.'
            );
        }

        $settings =
            $this->getInteractionSettings(
                $contentType,
                $content
            );

        if (
            !$settings['require_acknowledgment']
        ) {
            throw new RuntimeException(
                'Acknowledgment is not required for this content.'
            );
        }

        $wasInserted =
            $this->engagement
            ->acknowledge(
                $contentType,
                $contentId,
                $userId
            );

        if ($wasInserted) {
            $contentAuthorId =
                (int) (
                    $content['user_id']
                    ?? 0
                );

            $contentTitle =
                trim(
                    (string) (
                        $content['title']
                        ?? $content['file_name']
                        ?? ''
                    )
                );

            try {
                $this->notificationService
                    ->notifyContentEngagement(
                        $contentAuthorId,
                        $userId,
                        'acknowledgment',
                        $contentType,
                        $contentId,
                        $contentTitle,
                        'once'
                    );
            } catch (Throwable $exception) {
                /*
         * The acknowledgment remains successful
         * if notification delivery fails.
         */
                error_log(
                    'Acknowledgment notification error: '
                        . $exception->getMessage()
                );
            }
        }

        return [

            'acknowledgment_created' =>
            $wasInserted,

            'engagement' =>
            $this->engagement
                ->getEngagement(
                    $contentType,
                    $contentId,
                    $userId
                )
        ];
    }

    /* ==========================================
       CONTENT LOOKUP
    ========================================== */

    private function findContent(
        string $contentType,
        int $contentId,
        int $userId
    ): ?array {
        $content = match ($contentType) {
            'announcement' => $this->announcement->findById($contentId),
            'event' => $this->event->findById($contentId),
            'document' => $this->document->findById($contentId),
            default => null
        };

        if ($content !== null) {
            $this->audience->requirePublishedAccess($contentType, $content, $userId);
            if ($contentType === 'document') {
                $content['file_path'] = 'index.php?page=document_download&document_id=' . $contentId;
            }
        }
        return $content;
    }

    /* ==========================================
       INTERACTION SETTINGS
    ========================================== */

    private function getInteractionSettings(
        string $contentType,
        array $content
    ): array {
        return [
            'allow_reactions' =>
            $this->toBoolean(
                $content['allow_reactions']
                    ?? 1
            ),

            'allow_comments' =>
            $this->toBoolean(
                $content['allow_comments']
                    ?? 1
            ),

            'require_acknowledgment' =>
            $this->toBoolean(
                $content['require_acknowledgment']
                    ?? 0
            ),

            'can_download' =>
            $contentType ===
                'document'
        ];
    }

    private function toBoolean(
        mixed $value
    ): bool {
        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /* ==========================================
       VALIDATION
    ========================================== */

    private function normalizeContentType(
        string $contentType
    ): string {
        $contentType =
            strtolower(
                trim($contentType)
            );

        $allowedTypes = [
            'announcement',
            'event',
            'document',
            'survey'
        ];

        if (
            !in_array(
                $contentType,
                $allowedTypes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid content type.'
            );
        }

        return $contentType;
    }

    private function validateIdentifiers(
        int $contentId,
        int $userId
    ): void {
        if ($contentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid content ID.'
            );
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid user ID.'
            );
        }
    }
}
