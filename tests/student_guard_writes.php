<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../app/services/StudentProfileService.php';
require_once __DIR__.'/../config/database.php';
class GuardWriteConnection extends mysqli {
    public int $writes=0;
    public function prepare(string $sql):mysqli_stmt|false {
        if(preg_match('/^\s*(INSERT|UPDATE|DELETE)/i',$sql))$this->writes++;
        return parent::prepare($sql);
    }
}
$c=databaseConfiguration();$db=new GuardWriteConnection($c['host'],$c['username'],$c['password'],$c['database'],$c['port']);$db->set_charset('utf8mb4');
$checks=0;
function guardWriteCheck(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks++;}
try{
 foreach(['user','role','student_profile','student_profile_interest','content_interest','student_profile_survey_version','student_profile_cycle','student_profile_cycle_assignment'] as $t){
  $ddl=$db->query('SHOW CREATE TABLE `'.$t.'`')->fetch_assoc()['Create Table'];
  $ddl=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1);
  $ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->query($ddl);
 }
 $db->query("INSERT INTO role (role_id,role_prefix) VALUES (1,'Student'),(2,'Faculty')");
 $db->query("INSERT INTO user (user_id,first_name,last_name,password,gender,age,status,role_id) VALUES
 (1,'Fixture','Student','unused','Other',18,'Active',1),(2,'Fixture','Faculty','unused','Other',30,'Active',2),(3,'Fixture','Inactive','unused','Other',18,'Inactive',1)");
 $db->query("INSERT INTO student_profile_survey_version (survey_version,version_name,status) VALUES(1,'Fixture','Active')");
 $db->query("INSERT INTO student_profile_cycle (student_profile_cycle_id,cycle_name,academic_year,survey_version,status,is_default) VALUES(1,'Default','Fixture',1,'Active',1),(2,'Update','Fixture',1,'Active',0)");
 $model=new StudentProfile($db);
 $service=(new ReflectionClass(StudentProfileService::class))->newInstanceWithoutConstructor();
 (new ReflectionProperty($service,'profile'))->setValue($service,$model);
 $db->writes=0;
 guardWriteCheck($service->requiresSurveyCompletion(1),'New Student still requires survey');
 guardWriteCheck($db->writes===2,'Missing profile and default assignment created');
 $db->writes=0;guardWriteCheck($service->requiresSurveyCompletion(1)&&$db->writes===0,'Repeat request has no initialization write');
 foreach(['Assigned'=>true,'InProgress'=>true,'Completed'=>false,'Exempt'=>false] as $state=>$required){
  $db->query("UPDATE student_profile_cycle_assignment SET assignment_status='$state'");$db->writes=0;
  guardWriteCheck($service->requiresSurveyCompletion(1)===$required&&$db->writes===0,'Existing status preserved without writes: '.$state);
 }
 $db->query("INSERT INTO student_profile_cycle_assignment (student_profile_cycle_id,student_profile_id,assignment_status) SELECT 2,student_profile_id,'Assigned' FROM student_profile WHERE user_id=1");
 $db->writes=0;guardWriteCheck($service->requiresSurveyCompletion(1)&&$db->writes===0,'New non-default cycle detected immediately');
 $db->query("UPDATE student_profile_cycle SET opens_at=DATE_ADD(NOW(),INTERVAL 1 DAY) WHERE student_profile_cycle_id=2");
 guardWriteCheck(!$service->requiresSurveyCompletion(1),'Future cycle excluded');
 $db->query("UPDATE student_profile_cycle SET opens_at=NULL,status='Closed' WHERE student_profile_cycle_id=2");
 guardWriteCheck(!$service->requiresSurveyCompletion(1),'Closed cycle excluded');
 foreach([0,-1,2,3,999] as $id){$db->writes=0;try{$service->requiresSurveyCompletion($id);throw new LogicException('Denied actor accepted');}catch(InvalidArgumentException|RuntimeException $e){guardWriteCheck($db->writes===0,'Invalid/inactive/non-Student denied before writes');}}
 $db->query('DELETE FROM student_profile_cycle_assignment');$db->query("UPDATE student_profile SET survey_completion_status='Completed'");
 $db->writes=0;guardWriteCheck(!$service->requiresSurveyCompletion(1)&&$db->writes===1,'Missing assignment inherits existing completion, without profile upsert');
 echo "PASS: $checks Student guard checks; temporary tables only.\n";
}finally{$db->close();}
