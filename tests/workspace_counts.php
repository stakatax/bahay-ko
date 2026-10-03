<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/controllers/ContentWorkspaceController.php';
class WorkspaceCountConnection extends mysqli
{
    public int $prepares = 0;
    public function prepare(string $query): mysqli_stmt|false {
        $this->prepares++;
        return parent::prepare($query);
    }
}
$checks = 0;
function workspaceCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
$c = databaseConfiguration();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new WorkspaceCountConnection($c['host'], $c['username'], $c['password'], $c['database'], $c['port']);
$db->set_charset('utf8mb4');
try {
    foreach (['announcements', 'events', 'documents', 'survey', 'survey_response', 'user'] as $table) {
        $ddl = $db->query('SHOW CREATE TABLE `' . $table . '`')->fetch_assoc()['Create Table'];
        $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $ddl, 1);
        $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $ddl);
        $ddl = preg_replace('/,\n\)/', "\n)", $ddl);
        $db->query($ddl);
    }
    $service = (new ReflectionClass(ContentWorkspaceService::class))->newInstanceWithoutConstructor();
    foreach (['announcement'=>new Announcement($db), 'event'=>new Event($db), 'document'=>new Document($db), 'survey'=>new Survey($db)] as $name=>$model) {
        (new ReflectionProperty(ContentWorkspaceService::class, $name))->setValue($service, $model);
    }
    $statuses = ['draft', 'pending_review', 'scheduled', 'published', 'rejected', 'archived'];
    workspaceCheck($service->getStatusCounts('Admin', 1) === array_fill_keys($statuses, 0), 'Empty workspace has six integer-zero counts');
    foreach (['Student', 'Parent', 'Guest', '', 'Administrator'] as $role) {
        try { $service->getStatusCounts($role, 1); workspaceCheck(false, 'Unauthorized role rejected'); }
        catch (RuntimeException $exception) { workspaceCheck(true, 'Unauthorized role rejected'); }
    }
    foreach ([0,-1] as $id) {
        try { $service->getStatusCounts('Faculty', $id); workspaceCheck(false, 'Invalid actor rejected'); }
        catch (InvalidArgumentException $exception) { workspaceCheck(true, 'Invalid actor rejected'); }
    }
    foreach (['announcement'=>'announcements', 'event'=>'events', 'document'=>'documents', 'survey'=>'survey'] as $type=>$table) {
        $extra = match ($type) { 'announcement'=>",content,type", 'document'=>",description,file_name,file_type", default=>'' };
        $values = match ($type) { 'announcement'=>",'Fixture','announcement'", 'document'=>",'Fixture','fixture.pdf','pdf'", default=>'' };
        $stmt = $db->prepare("INSERT INTO $table (title,workflow_status,user_id,created_at $extra) VALUES ('Fixture',?,?,? $values)");
        foreach ($statuses as $status) {
            foreach ([1,2] as $owner) {
                $date = $owner === 1 ? '2020-01-01 00:00:00' : '2030-01-01 00:00:00';
                $number = $status === 'draft' ? 260 : $owner;
                for ($i=0; $i<$number; $i++) {
                    $stmt->bind_param('sis', $status, $owner, $date); $stmt->execute();
                }
            }
        }
        $stmt->close();
    }
    $db->prepares = 0;
    $admin = $service->getStatusCounts('Admin', 1);
    workspaceCheck($db->prepares === 1, 'All status totals require one aggregate statement');
    workspaceCheck($admin['draft'] === 2080, 'Admin totals exceed old four-type 1000-row cap');
    $faculty = $service->getStatusCounts('Faculty', 1);
    $other = $service->getStatusCounts('Faculty', 2);
    workspaceCheck($faculty['draft'] === 1040 && $other['draft'] === 1040, 'Faculty totals exceed old per-type limits');
    foreach (array_slice($statuses, 1) as $status) {
        workspaceCheck($admin[$status] === 12, 'Admin count covers all four types: ' . $status);
        workspaceCheck($faculty[$status] === 4 && $other[$status] === 8, 'Faculty ownership isolation: ' . $status);
    }
    workspaceCheck($service->getStatusCounts('Faculty', 99) === array_fill_keys($statuses, 0), 'Owner without content gets zero totals');
    $document = new Document($db);
    $rows = $document->getByWorkflowStatus('draft', 250, 1);
    workspaceCheck(count($rows) === 250 && array_unique(array_column($rows, 'user_id')) === [1], 'Older Faculty documents survive global newer-row limit');
    workspaceCheck(count($document->getByWorkflowStatus('draft', 1, 1)) === 1, 'Requested list limit preserved');
    workspaceCheck(count($document->getByWorkflowStatus('draft', 999, 1)) === 250, 'List cap remains bounded');
    workspaceCheck((int)$document->getByWorkflowStatus('draft', 1)[0]['user_id'] === 2, 'Existing two-argument Admin listing remains compatible');
    try { $document->getByWorkflowStatus('draft', 250, 0); workspaceCheck(false, 'Invalid document owner rejected'); }
    catch (InvalidArgumentException $exception) { workspaceCheck(true, 'Invalid document owner rejected'); }
    $workspace = $service->getWorkspace('draft', 'Faculty', 1);
    workspaceCheck(count($workspace['items']) === 1000 && $workspace['counts']['draft'] === 1040, 'Bounded displayed items use independent uncapped totals');
    workspaceCheck(array_unique(array_column($workspace['items'], 'author_id')) === [1], 'Workspace items remain owned by Faculty');
    foreach (['Admin'=>12, 'Faculty'=>4] as $role=>$expected) {
        $_SESSION = ['user_id'=>1, 'role'=>$role]; $_GET = ['status'=>'scheduled'];
        $controller = (new ReflectionClass(ContentWorkspaceController::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(ContentWorkspaceController::class, 'service'))->setValue($controller, $service);
        $view = $controller->index();
        workspaceCheck($view['counts']['scheduled'] === $expected && count($view['items']) === $expected, 'Controller scheduled count matches visible rows for ' . $role);
        $types = array_unique(array_column($view['items'], 'content_type')); sort($types);
        workspaceCheck($types === ['announcement','document','event','survey'], 'Scheduled tab contains every supported type for ' . $role);
    }
    // Refresh totals after a normal workflow-state change; no stale count cache.
    $db->query("UPDATE events SET workflow_status='published' WHERE workflow_status='scheduled' AND user_id=1");
    $fresh = $service->getStatusCounts('Faculty', 1);
    workspaceCheck($fresh['scheduled'] === 3 && $fresh['published'] === 5, 'Counts refresh after workflow transitions');
    foreach (['Student','Parent'] as $role) {
        try { $service->getWorkspace('draft', $role, 1); workspaceCheck(false, 'Workspace denied'); }
        catch (RuntimeException $exception) { workspaceCheck(true, 'Workspace denied'); }
    }
    workspaceCheck($service->getWorkspace('unknown', 'Admin', 1)['active_status'] === 'draft', 'Existing invalid-status fallback preserved');
    echo "PASS: $checks workspace count checks; temporary tables only, no existing records changed.\n";
} finally { $db->close(); }
