<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/EmailDelivery.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = openDatabaseConnection();
$checks = 0;
function decisionEmailCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
}
try {
    // Temporary tables shadow real tables for this connection; no live records change.
    foreach (['user', 'notification', 'notification_preference', 'notification_category_preference', 'email_delivery'] as $table) {
        $ddl = $db->query('SHOW CREATE TABLE `' . $table . '`')->fetch_assoc()['Create Table'];
        $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $ddl, 1);
        $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $ddl);
        $ddl = preg_replace('/,\n\)/', "\n)", $ddl);
        $db->query($ddl);
    }
    $statuses = [1=>'Active', 2=>'Rejected', 3=>'Active', 4=>'Active', 5=>'Rejected', 6=>'Inactive', 7=>'Active', 8=>'Active'];
    foreach ($statuses as $id=>$status) {
        $db->query("INSERT INTO user(user_id,first_name,last_name,password,gender,age,status,email) VALUES($id,'Fixture','Applicant','unused','Other',18,'$status','fixture$id@example.invalid')");
    }
    $db->query("INSERT INTO notification_preference(user_id,email_enabled,email_enabled_at) VALUES(3,0,NULL),(4,1,'2026-01-01'),(7,1,'2099-01-01'),(8,1,'2026-01-01')");
    $db->query("INSERT INTO notification_category_preference(user_id,notification_category,email_enabled) VALUES(3,'account_system',0),(8,'account_system',0)");
    $cases = [
        [1,1,'registration-approved:user:1'],
        [2,2,'registration-rejected:user:2'],
        [3,3,'registration-approved:user:3'],
        [4,4,'ordinary-account-message'],
        [5,5,'ordinary-account-message'],
        [6,6,'registration-approved:user:6'],
        [7,7,'ordinary-account-message'],
        [8,8,'ordinary-account-message'],
        [9,1,'registration-approved:user:999'],
        [10,5,'registration-approved:user:5']
    ];
    foreach ($cases as [$id,$owner,$key]) {
        $db->query("INSERT INTO notification(notification_id,user_id,notification_type,title,message,deduplication_key,created_at) VALUES($id,$owner,'system','Fixture','Fixture message','$key','2026-10-01')");
    }
    $model = new EmailDelivery($db);
    decisionEmailCheck($model->queueEligible() === 4, 'Only registration decisions and opted-in ordinary notifications queued');
    decisionEmailCheck($model->queueEligible() === 0, 'Repeated queue scan does not duplicate deliveries');
    $ids = array_map('intval', array_column($model->getPending(), 'notification_id'));
    sort($ids);
    decisionEmailCheck($ids === [1,2,3,4], 'Approved/rejected applicants and disabled-preference decision recipient remain sendable');
    $db->query('UPDATE notification_preference SET email_enabled=0 WHERE user_id=4');
    $ids = array_map('intval', array_column($model->getPending(), 'notification_id'));
    sort($ids);
    decisionEmailCheck($ids === [1,2,3], 'Disabling ordinary emails still prevents their delivery');
    $db->query("UPDATE email_delivery SET delivery_status='Sent' WHERE notification_id=1");
    decisionEmailCheck(count($model->getPending()) === 2, 'Sent decisions are not sent again');
    $db->query("UPDATE user SET status='Inactive' WHERE user_id=3");
    decisionEmailCheck(count($model->getPending()) === 1, 'Outdated decision does not bypass current account state');
    echo "PASS: $checks registration email checks; temporary tables only, no emails sent.\n";
} finally {
    $db->close();
}
