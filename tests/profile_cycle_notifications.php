<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../app/services/NotificationService.php';
$db=openDatabaseConnection(); mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$checks=0;
function cycleNoticeCheck(bool $ok,string $label):void { global $checks; if(!$ok)throw new RuntimeException($label);$checks++; }
try {
 foreach(['user','role','student_profile','student_profile_cycle','student_profile_cycle_assignment','notification','notification_category_preference'] as $table){
  $ddl=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
  $ddl=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1);
  $ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->query($ddl);
 }
 $db->query("INSERT INTO role(role_id,role_prefix) VALUES(1,'Student'),(2,'Faculty')");
 $db->query("INSERT INTO user(user_id,first_name,last_name,password,gender,age,status,role_id) VALUES (1,'Test','One','unused','Other',18,'Active',1),(2,'Test','Two','unused','Other',18,'Active',1),(3,'Test','Three','unused','Other',18,'Inactive',1),(4,'Test','Four','unused','Other',18,'Active',2)");
 $db->query("INSERT INTO student_profile(student_profile_id,user_id,survey_version) VALUES(1,1,1),(2,2,1),(3,3,1),(4,4,1)");
 $db->query("INSERT INTO student_profile_cycle(student_profile_cycle_id,cycle_name,survey_version,status,is_default,opens_at) VALUES(1,'Future update',1,'Active',0,'2099-01-01 08:00:00'),(2,'Draft update',1,'Draft',0,NULL),(3,'Closed update',1,'Closed',0,NULL),(4,'Default',1,'Active',1,NULL)");
 $db->query("INSERT INTO student_profile_cycle_assignment(student_profile_cycle_id,student_profile_id,assignment_status) VALUES(1,1,'Assigned'),(1,2,'InProgress'),(1,3,'Assigned'),(1,4,'Assigned'),(2,1,'Assigned'),(3,1,'Assigned'),(4,1,'Assigned')");
 $service=new NotificationService($db);$model=new StudentProfileCycle($db);
 cycleNoticeCheck(count($model->getMissingAssignmentNotifications())===2,'Only committed active Student assignments eligible');
 $failing=new class($db) extends Notification {
  public function createForUser(int $userId,string $type,string $title,string $message,?string $contentType=null,?int $contentId=null,?string $key=null,bool $visible=true):bool {
   throw new RuntimeException('Injected notification failure');
  }
 };
 $property=new ReflectionProperty($service,'notification');$property->setValue($service,$failing);
 cycleNoticeCheck($service->recoverStudentProfileNotifications()['failed']===2,'Failures reported');
 cycleNoticeCheck(count($model->getMissingAssignmentNotifications())===2,'Assignments retain retry intent after failure');
 $property->setValue($service,new Notification($db));
 cycleNoticeCheck($service->recoverStudentProfileNotifications(1)['created']===1,'Bounded recovery creates one');
 cycleNoticeCheck($service->recoverStudentProfileNotifications(1)['created']===1,'Next batch recovers remaining recipient');
 cycleNoticeCheck($service->recoverStudentProfileNotifications()['eligible']===0,'Successful reminders no longer pending');
 cycleNoticeCheck((int)$db->query('SELECT COUNT(*) n FROM notification')->fetch_assoc()['n']===2,'No duplicate notifications');
 $items=$service->getForUser(1);
 cycleNoticeCheck($items[0]['action_url']==='index.php?page=student_profile_survey','Reminder opens survey');
 cycleNoticeCheck(str_contains($items[0]['message'],'2099'),'Future opening appears in message');
 $dupe=$service->notifyStudentProfileCycleAssigned([1,1],1,'Future update');
 cycleNoticeCheck($dupe['created']===0 && $dupe['duplicates']===1,'Concurrent/stale retry deduplicates');
 $db->query('DELETE FROM notification');
 $db->query("UPDATE student_profile_cycle_assignment SET assignment_status='Completed' WHERE student_profile_cycle_id=1 AND student_profile_id=1");
 $db->query("UPDATE student_profile_cycle_assignment SET assignment_status='Exempt' WHERE student_profile_cycle_id=1 AND student_profile_id=2");
 cycleNoticeCheck($service->recoverStudentProfileNotifications()['eligible']===0,'Completed and exempt assignments do not receive stale reminders');
 $db->query("UPDATE student_profile_cycle_assignment SET assignment_status='Assigned' WHERE student_profile_cycle_id=1 AND student_profile_id=1");
 $db->query("INSERT INTO notification_category_preference(user_id,notification_category,system_enabled,email_enabled,browser_push_enabled) VALUES(1,'reminders',0,0,0)");
 cycleNoticeCheck($service->recoverStudentProfileNotifications()['created']===1,'Muted reminder still deduplicated');
 cycleNoticeCheck((int)$db->query('SELECT in_system_visible FROM notification')->fetch_assoc()['in_system_visible']===0,'Reminder preference respected');
 $guard=file_get_contents(__DIR__.'/../index.php');preg_match('/\$allowedDuringStudentSurvey = \[(.*?)\];/s',$guard,$match);
 cycleNoticeCheck(str_contains($match[1],"'notifications'") && str_contains($match[1],"'notification_open'") && !str_contains($match[1],"'news'"),'Guard permits notification access without releasing content access');
 echo "PASS: $checks profile reminder checks; temporary tables, no email or push.\n";
} finally { $db->close(); }
