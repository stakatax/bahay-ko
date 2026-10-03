<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/check_database_baseline.php';

final class FreshInstallBootstrap
{
    public const TARGET = 'olshco_c5_verify_20260923';
    private const COLUMNS = [
        'role' => ['role_id', 'role_prefix'],
        'actions' => ['action_id', 'action_name'],
        'department' => ['department_id', 'department_name', 'department_code', 'description', 'status'],
        'education_level' => ['education_level_id', 'department_id', 'education_level_name', 'status'],
        'academic_program' => ['academic_program_id', 'education_level_id', 'program_name', 'program_code', 'program_type', 'description', 'status'],
        'grade_level' => ['grade_level_id', 'education_level_id', 'grade_level_name', 'status'],
        'section' => ['section_id', 'grade_level_id', 'academic_program_id', 'section_name', 'status'],
        'content_interest' => ['interest_id', 'interest_name', 'interest_slug', 'description', 'status', 'sort_order'],
        'legal_document_version' => ['legal_document_version_id', 'document_type', 'version', 'title', 'content', 'effective_at', 'status'],
        'student_profile_survey_version' => ['survey_version', 'version_name', 'description', 'status'],
        'student_profile_consent_definition' => ['student_profile_consent_definition_id', 'consent_key', 'consent_version', 'title', 'consent_statement', 'status', 'effective_at'],
        'student_profile_question' => ['student_profile_question_id', 'question_key', 'section_key', 'section_label', 'section_sort_order', 'question_text', 'help_text', 'response_type', 'options_json', 'is_required', 'is_sensitive', 'consent_key', 'analytics_enabled', 'survey_version', 'status', 'sort_order'],
    ];
    private mysqli $connection;
    private array $tables;

    public function __construct(mysqli $connection, array $bundle)
    {
        $this->connection = $connection;
        $this->assertTarget();
        if (($bundle['format'] ?? null) !== 1 || array_keys($bundle['tables'] ?? []) !== array_keys(self::COLUMNS)) {
            throw new RuntimeException('Unexpected reference bundle format or tables.');
        }
        foreach (self::COLUMNS as $table => $columns) {
            $entry = $bundle['tables'][$table];
            if ($entry['columns'] !== $columns || !is_array($entry['rows']) || $entry['rows'] === []) {
                throw new RuntimeException('Invalid reference table: ' . $table);
            }
            foreach ($entry['rows'] as $row) {
                if (array_keys($row) !== $columns) throw new RuntimeException('Unexpected reference columns.');
                foreach ($row as $value) {
                    if ($value !== null && !is_scalar($value)) throw new RuntimeException('Invalid reference value.');
                }
            }
        }
        $this->tables = $bundle['tables'];
        $roles = array_column($this->tables['role']['rows'], 'role_prefix');
        sort($roles);
        if ($roles !== ['Admin','Faculty','Parent','Student']) throw new RuntimeException('Expected four application roles.');
    }

    public function assertTarget(): void
    {
        $name = $this->connection->query('SELECT DATABASE() AS name')->fetch_assoc()['name'];
        if ($name !== self::TARGET) throw new RuntimeException('Bootstrap is restricted to the approved scratch database.');
    }

    public function inspect(): array
    {
        $this->assertTarget();
        $expected = readBaselineTables(__DIR__ . '/../olshcodb-structure.sql');
        $rows = $this->connection->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetch_all(MYSQLI_ASSOC);
        $actual = [];
        foreach ($rows as $row) {
            $table = $row['TABLE_NAME'];
            $quoted = '`' . str_replace('`', '``', $table) . '`';
            if ($row['ENGINE'] !== 'InnoDB') throw new RuntimeException('Transactional InnoDB tables are required.');
            $actual[$table] = normalizeBaselineDdl($this->connection->query('SHOW CREATE TABLE '.$quoted)->fetch_assoc()['Create Table']);
            if (!isset(self::COLUMNS[$table]) && $table !== 'user' && (int)$this->connection->query('SELECT COUNT(*) AS n FROM '.$quoted)->fetch_assoc()['n'] !== 0) {
                throw new RuntimeException('Target contains operational records; bootstrap refused.');
            }
        }
        if (compareBaselineTables($expected,$actual) !== []) throw new RuntimeException('Target schema does not match the baseline.');
        $empty = 0;
        foreach ($this->tables as $table => $entry) {
            $current = $this->referenceRows($table);
            if ($current === []) { $empty++; continue; }
            if ($this->canonical($current) !== $this->canonical($entry['rows'])) throw new RuntimeException('Existing reference data differs: '.$table);
        }
        if ($empty !== 0 && $empty !== count($this->tables)) throw new RuntimeException('Partial seed state; refusing to fill or overwrite it.');
        $users = (int)$this->connection->query('SELECT COUNT(*) AS n FROM user')->fetch_assoc()['n'];
        if ($users > 1 || ($empty > 0 && $users > 0)) throw new RuntimeException('Target contains existing accounts.');
        return ['reference_state'=>$empty === 0 ? 'matching' : 'empty','users'=>$users,'tables'=>count($expected)];
    }

    private function canonical(array $rows): string
    {
        foreach ($rows as &$row) foreach ($row as &$value) if ($value !== null) $value = (string)$value;
        unset($row,$value);
        usort($rows,static fn($a,$b)=>strcmp(json_encode($a),json_encode($b)));
        return json_encode($rows,JSON_THROW_ON_ERROR);
    }

    private function referenceRows(string $table): array
    {
        return $this->connection->query('SELECT `'.implode('`,`',self::COLUMNS[$table]).'` FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC);
    }

    public static function validateAdministrator(array $admin): array
    {
        foreach (['first_name','last_name','email','password','birthdate','gender'] as $field) {
            if (!isset($admin[$field]) || !is_string($admin[$field]) || trim($admin[$field]) === '') throw new InvalidArgumentException('Explicit initial Administrator details are required.');
        }
        if (strlen($admin['first_name']) > 100 || strlen($admin['last_name']) > 100 || strlen($admin['email']) > 100 || !filter_var($admin['email'],FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Invalid Administrator identity fields.');
        if (strlen($admin['password']) < 12 || strlen($admin['password']) > 72 || !preg_match('/[A-Z]/',$admin['password']) || !preg_match('/[a-z]/',$admin['password']) || !preg_match('/[0-9]/',$admin['password']) || !preg_match('/[^a-zA-Z0-9]/',$admin['password'])) throw new InvalidArgumentException('Use a 12-72 byte password with upper/lowercase, a number, and a symbol.');
        $birthdate = DateTimeImmutable::createFromFormat('!Y-m-d',$admin['birthdate']);
        if (!$birthdate || $birthdate->format('Y-m-d') !== $admin['birthdate'] || $birthdate > new DateTimeImmutable('today')) throw new InvalidArgumentException('Invalid Administrator birthdate.');
        $age = $birthdate->diff(new DateTimeImmutable('today'))->y;
        if ($age < 18 || $age > 100 || !in_array($admin['gender'],['Male','Female','Other'],true)) throw new InvalidArgumentException('Invalid Administrator age or gender.');
        $admin['age'] = $age;
        return $admin;
    }

    // Caller owns the transaction so a rehearsal can inspect results then roll back.
    public function stage(array $administrator): array
    {
        $this->assertTarget();
        if ((int)$this->connection->query('SELECT @@in_transaction AS active')->fetch_assoc()['active'] !== 1) {
            throw new RuntimeException('An explicit transaction is required before staging any seed rows.');
        }
        $admin = self::validateAdministrator($administrator);
        $state = $this->inspect();
        if ($state['reference_state'] === 'empty') {
            foreach ($this->tables as $table => $entry) {
                $columns = self::COLUMNS[$table];
                $stmt = $this->connection->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',$columns).'`) VALUES ('.implode(',',array_fill(0,count($columns),'?')).')');
                foreach ($entry['rows'] as $row) {
                    $values = array_values($row);
                    $stmt->bind_param(str_repeat('s',count($values)),...$values);
                    $stmt->execute();
                }
                $stmt->close();
            }
        }
        $role = $this->connection->query("SELECT role_id FROM role WHERE role_prefix='Admin'")->fetch_assoc();
        if ($state['users'] === 0) {
            $hash = password_hash($admin['password'],PASSWORD_DEFAULT);
            $stmt = $this->connection->prepare("INSERT INTO user (first_name,last_name,email,password,must_change_password,gender,age,birthdate,status,role_id) VALUES (?,?,?,?,1,?,?,?,'Active',?)");
            $stmt->bind_param('sssssisi',$admin['first_name'],$admin['last_name'],$admin['email'],$hash,$admin['gender'],$admin['age'],$admin['birthdate'],$role['role_id']);
            $stmt->execute();$stmt->close();
        } else {
            $existing = $this->connection->query('SELECT first_name,last_name,email,password,status,role_id,gender,birthdate FROM user')->fetch_assoc();
            foreach (['first_name','last_name','email','gender','birthdate'] as $field) if ($existing[$field] !== $admin[$field]) throw new RuntimeException('Existing Administrator differs; no account will be changed.');
            if ($existing['status'] !== 'Active' || (int)$existing['role_id'] !== (int)$role['role_id'] || !password_verify($admin['password'],$existing['password'])) throw new RuntimeException('Existing Administrator differs; no credentials will be reset.');
        }
        return ['reference_rows'=>array_sum(array_map(static fn($t)=>count($t['rows']),$this->tables)),'administrators'=>1,'repeat'=>$state['reference_state']==='matching'];
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;

$connection = null;
try {
    $arguments = array_slice($argv,1);
    if ($arguments === [] || $arguments === ['--help']) {
        echo "Usage: php scripts/bootstrap_fresh_install.php --check | --apply --confirm-reference-sha256=HASH\n";
        echo "Restricted to the approved scratch database. Default/help never writes.\n";exit(0);
    }
    $apply = in_array('--apply',$arguments,true);
    if (!$apply && $arguments !== ['--check']) throw new RuntimeException('Unsupported arguments.');
    if ($apply && (count($arguments)!==2 || !str_starts_with($arguments[1],'--confirm-reference-sha256='))) throw new RuntimeException('Explicit reference confirmation is required.');
    $path = __DIR__.'/../database/bootstrap-reference.json';
    $hash = hash_file('sha256',$path);
    if ($apply && !hash_equals($hash,substr($arguments[1],strlen('--confirm-reference-sha256=')))) throw new RuntimeException('Reference checksum confirmation does not match.');
    $config = databaseConfiguration();
    if (!in_array($config['host'],['localhost','127.0.0.1','::1'],true)) throw new RuntimeException('This rehearsal is local only.');
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    $connection = new mysqli($config['host'],$config['username'],$config['password'],FreshInstallBootstrap::TARGET,$config['port']);
    $connection->set_charset('utf8mb4');
    $bootstrap = new FreshInstallBootstrap($connection,json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR));
    if (!$apply) {
        echo json_encode($bootstrap->inspect(),JSON_PRETTY_PRINT),PHP_EOL,'Reference SHA-256: ',$hash,PHP_EOL;exit(0);
    }
    $administrator = [];
    foreach (['first_name','last_name','email','password','birthdate','gender'] as $field) $administrator[$field] = getenv('OLSHCO_BOOTSTRAP_ADMIN_'.strtoupper($field));
    FreshInstallBootstrap::validateAdministrator($administrator);
    if ((int)$connection->query("SELECT GET_LOCK('olshco_c5_bootstrap',0) AS acquired")->fetch_assoc()['acquired'] !== 1) throw new RuntimeException('Another bootstrap is running.');
    try {
        $connection->begin_transaction();
        $result = $bootstrap->stage($administrator);
        $connection->commit();
        echo 'PASS: ',json_encode($result),PHP_EOL;
    } catch (Throwable $exception) { $connection->rollback(); throw $exception; }
    finally { $connection->query("SELECT RELEASE_LOCK('olshco_c5_bootstrap')"); }
} catch (Throwable $exception) {
    // SQL errors may contain submitted values; never print credentials or SQL details.
    fwrite(STDERR,($exception instanceof mysqli_sql_exception ? 'Bootstrap database validation failed; transaction rolled back.' : $exception->getMessage()).PHP_EOL);
    exit(1);
} finally { if ($connection instanceof mysqli) $connection->close(); }
