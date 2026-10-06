<?php
// CLI-only evaluator fixtures. Default is a read-only plan; never resets existing users.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/FacultyScopeService.php';
$mode = $argv[1] ?? '--plan';
$database = $argv[2] ?? 'olshcodb';
if (count($argv) > 3 || !in_array($mode, ['--plan', '--create', '--verify'], true)
    || !in_array($database, ['olshcodb', 'olshco_evaluation'], true)
    || strtolower((string) (getenv('OLSHCO_APP_ENV') ?: 'development')) === 'production') {
    fwrite(STDERR, "Usage: php prepare_test_accounts.php [--plan|--verify|--create] [olshcodb|olshco_evaluation]\n"); exit(2);
}
$c = openDatabaseConnection();
$c->select_db($database);
if ($c->query('SELECT DATABASE()')->fetch_row()[0] !== $database) { throw new RuntimeException('Wrong target database.'); }
$rows = static fn(string $sql): array => $c->query($sql)->fetch_all(MYSQLI_ASSOC);
$roles = array_column($rows('SELECT role_id, role_prefix FROM role'), 'role_id', 'role_prefix');
$departments = $rows("SELECT * FROM department WHERE status='Active' ORDER BY department_id");
$levels = $rows("SELECT el.* FROM education_level el JOIN department d ON d.department_id=el.department_id
    WHERE el.status='Active' AND d.status='Active' ORDER BY el.education_level_id");
$programs = $rows("SELECT ap.* FROM academic_program ap JOIN education_level el ON el.education_level_id=ap.education_level_id
    JOIN department d ON d.department_id=el.department_id WHERE ap.status='Active' AND el.status='Active'
    AND d.status='Active' AND d.department_code='COLLEGE' AND ap.program_type='Program' ORDER BY ap.academic_program_id");
foreach (['Admin', 'Faculty', 'Student'] as $role) { if (empty($roles[$role])) { throw new RuntimeException('Required role missing.'); } }
$accounts = [];
$add = static function(string $name, string $role, array $scope = []) use (&$accounts, $roles): void {
    $accounts[] = ['login'=>$name, 'email'=>$name.'@example.invalid', 'role'=>$role, 'role_id'=>(int)$roles[$role],
        'department_id'=>$scope['department_id'] ?? null, 'education_level_id'=>$scope['education_level_id'] ?? null,
        'academic_program_id'=>$scope['academic_program_id'] ?? null, 'grade_level_id'=>$scope['grade_level_id'] ?? null,
        'section_id'=>$scope['section_id'] ?? null];
};
$add('testadmin', 'Admin');
$directory = array_column($departments, null, 'department_id');
foreach ($programs as $program) {
    $level = array_column($levels, null, 'education_level_id')[$program['education_level_id']];
    $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $program['program_code']));
    $add('testfaculty-'.$slug, 'Faculty', ['department_id'=>$level['department_id'],
        'education_level_id'=>$level['education_level_id'], 'academic_program_id'=>$program['academic_program_id']]);
}
foreach ($levels as $level) {
    if (strtoupper($directory[$level['department_id']]['department_code']) === 'IBED') {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $level['education_level_name']));
        $add('testfaculty-ibed-'.$slug, 'Faculty', $level);
    }
}
foreach ($departments as $department) {
    $id = (int)$department['department_id'];
    $candidates = $rows("SELECT el.department_id, el.education_level_id, s.academic_program_id, g.grade_level_id, s.section_id
        FROM education_level el JOIN grade_level g ON g.education_level_id=el.education_level_id
        JOIN section s ON s.grade_level_id=g.grade_level_id
        LEFT JOIN academic_program ap ON ap.academic_program_id=s.academic_program_id
        WHERE el.department_id={$id} AND el.status='Active' AND g.status='Active' AND s.status='Active'
        AND (s.academic_program_id IS NULL OR (ap.status='Active' AND ap.education_level_id=el.education_level_id))
        ORDER BY el.education_level_id, g.grade_level_id, s.section_id LIMIT 1");
    if (!$candidates) { throw new RuntimeException('No complete active Student assignment for a division.'); }
    $add('teststudents-'.strtolower($department['department_code']), 'Student', $candidates[0]);
}
// Reuse the actual policy against the selected database's catalog.
$policyUser = new class($c) extends User { public function __construct(mysqli $connection) { $this->conn=$connection; } };
$policy = new FacultyScopeService($policyUser);
foreach ($accounts as $account) {
    if ($account['role'] === 'Faculty') { $policy->validateAssignment($account); }
    echo $account['login'].' | '.$account['role'].' | division='.($account['department_id'] ?? '-')
        .' level='.($account['education_level_id'] ?? '-').' program='.($account['academic_program_id'] ?? '-').PHP_EOL;
}
echo 'Target: '.$database.'; accounts: '.count($accounts).'; mode: '.$mode.PHP_EOL;
if ($mode === '--plan') { exit; }
$verify = $mode === '--verify';
if ($verify) {
    $ddl = $c->query('SHOW CREATE TABLE user')->fetch_row()[1];
    $ddl = preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $ddl);
    $ddl = preg_replace('/^\h*CONSTRAINT[^\r\n]+\R?/m', '', $ddl);
    $ddl = preg_replace('/,\s*\) ENGINE/', "\n) ENGINE", $ddl);
    $c->query($ddl);
}
$private = __DIR__.'/../deployment/test-accounts/private';
if (!$verify && !is_dir($private) && !mkdir($private, 0700, true)) { throw new RuntimeException('Private storage unavailable.'); }
$path = $private.'/'.$database.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
$handle = null;
$c->begin_transaction();
try {
    $lookup = $c->prepare('SELECT user_id FROM user WHERE studID=? OR email=? LIMIT 1');
    $insert = $c->prepare("INSERT INTO user (studID, first_name, last_name, email, password, must_change_password,
        gender, age, birthdate, status, account_review_notes, role_id, department_id, education_level_id,
        academic_program_id, grade_level_id, section_id) VALUES (?,?,?,?,?,1,'Other',25,'2001-01-01','Active',?,?,?,?,?,?,?)");
    foreach ($accounts as &$account) {
        $lookup->bind_param('ss', $account['login'], $account['email']); $lookup->execute();
        if ($lookup->get_result()->fetch_assoc()) { throw new RuntimeException('Evaluator identifier already exists; nothing reset or overwritten.'); }
        $account['initial_password'] = 'Olshco!'.bin2hex(random_bytes(10));
        $hash = password_hash($account['initial_password'], PASSWORD_DEFAULT);
        $first = 'Test'; $last = $account['login']; $notes = 'Synthetic evaluator account; not an enrollment verification. No email mailbox.';
        $insert->bind_param('ssssssiiiiii', $account['login'], $first, $last, $account['email'], $hash, $notes,
            $account['role_id'], $account['department_id'], $account['education_level_id'], $account['academic_program_id'],
            $account['grade_level_id'], $account['section_id']);
        $insert->execute(); $account['user_id'] = $c->insert_id;
    }
    unset($account);
    if ($verify) {
        foreach ($accounts as $account) {
            $actual = $policyUser->findByIdentifier($account['login']);
            if (!$actual || !password_verify($account['initial_password'], $actual['password'])
                || (int)$actual['must_change_password'] !== 1 || $actual['status'] !== 'Active') {
                throw new RuntimeException('Created fixture does not match the account plan.');
            }
            if ($account['role'] === 'Faculty') { $policy->forUser((int)$account['user_id']); }
        }
        $c->rollback();
        echo "PASS: account insertion, password verification, identifier lookup and Faculty scope; temporary users rolled back.\n";
        exit;
    }
    $handle = fopen($path, 'x');
    if (!$handle) { throw new RuntimeException('Unable to save private credentials.'); }
    $json = json_encode(['database'=>$database, 'created_at'=>date(DATE_ATOM), 'accounts'=>$accounts], JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
    if (fwrite($handle,$json) !== strlen($json) || !fflush($handle)) { throw new RuntimeException('Incomplete credential write.'); }
    fclose($handle); $handle=null;
    $c->commit();
    echo 'Created evaluator accounts. Credentials saved privately: '.basename($path).PHP_EOL;
    echo "First login requires password change and actual legal acceptance. No emails or push were sent.\n";
} catch (Throwable $error) {
    $c->rollback(); if (is_resource($handle)) { fclose($handle); }
    if (is_file($path)) { unlink($path); }
    throw $error;
}
