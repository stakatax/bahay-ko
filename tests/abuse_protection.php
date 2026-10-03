<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/migrations/028_request_rate_limit.php';
require_once __DIR__ . '/../app/services/RequestRateLimitService.php';
require_once __DIR__ . '/../app/services/AuthService.php';
$checks = 0;
function checkM4(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
function blockedM4(callable $call): void {
    try { $call(); } catch (RequestRateLimitException $e) {
        checkM4($e->retryAfter > 0, 'blocked attempt includes retry time'); return;
    }
    throw new RuntimeException('Expected rate limit.');
}
class M4User extends User {
    public ?array $row = null;
    public int $logins = 0;
    public int $lockMinutes = 0;
    public function __construct() {}
    public function findByIdentifier(string $identifier) { return $this->row; }
    public function findById(int $userId) { return $this->row; }
    public function incrementFailedAttempts(int $userId): bool { $this->row['failed_attempts']++; return true; }
    public function resetFailedAttempts(int $userId): bool { $this->row['failed_attempts'] = 0; $this->row['lock_until'] = null; return true; }
    public function lockAccount(int $userId, int $minutes = 15): bool {
        $this->lockMinutes = $minutes;
        $this->row['lock_until'] = date('Y-m-d H:i:s', time() + $minutes * 60); return true;
    }
    public function updateLastLogin(int $userId): bool { $this->logins++; return true; }
}
$db = openDatabaseConnection();
try {
    require_once __DIR__ . '/../database/migrations/029_parent_child_record.php';
    $childDdl=str_replace('CREATE TABLE ', 'CREATE TEMPORARY TABLE ', parentChildRecordSql());
    $childDdl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $childDdl);
    $childDdl=preg_replace('/,\s*\)/', "\n)", $childDdl);
    $db->query($childDdl);

    $db->query(str_replace('CREATE TABLE ', 'CREATE TEMPORARY TABLE ', requestRateLimitSql()));
    $model = new RequestRateLimit($db);
    $hash = hash('sha256', 'fixture');
    for ($i = 0; $i < 3; $i++) { checkM4($model->consume('fixture', $hash, 3, 60) === 0, 'allowed within window'); }
    $expiry = $db->query('SELECT expires_at FROM request_rate_limit')->fetch_assoc()['expires_at'];
    for ($i = 0; $i < 4; $i++) { checkM4($model->consume('fixture', $hash, 3, 60) > 0, 'excess blocked'); }
    $row = $db->query('SELECT attempts, expires_at FROM request_rate_limit')->fetch_assoc();
    checkM4((int) $row['attempts'] === 4 && $row['expires_at'] === $expiry, 'bounded counter and fixed expiry');
    $db->query('UPDATE request_rate_limit SET expires_at = UNIX_TIMESTAMP() - 1');
    checkM4($model->consume('fixture', $hash, 3, 60) === 0, 'expired window resets');
    $db->begin_transaction();
    try { $model->consume('fixture', $hash, 3, 60); throw new LogicException('Transaction was accepted'); }
    catch (RuntimeException $e) { checkM4(str_contains($e->getMessage(), 'before application'), 'application transaction protected'); }
    finally { $db->rollback(); }
    try { $model->consume('invalid!', $hash, 3, 60); throw new LogicException('Invalid policy accepted'); }
    catch (InvalidArgumentException $e) { checkM4(true, 'invalid policy rejected'); }
    $service = new RequestRateLimitService($model, str_repeat('t', 64));
    for ($i = 0; $i < 20; $i++) { $service->login(' Test@Example.test ', '192.0.2.1'); }
    blockedM4(fn() => $service->login('test@example.test', '192.0.2.2'));
    $service->login('other@example.test', '192.0.2.1'); checkM4(true, 'identifier isolation');
    for ($i = 0; $i < 120; $i++) { $service->login('fixture' . $i, '192.0.2.3'); }
    blockedM4(fn() => $service->login('new-fixture', '192.0.2.3'));
    for ($i = 0; $i < 30; $i++) { $service->register('2001:db8::1'); }
    blockedM4(fn() => $service->register('2001:0db8:0:0:0:0:0:1'));
    $service->register('192.0.2.1'); checkM4(true, 'registration independent from login');
    foreach (['open' => 120, 'react' => 60, 'comment' => 10, 'acknowledge' => 60] as $action => $limit) {
        for ($i = 0; $i < $limit; $i++) { $service->engagement($action, 7); }
        blockedM4(fn() => $service->engagement($action, 7));
        $service->engagement($action, 8); checkM4(true, 'independent users: ' . $action);
    }
    $service->register('invalid-address'); checkM4(true, 'invalid IP safely bucketed');
    $rows = $db->query('SELECT key_hash, scope FROM request_rate_limit')->fetch_all(MYSQLI_ASSOC);
    checkM4(!str_contains(json_encode($rows), 'example.test') && !str_contains(json_encode($rows), '192.0.2.'), 'no raw identifiers stored');
    $db->query('UPDATE request_rate_limit SET expires_at = UNIX_TIMESTAMP() - 90000');
    $before = count($rows);
    $service->register('192.0.2.99');
    $after = (int) $db->query('SELECT COUNT(*) n FROM request_rate_limit')->fetch_assoc()['n'];
    checkM4($after === $before - 100 + 1, 'cleanup limited to 100 stale rows');
    $fake = new M4User();
    $ref = new ReflectionClass(AuthService::class);
    $auth = $ref->newInstanceWithoutConstructor();
    $ref->getProperty('user')->setValue($auth, $fake);
    $ref->getProperty('rateLimits')->setValue($auth, $service);
    $generic = 'Invalid login credentials. Please try again.';
    $failure = function (string $password = 'wrong') use ($auth): string {
        try { $auth->login('fixture', $password); } catch (Exception $e) { return $e->getMessage(); }
        throw new RuntimeException('Expected authentication failure');
    };
    checkM4($failure() === $generic, 'unknown identifier generic');
    $fixture = ['user_id' => 7, 'password' => password_hash('fixture-password', PASSWORD_DEFAULT), 'status' => 'Active', 'failed_attempts' => 0, 'lock_until' => null];
    foreach (['Pending', 'Inactive', 'Rejected'] as $status) {
        $fake->row = array_replace($fixture, ['status' => $status]);
        checkM4($failure() === $generic, 'wrong password hides status');
        checkM4($failure('fixture-password') !== $generic, 'correct password retains status guidance');
    }
    $fake->row = $fixture;
    for ($i = 0; $i < 5; $i++) { checkM4($failure() === $generic, 'failed-password message consistent'); }
    checkM4($fake->lockMinutes === 15 && $fake->row['failed_attempts'] === 5, 'five failures lock for 15 minutes');
    checkM4($failure('fixture-password') === $generic && $fake->logins === 0, 'locked account cannot log in');
    $fake->row['lock_until'] = date('Y-m-d H:i:s', time() - 1);
    $result = $auth->login('fixture', 'fixture-password');
    checkM4($result['user_id'] === 7 && $fake->logins === 1 && $result['failed_attempts'] === 0 && $result['lock_until'] === null, 'expired lock permits valid login and resets attempts');

    // Exercise registration transactions with the real User model and temporary schema.
    foreach (['role','user','department','education_level','academic_program','grade_level','section',
        'parent_student','legal_document_version','user_legal_acceptance'] as $table) {
        $ddl = $db->query('SHOW CREATE TABLE ' . $table)->fetch_assoc()['Create Table'];
        $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $ddl, 1);
        $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $ddl);
        $ddl = preg_replace('/,\n\)/', "\n)", $ddl);
        $db->query($ddl);
    }
    $db->query("INSERT INTO role (role_id,role_prefix) VALUES (1,'Student'),(2,'Parent')");
    $db->query("INSERT INTO department (department_id,department_name) VALUES (1,'Fixture')");
    $db->query("INSERT INTO education_level (education_level_id,department_id,education_level_name) VALUES (1,1,'Fixture')");
    $db->query("INSERT INTO grade_level (grade_level_id,education_level_id,grade_level_name) VALUES (1,1,'Fixture')");
    $db->query("INSERT INTO section (section_id,grade_level_id,section_name) VALUES (1,1,'Fixture')");
    $db->query("INSERT INTO legal_document_version (legal_document_version_id,document_type,version,title,content,effective_at,status)
        VALUES (1,'Terms','test','Fixture','Fixture',NOW(),'Active'),(2,'Privacy','test','Fixture','Fixture',NOW(),'Active')");
    $registration = $ref->newInstanceWithoutConstructor();
    $realUser = new User($db);
    $ref->getProperty('user')->setValue($registration, $realUser);
    $ref->getProperty('rateLimits')->setValue($registration, new RequestRateLimitService(new RequestRateLimit($db), str_repeat('r',64)));
    $notifications = new class extends NotificationService {
        public int $calls = 0;
        public function __construct() {}
        public function notifyRegistrationSubmitted(int $applicantId,string $applicantName,string $roleType): array { $this->calls++; return []; }
    };
    $ref->getProperty('notifications')->setValue($registration, $notifications);
    $data = ['role_type'=>'Student','student_id'=>'M4-STUDENT','first_name'=>'Fixture','last_name'=>'Student',
        'email'=>'student@example.test','gender'=>'Other','birthdate'=>'2005-01-01','password'=>'FixturePass123',
        'password_confirmation'=>'FixturePass123','accept_terms'=>'1','accept_privacy'=>'1',
        'department_id'=>1,'education_level_id'=>1,'grade_level_id'=>1,'section_id'=>1];
    $studentId = $registration->register($data);
    $student = $realUser->findById($studentId);
    checkM4($student['status']==='Pending' && password_verify($data['password'],$student['password']), 'Student registration retains approval and hashing');
    checkM4((int)$db->query('SELECT COUNT(*) n FROM user_legal_acceptance')->fetch_assoc()['n']===2, 'Student legal acceptances committed');
    $db->query('UPDATE user SET status=\'Active\' WHERE user_id=' . $studentId);
    $parentData = array_replace($data,['role_type'=>'Parent','email'=>'parent@example.test','child_student_id'=>'M4-STUDENT','relationship'=>'Guardian']);
    $parentId = $registration->register($parentData);
    $link = $db->query('SELECT parent_user_id,student_user_id,status FROM parent_student')->fetch_assoc();
    checkM4((int)$link['parent_user_id']===$parentId && (int)$link['student_user_id']===$studentId && $link['status']==='Pending', 'Parent link still requires verification');
    checkM4($realUser->findById($parentId)['status']==='Pending' && $notifications->calls===2, 'Parent approval and registration notification hook preserved');
    checkM4((int)$db->query('SELECT COUNT(*) n FROM user_legal_acceptance')->fetch_assoc()['n']===4, 'Parent legal acceptances committed');
    checkM4($registration->login('student@example.test','FixturePass123')['user_id']==$studentId, 'real-model valid login succeeds');
    try { $registration->register($data); throw new LogicException('Duplicate registration accepted'); }
    catch (Exception $e) { checkM4(str_contains($e->getMessage(),'already registered'), 'duplicate registration still rejected'); }
    $failingUser = new class($db) extends User {
        public function createLegalAcceptance(int $userId,int $legalDocumentVersionId,string $acceptanceSource='Registration',?string $userAgent=null): void {
            if ($legalDocumentVersionId===2) { throw new RuntimeException('Synthetic legal acceptance failure'); }
            parent::createLegalAcceptance($userId,$legalDocumentVersionId,$acceptanceSource,$userAgent);
        }
    };
    $ref->getProperty('user')->setValue($registration,$failingUser);
    try { $registration->register(array_replace($data,['email'=>'rollback@example.test','student_id'=>'M4-ROLLBACK'])); throw new LogicException('Failure was ignored'); }
    catch (RuntimeException $e) { checkM4($e->getMessage()==='Synthetic legal acceptance failure','registration failure propagates'); }
    checkM4((int)$db->query('SELECT COUNT(*) n FROM user')->fetch_assoc()['n']===2 && (int)$db->query('SELECT COUNT(*) n FROM user_legal_acceptance')->fetch_assoc()['n']===4, 'failed registration rolls back user and legal rows');
    $db->query("UPDATE request_rate_limit SET attempts=30 WHERE scope='registration_ip'");
    blockedM4(fn()=>$registration->register(array_replace($data,['email'=>'blocked@example.test','student_id'=>'M4-BLOCKED'])));
    checkM4((int)$db->query('SELECT COUNT(*) n FROM user')->fetch_assoc()['n']===2 && $notifications->calls===2, 'limited registration does not write or notify');
    echo "PASS: $checks abuse-protection checks. Temporary tables; no existing records or external deliveries.\n";
} finally { $db->close(); }
