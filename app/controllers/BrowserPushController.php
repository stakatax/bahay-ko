<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/BrowserPushService.php';

class BrowserPushController extends BaseController
{
    private BrowserPushService $service;

    public function __construct()
    {
        $this->service =
            new BrowserPushService();
    }

    /* ==========================================
       JSON RESPONSE
    ========================================== */

    private function respond(
        array $payload,
        int $status = 200
    ): void {
        http_response_code(
            $status
        );

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
            'success' =>
            true,

            'message' =>
            $message,

            'data' =>
            $data
        ]);
    }

    private function respondError(
        string $message,
        int $status = 422
    ): void {
        $this->respond(
            [
                'success' =>
                false,

                'message' =>
                $message
            ],
            $status
        );
    }

    /* ==========================================
       REQUEST SECURITY
    ========================================== */

    private function requireJsonLogin(): void
    {
        if (
            empty($_SESSION['user_id'])
        ) {
            $this->respondError(
                'Authentication required.',
                401
            );
        }
    }

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
            $this->respondError(
                'Invalid request method.',
                405
            );
        }
    }

    private function requireJsonCsrfToken(): void
    {
        $submittedToken =
            $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? $_POST['csrf_token']
            ?? null;

        $isValid =
            validateCsrfToken(
                is_string(
                    $submittedToken
                )
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

    /* ==========================================
       REQUEST VALUES
    ========================================== */

    private function getUserId(): int
    {
        $userId =
            (int) (
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

    private function getJsonBody(): array
    {
        $rawBody =
            file_get_contents(
                'php://input'
            );

        if (
            !is_string($rawBody) ||
            trim($rawBody) === ''
        ) {
            return [];
        }

        $payload =
            json_decode(
                $rawBody,
                true
            );

        if (
            !is_array($payload)
        ) {
            throw new InvalidArgumentException(
                'Invalid JSON request body.'
            );
        }

        $this->validateJsonPayload($payload);

        return $payload;
    }

    private function validateJsonPayload(array $payload): void
    {
        foreach (['endpoint', 'content_encoding', 'device_label'] as $field) {
            if (isset($payload[$field]) && !is_string($payload[$field])) {
                throw new InvalidArgumentException('Invalid browser notification details.');
            }
        }
        if (!array_key_exists('subscription', $payload)) return;
        if (!is_array($payload['subscription'])) {
            throw new InvalidArgumentException('Invalid browser subscription.');
        }
        $subscription = $payload['subscription'];
        if (isset($subscription['endpoint']) && !is_string($subscription['endpoint'])) {
            throw new InvalidArgumentException('Invalid browser subscription endpoint.');
        }
        if (isset($subscription['keys'])) {
            if (!is_array($subscription['keys'])) {
                throw new InvalidArgumentException('Invalid browser subscription keys.');
            }
            foreach (['p256dh', 'auth'] as $key) {
                if (isset($subscription['keys'][$key]) && !is_string($subscription['keys'][$key])) {
                    throw new InvalidArgumentException('Invalid browser subscription keys.');
                }
            }
        }
    }

    /* ==========================================
       PUBLIC CLIENT CONFIGURATION
    ========================================== */

    public function configuration(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();
        $this->requireJsonCsrfToken();

        try {
            $data =
                $this->service
                ->getClientConfiguration(
                    $this->getUserId()
                );

            $this->respondSuccess(
                $data,
                'Browser push configuration loaded.'
            );
        } catch (Throwable $exception) {
            error_log(
                'Browser push configuration error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to load browser notification configuration.',
                500
            );
        }
    }

    /* ==========================================
       SUBSCRIBE CURRENT BROWSER
    ========================================== */

    public function subscribe(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();
        $this->requireJsonCsrfToken();

        try {
            $payload =
                $this->getJsonBody();

            $subscription =
                is_array(
                    $payload['subscription']
                        ?? null
                )
                ? $payload['subscription']
                : [];

            $contentEncoding =
                trim(
                    (string) (
                        $payload['content_encoding']
                        ?? 'aes128gcm'
                    )
                );

            $subscription['contentEncoding'] =
                $contentEncoding;

            $deviceLabel =
                trim(
                    (string) (
                        $payload['device_label']
                        ?? ''
                    )
                );

            $userAgent =
                $_SERVER['HTTP_USER_AGENT']
                ?? null;

            $data =
                $this->service
                ->subscribe(
                    $this->getUserId(),
                    $subscription,
                    is_string($userAgent)
                        ? $userAgent
                        : null,
                    $deviceLabel !== ''
                        ? $deviceLabel
                        : null
                );

            $this->respondSuccess(
                $data,
                'Browser notifications are now enabled.'
            );
        } catch (
            InvalidArgumentException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 422)
            );
        } catch (Throwable $exception) {
            error_log(
                'Browser push subscription error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to enable browser notifications.',
                500
            );
        }
    }

    /* ==========================================
       UNSUBSCRIBE CURRENT BROWSER
    ========================================== */

    public function unsubscribe(): void
    {
        $this->requirePostRequest();
        $this->requireJsonLogin();
        $this->requireJsonCsrfToken();

        try {
            $payload =
                $this->getJsonBody();

            $endpoint =
                trim(
                    (string) (
                        $payload['endpoint']
                        ?? ''
                    )
                );

            if ($endpoint === '') {
                throw new InvalidArgumentException(
                    'The browser subscription endpoint is missing.'
                );
            }

            $data =
                $this->service
                ->unsubscribe(
                    $this->getUserId(),
                    $endpoint
                );

            $this->respondSuccess(
                $data,
                'Browser notifications are now disabled on this browser.'
            );
        } catch (
            InvalidArgumentException $exception
        ) {
            $this->respondError(
                publicErrorMessage($exception),
                publicErrorStatus($exception, 422)
            );
        } catch (Throwable $exception) {
            error_log(
                'Browser push unsubscription error: '
                    . publicErrorMessage($exception)
            );

            $this->respondError(
                'Unable to disable browser notifications.',
                500
            );
        }
    }
}
