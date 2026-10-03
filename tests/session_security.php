<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../app/services/SessionSecurityService.php';
require_once __DIR__.'/support/session_fixture.php';
$connection=openDatabaseConnection();
$checks=0;
function sessionCheck(bool $ok,string $label):void { global $checks;if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks++; }
try {
    $original=sessionFixture($connection);
    $service=new SessionSecurityService(new SessionAccount($connection));
    foreach([1=>'Admin',2=>'Faculty',3=>'Student',4=>'Parent'] as $id=>$role) {
        $session=array_replace($original,['user_id'=>$id,'role_id'=>$id,'role'=>$role]);
        sessionCheck($service->refresh($session)==='valid','Active '.$role.' allowed');
        sessionCheck($session['csrf_token']==='fixture-token','Valid session retains CSRF');
    }
    foreach(['Inactive','Pending','Rejected'] as $status) {
        $connection->query("UPDATE user SET status='".$status."' WHERE user_id=1");
        $session=$original;sessionCheck($service->refresh($session)==='revoked' && $session===[],'Unavailable account clears entire session');
    }
    $connection->query("UPDATE user SET status='Active' WHERE user_id=1");
    foreach([2,3,4] as $roleId) {
        $connection->query('UPDATE user SET role_id='.$roleId.' WHERE user_id=1');
        $session=$original;sessionCheck($service->refresh($session)==='revoked','Changed Admin role loses access');
    }
    $connection->query('UPDATE user SET role_id=1 WHERE user_id=1');
    $session=$original;unset($session['auth_credential_version']);sessionCheck($service->refresh($session)==='revoked','Legacy session requires fresh login');
    foreach(['auth_credential_version'=>[], 'role'=>'Faculty','role_id'=>999,'user_id'=>999,'user_id_array'=>[1]] as $field=>$value) {
        $session=$original;$session[$field==='user_id_array'?'user_id':$field]=$value;
        sessionCheck($service->refresh($session)==='revoked','Malformed/stale session rejected');
    }
    $connection->query("INSERT INTO department (department_id,department_name) VALUES (10,'Fixture division')");
    $connection->query("INSERT INTO education_level (education_level_id,department_id,education_level_name) VALUES (11,10,'Fixture level')");
    $connection->query("INSERT INTO academic_program (academic_program_id,education_level_id,program_name,program_code,program_type) VALUES (12,11,'Fixture program','TEST','Program')");
    $connection->query("INSERT INTO grade_level (grade_level_id,education_level_id,grade_level_name) VALUES (13,11,'Fixture year')");
    $connection->query("INSERT INTO section (section_id,grade_level_id,academic_program_id,section_name) VALUES (14,13,12,'Fixture section')");
    $connection->query('UPDATE user SET department_id=10,education_level_id=11,academic_program_id=12,grade_level_id=13,section_id=14,must_change_password=1 WHERE user_id=1');
    $session=$original;sessionCheck($service->refresh($session)==='valid','Assignment update preserves eligible login');
    foreach(['department_id'=>10,'education_level_id'=>11,'academic_program_id'=>12,'grade_level_id'=>13,'section_id'=>14] as $field=>$value) sessionCheck($session[$field]===$value,'Fresh '.$field);
    sessionCheck($session['academic_program_name']==='Fixture program' && $session['academic_program_type']==='Program','Display metadata refreshed');
    sessionCheck($session['must_change_password']===true,'New password-change requirement refreshed');
    $connection->query('UPDATE user SET academic_program_id=NULL,must_change_password=0 WHERE user_id=1');
    $service->refresh($session);sessionCheck($session['academic_program_id']===null && $session['academic_program_name']===null && !$session['must_change_password'],'Removed scope and cleared requirement refreshed');
    $connection->query("UPDATE user SET password='a-different-fixture-hash' WHERE user_id=1");
    foreach([1,2] as $_) { $session=$original;sessionCheck($service->refresh($session)==='revoked','Password change revokes every old session'); }
    $session=$original;$session['auth_credential_version']=hash('sha256','a-different-fixture-hash');sessionCheck($service->refresh($session)==='valid','Fresh post-password-change marker accepted');
    $connection->query('DELETE FROM user WHERE user_id=1');$session=$original;sessionCheck($service->refresh($session)==='revoked','Deleted account rejected');
    $session=['csrf_token'=>'guest-token'];sessionCheck($service->refresh($session)==='guest' && $session['csrf_token']==='guest-token','Public guest CSRF preserved');
    $connection->close();$session=$original;$failed=false;
    try{$service->refresh($session);}catch(Throwable $exception){$failed=true;}
    sessionCheck($failed && $session===$original,'Lookup error propagates without claiming valid or destroying session');
    echo "PASS: {$checks} session security checks; temporary tables only.\n";
} finally { if(isset($connection) && $connection instanceof mysqli) { try{$connection->close();}catch(Error $exception){} } }
