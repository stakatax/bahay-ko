<?php

require_once __DIR__
    . '/../models/PushDelivery.php';

require_once __DIR__
    . '/../../config/push.php';
require_once __DIR__ . '/../../config/push-endpoint.php';

require_once __DIR__
    . '/../../vendor/autoload.php';

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class BrowserPushDeliveryService
{
    private PushDelivery $deliveries;

    private array $pushConfiguration;

    public function __construct()
    {
        $this->deliveries =
            new PushDelivery();

        $this->pushConfiguration =
            pushConfiguration();
    }

    /* ==========================================
       QUEUE AND SEND
    ========================================== */

    public function dispatch(
        int $queueLimit = 500,
        int $sendLimit = 100
    ): array {
        $queued =
            $this->deliveries
            ->queueEligible(
                $queueLimit
            );

        $pending =
            $this->deliveries
            ->getPending(
                $sendLimit
            );

        $result = [
            'queued' =>
            $queued,

            'processed' =>
            0,

            'sent' =>
            0,

            'failed' =>
            0,

            'expired' =>
            0
        ];

        if ($pending === []) {
            return $result;
        }

        $webPush =
            new WebPush([
                'VAPID' => [
                    'subject' =>
                    $this->pushConfiguration['subject'],

                    'publicKey' =>
                    $this->pushConfiguration['public_key'],

                    'privateKey' =>
                    $this->pushConfiguration['private_key']
                ]
            ]);

        foreach (
            $pending as $delivery
        ) {
            $result['processed']++;

            $deliveryId =
                (int) (
                    $delivery['delivery_id']
                    ?? 0
                );

            $subscriptionId =
                (int) (
                    $delivery['subscription_id']
                    ?? 0
                );

            try {
                validateBrowserPushEndpoint($delivery['endpoint'] ?? '');

                $subscription =
                    Subscription::create([
                        'endpoint' =>
                        $delivery['endpoint'],

                        'keys' => [
                            'p256dh' =>
                            $delivery['public_key'],

                            'auth' =>
                            $delivery['auth_token']
                        ],

                        'contentEncoding' =>
                        $delivery['content_encoding'] ?? 'aes128gcm'
                    ]);

                $payload =
                    $this->buildPayload(
                        $delivery
                    );

                $report =
                    $webPush
                    ->sendOneNotification(
                        $subscription,
                        $payload
                    );

                $response =
                    $report->getResponse();

                $httpStatus =
                    $response !== null
                    ? $response
                    ->getStatusCode()
                    : null;

                if ($report->isSuccess()) {
                    $this->deliveries
                        ->markSent(
                            $deliveryId,
                            $httpStatus
                        );

                    $result['sent']++;

                    continue;
                }

                $expired =
                    $report
                    ->isSubscriptionExpired();

                $this->deliveries
                    ->markFailed(
                        $deliveryId,
                        $subscriptionId,
                        $httpStatus,
                        $report->getReason(),
                        $expired
                    );

                if ($expired) {
                    $result['expired']++;
                } else {
                    $result['failed']++;
                }
            } catch (Throwable $exception) {
                $this->deliveries
                    ->markFailed(
                        $deliveryId,
                        $subscriptionId,
                        null,
                        $exception->getMessage(),
                        false
                    );

                $result['failed']++;
            }
        }

        return $result;
    }

    /* ==========================================
       NOTIFICATION PAYLOAD
    ========================================== */

    private function buildPayload(
        array $delivery
    ): string {
        $notificationId =
            (int) (
                $delivery['notification_id']
                ?? 0
            );

        $notificationType =
            strtolower(
                trim(
                    (string) (
                        $delivery['notification_type'] ?? 'system'
                    )
                )
            );

        $urgency =
            in_array(
                $notificationType,
                [
                    'workflow',
                    'reminder'
                ],
                true
            )
            ? 'high'
            : 'normal';

        $payload =
            json_encode(
                [
                    'notification_id' =>
                    $notificationId,

                    'title' =>
                    trim(
                        (string) (
                            $delivery['title']
                            ?? 'OLSHCO Digital Hub'
                        )
                    ),

                    'message' =>
                    trim(
                        (string) (
                            $delivery['message']
                            ?? 'You have a new school notification.'
                        )
                    ),

                    'content_type' =>
                    $delivery['content_type'] ?? null,

                    'content_id' =>
                    isset(
                        $delivery['content_id']
                    )
                        ? (int) $delivery['content_id']
                        : null,

                    'url' =>
                    'index.php?page=notification_open'
                        . '&notification_id='
                        . $notificationId,

                    'tag' =>
                    'olshco-notification-'
                        . $notificationId,

                    'urgency' =>
                    $urgency
                ],
                JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
            );

        if (!is_string($payload)) {
            throw new RuntimeException(
                'Unable to encode browser push payload.'
            );
        }

        return $payload;
    }
}
