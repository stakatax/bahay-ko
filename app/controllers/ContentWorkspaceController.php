<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/ContentWorkspaceService.php';

class ContentWorkspaceController extends BaseController
{
    private ContentWorkspaceService $service;

    public function __construct()
    {
        $this->service =
            new ContentWorkspaceService();
    }

    /* ==========================================
       CONTENT WORKSPACE PAGE
    ========================================== */

    public function index(): array
    {
        $this->requireAnyRole([
            'Admin',
            'Faculty'
        ]);

        $currentRole =
            (string) (
                $_SESSION['role']
                ?? 'Guest'
            );

        $currentUserId =
            (int) (
                $_SESSION['user_id']
                ?? 0
            );

        if ($currentUserId <= 0) {
            throw new RuntimeException(
                'Authentication is required.'
            );
        }

        $workflowStatus =
            strtolower(
                trim(
                    (string) (
                        $_GET['status']
                        ?? 'draft'
                    )
                )
            );

        $requestedType =
            strtolower(
                trim(
                    (string) (
                        $_GET['open_type']
                        ?? ''
                    )
                )
            );

        $requestedId =
            filter_input(
                INPUT_GET,
                'open_id',
                FILTER_VALIDATE_INT
            );

        $allowedTypes = [
            'announcement',
            'event',
            'document',
            'survey'
        ];

        if (
            in_array(
                $requestedType,
                $allowedTypes,
                true
            ) &&
            $requestedId !== false &&
            $requestedId !== null &&
            $requestedId > 0
        ) {
            try {
                $requestedItem =
                    $this->service
                    ->findItem(
                        $requestedType,
                        (int) $requestedId
                    );

                $authorId =
                    (int) (
                        $requestedItem['author_id']
                        ?? 0
                    );

                /*
             * Admin may inspect every workspace item.
             * Faculty may deep-link only to their own item.
             */
                $canOpenRequestedItem =
                    $currentRole === 'Admin'
                    || (
                        $currentRole === 'Faculty'
                        && $authorId ===
                        $currentUserId
                    );

                if ($canOpenRequestedItem) {
                    $requestedStatus =
                        strtolower(
                            trim(
                                (string) (
                                    $requestedItem['workflow_status']
                                    ?? ''
                                )
                            )
                        );

                    if (
                        in_array(
                            $requestedStatus,
                            [
                                'draft',
                                'pending_review',
                                'scheduled',
                                'published',
                                'rejected',
                                'archived'
                            ],
                            true
                        )
                    ) {
                        $workflowStatus =
                            $requestedStatus;
                    }
                }
            } catch (Throwable $exception) {
                /*
             * Invalid or inaccessible deep links fall back
             * to the requested/default Workspace status.
             */
                error_log(
                    'Workspace deep-link resolution error: '
                        . publicErrorMessage($exception)
                );
            }
        }

        return $this->service
            ->getWorkspace(
                $workflowStatus,
                $currentRole,
                $currentUserId
            );
    }

    /* ==========================================
       SUBMIT FOR REVIEW
    ========================================== */

    public function submitForReview(): void
    {
        try {
            $this->requireAnyRole([
                'Faculty'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $contentType =
                $this->getContentType();

            $contentId =
                $this->getContentId();

            $currentUserId =
                $this->getCurrentUserId();

            $this->service
                ->submitForReview(
                    $contentType,
                    $contentId,
                    $currentUserId
                );

            $this->redirectToWorkspace(
                'pending_review',
                'Content submitted for review.'
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                $exception
            );
        }
    }

    /* ==========================================
       RESTORE TO DRAFT
    ========================================== */

    public function restoreToDraft(): void
    {
        try {
            $this->requireAnyRole([
                'Admin',
                'Faculty'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $contentType =
                $this->getContentType();

            $contentId =
                $this->getContentId();

            $currentUserId =
                $this->getCurrentUserId();

            $currentRole =
                (string) (
                    $_SESSION['role']
                    ?? 'Guest'
                );

            /*
             * Faculty can restore only their own
             * rejected or archived content.
             *
             * Admin restoration currently also uses
             * ownership protection from the model.
             */
            $this->service
                ->restoreToDraft(
                    $contentType,
                    $contentId,
                    $currentUserId
                );

            $this->redirectToWorkspace(
                'draft',
                'Content restored to draft.'
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                $exception
            );
        }
    }

    /* ==========================================
       APPROVE CONTENT
    ========================================== */

    public function approve(): void
    {
        try {
            $this->requireAnyRole([
                'Admin'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $contentType =
                $this->getContentType();

            $contentId =
                $this->getContentId();

            $reviewerId =
                $this->getCurrentUserId();

            $this->service->approve(
                $contentType,
                $contentId,
                $reviewerId
            );

            $this->redirectToWorkspace(
                'pending_review',
                'Content approved successfully.'
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                $exception,
                'pending_review'
            );
        }
    }

    /* ==========================================
       REJECT CONTENT
    ========================================== */

    public function reject(): void
    {
        try {
            $this->requireAnyRole([
                'Admin'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $contentType =
                $this->getContentType();

            $contentId =
                $this->getContentId();

            $reviewerId =
                $this->getCurrentUserId();

            $reviewNotes =
                trim(
                    (string) (
                        $_POST['review_notes']
                        ?? ''
                    )
                );

            if ($reviewNotes === '') {
                throw new InvalidArgumentException(
                    'A rejection reason is required.'
                );
            }

            $this->service->reject(
                $contentType,
                $contentId,
                $reviewerId,
                $reviewNotes
            );

            $this->redirectToWorkspace(
                'pending_review',
                'Content rejected successfully.'
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                $exception,
                'pending_review'
            );
        }
    }

    /* ==========================================
       ARCHIVE CONTENT
    ========================================== */

    public function archive(): void
    {
        try {
            $this->requireAnyRole([
                'Admin'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $contentType =
                $this->getContentType();

            $contentId =
                $this->getContentId();

            $this->service->archive(
                $contentType,
                $contentId
            );

            $this->redirectToWorkspace(
                'published',
                'Content archived successfully.'
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                $exception,
                'published'
            );
        }
    }

    /* ==========================================
       REQUEST HELPERS
    ========================================== */

    private function requirePostRequest(): void
    {
        $requestMethod =
            strtoupper(
                (string) (
                    $_SERVER['REQUEST_METHOD']
                    ?? ''
                )
            );

        if ($requestMethod !== 'POST') {
            throw new RuntimeException(
                'Invalid request method.'
            );
        }
    }

    private function getCurrentUserId(): int
    {
        $currentUserId =
            (int) (
                $_SESSION['user_id']
                ?? 0
            );

        if ($currentUserId <= 0) {
            throw new RuntimeException(
                'Authentication is required.'
            );
        }

        return $currentUserId;
    }

    private function getContentType(): string
    {
        $contentType =
            strtolower(
                trim(
                    (string) (
                        $_POST['content_type']
                        ?? ''
                    )
                )
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

    private function getContentId(): int
    {
        $contentId =
            filter_var(
                $_POST['content_id']
                    ?? null,
                FILTER_VALIDATE_INT
            );

        if (
            !$contentId ||
            $contentId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid content ID.'
            );
        }

        return (int) $contentId;
    }

    /* ==========================================
       REDIRECT HELPERS
    ========================================== */

    private function redirectToWorkspace(
        string $status,
        string $message
    ): void {
        $this->redirect(
            'index.php?page=content_workspace'
                . '&status='
                . urlencode($status)
                . '&success='
                . urlencode($message)
        );
    }

    private function redirectWithError(
        Throwable $exception,
        string $status = 'draft'
    ): void {
        error_log(
            'Content Workspace error: '
                . publicErrorMessage($exception)
        );

        $this->redirect(
            'index.php?page=content_workspace'
                . '&status='
                . urlencode($status)
                . '&error='
                . urlencode(
                    publicErrorMessage($exception)
                )
        );
    }
}
