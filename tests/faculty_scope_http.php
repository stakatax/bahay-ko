<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/dbconnect.php';
$cgi = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'php-cgi' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
$temporary = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'olshco-faculty-http-' . bin2hex(random_bytes(12));
if (!mkdir($temporary, 0700)) { throw new RuntimeException('Unable to create isolated test directory.'); }
$checks = 0;
function scopeHttpCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
try {
    $root = dirname(__DIR__);
    $entry = $temporary . DIRECTORY_SEPARATOR . 'controller.php';
    $bootstrap = '<?php require_once ' . var_export($root . '/app/controllers/UserManagementController.php', true)
        . '; require_once ' . var_export($root . '/app/controllers/PostController.php', true) . ';'
        . <<<'BOOT'
$_SESSION = ['user_id'=>(int)getenv('SCOPE_TEST_ACTOR'), 'role'=>($_GET['fixture_role'] ?? 'Guest'), 'csrf_token'=>'fixture-csrf'];
$action=getenv('SCOPE_TEST_ACTION');
if ($action==='assignment') { (new UserManagementController())->updateFacultyAssignment(); exit; }
if ($action==='posting') { $viewData=(new PostController())->create(); }
if ($action==='management') { $viewData=(new UserManagementController())->index(); }
BOOT;
    $bootstrap .= 'if ($action === "posting") { require ' . var_export($root . '/pages/postings.php',true) . '; }';
    $bootstrap .= 'if ($action === "management") { require ' . var_export($root . '/pages/manage_users.php',true) . '; }';
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
            'SCOPE_TEST_ACTOR' => (string) $actor,
            'SCOPE_TEST_ACTION' => $action
        ]);
        $process = proc_open([
            $cgi, '-d', 'cgi.force_redirect=0',
            '-d', 'session.save_path=' . $temporary,
            '-d', 'display_errors=0'
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

    $actors=$conn->query("SELECT u.user_id,r.role_prefix FROM user u JOIN role r ON r.role_id=u.role_id WHERE u.status='Active' AND r.role_prefix IN ('Admin','Faculty','Student','Parent') ORDER BY u.user_id")->fetch_all(MYSQLI_ASSOC);
    $ids=[]; foreach($actors as $actor) { $ids[$actor['role_prefix']] ??= (int)$actor['user_id']; }
    scopeHttpCheck(isset($ids['Admin'],$ids['Faculty']), 'Staff metadata available');
    $admin=$ids['Admin'];
    [$status,$headers]=$request('assignment','GET',$admin,['fixture_role'=>'Admin']);
    scopeHttpCheck($status===405 && stripos($headers,'Allow: POST')!==false,'Assignment GET rejected');
    foreach([[],['csrf_token'=>'wrong'],['csrf_token'=>['fixture-csrf']]] as $post) {
        [$status]=$request('assignment','POST',$admin,['fixture_role'=>'Admin'],$post);
        scopeHttpCheck($status===419,'Missing/invalid CSRF rejected');
    }
    foreach(['Faculty','Student','Parent'] as $role) {
        if(!isset($ids[$role])) continue;
        [$status,$headers]=$request('assignment','POST',$ids[$role],['fixture_role'=>$role],['csrf_token'=>'fixture-csrf']);
        scopeHttpCheck($status===302 && str_contains($headers,'page=news'),'Non-Admin assignment access rejected');
    }
    [$status,$headers]=$request('assignment','POST',0,[],['csrf_token'=>'fixture-csrf']);
    scopeHttpCheck($status===302 && str_contains($headers,'page=login'),'Anonymous assignment rejected');
    foreach(['', '0', '-1', 'abc', ['1']] as $id) {
        [$status,$headers]=$request('assignment','POST',$admin,['fixture_role'=>'Admin'],['csrf_token'=>'fixture-csrf','confirm_faculty_assignment'=>'1','user_id'=>$id]);
        scopeHttpCheck($status===302 && str_contains($headers,'error='),'Invalid managed ID rejected');
        scopeHttpCheck(!str_contains($headers,'success='),'No success reported for invalid ID');
    }
    [$status,$headers]=$request('assignment','POST',$admin,['fixture_role'=>'Admin'],['csrf_token'=>'fixture-csrf','user_id'=>$ids['Faculty']]);
    scopeHttpCheck($status===302 && str_contains($headers,'Confirm'),'Confirmation required before assignment');
    foreach(['Admin','Faculty'] as $role) {
        [$status,$headers,$body]=$request('posting','GET',$ids[$role],['fixture_role'=>$role]);
        scopeHttpCheck($status===200,'Posting form renders for '.$role);
        scopeHttpCheck(!preg_match('/Warning:|Fatal error:|Notice:/',$body),'Posting render has no PHP errors');
        $dom=new DOMDocument(); libxml_use_internal_errors(true); $dom->loadHTML($body); libxml_clear_errors();
        $xpath=new DOMXPath($dom);
        scopeHttpCheck($xpath->query('//input[@name="audience_scope" and @value="schoolwide"]')->length===($role==='Admin'?1:0),'Schoolwide is Administrator-only');
        if($role==='Faculty') scopeHttpCheck($xpath->query('//input[@name="audience_scope" and @value="custom" and @checked]')->length===1,'Faculty starts with custom recipients');
    }
    [$status,$headers,$body]=$request('management','GET',$admin,['fixture_role'=>'Admin','user_id'=>$ids['Faculty']]);
    scopeHttpCheck($status===200,'Faculty management renders');
    scopeHttpCheck(!preg_match('/Warning:|Fatal error:|Notice:/',$body),'Management render has no PHP errors');
    $dom=new DOMDocument(); $dom->loadHTML($body); libxml_clear_errors(); $xpath=new DOMXPath($dom);
    foreach(['facultyAssignmentForm','facultyProvisionForm'] as $form) {
        foreach(['department_id','education_level_id','academic_program_id'] as $field) {
            scopeHttpCheck($xpath->query('//form[@id="'.$form.'"]//select[@name="'.$field.'"]')->length===1,$form.' has '.$field);
        }
        scopeHttpCheck($xpath->query('//form[@id="'.$form.'"]//input[@name="csrf_token"]')->length===1,$form.' includes CSRF');
    }
    echo "PASS: {$checks} Faculty HTTP/render checks; no existing record changes.\n";
} finally {
    $conn->close();
    // Only the uniquely created test directory and its CGI/session files are removed.
    foreach(glob($temporary . DIRECTORY_SEPARATOR . '*') ?: [] as $file) { if(is_file($file)) unlink($file); }
    rmdir($temporary);
}
