<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../database/migrations/027_publication_notification_outbox.php';
$conn = openDatabaseConnection();
$checks = 0;
function outboxSchemaCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException('FAIL: '.$label);
    $checks++;
}
try {
    $sql = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', publicationNotificationOutboxSql(), 1);
    if (!$conn->query($sql)) throw new RuntimeException('Temporary fixture creation failed.');
    foreach (['announcement','event','document','survey'] as $type) {
        $stmt=$conn->prepare('INSERT INTO publication_notification_outbox (content_type,content_id) VALUES (?,1)');
        $stmt->bind_param('s',$type);$stmt->execute();$stmt->close();
    }
    $rows=$conn->query('SELECT * FROM publication_notification_outbox')->fetch_all(MYSQLI_ASSOC);
    outboxSchemaCheck(count($rows)===4,'All four content types');
    foreach($rows as $row) {
        outboxSchemaCheck($row['delivery_status']==='Pending' && (int)$row['attempt_count']===0,'Durable pending default');
        outboxSchemaCheck($row['lock_token']===null && $row['locked_until']===null,'Initially unclaimed');
    }
    $conn->query("INSERT INTO publication_notification_outbox (content_type,content_id) VALUES ('survey',1)
        ON DUPLICATE KEY UPDATE content_id=VALUES(content_id)");
    outboxSchemaCheck((int)$conn->query('SELECT COUNT(*) AS n FROM publication_notification_outbox')->fetch_assoc()['n']===4,'Repeated enqueue deduplicated');
    $conn->begin_transaction();
    $conn->query("INSERT INTO publication_notification_outbox (content_type,content_id) VALUES ('survey',2)");
    $conn->rollback();
    outboxSchemaCheck((int)$conn->query('SELECT COUNT(*) AS n FROM publication_notification_outbox WHERE content_id=2')->fetch_assoc()['n']===0,'Queue insert rolls back');
    $conn->query("UPDATE publication_notification_outbox SET delivery_status='Processing',attempt_count=1,
        lock_token=REPEAT('a',64),locked_until=DATE_ADD(NOW(),INTERVAL 5 MINUTE) WHERE content_type='survey'");
    outboxSchemaCheck((int)$conn->query("SELECT COUNT(*) AS n FROM publication_notification_outbox WHERE delivery_status='Processing' AND locked_until>NOW()")->fetch_assoc()['n']===1,'Lease fields persist');
    $conn->query("UPDATE publication_notification_outbox SET delivery_status='Pending',available_at=DATE_ADD(NOW(),INTERVAL 1 MINUTE),lock_token=NULL,locked_until=NULL,last_error='Synthetic failure' WHERE content_type='survey'");
    outboxSchemaCheck((int)$conn->query("SELECT COUNT(*) AS n FROM publication_notification_outbox WHERE delivery_status='Pending' AND available_at>NOW()")->fetch_assoc()['n']===1,'Retry scheduling fields persist');
    $conn->query("UPDATE publication_notification_outbox SET delivery_status='Completed',completed_at=NOW() WHERE content_type='survey'");
    outboxSchemaCheck((int)$conn->query("SELECT COUNT(*) AS n FROM publication_notification_outbox WHERE delivery_status='Completed' AND completed_at IS NOT NULL")->fetch_assoc()['n']===1,'Completion persists');
    echo "PASS: $checks outbox schema checks; temporary table only. Runtime delivery is checked separately.\n";
} finally { $conn->close(); }
