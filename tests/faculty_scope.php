<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../app/services/FacultyScopeService.php';
require_once __DIR__ . '/../app/services/PostService.php';
require_once __DIR__ . '/../app/services/SurveyService.php';
require_once __DIR__ . '/../app/services/UserManagementService.php';
require_once __DIR__ . '/../app/services/ContentWorkspaceService.php';
require __DIR__ . '/../config/dbconnect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

trait ScopeReviewTripwire {
    public array $fixtureTargets = [];
    public int $writeCalls = 0;
    public function __construct() {}
    public function getTargets(int $id): array { return $this->fixtureTargets; }
    public function submitForReview(int $id, int $userId): bool {
        $this->writeCalls++;
        throw new LogicException('Authorized review write reached.');
    }
}
class ScopeReviewAnnouncement extends Announcement { use ScopeReviewTripwire; }
class ScopeReviewEvent extends Event { use ScopeReviewTripwire; }
class ScopeReviewDocument extends Document { use ScopeReviewTripwire; }
class ScopeReviewSurvey extends Survey { use ScopeReviewTripwire; }

$checks = 0;
function scopeCheck(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
function scopeDenies(callable $operation, string $label): void {
    try { $operation(); }
    catch (DomainException | InvalidArgumentException $exception) { scopeCheck(true, $label); return; }
    throw new RuntimeException('FAIL: expected denial: ' . $label);
}
function scopeModel(string $class, mysqli $connection): object {
    $model = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(BaseModel::class, 'conn'))->setValue($model, $connection);
    return $model;
}
function scopeInject(object $object, string $field, mixed $value): void {
    (new ReflectionProperty($object, $field))->setValue($object, $value);
}

try {
    require_once __DIR__ . '/../database/migrations/029_parent_child_record.php';
    $childDdl=str_replace('CREATE TABLE ', 'CREATE TEMPORARY TABLE ', parentChildRecordSql());
    $childDdl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $childDdl);
    $childDdl=preg_replace('/,\s*\)/', "\n)", $childDdl);
    $conn->query($childDdl);
    // Capture academic metadata before shadowing tables. No existing user records are copied.
    $roles = $conn->query('SELECT role_id,role_prefix FROM role')->fetch_all(MYSQLI_ASSOC);
    $catalog = [];
    foreach (['department','education_level','academic_program','grade_level','section'] as $table) {
        $catalog[$table] = $conn->query('SELECT * FROM ' . $table)->fetch_all(MYSQLI_ASSOC);
    }
    foreach (array_merge(array_keys($catalog), ['role','user','parent_student']) as $table) {
        $definition = $conn->query('SHOW CREATE TABLE ' . $table)->fetch_assoc()['Create Table'];
        $definition = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $definition, 1);
        $definition = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $definition);
        $definition = preg_replace('/,\n\)/', "\n)", $definition);
        $conn->query($definition);
        foreach ($catalog[$table] ?? [] as $row) {
            unset($row['academic_program_scope_id']); // Generated section column.
            $columns = array_keys($row);
            $stmt = $conn->prepare('INSERT INTO ' . $table . ' (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')');
            $values = array_values($row);
            $stmt->bind_param(str_repeat('s', count($values)), ...$values);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Role IDs are read-only metadata also used by the original builders on their own connections.
    $roleIds = [];
    foreach ($roles as $role) {
        $roleIds[$role['role_prefix']] = (int) $role['role_id'];
        $stmt = $conn->prepare('INSERT INTO role (role_id,role_prefix) VALUES (?,?)');
        $stmt->bind_param('is', $role['role_id'], $role['role_prefix']); $stmt->execute(); $stmt->close();
    }
    $college = array_values(array_filter($catalog['department'], fn($d) => strtoupper($d['department_code']) === 'COLLEGE'))[0];
    $ibed = array_values(array_filter($catalog['department'], fn($d) => strtoupper($d['department_code']) === 'IBED'))[0];
    $collegeLevel = array_values(array_filter($catalog['education_level'], fn($e) => $e['department_id'] === $college['department_id']))[0];
    $programs = array_values(array_filter($catalog['academic_program'], fn($p) => $p['education_level_id'] === $collegeLevel['education_level_id'] && $p['status'] === 'Active'));
    $levels = array_values(array_filter($catalog['education_level'], fn($e) => $e['department_id'] === $ibed['department_id'] && $e['status'] === 'Active'));
    scopeCheck(count($programs) >= 2 && count($levels) >= 3, 'College and IBED metadata available');
    $collegeScope = ['department_id'=>(int)$college['department_id'], 'education_level_id'=>(int)$collegeLevel['education_level_id'], 'academic_program_id'=>(int)$programs[0]['academic_program_id']];
    $insertActor = function(int $id, string $role, array $scope = [], string $status = 'Active') use ($conn,$roleIds): void {
        $stmt = $conn->prepare("INSERT INTO user (user_id,first_name,last_name,password,gender,age,status,role_id,department_id,education_level_id,academic_program_id) VALUES (?, 'Scope', 'Fixture', 'not-a-login-hash', 'Other', 30, ?, ?, ?, ?, ?)");
        $roleId=$roleIds[$role]; $department=$scope['department_id']??null; $education=$scope['education_level_id']??null; $program=$scope['academic_program_id']??null;
        $stmt->bind_param('isiiii',$id,$status,$roleId,$department,$education,$program); $stmt->execute(); $stmt->close();
    };
    $insertActor(1,'Admin'); $insertActor(2,'Faculty',$collegeScope); $insertActor(3,'Student',$collegeScope);
    $insertActor(4,'Faculty',$collegeScope,'Inactive'); $insertActor(5,'Faculty',['department_id'=>$collegeScope['department_id']]);
    $user = scopeModel(User::class,$conn);
    $policy = new FacultyScopeService($user);
    $data = ['audience_scope'=>'custom','target_roles'=>['Student','Parent'],'workflow_action'=>'submit_review','audience_scopes'=>[[]]];
    $_SESSION = ['user_id'=>2,'role'=>'Faculty','department_id'=>999];
    scopeCheck($policy->forUser(2)===$collegeScope,'Fresh assignment overrides stale session department');
    foreach ([0,3,4,5,9999] as $id) scopeDenies(fn()=>$policy->forUser($id),'Invalid/ineligible/incomplete actor '.$id);
    $normalized=$policy->prepareSubmission($data,2)['audience_scopes'][0];
    foreach ($collegeScope as $key=>$value) scopeCheck($normalized[$key]===$value,'College locks '.$key);
    foreach (['schoolwide',null,'invalid',[]] as $mode) scopeDenies(fn()=>$policy->prepareSubmission(array_replace($data,['audience_scope'=>$mode]),2),'Faculty broad or invalid audience denied');
    foreach (['publish','approve','scheduled'] as $action) scopeDenies(fn()=>$policy->prepareSubmission(array_replace($data,['workflow_action'=>$action]),2),'Faculty publishing bypass denied');
    foreach (['department_id','education_level_id','academic_program_id','grade_level_id','section_id'] as $field) {
        foreach ([-1,'abc',[],999999] as $value) {
            scopeDenies(fn()=>$policy->prepareSubmission(array_replace($data,['audience_scopes'=>[[$field=>$value]]]),2),'Invalid/outside '.$field);
        }
    }
    scopeDenies(fn()=>$policy->prepareSubmission(array_replace($data,['audience_scopes'=>[['academic_program_id'=>$programs[1]['academic_program_id']]]]),2),'Other College program denied');
    scopeDenies(fn()=>$policy->prepareSubmission(array_replace($data,['audience_scopes'=>[[],['education_level_id'=>$levels[0]['education_level_id']]]]),2),'Mixed-scope request denied');
    scopeDenies(fn()=>$policy->prepareSubmission(array_replace($data,['audience_scopes'=>['invalid']]),2),'Malformed scope row denied');
    scopeDenies(fn()=>$policy->prepareSubmission(array_replace($data,['audience_scopes'=>'invalid']),2),'Malformed scopes denied');
    $legacy=$data; unset($legacy['audience_scopes']);
    scopeCheck($policy->prepareSubmission($legacy,2)['audience_scopes'][0]['academic_program_id']===$collegeScope['academic_program_id'],'Legacy form remains scoped');
    $policy->assertSavedTargets([$normalized],2); scopeCheck(true,'Valid saved draft allowed');
    scopeDenies(fn()=>$policy->assertSavedTargets([],2),'Untargeted saved draft denied');
    scopeDenies(fn()=>$policy->assertSavedTargets([['department_id'=>$collegeScope['department_id']]],2),'Previously broad saved draft denied');
    // Exercise both real target builders. Their academic validation performs SELECTs only.
    $post=(new ReflectionClass(PostService::class))->newInstanceWithoutConstructor();
    scopeInject($post,'announcement',scopeModel(Announcement::class,$conn)); scopeInject($post,'facultyScope',$policy);
    $survey=(new ReflectionClass(SurveyService::class))->newInstanceWithoutConstructor(); scopeInject($survey,'facultyScope',$policy);
    foreach ([$post,$survey] as $service) {
        $build=new ReflectionMethod($service,'buildTargets');
        $targets=$build->invoke($service,$data);
        scopeCheck(count($targets)===2,'Real builder creates role-specific targets');
        foreach($targets as $target) foreach($collegeScope as $key=>$value) scopeCheck($target[$key]===$value,'Real builder persists College scope');
        scopeDenies(fn()=>$build->invoke($service,array_replace($data,['audience_scope'=>'schoolwide'])),'Real builder schoolwide bypass denied');
        scopeDenies(fn()=>$build->invoke($service,array_replace($data,['audience_scopes'=>[['academic_program_id'=>$programs[1]['academic_program_id']]]])),'Real builder cross-program bypass denied');
    }
    // Whole-program targeting must work even before sections are configured.
    foreach ($programs as $program) {
        $programId=(int)$program['academic_program_id'];
        $conn->query('UPDATE user SET academic_program_id='.$programId.' WHERE user_id=2');
        foreach ([$post,$survey] as $service) {
            $targets=(new ReflectionMethod($service,'buildTargets'))->invoke($service,$data);
            scopeCheck($targets[0]['academic_program_id']===$programId,'All active College programs can be targeted without selecting sections');
        }
    }
    $conn->query('UPDATE user SET academic_program_id='.$collegeScope['academic_program_id'].' WHERE user_id=2');
    foreach ($levels as $offset=>$level) {
        $id=10+$offset;
        $assignment=['department_id'=>(int)$ibed['department_id'],'education_level_id'=>(int)$level['education_level_id'],'academic_program_id'=>null];
        $insertActor($id,'Faculty',$assignment); $_SESSION=['user_id'=>$id,'role'=>'Faculty'];
        $scoped=$policy->prepareSubmission($data,$id)['audience_scopes'][0];
        scopeCheck($scoped['education_level_id']===$assignment['education_level_id'],'IBED locks '.$level['education_level_name']);
        foreach($levels as $other) if($other['education_level_id']!==$level['education_level_id']) {
            scopeDenies(fn()=>$policy->prepareSubmission(array_replace($data,['audience_scopes'=>[['education_level_id'=>$other['education_level_id']]]]),$id),'Cross-IBED level denied');
        }
        $grade=array_values(array_filter($catalog['grade_level'],fn($g)=>$g['education_level_id']===$level['education_level_id'] && $g['status']==='Active'))[0];
        $section=array_values(array_filter($catalog['section'],fn($s)=>$s['grade_level_id']===$grade['grade_level_id'] && $s['status']==='Active'))[0];
        $narrow=array_replace($data,['audience_scopes'=>[['grade_level_id'=>$grade['grade_level_id'],'section_id'=>$section['section_id']]]]);
        foreach([$post,$survey] as $service) {
            $targets=(new ReflectionMethod($service,'buildTargets'))->invoke($service,$narrow);
            scopeCheck($targets[0]['education_level_id']===$assignment['education_level_id'],'Real builder permits narrower IBED grade/section');
        }
    }
    $_SESSION=['user_id'=>1,'role'=>'Admin'];
    foreach([$post,$survey] as $service) {
        $build=new ReflectionMethod($service,'buildTargets');
        $targets=$build->invoke($service,array_replace($data,['audience_scope'=>'schoolwide','workflow_action'=>'publish']));
        scopeCheck($targets[0]['department_id']===null && $targets[0]['education_level_id']===null,'Admin retains schoolwide');
        $targets=$build->invoke($service,array_replace($data,['audience_scopes'=>[$collegeScope]]));
        scopeCheck($targets[0]['academic_program_id']===$collegeScope['academic_program_id'],'Admin retains specific recipients');
    }
    $_SESSION=['user_id'=>2,'role'=>'Admin'];
    scopeDenies(fn()=>$policy->prepareSubmission($data,2),'Stale Admin session cannot publish as Faculty');
    $_SESSION=['user_id'=>2,'role'=>'Faculty'];
    $workspace=(new ReflectionClass(ContentWorkspaceService::class))->newInstanceWithoutConstructor();
    scopeInject($workspace,'facultyScope',$policy);
    foreach(['announcement'=>ScopeReviewAnnouncement::class,'event'=>ScopeReviewEvent::class,'document'=>ScopeReviewDocument::class,'survey'=>ScopeReviewSurvey::class] as $type=>$class) {
        $model=new $class(); scopeInject($workspace,$type,$model);
        foreach([[],[['department_id'=>$collegeScope['department_id']]], [array_replace($normalized,['academic_program_id'=>(int)$programs[1]['academic_program_id']])]] as $badTargets) {
            $model->fixtureTargets=$badTargets;
            scopeDenies(fn()=>$workspace->submitForReview($type,1,2),'Workspace rejects existing out-of-scope '.$type.' draft');
            scopeCheck($model->writeCalls===0,'No workflow write on denied draft');
        }
        $model->fixtureTargets=[$normalized];
        try { $workspace->submitForReview($type,1,2); }
        catch(LogicException $exception) { scopeCheck($exception->getMessage()==='Authorized review write reached.','Allowed draft reaches original review workflow'); }
        scopeCheck($model->writeCalls===1,'Exactly one review write attempted');
    }
    // Authorization denial must precede uploads and all model writes on every create/edit path.
    foreach(['createAnnouncement','updateAnnouncement','createEvent','updateEvent','createDocument','updateDocument'] as $method) {
        $bad=array_replace($data,['audience_scope'=>'schoolwide']);
        $args=[$bad,[],2,'Fixture',$conn]; if(str_starts_with($method,'update')) array_unshift($args,1);
        scopeDenies(fn()=>(new ReflectionMethod($post,$method))->invokeArgs($post,$args),'Pre-write rejection '.$method);
    }
    scopeDenies(fn()=>$survey->createSurvey(array_replace($data,['audience_scope'=>'schoolwide']),2),'Survey create pre-write rejection');
    scopeDenies(fn()=>$survey->updateSurvey(1,array_replace($data,['audience_scope'=>'schoolwide']),2),'Survey update pre-write rejection');
    // Exercise real assignment persistence against the shadow user table, including an identical resubmission.
    $management=(new ReflectionClass(UserManagementService::class))->newInstanceWithoutConstructor(); scopeInject($management,'user',$user);
    scopeDenies(fn()=>$management->updateFacultyAssignment(2,3,$collegeScope),'Student cannot assign Faculty');
    scopeDenies(fn()=>$management->updateFacultyAssignment(3,1,$collegeScope),'Faculty endpoint cannot modify Students');
    scopeDenies(fn()=>$management->updateFacultyAssignment(0,1,$collegeScope),'Empty managed ID denied');
    scopeDenies(fn()=>$management->updateFacultyAssignment(2,1,array_replace($collegeScope,['academic_program_id'=>null])),'Missing College program denied');
    $updatedScope=array_replace($collegeScope,['academic_program_id'=>(int)$programs[1]['academic_program_id']]);
    foreach([1,2] as $attempt) {
        $saved=$management->updateFacultyAssignment(2,1,$updatedScope);
        scopeCheck((int)$saved['academic_program_id']===$updatedScope['academic_program_id'],'Assignment saves/reloads on attempt '.$attempt);
    }
    scopeCheck($policy->forUser(2)===$updatedScope,'Assignment change applies without relogin');
    scopeDenies(fn()=>$policy->assertSavedTargets([$normalized],2),'Old program draft denied after reassignment');
    $ibedScope=['department_id'=>(int)$ibed['department_id'],'education_level_id'=>(int)$levels[1]['education_level_id'],'academic_program_id'=>null];
    $saved=$management->updateFacultyAssignment(2,1,$ibedScope);
    scopeCheck($saved['academic_program_id']===null && (int)$saved['education_level_id']===$ibedScope['education_level_id'],'IBED assignment clears College program');
    foreach([$collegeScope,$ibedScope] as $index=>$assignment) {
        $created=$management->provisionFaculty(1,array_merge(['first_name'=>'Fixture','last_name'=>'Faculty','email'=>'scope-fixture-'.$index.'@example.invalid','gender'=>'Other','birthdate'=>'1990-01-01'], $assignment));
        scopeCheck((int)$created['education_level_id']===$assignment['education_level_id'],'New Faculty education saved');
        scopeCheck(($created['academic_program_id']===null?null:(int)$created['academic_program_id'])===$assignment['academic_program_id'],'New Faculty program saved');
        scopeCheck((int)$created['must_change_password']===1,'New Faculty still requires password change');
        scopeCheck($created['gender']===null && $created['age']===null && $created['birthdate']===null, 'Admin cannot supply Faculty personal data');
        scopeCheck($created['middle_name']===null && $created['name_suffix']===null, 'Personal names await Faculty input');
    }
    $conn->query("UPDATE user SET status='Inactive' WHERE user_id=2");
    scopeDenies(fn()=>$policy->forUser(2),'Deactivation enforced without relogin');
    $conn->query("UPDATE education_level SET status='Inactive' WHERE education_level_id=".$ibedScope['education_level_id']);
    scopeDenies(fn()=>$policy->validateAssignment($ibedScope),'Inactive education assignment denied');
    echo 'PASS: ' . $checks . " Faculty scope checks; all fixture writes used connection-local temporary tables.\n";
} finally {
    $conn->close();
}
