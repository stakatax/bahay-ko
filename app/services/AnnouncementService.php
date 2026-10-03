<?php

require_once __DIR__ . '/../models/Announcement.php';

class AnnouncementService
{
    private Announcement $announcement;

    public function __construct()
    {
        $this->announcement =
            new Announcement();
    }

    /* ==========================================
       SHARED VALIDATION
    ========================================== */

    private function validateIds(
        int $announcementId,
        int $userId
    ): void {
        if ($announcementId <= 0) {
            throw new InvalidArgumentException(
                'Invalid announcement ID.'
            );
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid user ID.'
            );
        }
    }

    private function requireAnnouncement(
        int $announcementId
    ): array {
        $announcement =
            $this->announcement->findById(
                $announcementId
            );

        if (!$announcement) {
            throw new RuntimeException(
                'Announcement not found.'
            );
        }

        return $announcement;
    }

    /* ==========================================
       OPEN ANNOUNCEMENT
    ========================================== */

    public function open(
        int $announcementId,
        int $userId
    ): array {
        $this->validateIds(
            $announcementId,
            $userId
        );

        $announcement =
            $this->requireAnnouncement(
                $announcementId
            );

        /*
         * The model should make sure that one user
         * does not create unlimited duplicate views.
         */
        $this->announcement->recordView(
            $announcementId,
            $userId
        );

        return [
            'announcement' =>
            $announcement,

            'engagement' =>
            $this->announcement->getEngagement(
                $announcementId,
                $userId
            ),

            'comments' =>
            $this->announcement->getComments(
                $announcementId
            )
        ];
    }

    /* ==========================================
       REACT TO ANNOUNCEMENT
    ========================================== */

    public function react(
        int $announcementId,
        int $userId,
        string $reaction
    ): array {
        $this->validateIds(
            $announcementId,
            $userId
        );

        $this->requireAnnouncement(
            $announcementId
        );

        $reaction =
            trim($reaction);

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
                'Invalid reaction type.'
            );
        }

        /*
         * Expected model behavior:
         *
         * No existing reaction:
         * Insert the selected reaction.
         *
         * Different existing reaction:
         * Update it.
         *
         * Same existing reaction:
         * Delete it to toggle the reaction off.
         */
        $selectedReaction =
            $this->announcement->react(
                $announcementId,
                $userId,
                $reaction
            );

        return [
            'selected_reaction' =>
            $selectedReaction,

            'engagement' =>
            $this->announcement->getEngagement(
                $announcementId,
                $userId
            )
        ];
    }

    /* ==========================================
       COMMENT ON ANNOUNCEMENT
    ========================================== */

    public function comment(
        int $announcementId,
        int $userId,
        string $comment
    ): array {
        $this->validateIds(
            $announcementId,
            $userId
        );

        $this->requireAnnouncement(
            $announcementId
        );

        $comment =
            trim($comment);

        if ($comment === '') {
            throw new InvalidArgumentException(
                'Comment cannot be empty.'
            );
        }

        if (
            mb_strlen($comment) > 1000
        ) {
            throw new InvalidArgumentException(
                'Comment cannot exceed 1,000 characters.'
            );
        }

        $this->announcement->addComment(
            $announcementId,
            $userId,
            $comment
        );

        return [
            'engagement' =>
            $this->announcement->getEngagement(
                $announcementId,
                $userId
            ),

            'comments' =>
            $this->announcement->getComments(
                $announcementId
            )
        ];
    }

    /* ==========================================
       ACKNOWLEDGE ANNOUNCEMENT
    ========================================== */

    public function acknowledge(
        int $announcementId,
        int $userId
    ): array {
        $this->validateIds(
            $announcementId,
            $userId
        );

        $this->requireAnnouncement(
            $announcementId
        );

        /*
         * This should be idempotent.
         * Clicking more than once must not create
         * duplicate acknowledgment records.
         */
        $this->announcement->acknowledge(
            $announcementId,
            $userId
        );

        return [
            'engagement' =>
            $this->announcement->getEngagement(
                $announcementId,
                $userId
            )
        ];
    }
}
