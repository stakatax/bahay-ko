<?php
// One-time evaluation configuration; preserve source settings and historical records.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') !== '--enable-approved-evaluation') {
    fwrite(STDERR, "Explicit evaluation delivery flag required.\n"); exit(1);
}
$private = __DIR__ . '/../deployment/tunnel/evaluation/private';
$stage = 'preflight';
try {
    if (is_file($private . '/delivery-initialization.json')) throw new RuntimeException('Already initialized.');
    require $private . '/bootstrap.php';
    $stage = 'SMTP configuration';
    require_once __DIR__ . '/../vendor/autoload.php';
    $smtp = require __DIR__ . '/../config/email.local.php';
    foreach (['host', 'username', 'password', 'from_email'] as $field) {
        if (!is_string($smtp[$field] ?? null) || trim($smtp[$field]) === '') throw new RuntimeException('Incomplete SMTP settings.');
    }
    if (empty($smtp['enabled']) || $smtp['host'] !== 'smtp.gmail.com'
        || ($smtp['encryption'] ?? '') !== 'tls' || (int)$smtp['port'] !== 587) {
        throw new RuntimeException('Reviewed Gmail TLS settings required.');
    }
    $stage = 'database isolation';
    $settings = require $private . '/environment.php';
    $connection = new mysqli($settings['OLSHCO_DB_HOST'], $settings['OLSHCO_DB_USER'],
        $settings['OLSHCO_DB_PASSWORD'], $settings['OLSHCO_DB_NAME'], (int)$settings['OLSHCO_DB_PORT']);
    $connection->set_charset('utf8mb4');
    if ($connection->query('SELECT DATABASE()')->fetch_row()[0] !== 'olshco_evaluation') throw new RuntimeException('Wrong database.');
    // Refuse unseen table/enum layouts instead of guessing columns.
    $expected = ['email_delivery'=>['delivery_status','last_error'],
        'push_delivery'=>['delivery_status','last_error'], 'push_subscription'=>['subscription_status'],
        'notification_preference'=>['email_enabled','email_enabled_at']];
    foreach ($expected as $table=>$columns) {
        $actual = array_column($connection->query('SHOW COLUMNS FROM `' . $table . '`')->fetch_all(MYSQLI_ASSOC), 'Field');
        if (array_diff($columns, $actual)) throw new RuntimeException('Unexpected delivery schema.');
    }
    $stage = 'generate evaluation push keys';
    putenv('OPENSSL_CONF=C:/xampp/php/extras/openssl/openssl.cnf');
    $keys = Minishlink\WebPush\VAPID::createVapidKeys();
    $push = ['subject'=>'mailto:' . $smtp['from_email'],
        'public_key'=>$keys['publicKey'], 'private_key'=>$keys['privateKey']];
    // Save rollback data privately. Endpoints and delivery records are not deleted.
    $snapshot = [];
    foreach (array_keys($expected) as $table) {
        $snapshot[$table] = $connection->query('SELECT * FROM `' . $table . '`')->fetch_all(MYSQLI_ASSOC);
    }
    file_put_contents($private . '/delivery-before.json', json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    $stage = 'evaluation delivery state';
    $connection->begin_transaction();
    try {
        $summary = [];
        $connection->query("UPDATE email_delivery SET delivery_status='Skipped', last_error='Copied evaluation backlog excluded' WHERE delivery_status IN ('Pending','Failed')");
        $summary['old_email_deliveries_skipped'] = $connection->affected_rows;
        $connection->query("UPDATE push_delivery SET delivery_status='Skipped', last_error='Copied evaluation backlog excluded' WHERE delivery_status IN ('Pending','Failed')");
        $summary['old_push_deliveries_skipped'] = $connection->affected_rows;
        $connection->query("UPDATE push_subscription SET subscription_status='Revoked' WHERE subscription_status='Active'");
        $summary['copied_subscriptions_revoked'] = $connection->affected_rows;
        $connection->query('UPDATE notification_preference SET email_enabled_at=NOW() WHERE email_enabled=1');
        $summary['fresh_email_cutoffs'] = $connection->affected_rows;
        $summary['email_cutoff'] = $connection->query('SELECT NOW()')->fetch_row()[0];
        $connection->commit();
    } catch (Throwable $exception) {
        $connection->rollback(); throw $exception;
    }
    $stage = 'private configuration';
    $smtp['from_name'] = 'OLSHCO Digital Hub (Evaluation)';
    foreach (['email'=>$smtp, 'push'=>$push] as $name=>$values) {
        file_put_contents($private . '/' . $name . '.php', "<?php\n// Private evaluation configuration.\nreturn " . var_export($values, true) . ";\n");
    }
    if (!is_dir($private . '/worker-temp')) mkdir($private . '/worker-temp');
    $summary['initialized_at'] = date(DATE_ATOM);
    $summary['evaluation_push_key_sha256'] = hash('sha256', $push['public_key']);
    $summary['source_configuration_unchanged'] = true;
    file_put_contents($private . '/delivery-initialization.json', json_encode($summary, JSON_PRETTY_PRINT) . "\n");
    echo 'PASS: ' . $summary['copied_subscriptions_revoked'] . " copied subscription revoked; historical records retained.\n";
    echo "PASS: Gmail SMTP copied privately; separate evaluation push keys generated.\n";
    echo "Fresh email cutoff set; no messages sent and no worker started by this script.\n";
} catch (Throwable $exception) {
    file_put_contents($private . '/logs/delivery-initialization-error.log',
        $stage . ': ' . get_class($exception) . ': ' . $exception->getMessage() . "\n", FILE_APPEND);
    fwrite(STDERR, 'Evaluation delivery initialization stopped at: ' . $stage . ". No source configuration changed.\n");
    exit(1);
}
