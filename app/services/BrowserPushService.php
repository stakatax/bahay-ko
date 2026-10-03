<?php

require_once __DIR__
    . '/../models/PushSubscription.php';

require_once __DIR__
    . '/NotificationService.php';

require_once __DIR__
    . '/../../config/push.php';

class BrowserPushService
{
    private PushSubscription $subscriptions;

    private NotificationService $notifications;

    public function __construct()
    {
        $this->subscriptions =
            new PushSubscription();

        $this->notifications =
            new NotificationService();
    }

    /* ==========================================
       CLIENT CONFIGURATION
    ========================================== */

    public function getClientConfiguration(
        int $userId
    ): array {
        $this->validateUserId(
            $userId
        );

        $pushConfiguration =
            pushConfiguration();

        $preferences =
            $this->notifications
            ->getPreferences(
                $userId
            );

        $activeSubscriptions =
            $this->subscriptions
            ->getActiveByUserId(
                $userId
            );

        return [
            'public_key' =>
            (string) (
                $pushConfiguration['public_key']
                ?? ''
            ),

            'browser_push_enabled' =>
            !empty($preferences['browser_push_enabled']),

            'active_subscription_count' =>
            count(
                $activeSubscriptions
            )
        ];
    }

    /* ==========================================
       SUBSCRIBE CURRENT BROWSER
    ========================================== */

    public function subscribe(
        int $userId,
        array $subscription,
        ?string $userAgent = null,
        ?string $deviceLabel = null
    ): array {
        $this->validateUserId(
            $userId
        );

        $saved =
            $this->subscriptions
            ->save(
                $userId,
                $subscription,
                $userAgent,
                $deviceLabel
            );

        $preferences =
            $this->notifications
            ->updateBrowserPushPreference(
                $userId,
                true
            );

        return [
            'subscription_id' =>
            (int) (
                $saved['subscription_id']
                ?? 0
            ),

            'subscription_status' =>
            (string) (
                $saved['subscription_status']
                ?? 'Active'
            ),

            'browser_push_enabled' =>
            !empty($preferences['browser_push_enabled'])
        ];
    }

    /* ==========================================
       UNSUBSCRIBE CURRENT BROWSER
    ========================================== */

    public function unsubscribe(
        int $userId,
        string $endpoint
    ): array {
        $this->validateUserId(
            $userId
        );

        $revoked =
            $this->subscriptions
            ->revoke(
                $userId,
                $endpoint
            );

        $remainingSubscriptions =
            $this->subscriptions
            ->getActiveByUserId(
                $userId
            );

        $browserPushEnabled =
            $remainingSubscriptions !== [];

        $preferences =
            $this->notifications
            ->updateBrowserPushPreference(
                $userId,
                $browserPushEnabled
            );

        return [
            'revoked' =>
            $revoked,

            'active_subscription_count' =>
            count(
                $remainingSubscriptions
            ),

            'browser_push_enabled' =>
            !empty($preferences['browser_push_enabled'])
        ];
    }

    /* ==========================================
       VALIDATION
    ========================================== */

    private function validateUserId(
        int $userId
    ): void {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid authenticated push user.'
            );
        }
    }
}
