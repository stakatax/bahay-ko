<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$private = __DIR__ . '/../deployment/tunnel/evaluation/private';
$public = dirname($private) . '/public';
$stage = 'local delivery checks';
try {
    require $private . '/bootstrap.php';
    require_once $public . '/app/models/EmailDelivery.php';
    require_once $public . '/app/models/PushDelivery.php';
    require_once $public . '/vendor/autoload.php';
    $smtp = require $private . '/email.php';
    $push = require $private . '/push.php';
    $vapid = Minishlink\WebPush\VAPID::validate(['subject'=>$push['subject'],
        'publicKey'=>$push['public_key'], 'privateKey'=>$push['private_key']]);
    Minishlink\WebPush\VAPID::getVapidHeaders('https://updates.push.services.mozilla.com',
        $vapid['subject'], $vapid['publicKey'], $vapid['privateKey'],
        Minishlink\WebPush\ContentEncoding::aes128gcm);
    $emails = (new EmailDelivery())->getPending(100);
    $pushes = (new PushDelivery())->getPending(100);
    $cutoff = json_decode(file_get_contents($private . '/delivery-initialization.json'), true)['email_cutoff'];
    foreach ($emails as $delivery) {
        if ($delivery['created_at'] < $cutoff) throw new RuntimeException('Historical email remains deliverable.');
    }
    if ($pushes !== []) throw new RuntimeException('Push delivery requires a fresh evaluation subscription.');
    echo 'PASS: evaluation VAPID validation/signing; historical email and push excluded.', PHP_EOL;
    $authenticated = false;
    if (($argv[1] ?? '') === '--smtp-auth-check') {
        $stage = 'Gmail TLS authentication';
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $smtp['host'];
        $mailer->Port = (int)$smtp['port'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtp['username'];
        $mailer->Password = $smtp['password'];
        $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Timeout = 20;
        if (!$mailer->smtpConnect()) throw new RuntimeException('SMTP authentication not confirmed.');
        $mailer->smtpClose();
        $authenticated = true;
        echo "PASS: Gmail TLS connection/authentication; no email sent by this check.\n";
    }
    file_put_contents($private . '/delivery-checks.json', json_encode([
        'checked_at'=>date(DATE_ATOM), 'evaluation_keys_valid'=>true,
        'vapid_signing_valid'=>true, 'historical_delivery_excluded'=>true,
        'smtp_authenticated'=>$authenticated, 'message_sent'=>false,
        'browser_push_receipt_tested'=>false], JSON_PRETTY_PRINT) . "\n");
} catch (Throwable $exception) {
    error_log('Evaluation delivery check failed: ' . $stage . ' (' . get_class($exception) . ').');
    fwrite(STDERR, 'FAIL: ' . $stage . "; no credentials disclosed.\n");
    exit(1);
}
