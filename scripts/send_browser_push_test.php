<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__
    . '/../config/push.php';

$pushConfiguration =
    pushConfiguration();

require_once __DIR__
    . '/../vendor/autoload.php';

require_once __DIR__
    . '/../config/dbconnect.php';

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

$userId =
    isset($argv[1])
    ? (int) $argv[1]
    : 0;

if ($userId <= 0) {
    echo "Usage: php scripts/send_browser_push_test.php USER_ID";
    echo PHP_EOL;

    exit(1);
}

$stmt =
    $conn->prepare("
        SELECT
            subscription_id,
            endpoint,
            public_key,
            auth_token,
            content_encoding

        FROM push_subscription

        WHERE user_id = ?
          AND subscription_status = 'Active'

        ORDER BY
            last_used_at DESC,
            subscription_id DESC

        LIMIT 1
    ");

$stmt->bind_param(
    'i',
    $userId
);

$stmt->execute();

$row =
    $stmt->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$row) {
    echo "No active browser subscription found.";
    echo PHP_EOL;

    exit(1);
}

$subscription =
    Subscription::create([
        'endpoint' =>
        $row['endpoint'],

        'keys' => [
            'p256dh' =>
            $row['public_key'],

            'auth' =>
            $row['auth_token']
        ],

        'contentEncoding' =>
        $row['content_encoding']
            ?? 'aes128gcm'
    ]);

$webPush =
    new WebPush([
        'VAPID' => [
            'subject' =>
            $pushConfiguration['subject'],

            'publicKey' =>
            $pushConfiguration['public_key'],

            'privateKey' =>
            $pushConfiguration['private_key']
        ]
    ]);

$payload =
    json_encode(
        [
            'title' =>
            'OLSHCO Browser Push Test',

            'message' =>
            'Browser notifications are working successfully.',

            'url' =>
            'index.php?page=notifications',

            'tag' =>
            'olshco-browser-push-test',

            'urgency' =>
            'normal'
        ],
        JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
    );

$report =
    $webPush->sendOneNotification(
        $subscription,
        $payload
    );

if ($report->isSuccess()) {
    echo "PUSH TEST SENT SUCCESSFULLY";
    echo PHP_EOL;

    exit(0);
}

echo "PUSH TEST FAILED: ";
echo $report->getReason();
echo PHP_EOL;

exit(1);
