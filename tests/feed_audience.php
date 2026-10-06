<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/ContentAudienceService.php';

class FeedAudienceFixture extends ContentAudience
{
    public int $actors = 0;
    public int $profiles = 0;
    public int $targets = 0;
    public ?array $viewer = null;
    public array $children = [];
    public function __construct() {}
    public function findActor(int $userId): ?array { $this->actors++; return $userId > 0 ? $this->viewer : null; }
    public function getVerifiedStudentProfiles(int $parentId): array { $this->profiles++; return $this->children; }
    public function getTargetMap(string $contentType, array $ids): array {
        return [2 => [['role_id'=>0, 'department_id'=>10]], 3 => [['role_id'=>0, 'department_id'=>20]]];
    }
    public function getTargetSets(array $sets): array {
        $this->targets++;
        $result = [];
        foreach ($sets as $type => $ids) { $result[$type] = $this->getTargetMap($type, $ids); }
        return $result;
    }
    public function resetCounts(): void { $this->actors = $this->profiles = $this->targets = 0; }
}
$checks = 0;
function feedCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
$model = new FeedAudienceFixture();
$service = new ContentAudienceService($model);
$sets = [];
foreach (['announcement', 'event', 'document', 'survey'] as $type) {
    $sets[$type] = [[$type.'_id'=>3], [$type.'_id'=>1], [$type.'_id'=>2], [$type.'_id'=>0]];
}
foreach (['Admin', 'Faculty', 'Student', 'Parent', 'Unknown'] as $role) {
    foreach (['Active', 'Inactive'] as $status) {
        foreach ([[], [['department_id'=>10]], [['department_id'=>10], ['department_id'=>20]]] as $children) {
            $model->viewer = ['role_prefix'=>$role, 'role_id'=>2, 'department_id'=>10, 'status'=>$status];
            $model->children = $children;
            $model->resetCounts();
            $expected = [];
            foreach ($sets as $type => $items) {
                $expected[$type] = $service->filterForUser($type, $type.'_id', $items, 1, true);
            }
            feedCheck($model->actors === 4, 'Separate content filtering repeats viewer lookup');
            $oldProfiles = $model->profiles;
            $oldTargets = $model->targets;
            $model->resetCounts();
            feedCheck($service->filterSetsForUser($sets, 1, true) === $expected, 'Exact visibility, ordering and specificity parity');
            feedCheck($model->actors === 1 && $model->profiles === (int)($oldProfiles > 0), 'Viewer and linked profiles resolved once');
            feedCheck($model->targets === (int)($oldTargets > 0), 'Live targets resolved once per batch');
        }
    }
}
$model->viewer = ['role_prefix'=>'Student','role_id'=>2,'department_id'=>10,'status'=>'Active'];
$first = $service->filterSetsForUser($sets, 1);
feedCheck(array_column($first['event'], 'event_id') === [1,2], 'Explicit expected student audience');
$model->viewer['status'] = 'Inactive';
feedCheck(!array_filter($service->filterSetsForUser($sets, 1)), 'Subsequent call rechecks deactivated account');
$model->viewer = ['role_prefix'=>'Parent','role_id'=>3,'status'=>'Active'];
$model->children = [['department_id'=>20]];
feedCheck(array_column($service->filterSetsForUser($sets, 1)['event'], 'event_id') === [3,1], 'Parent uses child scope');
$model->children = [];
feedCheck(!array_filter($service->filterSetsForUser($sets, 1)), 'Revoked child link rechecked, no cached scope');
feedCheck(!array_filter($service->filterSetsForUser($sets, 0)), 'Guest denied by default');
feedCheck(array_column($service->filterSetsForUser($sets, 0, true)['event'], 'event_id') === [1], 'Optional guest gets school-wide only');
$model->resetCounts();
feedCheck($service->filterSetsForUser(['event'=>[]], 1) === ['event'=>[]] && $model->actors === 0, 'Empty sets need no lookup');
foreach ([['invalid'=>[]], ['event'=>[], 'invalid'=>[['invalid_id'=>1]]]] as $invalid) {
    try { $service->filterSetsForUser($invalid, 1); throw new RuntimeException('Invalid type accepted'); }
    catch (InvalidArgumentException $e) { feedCheck(true, 'Invalid type rejected'); }
}
try { $service->filterForUser('event', 'wrong_id', [], 1); throw new RuntimeException('Invalid column accepted'); }
catch (InvalidArgumentException $e) { feedCheck(true, 'Existing ID validation preserved'); }
echo "PASS: $checks feed audience checks; model fixtures only, no database writes.\n";
