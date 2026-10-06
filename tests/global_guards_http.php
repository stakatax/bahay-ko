<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cgi = dirname(PHP_BINARY) . '/php-cgi' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
$temporary = sys_get_temp_dir() . '/olshco-guards-' . bin2hex(random_bytes(12));
if (!mkdir($temporary,0700)) throw new RuntimeException('Cannot create test directory.');
$checks = 0;
function guardCheck(bool $ok,string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException('FAIL: '.$label);
    $checks++;
}
try {
    $root = dirname(__DIR__);
    $source = file_get_contents($root.'/index.php');
    $boundary = strpos($source,'   PROCESS DUE CONTENT RELEASES');
    if ($boundary === false) throw new RuntimeException('Missing guard boundary.');
    $source = substr($source,0,strrpos(substr($source,0,$boundary),'/*'));
    // Execute the current front-controller prefix. Only survey lookup is stubbed;
    // stop before publishing/route dispatch so no real actions or deliveries run.
    $source = str_replace('__DIR__',var_export($root,true),$source);
    $source = str_replace('(new StudentProfileService())','(new GuardSurveyService())',$source,$replacements);
    if ($replacements !== 1) throw new RuntimeException('Unexpected survey dependency shape.');
    // Include the actual route-specific guard, but never invoke the page controller.
    $fullSource = file_get_contents($root.'/index.php');
    if (!preg_match("/case 'student_profile':\\s*case 'student_profile_survey':\\s*(requirePageRoles\\([\\s\\S]*?\\);)/", $fullSource, $studentRouteGuard)) {
        throw new RuntimeException('Student profile route guard not found.');
    }
    $source .= "if (in_array(\$page, ['student_profile', 'student_profile_survey'], true)) {" . $studentRouteGuard[1] . "}";
    if (!preg_match("/case 'government_advisories':([\\s\\S]*?)break;/", $fullSource, $retiredAdvisoryRoute)) {
        throw new RuntimeException('Retired advisory route not found.');
    }
    $source .= "if (\$page === 'government_advisories') {" . $retiredAdvisoryRoute[1] . "}";
    $source .= "header('Content-Type: application/json'); echo json_encode(['reached'=>true]);";
    file_put_contents($temporary.'/index-prefix.php',$source);
    $bootstrap = '<?php define("OLSHCO_SESSION_TEST_HARNESS",true); require_once '.var_export($root.'/config/security.php',true)
        . '; require_once '.var_export($root.'/config/dbconnect.php',true)
        . '; require_once '.var_export($root.'/tests/support/session_fixture.php',true)
        . '; require_once '.var_export($root.'/app/services/StudentProfileService.php',true).';';
    $bootstrap .= <<<'HARNESS'
startSecureSession();
$_SESSION = sessionFixture($conn);
$actor = (int)getenv('GUARD_ACTOR');
$roles = [1=>'Admin',2=>'Faculty',3=>'Student',4=>'Parent'];
if ($actor === 0) { $_SESSION = ['csrf_token'=>'fixture-token']; }
else { $_SESSION['user_id']=$actor; $_SESSION['role_id']=$actor; $_SESSION['role']=$roles[$actor]; }
$mode = getenv('GUARD_MODE');
if ($mode === 'faculty_profile') { $conn->query('UPDATE user SET gender=NULL,birthdate=NULL WHERE user_id=2'); $_SESSION['faculty_profile_required']=true; }
if (in_array($mode,['password','all'],true)) $conn->query('UPDATE user SET must_change_password=1');
$_SESSION['legal_reconsent_required'] = in_array($mode,['legal','all'],true);
class GuardSurveyService extends StudentProfileService {
    public function __construct() {}
    public function requiresSurveyCompletion(int $userId): bool { return in_array(getenv('GUARD_MODE'),['survey','all'],true); }
}
register_shutdown_function(static function() use ($conn) { $conn->close(); });
require __DIR__.'/index-prefix.php';
HARNESS;
    file_put_contents($temporary.'/guard.php',$bootstrap);
    file_put_contents($temporary.'/cookie.php','<?php require_once '.var_export($root.'/config/security.php',true)
        . '; startSecureSession(); header("Content-Type: application/json"); echo json_encode(["https"=>requestUsesHttps(),"params"=>session_get_cookie_params()]);');
    $request = static function (string $entry, array $query=[], array $settings=[]) use ($temporary,$cgi): array {
        $env=getenv();
        foreach (['HTTPS','HTTP_FORWARDED','HTTP_X_FORWARDED_PROTO','HTTP_X_FORWARDED_SSL','HTTP_ACCEPT','HTTP_X_REQUESTED_WITH','HTTP_COOKIE','OLSHCO_APP_URL'] as $name) unset($env[$name]);
        $env=array_merge($env,['REDIRECT_STATUS'=>'1','GATEWAY_INTERFACE'=>'CGI/1.1','SERVER_PROTOCOL'=>'HTTP/1.1',
            'SERVER_NAME'=>'localhost','SERVER_PORT'=>'80','REQUEST_METHOD'=>'GET','SCRIPT_FILENAME'=>$temporary.'/'.$entry,
            'SCRIPT_NAME'=>'/'.$entry,'QUERY_STRING'=>http_build_query($query),'CONTENT_LENGTH'=>'0',
            'GUARD_ACTOR'=>'3','GUARD_MODE'=>'clear'],$settings);
        $process=proc_open([$cgi,'-d','cgi.force_redirect=0','-d','session.save_path='.$temporary,
            '-d','display_errors=0','-d','log_errors=1','-d','error_log='.$temporary.'/errors.log'],
            [['pipe','r'],['pipe','w'],['pipe','w']],$pipes,$temporary,$env);
        if (!is_resource($process)) throw new RuntimeException('Cannot start CGI.');
        fclose($pipes[0]);$output=stream_get_contents($pipes[1]);fclose($pipes[1]);
        $errors=stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($process);
        if ($exit!==0 || trim($errors)!=='') throw new RuntimeException('CGI failed: '.$errors);
        [$headers,$body]=preg_split('/\r?\n\r?\n/',$output,2);
        $status=preg_match('/^Status:\s*(\d+)/mi',$headers,$m)?(int)$m[1]:200;
        return [$status,$headers,$body];
    };
    foreach (['content_open','content_react','post_store','manage_users'] as $blockedPage) {
        [$status,$headers,$body]=$request('guard.php',['page'=>$blockedPage],['GUARD_ACTOR'=>'2','GUARD_MODE'=>'faculty_profile','HTTP_ACCEPT'=>'application/json']);
        $data=json_decode($body,true,512,JSON_THROW_ON_ERROR);
        guardCheck($status===403 && !str_contains($headers,'Location:') && $data['code']==='faculty_profile_required','Faculty pending profile blocks JSON: '.$blockedPage);
        guardCheck($data['redirect_url']==='index.php?page=account_profile','Faculty profile destination');
    }
    foreach (['account_profile','account_profile_update_details','account_profile_photo_upload','account_profile_photo_remove','session_keep_alive','logout'] as $allowedPage) {
        [$status,,$body]=$request('guard.php',['page'=>$allowedPage],['GUARD_ACTOR'=>'2','GUARD_MODE'=>'faculty_profile']);
        guardCheck($status===200 && !empty(json_decode($body,true)['reached']),'Faculty profile allows completion route '.$allowedPage);
    }
    [$status,$headers]=$request('guard.php',['page'=>'news'],['GUARD_ACTOR'=>'2','GUARD_MODE'=>'faculty_profile']);
    guardCheck($status===302 && str_contains($headers,'page=account_profile'),'Faculty ordinary navigation redirects to profile');
    $guards=['password'=>['password_change_required','required_password_change'],
        'legal'=>['legal_consent_required','legal_reconsent'],'survey'=>['student_survey_required','student_profile_survey']];
    $jsonRoutes=['session_keep_alive','content_open','content_react','content_comment','content_acknowledge',
        'content_redundancy_check','browser_push_configuration','browser_push_subscribe','browser_push_unsubscribe','government_advisory_preview','government_advisory_prepare'];
    foreach ($guards as $mode=>[$code,$target]) {
        foreach ($jsonRoutes as $page) {
            [$status,$headers,$body]=$request('guard.php',['page'=>$page,'redirect_url'=>'https://untrusted.invalid'],['GUARD_MODE'=>$mode]);
            $data=json_decode($body,true,512,JSON_THROW_ON_ERROR);
            guardCheck($status===403 && !str_contains($headers,'Location:') && str_contains($headers,'application/json'),'blocked JSON route '.$mode.'/'.$page);
            guardCheck($data['success']===false && $data['code']===$code && $data['redirect_url']==='index.php?page='.$target && !empty($data['message']),'safe guard reason and fixed local destination');
            guardCheck(str_contains($headers,'no-store') && !isset($data['reached']),'guard prevents further processing');
        }
        foreach (['HTTP_ACCEPT'=>'application/json','HTTP_X_REQUESTED_WITH'=>'XMLHttpRequest'] as $header=>$value) {
            [$status,,$body]=$request('guard.php',['page'=>'post_store'],['GUARD_MODE'=>$mode,$header=>$value,'REQUEST_METHOD'=>'POST']);
            guardCheck($status===403 && json_decode($body,true)['code']===$code,'form action AJAX header detection');
        }
        [$status,$headers,$body]=$request('guard.php',['page'=>'news'],['GUARD_MODE'=>$mode]);
        guardCheck($status===302 && str_contains($headers,'Location: index.php?page='.$target) && $body==='','ordinary navigation keeps redirect');
    }
    foreach ([1,2,3,4] as $actor) {
        foreach (['password','legal'] as $mode) {
            [$status,,$body]=$request('guard.php',['page'=>'content_open'],['GUARD_MODE'=>$mode,'GUARD_ACTOR'=>(string)$actor]);
            guardCheck($status===403 && json_decode($body,true)['code']===$guards[$mode][0],'all roles obey account requirements');
        }
    }
    foreach (['password'=>['required_password_change','required_password_change_action','logout'],
        'legal'=>['legal_reconsent','legal_reconsent_action','logout'],
        'survey'=>['student_profile','student_profile_survey','student_profile_save','student_profile_survey_save','notifications','notification_open','notification_mark_all_read','logout']] as $mode=>$allowed) {
        foreach ($allowed as $page) {
            [$status,,$body]=$request('guard.php',['page'=>$page],['GUARD_MODE'=>$mode,'HTTP_ACCEPT'=>'application/json']);
            guardCheck($status===200 && json_decode($body,true)['reached']===true,'required remediation/logout remains reachable');
        }
    }
    [$status,,$body]=$request('guard.php',['page'=>'content_open'],['GUARD_MODE'=>'all']);
    guardCheck($status===403 && json_decode($body,true)['code']==='password_change_required','password requirement takes precedence');
    foreach ([1,2,4] as $actor) {
        [$status,,$body]=$request('guard.php',['page'=>'content_open'],['GUARD_MODE'=>'survey','GUARD_ACTOR'=>(string)$actor]);
        guardCheck($status===200 && json_decode($body,true)['reached'],'survey guard remains Student-only');
    }
    foreach ([['0','news',401,'authentication_required'],['3','manage_users',403,'access_denied'],
        ['0','government_advisories',401,'authentication_required'],['2','government_advisories',403,'access_denied'],
        ['3','government_advisories',403,'access_denied'],['4','government_advisories',403,'access_denied']] as [$actor,$page,$expected,$code]) {
        [$status,,$body]=$request('guard.php',['page'=>$page],['GUARD_ACTOR'=>$actor,'HTTP_ACCEPT'=>'application/json']);
        guardCheck($status===$expected && json_decode($body,true)['code']===$code,'page authorization is request-aware');
        [$status,$headers]=$request('guard.php',['page'=>$page],['GUARD_ACTOR'=>$actor]);
        guardCheck($status===302 && str_contains($headers,'Location:'),'ordinary page authorization redirect preserved');
    }
    [$status,$headers,$body]=$request('guard.php',['page'=>'government_advisories'],['GUARD_ACTOR'=>'1']);
    guardCheck($status===302 && str_contains($headers,'Location: index.php?page=postings') && $body==='',
        'Retired advisory page redirects Admin to posting without rendering the duplicate screen');
    foreach ([['student_profile_management',[2,3,4]],['student_profile',[1,2,4]],['student_profile_survey',[1,2,4]]] as [$page,$actors]) {
        foreach ($actors as $actor) {
            [$status,,$body]=$request('guard.php',['page'=>$page],['GUARD_ACTOR'=>(string)$actor,'HTTP_ACCEPT'=>'application/json']);
            guardCheck($status===403 && json_decode($body,true)['code']==='access_denied','Profile page rejects unauthorized role: '.$page.'/'.$actor);
        }
    }
    foreach ([['student_profile_management',1],['student_profile',3],['student_profile_survey',3]] as [$page,$actor]) {
        [$status,,$body]=$request('guard.php',['page'=>$page],['GUARD_ACTOR'=>(string)$actor,'HTTP_ACCEPT'=>'application/json']);
        guardCheck($status===200 && json_decode($body,true)['reached'],'Authorized profile role reaches route boundary');
    }
    foreach ($jsonRoutes as $page) {
        [$status,,$body]=$request('guard.php',['page'=>$page]);
        guardCheck($status===200 && json_decode($body,true)['reached'],'clear session reaches dispatch boundary');
    }
    foreach ([
        [[],false], [['HTTPS'=>'off'],false], [['HTTPS'=>'OFF'],false], [['HTTPS'=>'0'],false],
        [['HTTPS'=>'on'],true], [['HTTPS'=>'ON'],true], [['HTTPS'=>'1'],true],
        [['HTTP_X_FORWARDED_PROTO'=>'https'],false], [['HTTP_FORWARDED'=>'proto=https'],false],
        [['HTTP_X_FORWARDED_SSL'=>'on'],false], [['OLSHCO_APP_URL'=>'https://fixture.invalid'],false],
        [['HTTPS'=>'off','HTTP_X_FORWARDED_PROTO'=>'https'],false],
        [['HTTPS'=>'on','HTTP_X_FORWARDED_PROTO'=>'http'],true]
    ] as [$settings,$expected]) {
        [$status,$headers,$body]=$request('cookie.php',[],$settings);
        $data=json_decode($body,true,512,JSON_THROW_ON_ERROR);
        guardCheck($status===200 && $data['https']===$expected && $data['params']['secure']===$expected,'HTTPS depends on trusted server state');
        guardCheck($data['params']['httponly']===true && $data['params']['samesite']==='Lax','cookie privacy flags retained');
        guardCheck((stripos($headers,'; secure')!==false)===$expected && stripos($headers,'HttpOnly')!==false && stripos($headers,'SameSite=Lax')!==false,'actual Set-Cookie flags');
    }
    echo "PASS: $checks global-guard/HTTPS HTTP checks; temporary account tables and sessions, no real actions or deliveries.\n";
} finally {
    foreach (glob($temporary.'/*') ?: [] as $file) if (is_file($file)) unlink($file);
    rmdir($temporary);
}
