<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cgi = dirname(PHP_BINARY) . '/php-cgi.exe';
$temporary = sys_get_temp_dir() . '/olshco-abuse-http-' . bin2hex(random_bytes(12));
if (!mkdir($temporary, 0700)) { throw new RuntimeException('Cannot create test directory.'); }
$checks = 0;
function abuseHttpCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
try {
    $root = dirname(__DIR__);
    $bootstrap = '<?php require_once ' . var_export($root . '/app/controllers/AuthController.php', true)
        . '; require_once ' . var_export($root . '/app/controllers/ContentEngagementController.php', true) . ';';
    $bootstrap .= <<<'HARNESS'
installRequestErrorHandling();
$action = getenv('M4_ACTION');
$mode = getenv('M4_MODE');
$actor = (int) getenv('M4_ACTOR');
$_SESSION = ['user_id'=>$actor, 'role'=>getenv('M4_ROLE'), 'csrf_token'=>'fixture-csrf'];
$db = openDatabaseConnection();
$ddl = $db->query('SHOW CREATE TABLE request_rate_limit')->fetch_assoc()['Create Table'];
$db->query(str_replace('CREATE TABLE ', 'CREATE TEMPORARY TABLE ', $ddl));
$limiter = new RequestRateLimitService(new RequestRateLimit($db), str_repeat('f', 64));
$ip = '192.0.2.25';
if ($action === 'login') { $limiter->login('fixture@example.test', $ip); }
elseif ($action === 'register') { $limiter->register($ip); }
else { $limiter->engagement($action, 7); }
if ($mode === 'blocked' || $mode === 'expired') { $db->query('UPDATE request_rate_limit SET attempts=60000'); }
if ($mode === 'expired') { $db->query('UPDATE request_rate_limit SET expires_at=UNIX_TIMESTAMP()-1'); }
$writes = 0;
class HttpAbuseUser extends User {
    public function __construct() {}
    public function findByIdentifier(string $identifier) { return null; }
}
class HttpAbuseEngagement extends ContentEngagement {
    public function recordView(string $type, int $id, int $user): int { global $writes; $writes++; return 1; }
    public function react(string $type, int $id, int $user, string $reaction): int { global $writes; $writes++; return 1; }
    public function comment(string $type, int $id, int $user, string $comment, ?int $parentCommentId = null): int { global $writes; $writes++; return 1; }
    public function acknowledge(string $type, int $id, int $user): bool { global $writes; $writes++; return true; }
    public function getEngagement(string $type, int $id, int $user): array { return []; }
    public function getComments(string $type, int $id): array { return []; }
}
class HttpAbuseAnnouncement extends Announcement {
    public function findById(int $id): ?array { return ['announcement_id'=>$id,'title'=>'Fixture','require_acknowledgment'=>1]; }
}
class HttpAbuseAudience extends ContentAudienceService {
    public function __construct() {}
    public function requirePublishedAccess(string $type, array $content, int $user): void {
        if (getenv('M4_MODE') === 'denied') { throw new DomainException('Denied'); }
    }
}
class HttpAbuseNotifications extends NotificationService {
    public function __construct() {}
    public function notifyContentEngagement(int $recipientId, int $actorId, string $action, string $contentType, int $contentId, string $contentTitle, string $interactionKey, ?string $reaction = null): bool { return false; }
}
register_shutdown_function(static function () use ($db, &$writes) {
    file_put_contents(getenv('M4_STATE'), json_encode(['writes'=>$writes,'session'=>$_SESSION]));
    if (getenv('M4_MODE') !== 'storage_failure') { $db->close(); }
});
if ($mode === 'storage_failure') { $db->close(); }
if (in_array($action, ['login','register'], true)) {
    $service = (new ReflectionClass(AuthService::class))->newInstanceWithoutConstructor();
    foreach (['user'=>new HttpAbuseUser(), 'rateLimits'=>$limiter, 'clientIp'=>$ip] as $name=>$value) {
        (new ReflectionProperty(AuthService::class, $name))->setValue($service, $value);
    }
    $controller = (new ReflectionClass(AuthController::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(AuthController::class, 'service'))->setValue($controller, $service);
} else {
    $service = (new ReflectionClass(ContentEngagementService::class))->newInstanceWithoutConstructor();
    foreach (['engagement'=>new HttpAbuseEngagement($db), 'announcement'=>new HttpAbuseAnnouncement($db),
        'audience'=>new HttpAbuseAudience(), 'notificationService'=>new HttpAbuseNotifications(), 'rateLimits'=>$limiter] as $name=>$value) {
        (new ReflectionProperty(ContentEngagementService::class, $name))->setValue($service, $value);
    }
    $controller = (new ReflectionClass(ContentEngagementController::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(ContentEngagementController::class, 'service'))->setValue($controller, $service);
}
$controller->{$action}();
HARNESS;
    $entry = $temporary . '/controller.php';
    file_put_contents($entry, $bootstrap);
    $request = static function (string $action, string $mode, string $role='Student', string $method='POST', int $actor=7, array $overrides=[], bool $json=false) use ($entry, $temporary, $cgi): array {
        $post = array_replace(['identifier'=>'fixture@example.test','password'=>'secret-fixture','csrf_token'=>'fixture-csrf',
            'content_type'=>'announcement','content_id'=>1,'reaction'=>'Upvote','comment'=>'Fixture','user_id'=>999], $overrides);
        $body = http_build_query($post);
        $env = getenv();
        unset($env['HTTP_COOKIE'], $env['HTTP_X_CSRF_TOKEN'], $env['HTTP_X_REQUESTED_WITH']);
        $env = array_merge($env, ['REDIRECT_STATUS'=>'1','GATEWAY_INTERFACE'=>'CGI/1.1','SERVER_PROTOCOL'=>'HTTP/1.1',
            'SERVER_NAME'=>'localhost','SERVER_PORT'=>'80','REQUEST_METHOD'=>$method,'SCRIPT_FILENAME'=>$entry,
            'SCRIPT_NAME'=>'/controller.php','QUERY_STRING'=>'','CONTENT_TYPE'=>'application/x-www-form-urlencoded',
            'CONTENT_LENGTH'=>(string)strlen($body),'HTTP_ACCEPT'=>$json?'application/json':'text/html',
            'M4_ACTION'=>$action,'M4_MODE'=>$mode,'M4_ROLE'=>$role,'M4_ACTOR'=>(string)$actor,'M4_STATE'=>$temporary.'/state.json']);
        $process = proc_open([$cgi,'-d','cgi.force_redirect=0','-d','display_errors=0','-d','error_log='.$temporary.'/errors.log'],
            [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, dirname($entry), $env);
        if (!is_resource($process)) { throw new RuntimeException('Cannot start CGI.'); }
        fwrite($pipes[0], $body); fclose($pipes[0]);
        $output=stream_get_contents($pipes[1]); fclose($pipes[1]);
        $errors=stream_get_contents($pipes[2]); fclose($pipes[2]);
        $exit=proc_close($process);
        if ($exit !== 0 || trim($errors) !== '') { throw new RuntimeException('CGI failed: '.$errors); }
        [$headers,$response] = preg_split('/\r?\n\r?\n/', $output, 2);
        $status=preg_match('/^Status:\s*(\d+)/mi', $headers, $m)?(int)$m[1]:200;
        $state=json_decode(file_get_contents($temporary.'/state.json'),true,512,JSON_THROW_ON_ERROR);
        return [$status,$headers,$response,$state];
    };
    foreach (['Admin','Faculty','Student','Parent'] as $role) {
        foreach (['open','react','comment','acknowledge'] as $action) {
            [$status,$headers,$body,$state]=$request($action,'blocked',$role);
            abuseHttpCheck($status===429 && preg_match('/Retry-After: [1-9][0-9]*/i',$headers), $role.' '.$action.' rate status/retry');
            $data=json_decode($body,true,512,JSON_THROW_ON_ERROR);
            abuseHttpCheck($data['success']===false && str_contains($data['message'],'second(s)') && $state['writes']===0, 'blocked action has safe JSON and no writes');
        }
    }
    foreach (['open','react','comment','acknowledge'] as $action) {
        foreach (['allowed','expired'] as $mode) {
            [$status,$headers,$body,$state]=$request($action,$mode);
            abuseHttpCheck($status===200 && json_decode($body,true)['success']===true && $state['writes']===1, 'allowed/expired action completes');
        }
        foreach ([['GET',7,[],405],['POST',0,[],401],['POST',7,['csrf_token'=>'bad'],419],['POST',7,['content_id'=>0],422],['POST',7,['content_type'=>'invalid'],422]] as [$method,$actor,$post,$expected]) {
            [$status,$headers,$body,$state]=$request($action,'blocked','Student',$method,$actor,$post);
            abuseHttpCheck($status===$expected && $state['writes']===0, 'method/auth/CSRF/ID validation still precedes limiter');
        }
        [$status,,,$state]=$request($action,'storage_failure');
        abuseHttpCheck($status===500 && $state['writes']===0, 'storage failure does not allow an uncounted write');
        [$status,,,$state]=$request($action,'denied');
        abuseHttpCheck($status===404 && $state['writes']===0, 'audience denial remains enforced');
    }
    foreach (['login','register'] as $action) {
        foreach ([false,true] as $json) {
            [$status,$headers,$body,$state]=$request($action,'blocked','Student','POST',7,[],$json);
            abuseHttpCheck($status===429 && str_contains($headers,'Retry-After:') && !str_contains($headers,'Location:'), 'auth POST returns 429 without redirect');
            abuseHttpCheck(!str_contains($body,'secret-fixture') && !str_contains(json_encode($state),'secret-fixture'), 'password never returned or flashed');
            abuseHttpCheck($json ? json_decode($body,true)['success']===false : str_contains($body,'root.css'), 'auth response format');
        }
        [$status,,,$state]=$request($action,'allowed');
        abuseHttpCheck($status===302 && $state['writes']===0, 'normal invalid form keeps redirect flow');
        [$status]=$request($action,'blocked','Student','POST',7,['csrf_token'=>'bad']);
        abuseHttpCheck($status!==429, 'auth CSRF rejected before limiter');
    }
    foreach (['identifier', 'password'] as $field) {
        [$status,,,$state] = $request('login', 'allowed', 'Student', 'POST', 7, [$field => ['crafted']]);
        abuseHttpCheck($status === 302 && $state['writes'] === 0, 'malformed login input rejected without writes');
        abuseHttpCheck(($state['session']['login_flash']['error'] ?? '') === 'Invalid form values. Please review your entries and try again.', 'malformed login input receives safe validation');
        abuseHttpCheck(is_string($state['session']['login_flash']['old_identifier'] ?? null), 'login feedback identifier remains scalar');
    }
    foreach (['role_type', 'first_name', 'email', 'section_id', 'password'] as $field) {
        [$status,,,$state] = $request('register', 'allowed', 'Student', 'POST', 7, [$field => ['crafted']]);
        abuseHttpCheck($status === 302 && $state['writes'] === 0, 'malformed registration input rejected without writes');
        $flash = $state['session']['registration_flash'] ?? [];
        abuseHttpCheck(($flash['error'] ?? '') === 'Invalid form values. Please review your entries and try again.', 'malformed registration receives safe validation');
        abuseHttpCheck(count(array_filter($flash['old_input'] ?? [], 'is_array')) === 0, 'registration feedback excludes arrays');
        abuseHttpCheck(!isset($flash['old_input']['password']), 'registration feedback excludes passwords');
    }
    echo "PASS: $checks abuse-protection HTTP checks; temporary table and fake content/account models only.\n";
} finally {
    foreach (glob($temporary.'/*') ?: [] as $file) { if (is_file($file)) unlink($file); }
    rmdir($temporary);
}
