<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/PostService.php';
require_once __DIR__
    . '/../services/GovernmentAdvisoryIntakeService.php';

class PostController extends BaseController
{
    private PostService $service;

    private ?GovernmentAdvisoryIntakeService $governmentAdvisory = null;

    public function __construct(?mysqli $connection = null)
    {
        $this->service =
            new PostService($connection);
    }

    private function getGovernmentAdvisory(): GovernmentAdvisoryIntakeService
    {
        return $this->governmentAdvisory ??= new GovernmentAdvisoryIntakeService();
    }

    /* ==========================================
       INFORMATION HUB
    ========================================== */

    public function news(): array
    {
        $this->requireLogin();

        return $this->service
            ->getNewsFeed();
    }


    /* ==========================================
   AUTHENTICATED HOME
========================================== */

    public function home(): array
    {
        $this->requireLogin();

        try {
            return $this->service
                ->getHomeSummary();
        } catch (Throwable $exception) {
            /*
         * A summary failure must not prevent the
         * user from accessing the application.
         */
            error_log(
                'Authenticated Home summary error: '
                    . publicErrorMessage($exception)
            );

            return [
                'priority_announcement' =>
                null,

                'next_event' =>
                null
            ];
        }
    }

    /* ==========================================
   CREATE POST PAGE
========================================== */

    public function create(): array
    {
        $this->requireAnyRole([
            'Admin',
            'Faculty'
        ]);

        $viewData = [
            'content_interests' =>
            $this->service
                ->getContentInterests(),

            'departments' =>
            $this->service
                ->getDepartments(),

            'education_levels' =>
            $this->service
                ->getEducationLevels(),

            'academic_programs' =>
            $this->service
                ->getAcademicPrograms(),

            'grade_levels' =>
            $this->service
                ->getGradeLevels(),

            'sections' =>
            $this->service
                ->getSections(),

            'upcoming_events' =>
            $this->service
                ->getUpcomingEvents(),

            'edit_mode' =>
            false,

            'edit_type' =>
            null,

            'edit_id' =>
            0,

            'edit_item' =>
            null,

            'government_advisory_prefill' =>
            null
        ];

        try {
            $viewData['faculty_scope'] = (new FacultyScopeService())->forUser((int) ($_SESSION['user_id'] ?? 0));
            $viewData['faculty_scope_error'] = '';
        } catch (DomainException $exception) {
            $viewData['faculty_scope'] = null;
            $viewData['faculty_scope_error'] = publicErrorMessage($exception);
        }

        $editType =
            strtolower(
                trim(
                    (string) (
                        $_GET['edit_type']
                        ?? ''
                    )
                )
            );

        $editId =
            (int) (
                $_GET['edit_id']
                ?? 0
            );

        if (
            $editType !== '' &&
            $editId > 0
        ) {
            try {
                $userId =
                    (int) (
                        $_SESSION['user_id']
                        ?? 0
                    );

                $editItem =
                    $this->service
                    ->getEditableContent(
                        $editType,
                        $editId,
                        $userId
                    );

                $viewData['edit_mode'] =
                    true;

                $viewData['edit_type'] =
                    $editType;

                $viewData['edit_id'] =
                    $editId;

                $viewData['edit_item'] =
                    $editItem;
            } catch (Throwable $exception) {
                $this->redirect(
                    'index.php?page=content_workspace'
                        . '&status=draft'
                        . '&error='
                        . urlencode(
                            publicErrorMessage($exception)
                        )
                );
            }
        }

        $governmentAdvisoryId =
            (int) (
                $_GET['government_advisory_id']
                ?? 0
            );

        if (
            !$viewData['edit_mode'] &&
            $governmentAdvisoryId > 0
        ) {
            if (
                $this->getCurrentRole()
                !== 'Admin'
            ) {
                $this->redirectUnauthorized(
                    'Only administrators can convert government advisories.'
                );
            }

            try {
                $viewData['government_advisory_prefill'] =
                    $this->getGovernmentAdvisory()
                    ->getConversionCandidate(
                        $governmentAdvisoryId
                    );
            } catch (Throwable $exception) {
                $this->redirect(
                    'index.php?page=government_advisories'
                        . '&error='
                        . urlencode(
                            publicErrorMessage($exception)
                        )
                );
            }
        }

        $viewData['trusted_government_sources'] = [];
        if ($this->getCurrentRole() === 'Admin') {
            try {
                $viewData['trusted_government_sources'] = $this->getGovernmentAdvisory()->getSources();
            } catch (Throwable $exception) {
                error_log('Posting trusted-source directory failed: ' . $exception->getMessage());
                $viewData['trusted_government_sources_unavailable'] = true;
            }
        }

        return $viewData;
    }


    /* ==========================================
   CONTENT REDUNDANCY PREFLIGHT
========================================== */

    public function checkRedundancy(): void
    {
        header(
            'Content-Type: application/json; charset=UTF-8'
        );

        try {
            $this->requireAnyRole([
                'Admin',
                'Faculty'
            ]);

            $this->requirePostMethod();
            $this->requireCsrfToken();

            $assessment =
                $this->service
                ->assessContentRedundancy(
                    $_POST
                );

            echo json_encode(
                [
                    'success' =>
                    true,

                    'assessment' =>
                    $assessment
                ],
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            );
        } catch (Throwable $exception) {
            http_response_code(
                publicErrorStatus($exception, 422)
            );

            echo json_encode(
                [
                    'success' =>
                    false,

                    'message' =>
                    publicErrorMessage($exception)
                ],
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            );
        }

        exit;
    }

    /* ==========================================
       STORE CONTENT
    ========================================== */

    public function store(): void
    {
        try {
            $this->requireAnyRole([
                'Admin',
                'Faculty'
            ]);

            $this->requirePostMethod();
            $this->requireCsrfToken();

            $userId = (int) (
                $_SESSION['user_id']
                ?? 0
            );

            if ($userId <= 0) {
                throw new RuntimeException(
                    'You must be logged in to publish content.'
                );
            }

            $role =
                $_SESSION['role']
                ?? 'Guest';

            $postType =
                strtolower(
                    trim(
                        (string) (
                            $_POST['post_type']
                            ?? ''
                        )
                    )
                );

            $editId =
                (int) (
                    $_POST['edit_id']
                    ?? 0
                );

            $isEditMode =
                $editId > 0 &&
                in_array(
                    $postType,
                    [
                        'announcement',
                        'event',
                        'document',
                        'survey'
                    ],
                    true
                );

            $contentId =
                $this->service->create(
                    $_POST,
                    $_FILES,
                    $userId
                );

            if ($contentId <= 0) {
                throw new RuntimeException(
                    'The content could not be saved.'
                );
            }

            $governmentAdvisoryId = (int) ($_POST['government_advisory_id'] ?? 0);
            if ($governmentAdvisoryId > 0) {
                try {
                    $this->log('CONVERT_GOVERNMENT_ADVISORY',
                        'Converted government advisory #' . $governmentAdvisoryId
                        . ' into announcement #' . $contentId . '.');
                } catch (Throwable $loggingException) {
                    // Publication and its source link already committed together.
                }
            }

            $workflowAction = strtolower(
                trim(
                    (string) (
                        $_POST['workflow_action']
                        ?? 'draft'
                    )
                )
            );

            $successMessage =
                $this->resolveSuccessMessage(
                    $workflowAction,
                    $role
                );


            $successRedirect =
                'index.php?page=postings';

            if ($isEditMode) {
                $workspaceStatus =
                    'draft';

                if (
                    $workflowAction ===
                    'submit_review'
                ) {
                    $workspaceStatus =
                        'pending_review';
                } elseif (
                    $workflowAction ===
                    'publish'
                ) {
                    $releaseMode =
                        strtolower(
                            trim(
                                (string) (
                                    $_POST['release_mode']
                                    ?? 'immediate'
                                )
                            )
                        );

                    $workspaceStatus =
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
                }

                $successRedirect =
                    'index.php?page=content_workspace'
                    . '&status='
                    . urlencode(
                        $workspaceStatus
                    );
            }

            $this->redirect(
                $successRedirect
                    . '&success='
                    . urlencode(
                        $successMessage
                    )
            );
        } catch (Throwable $exception) {
            $role =
                $_SESSION['role']
                ?? 'Guest';

            $redirectPage =
                in_array(
                    $role,
                    [
                        'Admin',
                        'Faculty'
                    ],
                    true
                )
                ? 'postings'
                : 'news';

            $this->redirect(
                'index.php?page='
                    . urlencode(
                        $redirectPage
                    )
                    . '&error='
                    . urlencode(
                        publicErrorMessage($exception)
                    )
            );
        }
    }

    /* ==========================================
       SUCCESS MESSAGE
    ========================================== */

    private function resolveSuccessMessage(
        string $workflowAction,
        string $role
    ): string {
        if (
            $workflowAction === 'publish' &&
            $role === 'Admin'
        ) {
            return 'Content published successfully.';
        }

        if (
            $workflowAction === 'submit_review' ||
            (
                $workflowAction === 'publish' &&
                $role !== 'Admin'
            )
        ) {
            return 'Content submitted for review.';
        }

        return 'Draft saved successfully.';
    }
}
