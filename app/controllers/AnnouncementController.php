<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__
    . '/../services/ContentEngagementService.php';

class AnnouncementController extends BaseController
{
    private ContentEngagementService $service;

    public function __construct()
    {
        $this->service =
            new ContentEngagementService();
    }

    /* ==========================================
       JSON RESPONSE
    ========================================== */

    private function respond(
        array $payload,
        int $status = 200
    ): void {
        http_response_code($status);

        header(
            'Content-Type: application/json; charset=UTF-8'
        );

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    private function respondSuccess(
        array $data = [],
        string $message =
        'Request completed successfully.'
    ): void {
        $this->respond([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    private function respondError(
        string $message,
        int $status = 422
    ): void {
        $this->respond(
            [
                'success' => false,
                'message' => $message
            ],
            $status
        );
    }

    /* ==========================================
       REQUEST SECURITY
    ========================================== */

    private function requireJsonLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->respondError(
                'Authentication required.',
                401
            );
        }
    }

    private function requirePostRequest(): void
    {
        $requestMethod = strtoupper(
            $_SERVER['REQUEST_METHOD']
                ?? ''
        );

        if ($requestMethod !== 'POST') {
            $this->respondError(
                'Invalid request method.',
                405
            );
        }
    }

    private function getAnnouncementId(): int
    {
        $announcementId =
            filter_input(
                INPUT_POST,
                'announcement_id',
                FILTER_VALIDATE_INT
            );

        if (
            !$announcementId ||
            $announcementId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid announcement ID.'
            );
        }

        return (int) $announcementId;
    }

    private function getUserId(): int
    {
        $userId = (int) (
            $_SESSION['user_id']
            ?? 0
        );

        if ($userId <= 0) {
            throw new RuntimeException(
                'Invalid authenticated user.'
            );
        }

        return $userId;
    }

    /* ==========================================
       OPEN ANNOUNCEMENT
    ========================================== */

    public function open(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();

        try {
            $data =
                $this->service->open(
                    'announcement',
                    $this->getAnnouncementId(),
                    $this->getUserId()
                );

            $this->respondSuccess(
                $data,
                'Announcement loaded successfully.'
            );
        } catch (
            InvalidArgumentException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 422)
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 404)
            );
        } catch (
            Throwable $exception
        ) {
            error_log(
                'Announcement open error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to load the announcement.',
                500
            );
        }
    }

    /* ==========================================
       REACT TO ANNOUNCEMENT
    ========================================== */

    public function react(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();

        try {
            $reaction = trim(
                (string) (
                    $_POST['reaction']
                    ?? ''
                )
            );

            $data =
                $this->service->react(
                    'announcement',
                    $this->getAnnouncementId(),
                    $this->getUserId(),
                    $reaction
                );

            $this->respondSuccess(
                $data,
                'Reaction updated successfully.'
            );
        } catch (
            InvalidArgumentException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 422)
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 409)
            );
        } catch (
            Throwable $exception
        ) {
            error_log(
                'Announcement reaction error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to update the reaction.',
                500
            );
        }
    }

    /* ==========================================
       COMMENT ON ANNOUNCEMENT
    ========================================== */

    public function comment(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();

        try {
            $comment = trim(
                (string) (
                    $_POST['comment']
                    ?? ''
                )
            );

            $data =
                $this->service->comment(
                    'announcement',
                    $this->getAnnouncementId(),
                    $this->getUserId(),
                    $comment
                );

            $this->respondSuccess(
                $data,
                'Comment posted successfully.'
            );
        } catch (
            InvalidArgumentException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 422)
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 409)
            );
        } catch (
            Throwable $exception
        ) {
            error_log(
                'Announcement comment error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to post the comment.',
                500
            );
        }
    }

    /* ==========================================
       ACKNOWLEDGE ANNOUNCEMENT
    ========================================== */

    public function acknowledge(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();

        try {
            $data =
                $this->service->acknowledge(
                    'announcement',
                    $this->getAnnouncementId(),
                    $this->getUserId()
                );

            $this->respondSuccess(
                $data,
                'Announcement acknowledged successfully.'
            );
        } catch (
            InvalidArgumentException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 422)
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 409)
            );
        } catch (
            Throwable $exception
        ) {
            error_log(
                'Announcement acknowledgment error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to acknowledge the announcement.',
                500
            );
        }
    }
}
