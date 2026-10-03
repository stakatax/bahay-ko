<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/NotificationService.php';

class NotificationController extends BaseController
{
    private NotificationService $service;

    public function __construct()
    {
        $this->service =
            new NotificationService();
    }

    /* ==========================================
       NOTIFICATION CENTER
    ========================================== */

    public function index(): array
    {

        header(
            'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
        );

        header(
            'Pragma: no-cache'
        );

        header(
            'Expires: 0'
        );


        $this->requireLogin();

        $userId =
            $this->getCurrentUserId();

        return [
            'notifications' =>
            $this->service
                ->getForUser(
                    $userId,
                    50
                ),

            'unread_count' =>
            $this->service
                ->countUnread(
                    $userId
                ),

            'preferences' =>
            $this->service
                ->getPreferences(
                    $userId
                )
        ];
    }

    /* ==========================================
       OPEN OWNED NOTIFICATION
    ========================================== */

    public function open(): void
    {
        $this->requireLogin();

        try {
            $notificationId =
                $this->getNotificationIdFromQuery();

            $actionUrl =
                $this->service
                ->open(
                    $notificationId,
                    $this->getCurrentUserId()
                );

            $this->redirect(
                $actionUrl
            );
        } catch (Throwable $exception) {
            error_log(
                'Notification open error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=notifications'
                    . '&error='
                    . urlencode(
                        'Unable to open that notification.'
                    )
            );
        }
    }

    /* ==========================================
       MARK ALL AS READ
    ========================================== */

    public function markAllAsRead(): void
    {
        $this->requireLogin();

        try {
            $this->requirePostRequest();

            $this->requireCsrfToken();

            $updated =
                $this->service
                ->markAllAsRead(
                    $this->getCurrentUserId()
                );

            $message =
                $updated > 0
                ? (
                    $updated
                    . ' notification'
                    . (
                        $updated === 1
                        ? ''
                        : 's'
                    )
                    . ' marked as read.'
                )
                : 'You have no unread notifications.';

            $this->redirect(
                'index.php?page=notifications'
                    . '&success='
                    . urlencode($message)
            );
        } catch (Throwable $exception) {
            error_log(
                'Notification mark-all-read error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=notifications'
                    . '&error='
                    . urlencode(
                        'Unable to update notifications.'
                    )
            );
        }
    }

    /* ==========================================
   UPDATE NOTIFICATION PREFERENCES
========================================== */

    public function updatePreferences(): void
    {
        $this->requireLogin();

        try {
            $this->requirePostRequest();
            $this->requireCsrfToken();

            $systemEnabled =
                (
                    $_POST['system_enabled']
                    ?? '0'
                ) === '1';

            $preferences =
                $this->service
                ->updateSystemPreference(
                    $this->getCurrentUserId(),
                    $systemEnabled
                );

            $message =
                !empty($preferences['system_enabled'])
                ? 'In-system notifications are now enabled.'
                : 'In-system notifications are now disabled.';

            $this->redirect(
                'index.php?page=notifications'
                    . '&success='
                    . urlencode($message)
            );
        } catch (Throwable $exception) {
            error_log(
                'Notification preference update error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=notifications'
                    . '&error='
                    . urlencode(
                        'Unable to update notification preferences.'
                    )
            );
        }
    }


    public function updateEmailPreference(): void
    {
        $this->requireLogin();

        try {
            $this->requirePostRequest();
            $this->requireCsrfToken();

            $emailEnabled =
                (
                    $_POST['email_enabled']
                    ?? '0'
                ) === '1';

            $preferences =
                $this->service
                ->updateEmailPreference(
                    $this->getCurrentUserId(),
                    $emailEnabled
                );

            $message =
                !empty($preferences['email_enabled'])
                ? 'Email notifications are now enabled.'
                : 'Email notifications are now disabled.';

            $this->redirect(
                'index.php?page=notifications'
                    . '&success='
                    . urlencode($message)
            );
        } catch (Throwable $exception) {
            error_log(
                'Email preference update error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=notifications'
                    . '&error='
                    . urlencode(
                        'Unable to update email notifications.'
                    )
            );
        }
    }

    /* ==========================================
       UPDATE CATEGORY PREFERENCES
    ========================================== */

    public function updateCategoryPreferences(): void
    {
        $this->requireLogin();

        try {
            $this->requirePostRequest();
            $this->requireCsrfToken();

            $submittedCategories =
                $_POST['categories']
                ?? [];

            if (
                !is_array(
                    $submittedCategories
                )
            ) {
                throw new InvalidArgumentException(
                    'Invalid notification category preferences.'
                );
            }

            $allowedCategories = [
                'content_updates',
                'discussion',
                'engagement',
                'reminders',
                'workflow',
                'account_system'
            ];

            $preferences = [];

            foreach (
                $allowedCategories
                as $category
            ) {
                $submittedPreference =
                    $submittedCategories[$category]
                    ?? [];

                if (
                    !is_array(
                        $submittedPreference
                    )
                ) {
                    $submittedPreference = [];
                }

                $preferences[$category] = [
                    'system_enabled' => (
                        $submittedPreference['system_enabled']
                        ?? '0'
                    ) === '1',

                    'email_enabled' => (
                        $submittedPreference['email_enabled']
                        ?? '0'
                    ) === '1',

                    'browser_push_enabled' => (
                        $submittedPreference['browser_push_enabled']
                        ?? '0'
                    ) === '1'
                ];
            }

            $this->service
                ->updateCategoryPreferences(
                    $this->getCurrentUserId(),
                    $preferences
                );

            $this->redirect(
                'index.php?page=notifications'
                    . '&success='
                    . urlencode(
                        'Notification categories updated successfully.'
                    )
            );
        } catch (Throwable $exception) {
            error_log(
                'Notification category preference error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=notifications'
                    . '&error='
                    . urlencode(
                        'Unable to update notification categories.'
                    )
            );
        }
    }



    /* ==========================================
       REQUEST VALUES
    ========================================== */

    private function getCurrentUserId(): int
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

    private function getNotificationIdFromQuery(): int
    {
        $notificationId =
            filter_input(
                INPUT_GET,
                'notification_id',
                FILTER_VALIDATE_INT
            );

        if (
            !$notificationId ||
            $notificationId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid notification ID.'
            );
        }

        return (int) $notificationId;
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

        if ($requestMethod !== 'POST') {
            throw new RuntimeException(
                'Invalid request method.'
            );
        }
    }
}
