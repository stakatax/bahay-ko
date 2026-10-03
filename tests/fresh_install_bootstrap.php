<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../scripts/bootstrap_fresh_install.php';
require_once __DIR__.'/../app/models/User.php';
require_once __DIR__.'/../app/models/AcademicStructure.php';
require_once __DIR__.'/../app/models/StudentProfile.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$checks=0;
function bootstrapCheck(bool $ok,string $label):void {
    global $checks;
    if(!$ok)throw new RuntimeException('FAIL: '.$label);
    $checks++;
}
function bootstrapRejects(callable $call,string $label):void {
    try{$call();}catch(RuntimeException|InvalidArgumentException $exception){bootstrapCheck(true,$label);return;}
    throw new RuntimeException('FAIL: expected rejection: '.$label);
}
function bootstrapModel(string $class,mysqli $connection):object {
    $model=(new ReflectionClass($class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(BaseModel::class,'conn'))->setValue($model,$connection);return $model;
}
$bundle=json_decode(file_get_contents(__DIR__.'/../database/bootstrap-reference.json'),true,512,JSON_THROW_ON_ERROR);
$config=databaseConfiguration();
if(!in_array($config['host'],['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local rehearsal only.');
$live=openDatabaseConnection();
bootstrapRejects(fn()=>new FreshInstallBootstrap($live,$bundle),'Original application database refused');
$live->close();
$c=new mysqli($config['host'],$config['username'],$config['password'],FreshInstallBootstrap::TARGET,$config['port']);
$c->set_charset('utf8mb4');
$bootstrap=new FreshInstallBootstrap($c,$bundle);
$initial=$bootstrap->inspect();
if($initial['reference_state']!=='empty'||$initial['users']!==0)throw new RuntimeException('Rehearsal requires the still-empty scratch database.');
$admin=['first_name'=>'Bootstrap','last_name'=>'Fixture','email'=>'bootstrap-fixture@example.invalid','password'=>'Aa9!'.bin2hex(random_bytes(18)),'birthdate'=>'1990-01-01','gender'=>'Other'];
bootstrapRejects(fn()=>$bootstrap->stage($admin),'No mutation outside transaction');
foreach(['first_name','last_name','email','password','birthdate','gender'] as $field){$bad=$admin;unset($bad[$field]);bootstrapRejects(fn()=>FreshInstallBootstrap::validateAdministrator($bad),'Missing Administrator field rejected');}
foreach(['password'=>'short','email'=>'invalid','birthdate'=>'2020-02-31','gender'=>'unexpected'] as $field=>$value){$bad=$admin;$bad[$field]=$value;bootstrapRejects(fn()=>FreshInstallBootstrap::validateAdministrator($bad),'Invalid Administrator field rejected');}
try {
    if((int)$c->query("SELECT GET_LOCK('olshco_c5_bootstrap',0) AS acquired")->fetch_assoc()['acquired']!==1)throw new RuntimeException('Bootstrap lock unavailable.');
    $c->begin_transaction();
    $first=$bootstrap->stage($admin);
    bootstrapCheck($first['reference_rows']===221 && $first['administrators']===1 && !$first['repeat'],'Fresh seed staged');
    $user=bootstrapModel(User::class,$c);
    $account=$user->findByIdentifier($admin['email']);
    bootstrapCheck($account['role_prefix']==='Admin' && $account['status']==='Active','Normal user lookup finds initial active Admin');
    bootstrapCheck(password_verify($admin['password'],$account['password']) && $account['password']!==$admin['password'],'Password stored only as a valid hash');
    bootstrapCheck((int)$account['must_change_password']===1,'Initial password change required');
    $legal=$user->getActiveLegalDocumentVersions();
    bootstrapCheck(isset($legal['Terms'],$legal['Privacy']),'Current application legal lookup succeeds');
    $academic=bootstrapModel(AcademicStructure::class,$c)->getPublicCatalog();
    bootstrapCheck(count($academic['education_levels'])===4 && count($academic['academic_programs'])===14,'Public academic catalog loads');
    $profile=bootstrapModel(StudentProfile::class,$c);
    foreach([1,2] as $version)bootstrapCheck(count($profile->getActiveSurveyQuestions($version))>0,'Existing active questionnaire version loads');
    $second=$bootstrap->stage($admin);
    bootstrapCheck($second['repeat'] && (int)$c->query('SELECT COUNT(*) AS n FROM user')->fetch_assoc()['n']===1,'Repeat creates no second Admin');
    bootstrapCheck($user->findByIdentifier($admin['email'])['password']===$account['password'],'Repeat does not reset password');
    $different=$admin;$different['password']='DifferentAa9!'.bin2hex(random_bytes(12));
    bootstrapRejects(fn()=>$bootstrap->stage($different),'Existing password is never reset');
    $c->query('SAVEPOINT operational_guard');
    $c->query("INSERT INTO activity_log (description,action_id,user_id) SELECT 'Bootstrap fixture', MIN(actions.action_id), MIN(user.user_id) FROM actions CROSS JOIN user");
    bootstrapRejects(fn()=>$bootstrap->stage($admin),'Operational records prevent bootstrap');
    $c->query('ROLLBACK TO SAVEPOINT operational_guard');
    $c->query("UPDATE department SET department_name='changed fixture' LIMIT 1");
    bootstrapRejects(fn()=>$bootstrap->stage($admin),'Differing reference data is never overwritten');
    $c->rollback();
    bootstrapCheck($bootstrap->inspect()['reference_state']==='empty','Rehearsal rollback removes all seed data');
    bootstrapCheck((int)$c->query('SELECT COUNT(*) AS n FROM user')->fetch_assoc()['n']===0,'Rehearsal rollback removes fixture Admin');
    // Force a failure late in the insert sequence to verify transaction recovery.
    $invalid=$bundle;$invalid['tables']['student_profile_question']['rows'][0]['survey_version']=999999;
    $broken=new FreshInstallBootstrap($c,$invalid);
    $c->begin_transaction();$failed=false;
    try{$broken->stage($admin);}catch(mysqli_sql_exception $exception){$failed=true;}finally{$c->rollback();}
    bootstrapCheck($failed,'Foreign-key failure detected');
    bootstrapCheck($bootstrap->inspect()['reference_state']==='empty','Late failure leaves no partial seed data');
    // A conflicting reference table must also prevent installation.
    $c->begin_transaction();
    $c->query("INSERT INTO role (role_id,role_prefix) VALUES (99999,'Fixture')");
    bootstrapRejects(fn()=>$bootstrap->inspect(),'Conflicting or partial seed state rejected');
    $c->rollback();
    bootstrapCheck($bootstrap->inspect()['reference_state']==='empty','Scratch remains empty after negative tests');
    echo "PASS: {$checks} bootstrap checks; all fixture writes rolled back; no live database writes.\n";
} finally {
    $c->rollback();$c->query("SELECT RELEASE_LOCK('olshco_c5_bootstrap')");$c->close();
}
