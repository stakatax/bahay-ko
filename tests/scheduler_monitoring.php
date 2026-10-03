<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$temp = sys_get_temp_dir() . '/olshco-worker-' . bin2hex(random_bytes(10));
$checks = 0;
function workerCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
$directories = ['', '/scripts', '/app', '/app/services', '/logs'];
foreach ($directories as $directory) { mkdir($temp . $directory, 0700); }
$files = [];
$write = static function (string $relative, string $content) use ($temp, &$files): void {
    $path = $temp . '/' . $relative; file_put_contents($path, $content); $files[$path] = true;
};
try {
    $write('scripts/dispatch_browser_push.php', file_get_contents($root . '/scripts/dispatch_browser_push.php'));
    $write('scripts/dispatch_browser_push_hidden.vbs', file_get_contents($root . '/scripts/dispatch_browser_push_hidden.vbs'));
    $write('app/services/ContentReleaseService.php', <<<'PHP'
<?php
class ContentReleaseService {
    public function processPendingReleases(): array {
        if (getenv('WORKER_TEST') === 'throw') { throw new RuntimeException('SECRET_FIXTURE_PASSWORD', 123); }
        return ['total_released'=>0, 'notifications'=>['created'=>0,'failed'=>(int)(getenv('WORKER_TEST')==='publication')]];
    }
}
PHP);
    $write('app/services/NotificationService.php', '<?php class NotificationService { public function recoverStudentProfileNotifications(int $limit): array { return ["created"=>0,"failed"=>(int)(getenv("WORKER_TEST")==="profile")]; } public function processUpcomingEventReminders(): array { return ["created"=>0,"failed"=>(int)(getenv("WORKER_TEST")==="reminder")]; } }');
    foreach (['EmailDeliveryService'=>'email', 'BrowserPushDeliveryService'=>'push'] as $class=>$mode) {
        $write('app/services/' . $class . '.php', '<?php class ' . $class . ' { public function dispatch(int $queue, int $send): array { return ["queued"=>0,"sent"=>0,"failed"=>(int)(getenv("WORKER_TEST")===' . var_export($mode,true) . ')]; } }');
    }
    $run = static function (array $command, string $mode = 'success', array $extra = []) use ($temp): array {
        $process = proc_open($command, [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, $temp,
            array_merge(getenv(), ['WORKER_TEST'=>$mode], $extra));
        if (!is_resource($process)) { throw new RuntimeException('Could not start isolated worker'); }
        fclose($pipes[0]); $out=stream_get_contents($pipes[1]); fclose($pipes[1]);
        $err=stream_get_contents($pipes[2]); fclose($pipes[2]);
        return [proc_close($process), $out, $err];
    };
    $command = [PHP_BINARY, '-d', 'sys_temp_dir=' . $temp, $temp . '/scripts/dispatch_browser_push.php'];
    foreach (['success'=>0, 'publication'=>1, 'reminder'=>1, 'profile'=>1, 'email'=>1, 'push'=>1, 'throw'=>1] as $mode=>$expected) {
        [$code,$out,$err] = $run($command, $mode);
        workerCheck($code === $expected, 'Worker exit status: ' . $mode);
        workerCheck(!str_contains($out.$err, 'SECRET_FIXTURE_PASSWORD'), 'Safe worker output');
        $log = file_get_contents($temp . '/logs/browser-push-dispatch.log');
        workerCheck(!str_contains($log, 'SECRET_FIXTURE_PASSWORD'), 'Safe worker log');
        if ($mode === 'publication') { workerCheck(str_contains($out, 'Notification failures: 1'), 'Publication failure in summary'); }
        if ($mode === 'reminder') { workerCheck(str_contains($out, 'Reminder failures: 1'), 'Reminder failure in summary'); }
        if ($mode === 'throw') { workerCheck(str_contains($err, 'RuntimeException (code 123)'), 'Exception diagnostic preserves class/code'); }
    }
    $lock = fopen($temp . '/olshco_browser_push_dispatch.lock', 'c');
    flock($lock, LOCK_EX);
    try {
        [$code,$out] = $run($command, 'throw');
        workerCheck($code === 75 && str_contains($out, 'already running'), 'Overlap is distinct from successful completion');
    } finally { flock($lock, LOCK_UN); fclose($lock); }
    // Rotation and failure use only this test's log directory.
    file_put_contents($temp . '/logs/browser-push-dispatch.log', str_repeat('x', 5*1024*1024));
    [$code] = $run($command);
    workerCheck($code === 0 && is_file($temp . '/logs/browser-push-dispatch.log.1'), 'Existing log rotation retained');
    unlink($temp . '/logs/browser-push-dispatch.log');
    mkdir($temp . '/logs/browser-push-dispatch.log');
    try {
        [$code,$out,$err] = $run($command);
        workerCheck($code === 1 && str_contains($err, 'summary log could not be written'), 'Logging failure cannot report success');
    } finally { rmdir($temp . '/logs/browser-push-dispatch.log'); }
    $cgi = dirname(PHP_BINARY) . '/php-cgi' . (PHP_OS_FAMILY==='Windows'?'.exe':'');
    [$code,$out,$err] = $run([$cgi, '-d', 'cgi.force_redirect=0'], 'throw',
        ['REDIRECT_STATUS'=>'1','GATEWAY_INTERFACE'=>'CGI/1.1','REQUEST_METHOD'=>'GET',
            'SCRIPT_FILENAME'=>$temp.'/scripts/dispatch_browser_push.php','SCRIPT_NAME'=>'/worker.php']);
    workerCheck(str_contains($out, 'Status: 404') && !str_contains($out, 'FAILED'), 'Worker still rejects HTTP access');
    if (PHP_OS_FAMILY === 'Windows') {
        // Execute the real launcher against an inert worker in the temporary project.
        $write('scripts/dispatch_browser_push.php', '<?php usleep(600000); file_put_contents(dirname(__DIR__)."/finished.json",json_encode(["cwd"=>getcwd()])); exit(7);');
        $launcher = [getenv('SystemRoot') . '/System32/wscript.exe', '//B', '//Nologo', $temp . '/scripts/dispatch_browser_push_hidden.vbs'];
        $start = microtime(true);
        [$code] = $run($launcher);
        workerCheck($code === 7, 'Hidden launcher returns PHP failure exit code');
        workerCheck(microtime(true)-$start >= 0.5 && is_file($temp.'/finished.json'), 'Launcher waits for child completion');
        $finished = json_decode(file_get_contents($temp.'/finished.json'),true);
        workerCheck(str_replace('\\','/',$finished['cwd']) === str_replace('\\','/',$temp), 'Launcher sets project working directory');
        $write('scripts/dispatch_browser_push.php', '<?php exit(0);');
        [$code] = $run($launcher);
        workerCheck($code === 0, 'Launcher preserves success status');
        unlink($temp.'/scripts/dispatch_browser_push.php');
        [$code] = $run($launcher);
        workerCheck($code === 2, 'Missing worker has distinct launcher failure status');
    }
    echo "PASS: $checks scheduler monitoring checks; isolated workers and logs, no database or delivery calls.\n";
} finally {
    foreach (array_keys($files) as $path) { if (is_file($path)) { unlink($path); } }
    foreach (['finished.json','olshco_browser_push_dispatch.lock','logs/browser-push-dispatch.log','logs/browser-push-dispatch.log.1'] as $file) {
        if (is_file($temp.'/'.$file)) { unlink($temp.'/'.$file); }
    }
    foreach (array_reverse($directories) as $directory) { rmdir($temp . $directory); }
}
