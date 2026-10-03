<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
function parentChildRecordSql(): string
{
    return <<<'SQL'
CREATE TABLE parent_child_record (
    parent_user_id INT NOT NULL,
    child_name VARCHAR(200) NOT NULL,
    child_student_id VARCHAR(50) DEFAULT NULL,
    section_id INT NOT NULL,
    relationship VARCHAR(50) NOT NULL,
    reason VARCHAR(32) NOT NULL,
    reason_details VARCHAR(500) NOT NULL DEFAULT '',
    status ENUM('Pending','Verified','Rejected','Revoked','Linked') NOT NULL DEFAULT 'Pending',
    verified_by INT DEFAULT NULL,
    verified_at DATETIME DEFAULT NULL,
    review_notes VARCHAR(1000) DEFAULT NULL,
    review_history LONGTEXT DEFAULT NULL,
    linked_student_user_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (parent_user_id),
    KEY idx_child_record_student_id (child_student_id),
    CONSTRAINT fk_child_record_parent FOREIGN KEY (parent_user_id) REFERENCES user(user_id),
    CONSTRAINT fk_child_record_section FOREIGN KEY (section_id) REFERENCES section(section_id),
    CONSTRAINT fk_child_record_verifier FOREIGN KEY (verified_by) REFERENCES user(user_id),
    CONSTRAINT fk_child_record_student FOREIGN KEY (linked_student_user_id) REFERENCES user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL;
}
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) { return; }
$mode=$argv[1] ?? '--check';
if (count($argv)>2 || !in_array($mode,['--check','--apply'],true)) { fwrite(STDERR,"Use --check or --apply.\n"); exit(2); }
require_once __DIR__ . '/../../config/database.php';
try {
    $db=openDatabaseConnection();
    $exists=(int)$db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='parent_child_record'")->fetch_assoc()['n']>0;
    if ($exists) { echo "Table already exists; no changes made.\n"; }
    elseif ($mode==='--check') { echo "Pending: add parent_child_record; existing tables and records unchanged.\n"; }
    else { $db->query(parentChildRecordSql()); echo "Created parent_child_record; existing tables and records unchanged.\n"; }
    $db->close();
} catch (Throwable $e) { fwrite(STDERR,'Migration failed: '.get_class($e).' ('.(int)$e->getCode().").\n"); exit(1); }
