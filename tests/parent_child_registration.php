<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/migrations/029_parent_child_record.php';
require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/services/AccountApprovalService.php';
require_once __DIR__ . '/../app/services/ContentAudienceService.php';
$checks=0;
function childCheck(bool $ok,string $label):void { global $checks; if (!$ok) { throw new RuntimeException('FAIL: '.$label); } $checks++; }
function childDeny(callable $call,string $label):void {
    try { $call(); } catch (InvalidArgumentException|DomainException $e) { childCheck(true,$label); return; }
    catch (RuntimeException $e) { if (!str_starts_with($e->getMessage(),'Unable')) { childCheck(true,$label); return; } throw $e; }
    throw new RuntimeException('FAIL: expected denial: '.$label);
}
function childInject(object $object,string $property,mixed $value):void { (new ReflectionProperty($object,$property))->setValue($object,$value); }
$db=openDatabaseConnection();
try {
    foreach (['role','user','department','education_level','academic_program','grade_level','section','parent_student',
        'legal_document_version','user_legal_acceptance','request_rate_limit','announcement_target','event_target','document_target','survey_target','notification_preference'] as $table) {
        $ddl=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
        $ddl=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1);
        $ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);
        $ddl=preg_replace('/,\s*\)/',"\n)",$ddl);
        $db->query($ddl);
    }
    $ddl=str_replace('CREATE TABLE ','CREATE TEMPORARY TABLE ',parentChildRecordSql());
    $ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);
    $ddl=preg_replace('/,\s*\)/',"\n)",$ddl); $db->query($ddl);
    $db->query("INSERT INTO role (role_id,role_prefix) VALUES (1,'Admin'),(2,'Student'),(3,'Parent'),(4,'Faculty')");
    $db->query("INSERT INTO department (department_id,department_name) VALUES (1,'IBED')");
    $db->query("INSERT INTO education_level (education_level_id,department_id,education_level_name) VALUES (1,1,'Elementary'),(2,1,'Junior High')");
    $db->query("INSERT INTO grade_level (grade_level_id,education_level_id,grade_level_name) VALUES (1,1,'Grade 2'),(2,2,'Grade 7')");
    $db->query("INSERT INTO section (section_id,grade_level_id,section_name) VALUES (1,1,'A'),(2,2,'B')");
    $db->query("INSERT INTO user (user_id,first_name,last_name,password,gender,age,status,role_id) VALUES (1,'Admin','Fixture','unused','Other',30,'Active',1),(2,'Faculty','Fixture','unused','Other',30,'Active',4)");
    $db->query("INSERT INTO legal_document_version (legal_document_version_id,document_type,version,title,content,effective_at,status) VALUES (1,'Terms','test','Fixture','Fixture',NOW(),'Active'),(2,'Privacy','test','Fixture','Fixture',NOW(),'Active')");
    $user=new User($db);$records=new ParentChildRecord($db);
    $auth=(new ReflectionClass(AuthService::class))->newInstanceWithoutConstructor();
    childInject($auth,'user',$user);
    childInject($auth,'rateLimits',new RequestRateLimitService(new RequestRateLimit($db),str_repeat('r',64)));
    $notifications=new class extends NotificationService {
        public function __construct() {}
        public function notifyRegistrationSubmitted(int $id,string $name,string $role):array { return []; }
        public function notifyRegistrationDecision(int $id,string $name,string $decision,?string $notes=null):bool { return true; }
    };
    childInject($auth,'notifications',$notifications);
    $approval=(new ReflectionClass(AccountApprovalService::class))->newInstanceWithoutConstructor();
    childInject($approval,'user',$user);childInject($approval,'notifications',$notifications);
    $data=['role_type'=>'Parent','first_name'=>'Parent','last_name'=>'Fixture','email'=>'parent@example.test',
        'gender'=>'Other','birthdate'=>'1990-01-01','password'=>'FixturePass123','password_confirmation'=>'FixturePass123',
        'accept_terms'=>'1','accept_privacy'=>'1','relationship'=>'Mother','child_no_account'=>'1',
        'child_name'=>'Child Fixture','child_student_id'=>'','child_section_id'=>'1','child_reason'=>'assistance','child_reason_details'=>'Needs help using the app'];
    foreach (['child_name'=>'','child_section_id'=>'99999','child_reason'=>'invalid','relationship'=>'invalid'] as $key=>$bad) {
        childDeny(fn()=>$auth->register(array_replace($data,[$key=>$bad])),'invalid '.$key);
    }
    foreach (['', '0', '-1', '1abc', '999999999999999999999'] as $invalidId) {
        childDeny(fn()=>$records->validate(array_replace($data,['child_section_id'=>$invalidId])),'invalid section syntax');
    }
    childDeny(fn()=>$records->validate(array_replace($data,['child_name'=>['tampered']])),'array field rejected');
    childCheck((int)$db->query('SELECT COUNT(*) n FROM user')->fetch_assoc()['n']===2,'invalid claims do not create accounts');
    $parent=$auth->register($data);
    childCheck($user->findById($parent)['status']==='Pending','Parent starts Pending');
    childCheck($records->find($parent)['status']==='Pending','child claim starts Pending');
    childCheck((int)$db->query('SELECT COUNT(*) n FROM user WHERE role_id=2')->fetch_assoc()['n']===0,'no fake Student account created');
    childCheck((int)$db->query('SELECT COUNT(*) n FROM user_legal_acceptance')->fetch_assoc()['n']===2,'legal acceptance saved');
    childCheck($user->findById($parent)['section_id']===null,'claimed class not copied to Parent cache');
    childCheck($approval->getRegistrationForReview($parent)['child_record']['reason']==='assistance','Admin review contains reason');
    $audience=new ContentAudienceService(new ContentAudience($db));
    foreach (['announcement','event','document','survey'] as $type) {
        $db->query("INSERT INTO {$type}_target ({$type}_id,role_id,section_id) VALUES (10,3,1),(11,3,2),(12,2,1)");
        childCheck($audience->filterForUser($type,$type.'_id',[[$type.'_id'=>10]],$parent)===[],'pending denied '.$type);
    }
    childDeny(fn()=>$user->approveRegistration($parent,2,'Fixture checked'),'Faculty cannot approve');
    childDeny(fn()=>$approval->approveRegistration($parent,1,''),'verification note required');
    childCheck($records->find($parent)['status']==='Pending' && $user->findById($parent)['status']==='Pending','failed approval rolls back both states');
    $claimBefore = $records->find($parent);
    childDeny(fn()=>$records->update($parent,1,array_replace($data,['child_action'=>'update','confirm_child_review'=>'1','review_notes'=>'Attempted correction.','child_name'=>'Changed claim','child_section_id'=>'2'])),'pending claim cannot be edited through direct update');
    childCheck($records->find($parent)===$claimBefore,'denied edit preserves submitted claim and history');
    $approval->approveRegistration($parent,1,'Enrollment and relationship checked with test school records.');
    childCheck($records->find($parent)['status']==='Verified' && $user->findById($parent)['status']==='Active','approval commits both states');
    childDeny(fn()=>$user->approveRegistration($parent,1,'repeat'),'duplicate approval denied');
    foreach (['announcement','event','document','survey'] as $type) {
        $visible=$audience->filterForUser($type,$type.'_id',[[$type.'_id'=>10],[$type.'_id'=>11],[$type.'_id'=>12],[$type.'_id'=>13]],$parent);
        childCheck(array_column($visible,$type.'_id')===[10,13],'correct class and schoolwide only '.$type);
    }
    $delivery=new NotificationService($db);$resolve=new ReflectionMethod($delivery,'resolveRecipientIds');
    foreach (['announcement','event','document','survey'] as $type) {
        childCheck(in_array($parent,array_column($resolve->invoke($delivery,$type,10,1),'user_id')),'notifications include verified class '.$type);
        childCheck(!in_array($parent,array_column($resolve->invoke($delivery,$type,11,1),'user_id')),'notifications exclude other class '.$type);
    }
    $update=array_replace($data,['child_action'=>'update','confirm_child_review'=>'1','review_notes'=>'Verified class correction.','child_section_id'=>'2']);
    childDeny(fn()=>$records->update($parent,2,$update),'Faculty cannot edit record');
    childDeny(fn()=>$records->update($parent,1,array_replace($update,['confirm_child_review'=>'0'])),'confirmation required');
    childDeny(fn()=>$records->update(99999,1,$update),'unknown Parent denied');
    $db->query("UPDATE section SET status='Inactive' WHERE section_id=1");
    childCheck($audience->filterForUser('announcement','announcement_id',[['announcement_id'=>13]],$parent)===[],'inactive academic placement removes eligibility');
    $db->query("UPDATE section SET status='Active' WHERE section_id=1");
    $db->query('INSERT INTO notification_preference (user_id,system_enabled,email_enabled,browser_push_enabled) VALUES ('.$parent.',0,0,0)');
    childCheck(!in_array($parent,array_column($resolve->invoke($delivery,'announcement',10,1),'user_id')),'notification channel preferences respected');
    $db->query('UPDATE notification_preference SET system_enabled=1 WHERE user_id='.$parent);
    $records->update($parent,1,$update);
    childCheck($audience->filterForUser('announcement','announcement_id',[['announcement_id'=>10]],$parent)===[],'old class loses access');
    childCheck(count($audience->filterForUser('announcement','announcement_id',[['announcement_id'=>11]],$parent))===1,'new verified class gains access');
    $records->update($parent,1,array_replace($update,['child_action'=>'revoke']));
    childCheck($audience->filterForUser('announcement','announcement_id',[['announcement_id'=>13]],$parent)===[],'revocation removes schoolwide access without another link');
    childCheck(!in_array($parent,array_column($resolve->invoke($delivery,'announcement',13,1),'user_id')),'revocation removes notification eligibility');
    $records->update($parent,1,$update);
    $studentData=array_replace($data,['role_type'=>'Student','email'=>'student@example.test','first_name'=>'Child','student_id'=>'CHILD-1',
        'department_id'=>1,'education_level_id'=>1,'grade_level_id'=>1,'section_id'=>1,'birthdate'=>'2015-01-01']);
    $student=$auth->register($studentData);$approval->approveRegistration($student,1,'Student independently verified.');
    $link=array_replace($update,['child_action'=>'link','link_student_id'=>'CHILD-1']);
    childDeny(fn()=>$records->update($parent,1,array_replace($link,['link_student_id'=>'MISSING'])),'unknown Student cannot link');
    $records->update($parent,1,$link);
    childCheck($records->find($parent)['status']==='Linked','child record retired on link');
    childCheck(count($audience->filterForUser('announcement','announcement_id',[['announcement_id'=>10]],$parent))===1,'linked Student current class takes precedence');
    childCheck($audience->filterForUser('announcement','announcement_id',[['announcement_id'=>11]],$parent)===[],'former independent class no longer grants access');
    childDeny(fn()=>$records->update($parent,1,$link),'duplicate link action denied');
    childCheck((int)$db->query('SELECT COUNT(*) n FROM parent_student')->fetch_assoc()['n']===1,'only one relationship created');
    $db->query("UPDATE user SET status='Inactive' WHERE user_id=".$student);
    childCheck($audience->filterForUser('announcement','announcement_id',[['announcement_id'=>13]],$parent)===[],'inactive linked Student does not revive fallback');
    $db->query("UPDATE user SET status='Active' WHERE user_id=".$student);
    $legacy=$auth->register(array_replace($data,['email'=>'legacy@example.test','child_no_account'=>'','child_student_id'=>'CHILD-1']));
    $approval->approveRegistration($legacy,1,'Existing relationship checked.');
    childCheck($records->find($legacy)===null && count($audience->filterForUser('announcement','announcement_id',[['announcement_id'=>10]],$legacy))===1,'legacy registration and approval still work');
    $reject=$auth->register(array_replace($data,['email'=>'reject@example.test']));
    $approval->rejectRegistration($reject,1,'Unable to verify relationship.');
    childCheck($records->find($reject)['status']==='Rejected' && $user->findById($reject)['status']==='Rejected','rejection updates both states');
    childCheck(count(json_decode($records->find($parent)['review_history'],true))===5,'all successful review decisions retained');
    // Forced legal-write failure must roll back the new Parent and child record together.
    $maxUser=(int)$db->query('SELECT MAX(user_id) n FROM user')->fetch_assoc()['n'];
    $db->query('ALTER TABLE user_legal_acceptance ADD CONSTRAINT fixture_reject_legal CHECK (user_id <= '.$maxUser.')');
    $before=(int)$db->query('SELECT COUNT(*) n FROM user')->fetch_assoc()['n'];
    try { $auth->register(array_replace($data,['email'=>'rollback@example.test'])); throw new LogicException('Expected database failure'); }
    catch (mysqli_sql_exception $e) { childCheck(true,'forced legal failure'); }
    childCheck((int)$db->query('SELECT COUNT(*) n FROM user')->fetch_assoc()['n']===$before,'failed registration rolls back account');
    echo "PASS: $checks Parent/child registration checks. Temporary tables only; no deliveries or existing data changes.\n";
} finally { $db->close(); }
