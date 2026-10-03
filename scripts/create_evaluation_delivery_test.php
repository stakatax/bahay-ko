<?php
// One deduplicated system notification to the user's explicitly designated Admin account.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') !== '--notify-approved-admin') {
    fwrite(STDERR, "Explicit Admin notification flag required.\n"); exit(1);
}
$email = $argv[2] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Provide the designated Admin email as the second argument.\n");
    exit(1);
}
$private = __DIR__ . '/../deployment/tunnel/evaluation/private';
try {
    require $private . '/bootstrap.php';
    require_once dirname($private) . '/public/app/models/Notification.php';
    $notification = new Notification();
    $connection = $notification->getDatabaseConnection();
    if ($connection->query('SELECT DATABASE()')->fetch_row()[0] !== 'olshco_evaluation') throw new RuntimeException('Wrong database.');
    $statement = $connection->prepare("SELECT u.user_id FROM user u INNER JOIN role r ON r.role_id=u.role_id WHERE u.email=? AND r.role_prefix='Admin' AND u.status='Active'");
    $statement->bind_param('s', $email);
    $statement->execute();
    $account = $statement->get_result()->fetch_assoc();
    if (!$account) throw new RuntimeException('Expected evaluation Admin not found.');
    $created = $notification->createForUser((int)$account['user_id'], 'system',
        'Evaluation notification delivery test',
        'This is a delivery test from the separate OLSHCO evaluation site. Email notifications are now configured for this copy. Browser notifications require enabling them on the evaluation HTTPS address.',
        null, null, 'evaluation:delivery-smoke:admin:v1');
    echo $created ? "PASS: one Admin evaluation notification created for normal queue delivery.\n"
        : "Admin delivery test already exists; no duplicate created.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Admin evaluation notification could not be created; no credentials disclosed.\n");
    exit(1);
}
