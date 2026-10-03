<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=dirname(__DIR__);$cgi=dirname(PHP_BINARY).'/php-cgi.exe';
$temp=sys_get_temp_dir().'/olshco-parent-http-'.bin2hex(random_bytes(10));
if (!mkdir($temp,0700)) { throw new RuntimeException('Cannot prepare isolated HTTP fixture.'); }
$checks=0;
function parentHttpCheck(bool $ok,string $label):void { global $checks; if (!$ok) { throw new RuntimeException('FAIL: '.$label); } $checks++; }
try {
    $bootstrap='<?php require_once '.var_export($root.'/app/controllers/AccountApprovalController.php',true).';';
    $bootstrap.= <<<'PHPFIXTURE'
$_SESSION=['user_id'=>1,'role'=>getenv('TEST_ROLE'),'csrf_token'=>'fixture-token'];
class ParentHttpService extends AccountApprovalService {
    public function __construct() {}
    public function updateChildRecord(int $parentId,int $adminId,array $data):void {
        echo 'SERVICE_REACHED:'.$parentId;
    }
}
$controller=(new ReflectionClass(AccountApprovalController::class))->newInstanceWithoutConstructor();
(new ReflectionProperty($controller,'service'))->setValue($controller,new ParentHttpService());
$controller->updateChild();
PHPFIXTURE;
    file_put_contents($temp.'/controller.php',$bootstrap);
    $request=static function(string $role,string $method,array $post)use($temp,$cgi):array {
        $body=http_build_query($post);$env=getenv();
        $env=array_merge($env,['REDIRECT_STATUS'=>'1','GATEWAY_INTERFACE'=>'CGI/1.1','SERVER_PROTOCOL'=>'HTTP/1.1',
            'SERVER_NAME'=>'localhost','SERVER_PORT'=>'80','REQUEST_METHOD'=>$method,'SCRIPT_FILENAME'=>$temp.'/controller.php',
            'SCRIPT_NAME'=>'/controller.php','QUERY_STRING'=>'','CONTENT_TYPE'=>'application/x-www-form-urlencoded',
            'CONTENT_LENGTH'=>(string)strlen($body),'TEST_ROLE'=>$role]);
        $process=proc_open([$cgi,'-d','cgi.force_redirect=0','-d','session.save_path='.$temp,'-d','display_errors=0'],
            [['pipe','r'],['pipe','w'],['pipe','w']],$pipes,$temp,$env);
        fwrite($pipes[0],$body);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);
        $err=stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($process);
        parentHttpCheck($exit===0 && trim($err)==='', 'CGI clean exit');
        return [$out,str_contains($out,'SERVICE_REACHED:')];
    };
    $valid=['registration_user_id'=>'55','csrf_token'=>'fixture-token'];
    foreach (['Student','Faculty','Parent',''] as $role) { [$out,$called]=$request($role,'POST',$valid);parentHttpCheck(!$called,'role denied: '.$role); }
    foreach (['GET','HEAD','PUT'] as $method) { [$out,$called]=$request('Admin',$method,$valid);parentHttpCheck(!$called,'method denied: '.$method); }
    foreach (['','wrong'] as $token) { [$out,$called]=$request('Admin','POST',array_replace($valid,['csrf_token'=>$token]));parentHttpCheck(!$called,'CSRF denied'); }
    foreach (['','0','-1','abc','9999999999999999999999999999'] as $id) { [$out,$called]=$request('Admin','POST',array_replace($valid,['registration_user_id'=>$id]));parentHttpCheck(!$called,'invalid ID denied'); }
    [$out,$called]=$request('Admin','POST',$valid);parentHttpCheck($called && str_contains($out,'SERVICE_REACHED:55'),'authorized POST dispatches intended ID');
    echo "PASS: $checks Parent HTTP guard checks. Service stub; no application data changes.\n";
} finally {
    // Only exact generated files inside this private test directory are removed.
    foreach (glob($temp.'/*') ?: [] as $file) { if (is_file($file)) unlink($file); }
    rmdir($temp);
}
