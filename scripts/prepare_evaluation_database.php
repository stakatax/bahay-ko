<?php
// Explicitly authorized one-time local evaluation setup. No source writes.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';

function evaluationFingerprint(mysqli $connection): array
{
    $tables = $connection->query('SHOW FULL TABLES')->fetch_all(MYSQLI_NUM);
    $result = [];
    foreach ($tables as [$name, $type]) {
        if ($type !== 'BASE TABLE') throw new RuntimeException('Unexpected database object.');
        $quoted = '`' . str_replace('`', '``', $name) . '`';
        $ddl = $connection->query('SHOW CREATE TABLE ' . $quoted)->fetch_row()[1];
        $hashes = [];
        $rows = $connection->query('SELECT * FROM ' . $quoted, MYSQLI_USE_RESULT);
        while ($row = $rows->fetch_row()) $hashes[] = hash('sha256', serialize($row));
        $rows->free();
        sort($hashes, SORT_STRING);
        $result[$name] = ['schema' => hash('sha256', $ddl), 'rows' => count($hashes),
            'data' => hash('sha256', implode('', $hashes))];
    }
    ksort($result);
    return $result;
}

function evaluationClient(array $command, string $input, string $output, string $errors): void
{
    $process = proc_open($command, [0 => ['file', $input, 'r'],
        1 => ['file', $output, 'w'], 2 => ['file', $errors, 'w']], $pipes);
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Database client failed; private diagnostics retained.');
    }
}

function evaluationIni(string $value): string
{
    return '"' . str_replace(["\\", '"', "\n", "\r"], ["\\\\", '\\"', '\\n', '\\r'], $value) . '"';
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;

$stage = 'preflight';
$defaults = null;
try {
    if (($argv[1] ?? '') !== '--create-approved-evaluation') {
        throw new RuntimeException('Explicit setup flag required.');
    }
    $private = realpath(__DIR__ . '/../deployment/tunnel/evaluation/private');
    if ($private === false || !is_file(dirname($private, 2) . '/.htaccess')) {
        throw new RuntimeException('Protected evaluation directory required.');
    }
    $configuration = databaseConfiguration();
    if ($configuration['database'] !== 'olshcodb') throw new RuntimeException('Unexpected source database.');
    $source = openDatabaseConnection();
    if ((int)$source->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='olshco_evaluation'")->fetch_row()[0] !== 0
        || (int)$source->query("SELECT COUNT(*) FROM mysql.user WHERE User='olshco_eval'")->fetch_row()[0] !== 0) {
        throw new RuntimeException('Evaluation database or account already exists; refusing overwrite.');
    }
    $objects = $source->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND (TABLE_TYPE<>'BASE TABLE' OR ENGINE<>'InnoDB')")->fetch_row()[0];
    if ((int)$objects !== 0) throw new RuntimeException('Transactional base tables required.');
    foreach (['TRIGGERS'=>'TRIGGER_SCHEMA', 'ROUTINES'=>'ROUTINE_SCHEMA', 'EVENTS'=>'EVENT_SCHEMA'] as $table=>$field) {
        if ((int)$source->query("SELECT COUNT(*) FROM information_schema.$table WHERE $field=DATABASE()")->fetch_row()[0] !== 0) {
            throw new RuntimeException('Stored objects require separate review.');
        }
    }
    $before = evaluationFingerprint($source);
    $databaseDdl = $source->query('SHOW CREATE DATABASE `olshcodb`')->fetch_row()[1];
    $targetDdl = str_replace('`olshcodb`', '`olshco_evaluation`', $databaseDdl);
    $backupDirectory = $private . '/backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    if (!mkdir($backupDirectory)) throw new RuntimeException('Cannot create backup directory.');
    $defaults = $backupDirectory . '/client.cnf';
    $settings = "[client]\n";
    foreach (['host'=>'host', 'username'=>'user', 'password'=>'password', 'port'=>'port'] as $field=>$option) {
        $settings .= $option . '=' . evaluationIni((string)$configuration[$field]) . "\n";
    }
    file_put_contents($defaults, $settings);
    $dump = $backupDirectory . '/olshcodb.sql';
    $stage = 'backup';
    evaluationClient(['C:/xampp/mysql/bin/mysqldump.exe', '--defaults-extra-file=' . $defaults,
        '--single-transaction', '--skip-lock-tables', '--hex-blob', '--skip-add-drop-table',
        '--skip-triggers', '--default-character-set=utf8mb4', 'olshcodb'], 'NUL', $dump,
        $backupDirectory . '/dump-errors.log');
    if (filesize($dump) === 0 || evaluationFingerprint($source) !== $before) {
        throw new RuntimeException('Source changed during backup or backup is empty; no target created.');
    }
    $stage = 'create evaluation database';
    $source->query($targetDdl);
    $stage = 'import backup';
    evaluationClient(['C:/xampp/mysql/bin/mysql.exe', '--defaults-extra-file=' . $defaults,
        '--default-character-set=utf8mb4', 'olshco_evaluation'], $dump,
        $backupDirectory . '/import-output.log', $backupDirectory . '/import-errors.log');
    $copy = new mysqli($configuration['host'], $configuration['username'],
        $configuration['password'], 'olshco_evaluation', $configuration['port']);
    $copy->set_charset('utf8mb4');
    $stage = 'verify restored data';
    $restored = evaluationFingerprint($copy);
    if ($restored !== $before || evaluationFingerprint($source) !== $before) {
        throw new RuntimeException('Restored data or source verification differs; retain artifacts for review.');
    }
    $stage = 'create restricted account';
    $password = bin2hex(random_bytes(32));
    $escapedPassword = $source->real_escape_string($password);
    $source->query("CREATE USER 'olshco_eval'@'localhost' IDENTIFIED BY '$escapedPassword'");
    $source->query("GRANT SELECT, INSERT, UPDATE, DELETE ON `olshco_evaluation`.* TO 'olshco_eval'@'localhost'");
    $restricted = new mysqli('localhost', 'olshco_eval', $password, 'olshco_evaluation', $configuration['port']);
    $restricted->set_charset('utf8mb4');
    if (evaluationFingerprint($restricted) !== $before) throw new RuntimeException('Restricted account verification failed.');
    $sourceDenied = false;
    try { $restricted->select_db('olshcodb'); } catch (mysqli_sql_exception $exception) {
        $sourceDenied = in_array($exception->getCode(), [1044, 1142], true);
    }
    if (!$sourceDenied) throw new RuntimeException('Source database isolation failed.');
    $settings = ['OLSHCO_APP_ENV'=>'production', 'OLSHCO_APP_KEY'=>bin2hex(random_bytes(32)),
        'OLSHCO_APP_URL'=>'', 'OLSHCO_DB_HOST'=>'localhost', 'OLSHCO_DB_USER'=>'olshco_eval',
        'OLSHCO_DB_PASSWORD'=>$password, 'OLSHCO_DB_NAME'=>'olshco_evaluation',
        'OLSHCO_DB_PORT'=>(string)$configuration['port']];
    file_put_contents($private . '/environment.php', "<?php\n// Evaluation only; HTTPS tunnel URL remains unset.\nreturn " . var_export($settings, true) . ";\n");
    $report = ['created_at'=>date(DATE_ATOM), 'source'=>'olshcodb', 'target'=>'olshco_evaluation',
        'table_count'=>count($before), 'row_count'=>array_sum(array_column($before, 'rows')),
        'tables'=>$before, 'backup_sha256'=>hash_file('sha256', $dump),
        'restore_matches'=>true, 'source_unchanged'=>true, 'evaluation_user_source_access_denied'=>true,
        'delivery_workers_started'=>false, 'public_tunnel_started'=>false];
    file_put_contents($private . '/database-verification.json', json_encode($report, JSON_PRETTY_PRINT) . "\n");
    echo 'PASS: ' . count($before) . ' tables and ' . $report['row_count'] . " rows restored and verified.\n";
    echo "PASS: original database unchanged; restricted user cannot access original database.\n";
    echo "Private backup retained. Credentials saved privately; tunnel URL remains unset.\n";
} catch (Throwable $exception) {
    // Never print client errors, SQL or passwords. Do not auto-drop partial artifacts.
    fwrite(STDERR, 'Evaluation preparation stopped at: ' . $stage . ". Review private artifacts before retrying.\n");
    $exitCode = 1;
} finally {
    if (is_string($defaults) && is_file($defaults)) unlink($defaults);
}
exit($exitCode ?? 0);
