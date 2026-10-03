<?php
if(PHP_SAPI!=='cli')exit(1);
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/support/session_fixture.php';
require_once __DIR__.'/../database/migrations/030_faculty_personal_profile.php';
require_once __DIR__.'/../app/services/AccountProfileService.php';
require_once __DIR__.'/../app/services/SessionSecurityService.php';
$db=openDatabaseConnection();sessionFixture($db);$checks=0;
function personalCheck(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
$before=$db->query('SELECT * FROM user ORDER BY user_id')->fetch_all(MYSQLI_ASSOC);
migrateFacultyPersonalProfile($db);
personalCheck(!migrateFacultyPersonalProfile($db),'Migration repeat');
personalCheck($before===$db->query('SELECT * FROM user ORDER BY user_id')->fetch_all(MYSQLI_ASSOC),'Existing rows preserved');
$db->query('UPDATE user SET gender=NULL,age=NULL,birthdate=NULL WHERE user_id=2');
$model=new User($db);$service=(new ReflectionClass(AccountProfileService::class))->newInstanceWithoutConstructor();(new ReflectionProperty(AccountProfileService::class,'user'))->setValue($service,$model);
$sessionModel=new SessionAccount($db);$security=new SessionSecurityService($sessionModel);$actor=$sessionModel->findForSession(2);
$session=['user_id'=>2,'role_id'=>2,'role'=>'Faculty','auth_credential_version'=>$actor['credential_version']];
personalCheck($security->refresh($session)==='valid'&&!empty($session['faculty_profile_required']),'Pending profile enforced');
$data=['middle_name'=>'Faculty','name_suffix'=>'Jr.','gender'=>'Other','birthdate'=>'1990-05-12'];
foreach(['invalid_date','future_date','underage','gender','suffix','long_name','password_required','inactive','other_role','invalid_id'] as $case){
 $input=$data;$id=2;
 switch($case){
 case 'invalid_date':$input['birthdate']='1990-02-31';break;
 case 'future_date':$input['birthdate']='2090-01-01';break;
 case 'underage':$input['birthdate']='2020-01-01';break;
 case 'gender':$input['gender']='invalid';break;
 case 'suffix':$input['name_suffix']='invalid';break;
 case 'long_name':$input['middle_name']=str_repeat('x',51);break;
 case 'password_required':$db->query('UPDATE user SET must_change_password=1 WHERE user_id=2');break;
 case 'inactive':$db->query("UPDATE user SET status='Inactive' WHERE user_id=2");break;
 case 'other_role':$id=3;break;
 case 'invalid_id':$id=0;break;
 }
 $denied=false;try{$service->updatePersonalDetails($id,$input);}catch(RuntimeException|InvalidArgumentException $e){$denied=true;}
 personalCheck($denied,'Denied '.$case);
 $db->query("UPDATE user SET must_change_password=0,status='Active' WHERE user_id=2");
}
$row=$model->findById(2);$result=$service->updatePersonalDetails(2,$data+['user_id'=>1,'role_id'=>1,'department_id'=>99]);
personalCheck($result['gender']==='Other'&&$result['birthdate']==='1990-05-12','Details saved/reloaded');
personalCheck((int)$result['role_id']===2&&$result['department_id']===$row['department_id'],'Role/scope tampering ignored');
personalCheck($security->refresh($session)==='valid'&&empty($session['faculty_profile_required']),'Completed profile unlocks session');
$service->updatePersonalDetails(2,array_replace($data,['middle_name'=>'','name_suffix'=>'']));
$r=$model->findById(2);personalCheck($r['middle_name']===null&&$r['name_suffix']===null,'Optional fields clear');
function csrfInput(): string { return '<input type="hidden" name="csrf_token" value="fixture">'; }
foreach (['Admin','Faculty','Student','Parent'] as $role) {
    $viewData=['user'=>['first_name'=>'Fixture','last_name'=>'User','role_prefix'=>$role,'gender'=>null,'birthdate'=>null]];
    ob_start(); include __DIR__.'/../pages/account_profile.php'; $html=ob_get_clean();
    personalCheck(str_contains($html,'data-faculty-personal-form')===($role==='Faculty'),'Profile form visibility: '.$role);
}
echo "Faculty personal profile: {$checks} checks passed; temporary tables only.\n";
