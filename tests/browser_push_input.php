<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/controllers/BrowserPushController.php';
$controller = (new ReflectionClass(BrowserPushController::class))->newInstanceWithoutConstructor();
$validate = new ReflectionMethod(BrowserPushController::class, 'validateJsonPayload');
$checks = 0;
foreach ([['endpoint'=>[]], ['content_encoding'=>[]], ['device_label'=>[]], ['subscription'=>'invalid'], ['subscription'=>['endpoint'=>[]]], ['subscription'=>['keys'=>'invalid']], ['subscription'=>['keys'=>['p256dh'=>[]]]], ['subscription'=>['keys'=>['auth'=>[]]]]] as $payload) {
    try { $validate->invoke($controller, $payload); throw new RuntimeException('Malformed push input accepted.'); }
    catch (InvalidArgumentException $exception) { $checks++; }
}
foreach ([[], ['endpoint'=>'https://example.com/push'], ['subscription'=>['endpoint'=>'https://example.com/push','expirationTime'=>null,'keys'=>['p256dh'=>'fixture','auth'=>'fixture']],'content_encoding'=>'aes128gcm','device_label'=>'Browser']] as $payload) {
    $validate->invoke($controller, $payload); $checks++;
}
echo "PASS: $checks push JSON input checks; no subscriptions or deliveries changed.\n";
