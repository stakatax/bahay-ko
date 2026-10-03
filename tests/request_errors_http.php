<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cgi=dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'php-cgi'.(PHP_OS_FAMILY==='Windows'?'.exe':'');
$temporary=sys_get_temp_dir().DIRECTORY_SEPARATOR.'olshco-errors-http-'.bin2hex(random_bytes(12));
if(!mkdir($temporary,0700))throw new RuntimeException('Cannot create isolated error-test directory.');
$checks=0;
function errorCheck(bool $ok,string $label):void { global $checks;if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks++; }
try {
    $root=dirname(__DIR__);
    $entry=$temporary.'/controller.php';
    $bootstrap='<?php if(getenv("SESSION_TEST_ACTION")!=="index_fatal"){ require_once '.var_export($root.'/config/request-errors.php',true).'; installRequestErrorHandling(); }';
    $bootstrap.=<<<'HARNESS'
$scenario=getenv('SESSION_TEST_ACTION');
if(!empty($_GET['accept_json']))$_SERVER['HTTP_ACCEPT']='application/json';
if(!empty($_GET['xhr']))$_SERVER['HTTP_X_REQUESTED_WITH']='XMLHttpRequest';
if($scenario==='success'){echo 'HEALTHY';exit;}
if($scenario==='warning'){trigger_error('SECRET SQL warning',E_USER_WARNING);header('Content-Type: application/json');echo '{"success":true}';exit;}
if($scenario==='fatal'){echo 'PARTIAL SECRET';trigger_error('SECRET fatal',E_USER_ERROR);}
if($scenario==='nested'){ob_start();echo 'PARTIAL SECRET';throw new RuntimeException('SECRET SQL /private/config.php');}
if($scenario==='sql'){header('Content-Disposition: attachment; filename=secret.csv');header('Location: /secret');header('Content-Length: 999');echo 'PARTIAL SECRET';throw new mysqli_sql_exception('SECRET SQL password=hidden');}
if($scenario==='type'){strlen([]);}
if($scenario==='validation'){throw new InvalidArgumentException('Please select a valid option.');}
if($scenario==='escaped'){throw new InvalidArgumentException('<script>fixture</script>');}
if($scenario==='domain'){throw new DomainException('Access denied.');}
if($scenario==='not_found'){throw new RequestNotFoundException('Page not found.');}
if($scenario==='runtime'){throw new RuntimeException('SECRET internal failure');}
if($scenario==='index_fatal'){
    function startSecureSession():void{}
    require ROOT_PATH.'/index.php';exit;
}
if($scenario==='index_guest'){require ROOT_PATH.'/index.php';exit;}
require_once ROOT_PATH.'/app/controllers/AuthController.php';
require_once ROOT_PATH.'/app/controllers/ContentEngagementController.php';
$_SESSION=['user_id'=>1,'role'=>'Admin','csrf_token'=>'fixture-csrf'];
class ErrorAuthFixture extends AuthService {
    public function __construct(){}
    public function login(string $identifier,string $password):array {
        if(getenv('SESSION_TEST_ACTION')==='login_validation')throw new RuntimeException('Incorrect password. 3 attempt(s) remaining.');
        throw new RuntimeException('SECRET SQL password=hidden /private/config.php');
    }
}
class ErrorEngagementFixture extends ContentEngagementService {
    public function __construct(){}
    public function open(string $type,int $id,int $user):array { throw new RuntimeException('SECRET SQL /private/config.php'); }
}
if(str_starts_with($scenario,'login_')){
    register_shutdown_function(static function(){file_put_contents(getenv('SESSION_TEST_STATE'),json_encode($_SESSION['login_flash']??[]));});
    $controller=(new ReflectionClass(AuthController::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(AuthController::class,'service'))->setValue($controller,new ErrorAuthFixture());
    $controller->login();exit;
}
$controller=(new ReflectionClass(ContentEngagementController::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(ContentEngagementController::class,'service'))->setValue($controller,new ErrorEngagementFixture());
$controller->open();
HARNESS;
    $bootstrap=str_replace('ROOT_PATH',var_export($root,true),$bootstrap);
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
        if (trim($errors) !== '') {
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

    foreach(['runtime','sql','type','fatal','nested','index_fatal'] as $scenario){
        foreach([false,true] as $json){
            [$status,$headers,$body]=$request($scenario,'GET',0,['page'=>$json?'content_open':'home']);
            errorCheck($status===500,$scenario.' returns 500');
            errorCheck(!str_contains($body,'SECRET') && !str_contains($body,'Stack trace') && !str_contains($body,'PARTIAL') && !str_contains($headers,'/secret'),$scenario.' does not leak');
            errorCheck(!str_contains($headers,'Content-Disposition:') && !str_contains($headers,'Content-Length: 999'),$scenario.' discards stale headers');
            errorCheck(str_contains($headers,'no-store'),$scenario.' is not cached');
            if($json){$data=json_decode($body,true,512,JSON_THROW_ON_ERROR);errorCheck($data['success']===false && !empty($data['reference']),'Safe JSON contract');}
            else errorCheck(str_contains($body,'root.css') && str_contains($body,'Reference:'),'Safe themed HTML');
        }
    }
    foreach(['accept_json','xhr'] as $signal){
        [$status,$headers,$body]=$request('runtime','GET',0,['page'=>'home',$signal=>1]);
        errorCheck($status===500 && json_decode($body,true,512,JSON_THROW_ON_ERROR)['success']===false,'AJAX negotiation');
    }
    foreach(['validation'=>422,'domain'=>403,'not_found'=>404] as $scenario=>$expected){
        [$status,$headers,$body]=$request($scenario,'GET',0,['page'=>'content_open']);
        errorCheck($status===$expected && !str_contains($body,'could not complete'),'Expected validation/status preserved');
    }
    [$status,$headers,$body]=$request('escaped','GET',0,['page'=>'home']);
    errorCheck($status===422 && str_contains($body,'&lt;script&gt;') && !str_contains($body,'<script>'),'HTML validation escaped');
    [$status,$headers,$body]=$request('warning','GET',0,['page'=>'content_open']);
    errorCheck($status===200 && json_decode($body,true,512,JSON_THROW_ON_ERROR)['success']===true,'Warnings do not corrupt successful JSON');
    [$status,$headers,$body]=$request('success','GET',0,['page'=>'home']);
    errorCheck($status===200 && $body==='HEALTHY','Success unchanged');
    [$status,$headers,$body]=$request('runtime','GET',0,['page'=>'document_download']);
    errorCheck($status===500 && str_contains($headers,'text/plain') && !str_contains($body,'SECRET'),'Download failure is safe plain text');
    [$status,$headers,$body]=$request('index_guest','GET',0,['page'=>'document_download','document_id'=>1]);
    errorCheck($status===401 && !str_contains($body,'SECRET'),'Actual index guest document denial preserved');
    $post=['identifier'=>'fixture','password'=>'fixture','csrf_token'=>'fixture-csrf'];
    foreach(['login_internal','login_validation'] as $scenario){
        [$status,$headers,$body]=$request($scenario,'POST',1,['page'=>'login_action'],$post);
        errorCheck($status===302 && str_contains($headers,'page=login'),'Login redirect preserved');
        errorCheck(!str_contains(urldecode($headers.$body),'SECRET'),'Caught login exception sanitized');
        $flash=json_decode(file_get_contents($temporary.'/state.json'),true,512,JSON_THROW_ON_ERROR);
        errorCheck(!str_contains(json_encode($flash),'SECRET'),'Session flash never retains internal exception text');
        errorCheck($scenario==='login_validation' ? str_contains($flash['error'],'Incorrect password. 3 attempt(s) remaining.') : str_contains($flash['error'],'Reference:'),'Login validation preserved/internal reference supplied');
    }
    [$status,$headers,$body]=$request('engagement','POST',1,['page'=>'content_open'],['content_type'=>'announcement','content_id'=>1,'csrf_token'=>'fixture-csrf']);
    errorCheck($status===500 && json_decode($body,true,512,JSON_THROW_ON_ERROR)['success']===false && !str_contains($body,'SECRET'),'Caught internal JSON RuntimeException returns safe 500');
    $logs=file_get_contents($temporary.'/errors.log');
    errorCheck(str_contains($logs,'[request ') && str_contains($logs,'RuntimeException') && str_contains($logs,'code=') && str_contains($logs,'controller.php:'),'Diagnostics retained in server log');
    echo "PASS: $checks request error HTTP checks; isolated CGI/session/log files, no application writes.\n";
} finally {
    foreach(glob($temporary.DIRECTORY_SEPARATOR.'*')?:[] as $file)if(is_file($file))unlink($file);
    rmdir($temporary);
}
