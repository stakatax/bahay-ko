<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/olshco-placement-http-' . bin2hex(random_bytes(8));
mkdir($temporary, 0700);
$cgi = dirname(PHP_BINARY) . '/php-cgi' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
$bootstrap = '<?php require ' . var_export($root.'/config/security.php',true) . '; startSecureSession(); '
    . 'require ' . var_export($root.'/app/controllers/StudentPlacementController.php',true) . '; '
    . '$_SESSION=["user_id"=>1,"role"=>getenv("PLACEMENT_ROLE"),"csrf_token"=>"fixture-csrf-token"]; '
    . '$controller=new StudentPlacementController(); if(getenv("PLACEMENT_ROLE")==="Admin")$controller->review();else $controller->save();';
file_put_contents($temporary.'/controller.php', $bootstrap);
$checks = 0;
try {
    foreach (['Student','Admin'] as $role) {
        foreach (['GET','POST'] as $method) {
            $environment = array_merge(getenv(), ['REDIRECT_STATUS'=>'1','GATEWAY_INTERFACE'=>'CGI/1.1',
                'SERVER_PROTOCOL'=>'HTTP/1.1','SERVER_NAME'=>'localhost','SERVER_PORT'=>'80',
                'REQUEST_METHOD'=>$method,'SCRIPT_FILENAME'=>$temporary.'/controller.php','SCRIPT_NAME'=>'/controller.php',
                'CONTENT_LENGTH'=>'0','HTTP_ACCEPT'=>'application/json','PLACEMENT_ROLE'=>$role]);
            $process = proc_open([$cgi,'-d','cgi.force_redirect=0','-d','session.save_path='.$temporary,
                '-d','display_errors=0'], [['pipe','r'],['pipe','w'],['pipe','w']],$pipes,$temporary,$environment);
            fclose($pipes[0]); $output=stream_get_contents($pipes[1]); fclose($pipes[1]);
            $errors=stream_get_contents($pipes[2]); fclose($pipes[2]); $exit=proc_close($process);
            if ($exit || trim($errors)) throw new RuntimeException('Placement controller CGI failed: ' . $errors);
            [$headers,$body] = preg_split('/\r?\n\r?\n/', $output, 2);
            $expected = $method === 'GET' ? 405 : 419;
            if (!preg_match('/^Status:\s*'.$expected.'/mi',$headers) || !is_array(json_decode($body,true))) {
                throw new RuntimeException('Placement method/CSRF protection failed: '.$role.'/'.$method);
            }
            $checks++;
        }
    }
    echo "PASS: $checks placement method/CSRF HTTP checks; no model writes or deliveries.\n";
} finally {
    foreach (glob($temporary.'/*') ?: [] as $file) if (is_file($file)) unlink($file);
    rmdir($temporary);
}
