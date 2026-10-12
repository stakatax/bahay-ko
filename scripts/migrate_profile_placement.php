<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/database.php';
$configuration = databaseConfiguration();
if ($configuration['database'] !== 'olshcodb' || ($argv[1] ?? '') !== '--apply-local') {
    fwrite(STDERR, "Usage: php scripts/migrate_profile_placement.php --apply-local (olshcodb only)\n");
    exit(2);
}
$directory = __DIR__ . '/../logs/private-profile-backups';
if (!is_dir($directory)) mkdir($directory, 0700, true);
file_put_contents($directory . '/.htaccess', "Require all denied\n");
$stamp = date('Ymd-His');
$defaults = $directory . '/mysql-' . $stamp . '.cnf';
$quote = static fn(string $value): string => '"' . str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $value) . '"';
file_put_contents($defaults, "[client]\nhost=" . $quote($configuration['host']) . "\nuser=" . $quote($configuration['username']) . "\npassword=" . $quote($configuration['password']) . "\nport=" . $configuration['port'] . "\n");
$backup = $directory . '/olshcodb-' . $stamp . '.sql';
try {
    $process = proc_open(['C:/xampp/mysql/bin/mysqldump.exe', '--defaults-extra-file=' . $defaults,
        '--single-transaction', '--routines', '--triggers', '--result-file=' . $backup, 'olshcodb'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Backup could not start.');
    fclose($pipes[0]); stream_get_contents($pipes[1]); fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
    if (proc_close($process) !== 0 || !is_file($backup) || filesize($backup) === 0) {
        throw new RuntimeException('Backup failed; migration was not applied.');
    }
} finally { if (is_file($defaults)) unlink($defaults); }
$db = openDatabaseConnection();
$columns = array_column($db->query('SHOW COLUMNS FROM student_profile_cycle_assignment')->fetch_all(MYSQLI_ASSOC), 'Field');
$additions = [];
if (!in_array('account_details_confirmed_at', $columns, true)) $additions[] = 'ADD COLUMN account_details_confirmed_at DATETIME NULL';
if (!in_array('account_details_draft_json', $columns, true)) $additions[] = 'ADD COLUMN account_details_draft_json LONGTEXT NULL';
if ($additions) $db->query('ALTER TABLE student_profile_cycle_assignment ' . implode(', ', $additions));
$sql = file_get_contents(__DIR__ . '/../database/migrations/student_profile_placement_update.sql');
$create = substr($sql, strpos($sql, 'CREATE TABLE student_profile_placement_change'));
$db->query(str_replace('CREATE TABLE student_profile_placement_change', 'CREATE TABLE IF NOT EXISTS student_profile_placement_change', $create));
echo "Local backup completed; placement schema is ready. No account assignments changed.\n";
