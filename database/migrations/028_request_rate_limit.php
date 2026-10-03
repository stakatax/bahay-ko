<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
function requestRateLimitSql(): string
{
    return <<<'SQL'
CREATE TABLE request_rate_limit (
    key_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    scope VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (key_hash),
    KEY idx_request_rate_limit_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
}
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) { return; }
$mode = $argv[1] ?? '--check';
if (count($argv) > 2 || !in_array($mode, ['--check','--apply'], true)) {
    fwrite(STDERR, "Usage: php 028_request_rate_limit.php [--check|--apply]\n"); exit(2);
}
require_once __DIR__ . '/../../config/database.php';
try {
    $db = openDatabaseConnection();
    $exists = (int) $db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='request_rate_limit'")->fetch_assoc()['n'] > 0;
    if ($exists) {
        echo "Table exists; no changes made. Verify its definition before activation.\n";
    } elseif ($mode === '--check') {
        echo "Table absent. Pending approval: add request_rate_limit. No changes made.\n";
    } else {
        if (!$db->query(requestRateLimitSql())) { throw new RuntimeException('Rate-limit table creation failed.'); }
        echo "Created request_rate_limit. Existing tables and records unchanged.\n";
    }
    $db->close();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . get_class($exception) . ' (code ' . (int) $exception->getCode() . ").\n"); exit(1);
}
