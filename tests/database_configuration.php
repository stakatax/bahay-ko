<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
$checks=0;
function databaseCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException('FAIL: '.$label);
    $checks++;
}
function databaseRejects(array $environment, array $local=[]): void {
    try { resolveDatabaseConfiguration($environment,$local); }
    catch(RuntimeException $exception) { databaseCheck(true,'Invalid configuration rejected'); return; }
    throw new RuntimeException('FAIL: invalid configuration accepted');
}
$local=['environment'=>'development','host'=>'localhost','username'=>'root','password'=>'','database'=>'olshcodb'];
$production=['OLSHCO_APP_ENV'=>'production','OLSHCO_DB_HOST'=>'db.example.invalid','OLSHCO_DB_USER'=>'hub_runtime','OLSHCO_DB_PASSWORD'=>'  fixture-secret  ','OLSHCO_DB_NAME'=>'hub'];
$c=resolveDatabaseConfiguration($production,$local);
databaseCheck($c['password']==='  fixture-secret  ','Password whitespace preserved');
databaseCheck($c['port']===3306,'Default port');
databaseCheck($c['username']==='hub_runtime','Environment takes precedence');
databaseCheck(resolveDatabaseConfiguration([],$local)['username']==='root','Explicit development config retained');
databaseRejects([]);
databaseRejects(['OLSHCO_APP_ENV'=>'production'],$local);
databaseRejects(['OLSHCO_DB_HOST'=>'db.example.invalid'],$local);
foreach(['root','ROOT',' root '] as $username) databaseRejects(array_replace($production,['OLSHCO_DB_USER'=>$username]));
foreach(['','   '] as $password) databaseRejects(array_replace($production,['OLSHCO_DB_PASSWORD'=>$password]));
foreach(['',0,-1,65536,'abc',[]] as $port) databaseRejects(array_replace($production,['OLSHCO_DB_PORT'=>$port]));
foreach(['OLSHCO_DB_HOST','OLSHCO_DB_USER','OLSHCO_DB_PASSWORD','OLSHCO_DB_NAME'] as $key) {
    $missing=$production;unset($missing[$key]);databaseRejects($missing,$local);
}
databaseRejects(array_replace($production,['OLSHCO_APP_ENV'=>'unexpected']));
$c=resolveDatabaseConfiguration(array_replace($production,['OLSHCO_DB_PORT'=>'3307']));
databaseCheck($c['port']===3307,'Custom port');
// Read-only connection smoke test using this workstation's actual configuration.
$connection=openDatabaseConnection();
databaseCheck($connection->character_set_name()==='utf8mb4','Explicit connection charset');
databaseCheck((int)$connection->query('SELECT 1 AS ready')->fetch_assoc()['ready']===1,'Connection available');
$connection->close();

$log=tempnam(sys_get_temp_dir(),'olshco-db-test-');
if($log===false) throw new RuntimeException('Cannot allocate isolated test log.');
try {
    $entry=realpath(__DIR__.'/../config/dbconnect.php');
    $environment=getenv();
    foreach(['OLSHCO_APP_ENV','OLSHCO_DB_HOST','OLSHCO_DB_USER','OLSHCO_DB_PASSWORD','OLSHCO_DB_NAME','OLSHCO_DB_PORT'] as $key) unset($environment[$key]);
    $environment=array_merge($environment,$production,['OLSHCO_DB_USER'=>'root','OLSHCO_DB_PASSWORD'=>'fixture-secret-do-not-expose']);
    $run=static function(array $command,array $env) use($log): array {
        $process=proc_open($command,[['pipe','r'],['pipe','w'],['pipe','w']],$pipes,null,$env);
        if(!is_resource($process)) throw new RuntimeException('Cannot start verification subprocess.');
        fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);
        return [proc_close($process),$out,$err];
    };
    [$code,$out,$err]=$run([PHP_BINARY,'-d','display_errors=1','-d','log_errors=1','-d','error_log='.$log,$entry],$environment);
    databaseCheck($code===1 && str_contains($err,'Database service is unavailable.'),'CLI fails safely with nonzero exit');
    databaseCheck(!str_contains($out.$err,'fixture-secret') && !str_contains($out.$err,'db.example.invalid'),'CLI does not disclose connection details');
    $cgi=dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'php-cgi'.(PHP_OS_FAMILY==='Windows'?'.exe':'');
    foreach(['text/html','application/json'] as $accept) {
        $env=array_merge($environment,['REDIRECT_STATUS'=>'1','GATEWAY_INTERFACE'=>'CGI/1.1','SERVER_PROTOCOL'=>'HTTP/1.1','SERVER_NAME'=>'localhost','SERVER_PORT'=>'80','REQUEST_METHOD'=>'GET','SCRIPT_FILENAME'=>$entry,'SCRIPT_NAME'=>'/dbconnect.php','HTTP_ACCEPT'=>$accept]);
        unset($env['HTTP_X_REQUESTED_WITH']);
        [$code,$out,$err]=$run([$cgi,'-d','cgi.force_redirect=0','-d','display_errors=1','-d','log_errors=1','-d','error_log='.$log],$env);
        databaseCheck($code===0 && $err==='','CGI failure handled cleanly');
        databaseCheck(str_contains($out,'Status: 503'),'Database failure returns HTTP 503');
        databaseCheck(str_contains($out,'Cache-Control: no-store'),'Failure is not cached');
        databaseCheck(!str_contains($out,'fixture-secret') && !str_contains($out,'db.example.invalid') && !str_contains($out,'Stack trace'),'Public response is safe');
        if($accept==='application/json') {
            $parts=preg_split("/\r?\n\r?\n/",$out,2);$json=json_decode($parts[1],true,512,JSON_THROW_ON_ERROR);
            databaseCheck($json['success']===false,'JSON clients receive valid error object');
        }
    }
    databaseCheck(!str_contains(file_get_contents($log),'fixture-secret'),'Server log excludes secret');
    echo "PASS: {$checks} database configuration checks; no database writes.\n";
} finally { unlink($log); }
