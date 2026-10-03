<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/NotificationService.php';
class ActionUrlNotificationFixture extends Notification {
    public array $items = [];
    public array $reads = [];
    public function __construct() {}
    public function findForUser(int $notificationId, int $userId): ?array {
        $item = $this->items[$notificationId] ?? null;
        return $item && $item['user_id'] === $userId ? $item : null;
    }
    public function markAsRead(int $notificationId, int $userId): bool {
        $this->reads[] = [$notificationId, $userId]; return true;
    }
}
$model = new ActionUrlNotificationFixture();
$service = (new ReflectionClass(NotificationService::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(NotificationService::class, 'notification'))->setValue($service, $model);
$checks = 0;
function urlCheck(bool $ok, string $label): void { global $checks; if (!$ok) throw new RuntimeException($label); $checks++; }
function notice(ActionUrlNotificationFixture $model, int $owner, string $type, string $key, ?string $content = null, ?int $id = null): void {
    $model->items[1] = ['user_id'=>$owner, 'notification_type'=>$type, 'deduplication_key'=>$key, 'content_type'=>$content, 'content_id'=>$id];
}
foreach (['Student'=>1, 'Parent'=>2, 'Faculty'=>3, 'Admin'=>4] as $role=>$owner) {
    notice($model, $owner, 'system', 'registration-approved:user:'.$owner);
    urlCheck($service->open(1, $owner) === 'index.php?page=account_profile', $role.' account notice opens own profile');
    urlCheck(end($model->reads) === [1,$owner], 'Owned notice marked read');
    notice($model, $owner, 'system', 'unrelated-account-notice');
    urlCheck($service->open(1, $owner) === 'index.php?page=notifications', 'Unlinked notices stay in Notifications');
}
$cases = [
    ['system','registration-submitted:user:7',null,null,'index.php?page=account_approvals&user_id=7'],
    ['reminder','student-profile-cycle-assigned:cycle:7',null,null,'index.php?page=student_profile#expandedProfileSurvey'],
    ['content','published','announcement',7,'index.php?page=news&open_type=announcement&open_id=7'],
    ['content','published','event',7,'index.php?page=news&open_type=event&open_id=7'],
    ['content','published','document',7,'index.php?page=news&open_type=document&open_id=7'],
    ['content','published','survey',7,'index.php?page=survey_participate&survey_id=7'],
    ['workflow','approved','announcement',7,'index.php?page=content_workspace&open_type=announcement&open_id=7'],
    ['content','engagement:survey_response:7','survey',7,'index.php?page=survey_results&survey_id=7']
];
foreach ($cases as [$type,$key,$content,$id,$expected]) {
    notice($model,1,$type,$key,$content,$id);
    urlCheck($service->open(1,1) === $expected,'Related destination preserved: '.$expected);
}
$readCount=count($model->reads);
foreach ([[1,2],[0,1],[-1,1],[99,1],[1,0]] as [$id,$owner]) {
    try { $service->open($id,$owner); throw new LogicException('Unexpected access'); }
    catch (InvalidArgumentException|RuntimeException $e) { urlCheck(true,'Invalid or foreign notice denied'); }
}
urlCheck(count($model->reads)===$readCount,'Denied notices never marked read');
echo "PASS: $checks notification routing and ownership checks. Fixtures only; no database writes or deliveries.\n";
