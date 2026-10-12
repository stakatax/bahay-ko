<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../app/services/StudentProfileCycleService.php';
require_once __DIR__.'/../app/services/StudentProfileService.php';
$db=openDatabaseConnection();mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$checks=0;
function lifecycleCheck(bool $ok,string $label):void {global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
try {
 foreach(['role','user','department','education_level','academic_program','grade_level','section','student_profile','student_profile_survey_version','student_profile_question','student_profile_cycle','student_profile_cycle_scope','student_profile_cycle_assignment','student_profile_placement_change','student_profile_response','student_profile_consent','student_profile_consent_definition','notification','notification_category_preference'] as $table){
  $ddl=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
  $ddl=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1);$ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);$ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->query($ddl);
 }
 $db->query("INSERT INTO role(role_id,role_prefix) VALUES(1,'Student'),(2,'Admin'),(3,'Faculty')");
 $db->query("INSERT INTO user(user_id,first_name,last_name,password,gender,age,status,role_id) VALUES(1,'Fixture','Student','unused','Other',18,'Active',1),(2,'Fixture','Admin','unused','Other',30,'Active',2),(3,'Fixture','Faculty','unused','Other',30,'Active',3),(4,'Fixture','Inactive','unused','Other',18,'Inactive',1)");
 $db->query("INSERT INTO department(department_id,department_name) VALUES(1,'IBED')");
 $db->query("INSERT INTO education_level(education_level_id,department_id,education_level_name) VALUES(1,1,'Junior High'),(2,1,'Senior High')");
 $db->query("INSERT INTO grade_level(grade_level_id,education_level_id,grade_level_name) VALUES(1,1,'Grade 10'),(2,2,'Grade 11')");
 $db->query("INSERT INTO academic_program(academic_program_id,education_level_id,program_name,program_code,program_type) VALUES(1,2,'STEM','STEM','Strand')");
 $db->query("INSERT INTO section(section_id,grade_level_id,academic_program_id,section_name) VALUES(1,1,NULL,'A'),(2,2,1,'B'),(3,1,NULL,'C')");
 $db->query('UPDATE user SET department_id=1,education_level_id=1,grade_level_id=1,section_id=1 WHERE user_id=1');
 $db->query("INSERT INTO student_profile_survey_version(survey_version,version_name,status) VALUES(1,'Default','Active'),(2,'Update questionnaire','Draft')");
 $db->query("INSERT INTO student_profile_question(question_key,section_key,section_label,question_text,response_type,is_required,survey_version) VALUES('device','access','Access','Which device?','ShortText',1,2)");
 $db->query("INSERT INTO student_profile_cycle(student_profile_cycle_id,cycle_name,survey_version,status,is_default) VALUES(1,'Default',1,'Active',1),(2,'Update',2,'Draft',0),(3,'Overlap',2,'Draft',0)");
 $db->query("INSERT INTO student_profile_cycle_scope(student_profile_cycle_id,scope_type) VALUES(1,'AllStudents'),(2,'AllStudents'),(3,'AllStudents')");
 $cycleModel=new StudentProfileCycle($db);
 $cycles=(new ReflectionClass(StudentProfileCycleService::class))->newInstanceWithoutConstructor();
 (new ReflectionProperty($cycles,'cycles'))->setValue($cycles,$cycleModel);
 (new ReflectionProperty($cycles,'management'))->setValue($cycles,new StudentProfileManagement($db));
 (new ReflectionProperty($cycles,'notifications'))->setValue($cycles,new NotificationService($db));
 $student=(new ReflectionClass(StudentProfileService::class))->newInstanceWithoutConstructor();
 (new ReflectionProperty($student,'profile'))->setValue($student,new StudentProfile($db));
 lifecycleCheck($cycles->previewCycle(2)['target_count']===1,'Only active Students previewed');
 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM notification')->fetch_assoc()['n']===0,'Draft creates no reminder');
 $activated=$cycles->activateCycle(2,2);
 lifecycleCheck($activated['status']==='Active' && $activated['target_count']===1,'Real activation commits selected Student');
 lifecycleCheck(abs(strtotime($activated['due_at'])-strtotime($activated['activated_at'])-14*86400)<2,'Blank deadline defaults to fourteen days at activation');
 lifecycleCheck($activated['notifications']['created']===1,'Activation creates matching reminder');
 lifecycleCheck($student->requiresSurveyCompletion(1),'Student required to answer new update');
 try {$cycles->activateCycle(2,2);throw new LogicException('Duplicate activation accepted');}
 catch(RuntimeException $e){lifecycleCheck(str_contains($e->getMessage(),'Only Draft'),'Duplicate activation rejected');}
 try {$cycles->activateCycle(3,2);throw new LogicException('Overlapping activation accepted');}
 catch(RuntimeException $e){lifecycleCheck(str_contains($e->getMessage(),'unfinished'),'Overlapping update rejected');}
 lifecycleCheck($cycleModel->findCycle(3)['status']==='Draft','Failed activation rolls back cycle');
 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM student_profile_cycle_assignment WHERE student_profile_cycle_id=3')->fetch_assoc()['n']===0,'Failed activation leaves no assignments');
 foreach ([2,3,4,0,-1,99999] as $actor) {
  try {$student->saveSurvey($actor,['device'=>'Tampered'],[],'access',true);throw new LogicException('Unauthorized actor accepted');}
  catch(InvalidArgumentException|RuntimeException $e){lifecycleCheck(true,'Non-Student/inactive/invalid actor cannot submit');}
 }
 $assignmentId=(int)$db->query('SELECT student_profile_cycle_assignment_id FROM student_profile_cycle_assignment WHERE student_profile_cycle_id=2')->fetch_assoc()['student_profile_cycle_assignment_id'];
 $profileModel=new StudentProfile($db);
 foreach ([[99999,2],[$assignmentId,1],[0,2]] as [$forgedAssignment,$forgedVersion]) {
  try {$profileModel->saveSurveyProgress(1,$forgedVersion,$forgedAssignment,[],[],[],'access',true);throw new LogicException('Forged assignment accepted');}
  catch(InvalidArgumentException|RuntimeException $e){lifecycleCheck(true,'Invalid assignment/version rejected');}
 }
 $db->query("INSERT INTO user(user_id,first_name,last_name,password,gender,age,status,role_id) VALUES(5,'Another','Student','unused','Other',18,'Active',1)");
 $db->query("INSERT INTO student_profile(user_id,survey_version) VALUES(5,2)");
 try {$profileModel->saveSurveyProgress(5,2,$assignmentId,[],[],[],'access',true);throw new LogicException('Cross-Student assignment accepted');}
 catch(RuntimeException $e){lifecycleCheck(str_contains($e->getMessage(),'no longer active'),'Another Student cannot use the first Student assignment');}
 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM student_profile_response')->fetch_assoc()['n']===0,'Rejected saves write no responses');
 $student->saveSurvey(1,[],[],'access',false);
 lifecycleCheck($student->requiresSurveyCompletion(1),'Partial save keeps requirement');
 try {$student->saveSurvey(1,[],[],'access',true);throw new LogicException('Missing answer accepted');}
 catch(InvalidArgumentException $e){lifecycleCheck(true,'Required answer enforced');}
 require_once __DIR__.'/../app/models/StudentPlacement.php';
 $placements = new StudentPlacement($db);
 try {$student->saveSurvey(1,['device'=>'Laptop'],[],'access',true);throw new LogicException('Confirmation bypass accepted');}
 catch(DomainException $e){lifecycleCheck(true,'Account confirmation required before final completion');}
 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM student_profile_response')->fetch_assoc()['n']===0,'Confirmation failure rolls back responses');
 $newPlacement=['department_id'=>1,'education_level_id'=>2,'academic_program_id'=>1,'grade_level_id'=>2,'section_id'=>2];
 foreach ([['section_id'=>1],['grade_level_id'=>1],['education_level_id'=>1],['department_id'=>999],['section_id'=>[2]]] as $invalid) {
  try {$placements->stage(1,$assignmentId,array_replace($newPlacement,$invalid));throw new LogicException('Invalid placement accepted');}
  catch(InvalidArgumentException $e){lifecycleCheck(true,'Invalid hierarchy/scalar rejected');}
 }
 try {$placements->stage(5,$assignmentId,$newPlacement);throw new LogicException('Other Student assignment accepted');}
 catch(DomainException $e){lifecycleCheck(true,'Placement staging rejects cross-Student IDOR');}
 $placements->stage(1,$assignmentId,$newPlacement);
 lifecycleCheck((int)$db->query('SELECT section_id FROM user WHERE user_id=1')->fetch_assoc()['section_id']===1,'Staged placement does not change account');
 lifecycleCheck($placements->pageData(1)['draft']['section_id']===2,'Staged placement survives resume');
 $db->query('UPDATE user SET section_id=3 WHERE user_id=1');
 try {$student->saveSurvey(1,['device'=>'Laptop'],[],'access',true);throw new LogicException('Concurrent school correction overwritten');}
 catch(DomainException $e){lifecycleCheck(true,'Stale progress does not overwrite a school correction');}
 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM student_profile_response')->fetch_assoc()['n']===0,'Concurrent correction conflict rolls back responses');
 $db->query('UPDATE user SET section_id=1 WHERE user_id=1');
 $db->query("UPDATE section SET status='Inactive' WHERE section_id=2");
 try {$student->saveSurvey(1,['device'=>'Laptop'],[],'access',true);throw new LogicException('Inactive section accepted');}
 catch(InvalidArgumentException $e){lifecycleCheck(true,'Final submission revalidates active catalog');}
 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM student_profile_response')->fetch_assoc()['n']===0,'Invalid final placement rolls back responses');
 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM student_profile_placement_change')->fetch_assoc()['n']===0,'Failed completion writes no history');
 $db->query("UPDATE section SET status='Active' WHERE section_id=2");
 $db->query("UPDATE student_profile_cycle SET due_at=DATE_SUB(NOW(),INTERVAL 1 DAY) WHERE student_profile_cycle_id=2");
 $student->saveSurvey(1,['device'=>'Laptop'],[],'access',true);
 lifecycleCheck(!$student->requiresSurveyCompletion(1),'Overdue Active update permits late completion');
 lifecycleCheck((int)$db->query('SELECT section_id FROM user WHERE user_id=1')->fetch_assoc()['section_id']===2,'Final completion applies placement');
 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM student_profile_placement_change')->fetch_assoc()['n']===1,'Placement history persisted');
 foreach ([1,3,4] as $actor) {
  try {$placements->reviewDirectory($actor);throw new LogicException('Unauthorized reviewer accepted');}
  catch(DomainException $e){lifecycleCheck(true,'Only active Admin may review placement');}
 }
 $historyId=(int)$db->query('SELECT student_profile_placement_change_id FROM student_profile_placement_change')->fetch_assoc()['student_profile_placement_change_id'];
 $placements->review(2,$historyId,'correct',['review_notes'=>'Verified fixture class list','department_id'=>1,'education_level_id'=>1,'academic_program_id'=>'','grade_level_id'=>1,'section_id'=>1]);
 lifecycleCheck((int)$db->query('SELECT section_id FROM user WHERE user_id=1')->fetch_assoc()['section_id']===1,'Admin correction applies verified placement');
 lifecycleCheck($db->query('SELECT review_status FROM student_profile_placement_change')->fetch_assoc()['review_status']==='Corrected','Correction retains reported history and review record');
 lifecycleCheck(!$student->requiresSurveyCompletion(1),'Completion releases Student guard');
 $data=$student->getSurveyData(1);
 lifecycleCheck($data['survey_version']===2,'Responses remain on assigned questionnaire version');
 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM student_profile_response')->fetch_assoc()['n']===1,'Answer persisted once');
 lifecycleCheck($cycles->closeCycle(2,2)['status']==='Closed','Closing succeeds');
 try {$profileModel->saveSurveyProgress(1,2,$assignmentId,[],[],[],'access',true);throw new LogicException('Closed assignment accepted');}
 catch(RuntimeException $e){lifecycleCheck(str_contains($e->getMessage(),'no longer active'),'Closed update rejects stale submission');}

 lifecycleCheck((int)$db->query('SELECT COUNT(*) n FROM student_profile_response')->fetch_assoc()['n']===1,'Closing preserves answer history');
 lifecycleCheck((new NotificationService($db))->recoverStudentProfileNotifications()['eligible']===0,'Closed update produces no further reminders');
 echo "PASS: $checks profile lifecycle checks; temporary tables only, no email/push.\n";
}finally{$db->close();}
