<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__
    . '/../services/ContentEngagementService.php';

class ContentEngagementController extends BaseController
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

    private function requireJsonCsrfToken(): void
    {
        $submittedToken =
            $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;

        $isValid =
            validateCsrfToken(
                is_string($submittedToken)
                    ? $submittedToken
                    : null
            );

        if (!$isValid) {
            $this->respondError(
                'Your page session expired. Refresh the page and try again.',
                419
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

    /* ==========================================
       REQUEST VALUES
    ========================================== */

    private function getContentType(): string
    {
        $submittedType = $_POST['content_type'] ?? '';
        if (!is_string($submittedType)) {
            throw new InvalidArgumentException('Invalid content type.');
        }
        $contentType = strtolower(trim($submittedType));

        $allowedTypes = [
            'announcement',
            'event',
            'document'
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
        $contentId = filter_input(
            INPUT_POST,
            'content_id',
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
       OPEN CONTENT
    ========================================== */

    public function open(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();
        $this->requireJsonCsrfToken();

        try {
            $contentType =
                $this->getContentType();

            $data =
                $this->service->open(
                    $contentType,
                    $this->getContentId(),
                    $this->getUserId()
                );

            $this->respondSuccess(
                $data,
                ucfirst($contentType)
                    . ' loaded successfully.'
            );
        } catch (DomainException $exception) {
            $this->respondError('Content not found.', 404);
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
                'Content open error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to load the selected content.',
                500
            );
        }
    }

    /* ==========================================
       REACT TO CONTENT
    ========================================== */

    public function react(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();
        $this->requireJsonCsrfToken();

        try {
            $reaction = trim(
                (string) (
                    $_POST['reaction']
                    ?? ''
                )
            );

            $data =
                $this->service->react(
                    $this->getContentType(),
                    $this->getContentId(),
                    $this->getUserId(),
                    $reaction
                );

            $this->respondSuccess(
                $data,
                'Vote updated successfully.'
            );
        } catch (DomainException $exception) {
            $this->respondError('Content not found.', 404);
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
                'Content reaction error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to update the vote.',
                500
            );
        }
    }

    /* ==========================================
       COMMENT ON CONTENT
    ========================================== */

    public function comment(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();
        $this->requireJsonCsrfToken();

        try {
            $comment =
                trim(
                    (string) (
                        $_POST['comment']
                        ?? ''
                    )
                );

            $parentCommentId =
                null;

            $submittedParentCommentId =
                $_POST['parent_comment_id']
                ?? null;

            if (
                $submittedParentCommentId !== null &&
                $submittedParentCommentId !== ''
            ) {
                if (
                    is_array(
                        $submittedParentCommentId
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Invalid parent comment.'
                    );
                }

                $validatedParentCommentId =
                    filter_var(
                        $submittedParentCommentId,
                        FILTER_VALIDATE_INT
                    );

                if (
                    $validatedParentCommentId === false ||
                    $validatedParentCommentId <= 0
                ) {
                    throw new InvalidArgumentException(
                        'Invalid parent comment.'
                    );
                }

                $parentCommentId =
                    (int) $validatedParentCommentId;
            }

            $data =
                $this->service
                ->comment(
                    $this->getContentType(),
                    $this->getContentId(),
                    $this->getUserId(),
                    $comment,
                    $parentCommentId
                );

            $this->respondSuccess(
                $data,
                $parentCommentId !== null
                    ? 'Reply posted successfully.'
                    : 'Comment posted successfully.'
            );
        } catch (DomainException $exception) {
            $this->respondError('Content not found.', 404);
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
                'Content comment error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to post the comment.',
                500
            );
        }
    }

    /* ==========================================
       ACKNOWLEDGE CONTENT
    ========================================== */

    public function acknowledge(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();
        $this->requireJsonCsrfToken();

        try {
            $data =
                $this->service->acknowledge(
                    $this->getContentType(),
                    $this->getContentId(),
                    $this->getUserId()
                );

            $this->respondSuccess(
                $data,
                'Content acknowledged successfully.'
            );
        } catch (DomainException $exception) {
            $this->respondError('Content not found.', 404);
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
                'Content acknowledgment error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to acknowledge the content.',
                500
            );
        }
    }
}
