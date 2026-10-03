<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cgi=dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'php-cgi'.(PHP_OS_FAMILY==='Windows'?'.exe':'');
$temporary=sys_get_temp_dir().DIRECTORY_SEPARATOR.'olshco-session-http-'.bin2hex(random_bytes(12));
if(!mkdir($temporary,0700))throw new RuntimeException('Cannot create isolated test directory.');
$checks=0;
function sessionHttpCheck(bool $ok,string $label):void { global $checks;if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks++; }
try {
    $root=dirname(__DIR__);
    $entry=$temporary.DIRECTORY_SEPARATOR.'controller.php';
    $bootstrap='<?php define("OLSHCO_SESSION_TEST_HARNESS",true); require_once '.var_export($root.'/app/controllers/AuthController.php',true).'; require_once '.var_export($root.'/config/authenticated-session.php',true).'; require_once '.var_export($root.'/tests/support/session_fixture.php',true).';';
    $bootstrap.=<<<'HARNESS'
$_SESSION=sessionFixture($conn);
$before=session_id();
$scenario=getenv('SESSION_TEST_ACTION');
if(($_GET['fixture_json']??'')==='accept')$_SERVER['HTTP_ACCEPT']='application/json';
if(($_GET['fixture_json']??'')==='xhr')$_SERVER['HTTP_X_REQUESTED_WITH']='XMLHttpRequest';
if($scenario==='inactive')$conn->query("UPDATE user SET status='Inactive' WHERE user_id=1");
if($scenario==='role')$conn->query('UPDATE user SET role_id=2 WHERE user_id=1');
if($scenario==='password')$conn->query("UPDATE user SET password='changed-fixture' WHERE user_id=1");
if($scenario==='legacy')unset($_SESSION['auth_credential_version']);
if($scenario==='expired')$_SESSION['last_activity_at']=time()-1900;
if($scenario==='failure')$conn->close();
if($scenario==='guest')$_SESSION=['csrf_token'=>'guest-fixture'];
if($scenario==='required')$conn->query('UPDATE user SET must_change_password=1 WHERE user_id=1');
if($scenario==='store') {
    class SessionTestAuthService extends AuthService {
        public function __construct() {}
        public function getPendingLegalDocuments(int $userId):array{return [];}
    }
    $controller=(new ReflectionClass(AuthController::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(AuthController::class,'service'))->setValue($controller,new SessionTestAuthService());
    $user=$conn->query("SELECT u.*,r.role_prefix FROM user u JOIN role r ON r.role_id=u.role_id WHERE u.user_id=1")->fetch_assoc();
    (new ReflectionMethod(AuthController::class,'storeUserSession'))->invoke($controller,$user);
}
register_shutdown_function(function()use($before){file_put_contents(getenv('SESSION_TEST_STATE'),json_encode(['authenticated'=>!empty($_SESSION['user_id']),'csrf_present'=>isset($_SESSION['csrf_token']),'rotated'=>session_id()!==$before]));});
HARNESS;
    $bootstrap.='if (!empty($_GET["through_index"])) { require '.var_export($root.'/index.php',true).'; exit; }';
    $bootstrap.='maintainAuthenticatedSession(); enforceCurrentAccountSession($conn); header("Content-Type: application/json"); echo json_encode(["reached"=>true,"authenticated"=>!empty($_SESSION["user_id"]),"must_change_password"=>!empty($_SESSION["must_change_password"])]);';
    file_put_contents($entry,$bootstrap);
    $request = static function (
        string $action, string $method, int $actor, array $query = [], array $post = []
    ) use ($cgi, $temporary, $entry): array {
        $body = http_build_query($post);
        $environment = getenv();
        unset($environment['HTTP_COOKIE'], $environment['HTTP_X_CSRF_TOKEN']);
        $environment = array_merge($environment, [
            'REDIRECT_STATUS' => '1',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'REQUEST_METHOD' => $method,
            'SCRIPT_FILENAME' => $entry,
            'SCRIPT_NAME' => '/controller.php',
            'QUERY_STRING' => http_build_query($query),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => (string) strlen($body),
            'SESSION_TEST_STATE' => $temporary . '/state.json',
            'SESSION_TEST_ACTOR' => (string) $actor,
            'SESSION_TEST_ACTION' => $action
        ]);
        $process = proc_open([
            $cgi, '-d', 'cgi.force_redirect=0',
            '-d', 'session.save_path=' . $temporary,
            '-d', 'display_errors=0', '-d', 'log_errors=1', '-d', 'error_log=' . $temporary . '/errors.log'
        ], [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, dirname($entry), $environment);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start isolated CGI verification.');
        }
        fwrite($pipes[0], $body);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0 || trim($errors) !== '') {
            throw new RuntimeException('CGI verification failed: ' . $errors);
        }
        $parts = preg_split("/\r?\n\r?\n/", $output, 2);
        if (count($parts) !== 2) {
            throw new RuntimeException('Invalid CGI response.');
        }
        [$headers, $responseBody] = $parts;
        $status = preg_match('/^Status:\s*(\d+)/mi', $headers, $match) ? (int) $match[1] : 200;
        return [$status, $headers, $responseBody];
    };

    foreach(['inactive','role','password','legacy','expired'] as $scenario) {
        foreach(['session_keep_alive','content_react','browser_push_subscribe','government_advisory_preview'] as $page) {
            [$status,$headers,$body]=$request($scenario,'POST',1,['page'=>$page]);
            sessionHttpCheck($status===401,'Revoked/expired AJAX returns 401');
            sessionHttpCheck(stripos($headers,'Content-Type: application/json')!==false,'AJAX errors stay JSON');
            sessionHttpCheck(json_decode($body,true,512,JSON_THROW_ON_ERROR)['success']===false,'AJAX failure payload');
            $state=json_decode(file_get_contents($temporary.'/state.json'),true);
            sessionHttpCheck(!$state['authenticated'] && !$state['csrf_present'] && $state['rotated'],'Invalid session cleared and ID rotated');
        }
    }
    [$status,$headers]=$request('inactive','GET',1,['page'=>'manage_users']);
    sessionHttpCheck($status===303 && str_contains($headers,'page=login'),'Revoked HTML request redirects to login');
    foreach(['inactive','role','password','legacy','expired'] as $scenario) {
        [$status,$headers]=$request($scenario,'GET',1,['page'=>'document_download','through_index'=>1]);
        sessionHttpCheck($scenario==='expired' ? $status===401 : ($status===303 && str_contains($headers,'page=login')),'Actual index denies stale document request: '.$scenario);
    }
    [$status,$headers,$body]=$request('failure','POST',1,['page'=>'session_keep_alive']);
    sessionHttpCheck($status===503 && json_decode($body,true,512,JSON_THROW_ON_ERROR)['success']===false,'Verification failure fails closed with JSON 503');
    sessionHttpCheck(!str_contains($body,'mysqli') && !str_contains($body,'Stack trace'),'Lookup failure does not expose internals');
    $state=json_decode(file_get_contents($temporary.'/state.json'),true);
    sessionHttpCheck($state['authenticated'],'Transient lookup failure preserves session for retry');
    [$status,$headers,$body]=$request('failure','GET',1,['page'=>'manage_users']);
    sessionHttpCheck($status===503 && !str_contains($headers,'Location:') && !str_contains($body,'Stack trace'),'HTML lookup failure is safe 503 without redirect');
    foreach(['accept','xhr'] as $signal) {
        [$status,$headers,$body]=$request('inactive','POST',1,['page'=>'manage_user_change_role','fixture_json'=>$signal]);
        sessionHttpCheck($status===401 && json_decode($body,true,512,JSON_THROW_ON_ERROR)['success']===false,'AJAX headers preserve JSON on form actions');
    }
    [$status,$headers]=$request('required','GET',1,['page'=>'document_download','through_index'=>1]);
    sessionHttpCheck($status===302 && str_contains($headers,'page=required_password_change'),'Actual index enforces refreshed password-change requirement');
    foreach(['valid','store','required','guest'] as $scenario) {
        [$status,$headers,$body]=$request($scenario,'GET',1,['page'=>'home']);
        $result=json_decode($body,true,512,JSON_THROW_ON_ERROR);
        sessionHttpCheck($status===200 && $result['reached'],'Valid/guest flow continues');
        sessionHttpCheck($result['authenticated']===($scenario!=='guest'),'Expected authenticated state');
        sessionHttpCheck($result['must_change_password']===($scenario==='required'),'Password-change flag refreshed');
    }
    echo "PASS: {$checks} session HTTP checks; only temporary tables and isolated session files used.\n";
} finally {
    foreach(glob($temporary.DIRECTORY_SEPARATOR.'*')?:[] as $file)if(is_file($file))unlink($file);
    rmdir($temporary);
}
