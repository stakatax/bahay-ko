<?php
// CLI only. Default mode inspects; --apply requires explicit operator approval.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function publicationNotificationOutboxSql(): string
{
    return <<<'SQL'
CREATE TABLE publication_notification_outbox (
    outbox_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    content_type ENUM('announcement','event','document','survey') NOT NULL,
    content_id INT NOT NULL,
    delivery_status ENUM('Pending','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_until DATETIME DEFAULT NULL,
    lock_token CHAR(64) DEFAULT NULL,
    last_error VARCHAR(1000) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (outbox_id),
    UNIQUE KEY uq_publication_notification_content (content_type, content_id),
    KEY idx_publication_notification_due (delivery_status, available_at, outbox_id),
    KEY idx_publication_notification_lease (delivery_status, locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;
$mode = $argv[1] ?? '--check';
if (count($argv) > 2 || !in_array($mode, ['--check', '--apply'], true)) {
    fwrite(STDERR, "Usage: php 027_publication_notification_outbox.php [--check|--apply]\n");
    exit(2);
}
require_once __DIR__.'/../../config/database.php';
try {
    $conn = openDatabaseConnection();
    $result = $conn->query("SELECT COUNT(*) AS total FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'publication_notification_outbox'");
    $exists = (int) $result->fetch_assoc()['total'] > 0;
    if ($exists) {
        // Never silently change an existing table or assume its definition matches.
        echo "Table already exists; no changes made. Review SHOW CREATE TABLE against this migration before activation.\n";
    } elseif ($mode === '--check') {
        echo "Table absent. Pending approval: add publication_notification_outbox. No changes made.\n";
    } else {
        // MySQL DDL implicitly commits. Do not claim transactional DDL rollback.
        if (!$conn->query(publicationNotificationOutboxSql())) {
            throw new RuntimeException('Outbox table creation failed.');
        }
        echo "Created publication_notification_outbox. No existing rows or tables changed.\n";
    }
    $conn->close();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: '.get_class($exception).' (code '.(int)$exception->getCode().").\n");
    exit(1);
}
