<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// Metadata only: this script never executes the SQL snapshot.
function normalizeBaselineDdl(string $ddl): string
{
    $ddl = rtrim(trim($ddl), ';');
    $tokens = preg_split('/(\'(?:[^\'\\\\]|\\\\.|\'\')*\'|`(?:[^`]|``)*`)/s', $ddl, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($tokens as $index => &$token) {
        if ($index % 2 === 0) {
            // Row-dependent next-ID counters are not a schema difference.
            $token = preg_replace('/\bAUTO_INCREMENT=\d+\s*/', '', $token);
            $token = preg_replace('/\s+/', ' ', $token);
        }
    }
    unset($token);
    return trim(implode('', $tokens));
}

function readBaselineTables(string $path): array
{
    $sql = file_get_contents($path);
    if ($sql === false) throw new RuntimeException('Cannot read schema baseline.');
    preg_match_all('/CREATE TABLE `([A-Za-z0-9_]+)` \(.*?\) ENGINE=[^;]+;/s', $sql, $matches, PREG_SET_ORDER);
    $tables = [];
    foreach ($matches as $match) {
        if (isset($tables[$match[1]])) throw new RuntimeException('Duplicate table in schema baseline.');
        $tables[$match[1]] = normalizeBaselineDdl($match[0]);
    }
    if ($tables === []) throw new RuntimeException('No table definitions found in schema baseline.');
    return $tables;
}

function compareBaselineTables(array $expected, array $actual): array
{
    $issues = [];
    foreach ($expected as $table => $definition) {
        if (!isset($actual[$table])) $issues[] = 'Missing table: ' . $table;
        elseif ($definition !== $actual[$table]) $issues[] = 'Definition differs: ' . $table;
    }
    foreach (array_diff_key($actual, $expected) as $table => $_) $issues[] = 'Unexpected table: ' . $table;
    return $issues;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;

try {
    require_once __DIR__ . '/../config/database.php';
    $snapshot = __DIR__ . '/../olshcodb-structure.sql';
    $expected = readBaselineTables($snapshot);
    // Preserve the original snapshot and verify the explicitly approved additive migration.
    $expected += readBaselineTables(__DIR__ . '/../database/baselines/027_publication_notification_outbox.sql');
    $expected += readBaselineTables(__DIR__ . '/../database/baselines/028_request_rate_limit.sql');
    $expected += readBaselineTables(__DIR__ . '/../database/baselines/029_parent_child_record.sql');
    // Migration 030 permits Faculty-owned details to be pending at provisioning.
    $expected['user'] = str_replace(
        ["`gender` enum('Male','Female','Other') NOT NULL", "`age` int(11) NOT NULL"],
        ["`gender` enum('Male','Female','Other') DEFAULT NULL", "`age` int(11) DEFAULT NULL"],
        $expected['user']
    );
    $connection = openDatabaseConnection();
    try {
        $rows = $connection->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME")->fetch_all(MYSQLI_ASSOC);
        $actual = [];
        foreach ($rows as $row) {
            $name = $row['TABLE_NAME'];
            $quoted = '`' . str_replace('`', '``', $name) . '`';
            $definition = $connection->query('SHOW CREATE TABLE ' . $quoted)->fetch_assoc()['Create Table'];
            $actual[$name] = normalizeBaselineDdl($definition);
        }
        $issues = compareBaselineTables($expected, $actual);
        foreach ($issues as $issue) echo $issue . PHP_EOL;
        echo ($issues === [] ? 'PASS' : 'FAIL') . ': ' . count($expected) . ' baseline tables, ' . count($actual) . ' current tables; ' . count($issues) . ' differences.' . PHP_EOL;
        echo 'Snapshot SHA-256: ' . hash_file('sha256', $snapshot) . PHP_EOL;
        echo 'Read-only schema comparison; does not verify seed data, backups, or fresh installation.' . PHP_EOL;
        exit($issues === [] ? 0 : 1);
    } finally { $connection->close(); }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Baseline verification could not finish (' . get_class($exception) . '). Check configuration and snapshot availability.' . PHP_EOL);
    exit(2);
}
