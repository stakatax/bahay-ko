<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../app/services/FacultyScopeService.php';
$c = openDatabaseConnection();
$programs = $c->query("SELECT ap.academic_program_id, el.department_id, el.education_level_id FROM academic_program ap
    JOIN education_level el ON el.education_level_id=ap.education_level_id
    JOIN department d ON d.department_id=el.department_id
    WHERE ap.status='Active' AND el.status='Active' AND d.status='Active' AND d.department_code='COLLEGE'")->fetch_all(MYSQLI_ASSOC);
$ibed = $c->query("SELECT el.department_id, el.education_level_id FROM education_level el JOIN department d ON d.department_id=el.department_id
    WHERE el.status='Active' AND d.status='Active' AND d.department_code='IBED' ORDER BY el.education_level_id")->fetch_all(MYSQLI_ASSOC);
if (count($programs)<2 || !$ibed) { throw new RuntimeException('Catalog insufficient for cross-program/division checks.'); }
$model = new class($c) extends User {
    public array $actor=[];
    public function __construct(mysqli $c) { $this->conn=$c; }
    public function findById(int $id) { return $this->actor; }
};
$policy = new FacultyScopeService($model);
$_SESSION['role']='Faculty';
$checks=0;
$deny=static function(callable $operation) use (&$checks):void {
    try { $operation(); } catch (DomainException|InvalidArgumentException $e) { $checks++; return; }
    throw new RuntimeException('Cross-scope submission was accepted.');
};
$base=['audience_scope'=>'custom','workflow_action'=>'submit_review'];
foreach ($programs as $scope) {
    $model->actor=$scope+['role_prefix'=>'Faculty','status'=>'Active'];
    $prepared=$policy->prepareSubmission($base+['audience_scopes'=>[$scope]],1);
    if ((int)$prepared['audience_scopes'][0]['academic_program_id'] !== (int)$scope['academic_program_id']) {
        throw new RuntimeException('Assigned program was changed.');
    }
    $checks++;
    foreach ($programs as $other) {
        if ($other['academic_program_id'] === $scope['academic_program_id']) { continue; }
        $deny(fn()=>$policy->prepareSubmission($base+['audience_scopes'=>[$other]],1));
    }
    $deny(fn()=>$policy->prepareSubmission($base+['audience_scopes'=>[$ibed[0]]],1));
    $deny(fn()=>$policy->prepareSubmission(array_replace($base,['audience_scope'=>'schoolwide']),1));
    $deny(fn()=>$policy->prepareSubmission(array_replace($base,['workflow_action'=>'publish']),1));
}
foreach ($ibed as $scope) {
    $model->actor=$scope+['academic_program_id'=>null,'role_prefix'=>'Faculty','status'=>'Active'];
    $policy->prepareSubmission($base+['audience_scopes'=>[$scope]],1); $checks++;
    $deny(fn()=>$policy->prepareSubmission($base+['audience_scopes'=>[$programs[0]]],1));
}
echo 'PASS: '.$checks.' current-catalog Faculty scope checks across '.count($programs)
    ." College programs and ".count($ibed)." IBED levels; read-only catalog and synthetic actors, no posts or accounts created.\n";
