<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../models/Survey.php';

require_once __DIR__
    . '/../services/SurveyService.php';

class SurveyController extends BaseController
{
    private SurveyService $service;

    public function __construct()
    {
        global $conn;

        $survey =
            new Survey(
                $conn
            );

        $this->service =
            new SurveyService(
                $survey
            );
    }

    /* ==========================================
       CREATE / STORE SURVEY
    ========================================== */

    public function store(): void
    {
        try {
            $this->requireAnyRole([
                'Admin',
                'Faculty'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $currentUserId =
                $this->getCurrentUserId();

            $editId =
                (int) (
                    $_POST['edit_id']
                    ?? 0
                );

            if ($editId > 0) {
                $surveyId =
                    $this->service
                    ->updateSurvey(
                        $editId,
                        $_POST,
                        $currentUserId
                    );

                $successMessage =
                    'Survey updated successfully.';
            } else {
                $surveyId =
                    $this->service
                    ->createSurvey(
                        $_POST,
                        $currentUserId
                    );

                $successMessage =
                    'Survey created successfully.';
            }

            if ($surveyId <= 0) {
                throw new RuntimeException(
                    $editId > 0
                        ? 'The survey could not be updated.'
                        : 'The survey could not be created.'
                );
            }

            $workflowAction =
                strtolower(
                    trim(
                        (string) (
                            $_POST['workflow_action']
                            ?? 'draft'
                        )
                    )
                );

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
                match ($workflowAction) {
                    'draft' =>
                    'draft',

                    'submit_review' =>
                    'pending_review',

                    'publish' =>
                    in_array(
                        $releaseMode,
                        [
                            'scheduled',
                            'calendar'
                        ],
                        true
                    )
                        ? 'scheduled'
                        : 'published',

                    default =>
                    'draft'
                };

            $this->redirect(
                'index.php?page=content_workspace'
                    . '&status='
                    . urlencode(
                        $workspaceStatus
                    )
                    . '&success='
                    . urlencode(
                        $successMessage
                    )
            );
        } catch (Throwable $exception) {
            $this->redirectWithError(
                $exception,
                'post'
            );
        }
    }
    /* ==========================================
       SURVEY PARTICIPATION PAGE
    ========================================== */

    public function participate(): array
    {
        try {
            $this->requireAnyRole([
                'Admin',
                'Faculty',
                'Student',
                'Parent'
            ]);

            $surveyId =
                $this->getSurveyIdFromQuery();

            $currentUserId =
                $this->getCurrentUserId();

            return $this->service
                ->getSurveyForParticipation(
                    $surveyId,
                    $currentUserId
                );
        } catch (Throwable $exception) {

            error_log(
                'Survey participation error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=news'
                    . '&error='
                    . urlencode(
                        publicErrorMessage($exception)
                    )
            );

            exit;
        }
    }

    /* ==========================================
       SUBMIT SURVEY RESPONSE
    ========================================== */

    public function submitResponse(): void
    {
        try {
            $this->requireAnyRole([
                'Admin',
                'Faculty',
                'Student',
                'Parent'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $surveyId =
                $this->getSurveyIdFromPost();

            $currentUserId =
                $this->getCurrentUserId();

            $answers =
                $_POST['answers']
                ?? [];

            if (!is_array($answers)) {
                throw new InvalidArgumentException(
                    'Invalid survey response.'
                );
            }

            $this->service
                ->submitSurveyResponse(
                    $surveyId,
                    $currentUserId,
                    $answers
                );

            $this->redirect(
                'index.php?page=survey_participate'
                    . '&survey_id='
                    . $surveyId
                    . '&success='
                    . urlencode(
                        'Survey response submitted successfully.'
                    )
            );
        } catch (Throwable $exception) {
            $surveyId =
                (int) (
                    $_POST['survey_id']
                    ?? 0
                );

            $this->redirect(
                'index.php?page=survey_participate'
                    . '&survey_id='
                    . max(
                        0,
                        $surveyId
                    )
                    . '&error='
                    . urlencode(
                        publicErrorMessage($exception)
                    )
            );
        }
    }

    /* ==========================================
       SURVEY RESULTS PAGE
    ========================================== */

    public function results(): array
    {
        $this->requireAnyRole([
            'Admin',
            'Faculty'
        ]);

        $surveyId =
            $this->getSurveyIdFromQuery();

        $result =
            $this->service
            ->getSurveyResults(
                $surveyId
            );

        $currentRole =
            (string) (
                $_SESSION['role']
                ?? 'Guest'
            );

        $currentUserId =
            $this->getCurrentUserId();

        /*
         * Faculty may view results only
         * for surveys they created.
         */
        if (
            $currentRole === 'Faculty'
        ) {
            $ownerId =
                (int) (
                    $result['survey']['user_id']
                    ?? 0
                );

            if (
                $ownerId !==
                $currentUserId
            ) {
                throw new RuntimeException(
                    'You are not allowed to view results for this survey.'
                );
            }
        }

        return $result;
    }

    /* ==========================================
       APPROVE SURVEY
    ========================================== */

    public function approve(): void
    {
        try {
            $this->requireAnyRole([
                'Admin'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $surveyId =
                $this->getSurveyIdFromPost();

            $reviewerId =
                $this->getCurrentUserId();

            $updated =
                $this->service
                ->approveSurvey(
                    $surveyId,
                    $reviewerId
                );

            if (!$updated) {
                throw new RuntimeException(
                    'The survey could not be approved.'
                );
            }

            $this->redirectToWorkspace(
                'pending_review',
                'Survey approved successfully.'
            );
        } catch (Throwable $exception) {
            $this->redirectWorkspaceError(
                $exception,
                'pending_review'
            );
        }
    }

    /* ==========================================
       REJECT SURVEY
    ========================================== */

    public function reject(): void
    {
        try {
            $this->requireAnyRole([
                'Admin'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $surveyId =
                $this->getSurveyIdFromPost();

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

            $updated =
                $this->service
                ->rejectSurvey(
                    $surveyId,
                    $reviewerId,
                    $reviewNotes
                );

            if (!$updated) {
                throw new RuntimeException(
                    'The survey could not be rejected.'
                );
            }

            $this->redirectToWorkspace(
                'pending_review',
                'Survey rejected successfully.'
            );
        } catch (Throwable $exception) {
            $this->redirectWorkspaceError(
                $exception,
                'pending_review'
            );
        }
    }

    /* ==========================================
       ARCHIVE SURVEY
    ========================================== */

    public function archive(): void
    {
        try {
            $this->requireAnyRole([
                'Admin'
            ]);

            $this->requirePostRequest();

            $this->requireCsrfToken();

            $surveyId =
                $this->getSurveyIdFromPost();

            $updated =
                $this->service
                ->archiveSurvey(
                    $surveyId
                );

            if (!$updated) {
                throw new RuntimeException(
                    'The survey could not be archived.'
                );
            }

            $this->redirectToWorkspace(
                'published',
                'Survey archived successfully.'
            );
        } catch (Throwable $exception) {
            $this->redirectWorkspaceError(
                $exception,
                'published'
            );
        }
    }

    /* ==========================================
       RESTORE SURVEY TO DRAFT
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

            $surveyId =
                $this->getSurveyIdFromPost();

            $currentUserId =
                $this->getCurrentUserId();

            $updated =
                $this->service
                ->restoreSurvey(
                    $surveyId,
                    $currentUserId
                );

            if (!$updated) {
                throw new RuntimeException(
                    'The survey could not be restored.'
                );
            }

            $this->redirectToWorkspace(
                'draft',
                'Survey restored to draft.'
            );
        } catch (Throwable $exception) {
            $this->redirectWorkspaceError(
                $exception,
                'draft'
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

        if (
            $requestMethod !== 'POST'
        ) {
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

    private function getSurveyIdFromQuery(): int
    {
        $surveyId =
            filter_var(
                $_GET['survey_id']
                    ?? null,
                FILTER_VALIDATE_INT
            );

        if (
            !$surveyId ||
            $surveyId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        return (int) $surveyId;
    }

    private function getSurveyIdFromPost(): int
    {
        $surveyId =
            filter_var(
                $_POST['survey_id']
                    ?? null,
                FILTER_VALIDATE_INT
            );

        if (
            !$surveyId ||
            $surveyId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        return (int) $surveyId;
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
                . urlencode(
                    $status
                )
                . '&success='
                . urlencode(
                    $message
                )
        );
    }

    private function redirectWorkspaceError(
        Throwable $exception,
        string $status
    ): void {
        error_log(
            'Survey Controller error: '
                . publicErrorMessage($exception)
        );

        $this->redirect(
            'index.php?page=content_workspace'
                . '&status='
                . urlencode(
                    $status
                )
                . '&error='
                . urlencode(
                    publicErrorMessage($exception)
                )
        );
    }

    private function redirectWithError(
        Throwable $exception,
        string $page
    ): void {
        error_log(
            'Survey Controller error: '
                . publicErrorMessage($exception)
        );

        $this->redirect(
            'index.php?page='
                . urlencode(
                    $page
                )
                . '&error='
                . urlencode(
                    publicErrorMessage($exception)
                )
        );
    }
}
