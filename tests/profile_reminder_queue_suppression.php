<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/EmailDelivery.php';
require_once __DIR__ . '/../app/models/PushDelivery.php';
$db = openDatabaseConnection();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    foreach (['email_delivery','push_delivery'] as $table) {
        $ddl = $db->query('SHOW CREATE TABLE `' . $table . '`')->fetch_assoc()['Create Table'];
        $ddl = preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1);
        $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);
        $ddl = preg_replace('/,\n\)/',"\n)",$ddl);
        $db->query($ddl);
    }
    $db->query("INSERT INTO email_delivery(delivery_id,notification_id,recipient_email,delivery_status) VALUES(1,1,'fixture@example.invalid','Pending'),(2,2,'fixture@example.invalid','Sent')");
    $db->query("INSERT INTO push_delivery(delivery_id,notification_id,subscription_id,delivery_status) VALUES(1,1,1,'Pending'),(2,2,1,'Sent')");
    (new EmailDelivery($db))->suppressStaleProfileReminder(1);
    (new PushDelivery($db))->suppressStaleProfileReminder(1);
    (new EmailDelivery($db))->suppressStaleProfileReminder(2);
    (new PushDelivery($db))->suppressStaleProfileReminder(2);
    $checks = 0;
    foreach (['email_delivery','push_delivery'] as $table) {
        $row = $db->query('SELECT * FROM `' . $table . '` WHERE delivery_id=1')->fetch_assoc();
        if ($row['delivery_status'] !== 'Failed' || (int) $row['attempt_count'] < 3
            || !str_contains($row['last_error'],'no longer requires')) throw new RuntimeException('Stale queue item can still retry.');
        $checks++;
        if ($db->query('SELECT delivery_status FROM `' . $table . '` WHERE delivery_id=2')->fetch_assoc()['delivery_status'] !== 'Sent') {
            throw new RuntimeException('Previously delivered item was changed.');
        }
        $checks++;
    }
    echo "PASS: $checks stale reminder queue checks; temporary tables only, no deliveries.\n";
} finally { $db->close(); }
