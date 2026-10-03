<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/AuthService.php';
class RequiredPasswordFixture extends User {
    public array $row;
    public int $writes = 0;
    public function __construct() {
        $this->row = ['user_id'=>1,'status'=>'Active','must_change_password'=>1,
            'password'=>password_hash('Temporary!123', PASSWORD_DEFAULT),'role_prefix'=>'Admin'];
    }
    public function findById(int $userId) { return $userId === 1 ? $this->row : null; }
    public function completeRequiredPasswordChange(int $userId, string $passwordHash): bool {
        $this->writes++; $this->row['password']=$passwordHash; $this->row['must_change_password']=0; return true;
    }
}
$checks=0;
function checkRequired(bool $ok, string $why): void {
    global $checks; if (!$ok) throw new LogicException($why); $checks++;
}
function fixtureRequired(): array {
    $model=new RequiredPasswordFixture();
    $service=(new ReflectionClass(AuthService::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(AuthService::class,'user'))->setValue($service,$model);
    return [$service,$model,hash('sha256',$model->row['password'])];
}
foreach (['missing_proof','stale_proof','inactive','not_required','invalid_id','missing_user','weak','mismatch','reuse'] as $case) {
    [$service,$model,$proof]=fixtureRequired(); $id=1; $new='Permanent!456'; $confirm=$new;
    switch($case) {
        case 'missing_proof': $proof=''; break;
        case 'stale_proof': $proof=str_repeat('0',64); break;
        case 'inactive': $model->row['status']='Inactive'; break;
        case 'not_required': $model->row['must_change_password']=0; break;
        case 'invalid_id': $id=0; break;
        case 'missing_user': $id=2; break;
        case 'weak': $new=$confirm='weak'; break;
        case 'mismatch': $confirm='Different!789'; break;
        case 'reuse': $new=$confirm='Temporary!123'; break;
    }
    try { $service->changeRequiredPassword($id,$new,$confirm,$proof); throw new LogicException('Allowed '.$case); }
    catch (Exception $e) { checkRequired($model->writes===0,'Rejected before writing: '.$case); }
}
foreach(['Admin','Faculty','Student','Parent'] as $role) {
    [$service,$model,$proof]=fixtureRequired(); $model->row['role_prefix']=$role;
    $result=$service->changeRequiredPassword(1,'Permanent!456','Permanent!456',$proof);
    checkRequired(password_verify('Permanent!456',$model->row['password']) && !$model->row['must_change_password'],'Success for '.$role);
    checkRequired(!isset($result['password']),'No credential disclosure');
    try { $service->changeRequiredPassword(1,'NextPrivate!789','NextPrivate!789',$proof); throw new LogicException('Repeated change allowed'); }
    catch(RuntimeException $e) { checkRequired($model->writes===1,'Repeated change rejected'); }
}
checkRequired(!str_contains(file_get_contents(__DIR__.'/../pages/required_password_change.php'),'name="current_password"'),'No temporary-password input');
echo "Required password change: {$checks} checks passed.\n";
