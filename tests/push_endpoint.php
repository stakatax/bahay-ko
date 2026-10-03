<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/push-endpoint.php';
$checks = 0;
foreach (['https://fcm.googleapis.com/wp/fixture','https://updates.push.services.mozilla.com/wpush/v2/fixture','https://web.push.apple.com/fixture','https://wns.notify.windows.com/w/?token=fixture','https://fcm.googleapis.com:443/wp/fixture'] as $endpoint) {
    if (validateBrowserPushEndpoint($endpoint) !== $endpoint) throw new RuntimeException('Supported endpoint changed.');
    $checks++;
}
foreach (['https://127.0.0.1/push','https://[::1]/push','https://169.254.169.254/','https://192.168.1.1/push','https://localhost/push','https://example.com/push','https://fcm.googleapis.com.evil.example/push','https://evilpush.apple.com/push','https://push.apple.com.evil.example/push','https://user:pass@fcm.googleapis.com/push','https://fcm.googleapis.com:8443/push','http://fcm.googleapis.com/push','javascript:alert(1)','https://fcm.googleapis.com/push#fragment',"https://fcm.googleapis.com/push\r\nHeader: bad",['crafted'],'',null] as $endpoint) {
    try { validateBrowserPushEndpoint($endpoint); throw new RuntimeException('Unsafe endpoint accepted.'); }
    catch (InvalidArgumentException $exception) { $checks++; }
}
$publicPoint = hex2bin('046b17d1f2e12c4247f8bce6e563a440f277037d812deb33a0f4a13945d898c2964fe342e2fe1a7f9b8ee7eb4a7c0f9e162bce33576b315ececbb6406837bf51f5');
$encode = static fn(string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
$validKeys = ['p256dh'=>$encode($publicPoint), 'auth'=>$encode(random_bytes(16))];
validateBrowserPushKeys($validKeys); $checks++;
foreach ([[], ['p256dh'=>[], 'auth'=>'fixture'], ['p256dh'=>'fixture','auth'=>'fixture'], array_replace($validKeys, ['auth'=>$encode('short')]), array_replace($validKeys, ['p256dh'=>$encode("\x04" . str_repeat("\0", 64))])] as $keys) {
    try { validateBrowserPushKeys($keys); throw new RuntimeException('Invalid encryption keys accepted.'); }
    catch (InvalidArgumentException $exception) { $checks++; }
}
echo "PASS: $checks push endpoint checks; no network calls or writes.\n";
