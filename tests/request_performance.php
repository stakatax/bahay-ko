<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/PostService.php';
require_once __DIR__ . '/../app/services/ContentReleaseService.php';
class PerformanceConnection extends mysqli
{
    public int $prepares = 0;
    public int $transactions = 0;
    public array $queries = [];
    public function prepare(string $query): mysqli_stmt|false {
        $this->prepares++;
        return parent::prepare($query);
    }
    public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): mysqli_result|bool {
        $this->queries[] = $query;
        return parent::query($query, $result_mode);
    }
    public function begin_transaction(int $flags = 0, ?string $name = null): bool {
        $this->transactions++;
        return parent::begin_transaction($flags, $name);
    }
    public function resetCounts(): void { $this->prepares = 0; $this->transactions = 0; $this->queries = []; }
}
class PerformanceDispatcher extends PublicationNotificationDispatcher
{
    public int $calls = 0;
    public function __construct() {}
    public function dispatch(int $limit = 25, ?string $type = null, ?int $id = null): array {
        $this->calls++;
        return ['eligible'=>0,'created'=>0,'duplicates'=>0,'completed'=>0,'cancelled'=>0,'failed'=>0];
    }
}
$checks = 0;
function performanceCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
$c = databaseConfiguration();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new PerformanceConnection($c['host'], $c['username'], $c['password'], $c['database'], $c['port']);
$db->set_charset('utf8mb4');
try {
    foreach (['content_view', 'content_comment', 'content_reaction', 'content_acknowledgment', 'survey_response',
        'events', 'announcements', 'documents', 'survey', 'publication_notification_outbox'] as $table) {
        $ddl = $db->query('SHOW CREATE TABLE `' . $table . '`')->fetch_assoc()['Create Table'];
        $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $ddl, 1);
        $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $ddl);
        $ddl = preg_replace('/,\n\)/', "\n)", $ddl);
        $db->query($ddl);
    }
    $engagement = new ContentEngagement($db);
    foreach (['announcement', 'event', 'document', 'survey'] as $type) {
        $db->query("INSERT INTO content_view (content_type,content_id,user_id) VALUES ('$type',1,1),('$type',1,2),('$type',3,2)");
        $db->query("INSERT INTO content_acknowledgment (content_type,content_id,user_id) VALUES ('$type',1,1),('$type',3,2)");
        $db->query("INSERT INTO content_reaction (content_type,content_id,user_id,reaction_type) VALUES ('$type',1,1,'Love'),('$type',1,2,'Like'),('$type',1,3,'Care'),('$type',1,4,'Wow'),('$type',3,2,'Love')");
        $db->query("INSERT INTO content_comment (content_type,content_id,user_id,comment,status) VALUES ('$type',1,1,'Visible','Active'),('$type',1,2,'Moderated','Hidden'),('$type',3,2,'Removed','Deleted')");
        foreach ([0, 1, 2, 99] as $user) {
            $batch = $engagement->getEngagementBatch($type, [1, 2, 3], $user);
            foreach ([1, 2, 3] as $id) {
                performanceCheck($batch[$id] === $engagement->getEngagement($type, $id, $user), 'Exact old/new engagement parity: ' . $type . '/' . $id . '/' . $user);
            }
        }
    }
    $db->resetCounts();
    $engagement->getEngagementBatch('announcement', range(1, 100), 1);
    performanceCheck($db->prepares === 4, '100 feed cards use four engagement queries');
    $db->resetCounts();
    foreach (range(1, 100) as $id) { $engagement->getEngagement('announcement', $id, 1); }
    performanceCheck($db->prepares === 800, 'Measured old path uses 800 queries for 100 cards');
    $db->resetCounts();
    performanceCheck($engagement->getEngagementBatch('announcement', [], 1) === [] && $db->prepares === 0, 'Empty batch has no queries');
    performanceCheck($engagement->getEngagementBatch('announcement', [0, -1], 1) === [] && $db->prepares === 0, 'Invalid IDs have no queries');
    performanceCheck(count($engagement->getEngagementBatch('announcement', [1, '1', 0], 1)) === 1, 'Duplicate IDs normalized');
    $db->resetCounts();
    performanceCheck(count($engagement->getEngagementBatch('announcement', range(1, 501), 1)) === 501 && $db->prepares === 8, 'Large batch is bounded in 500-ID chunks');
    try { $engagement->getEngagementBatch('invalid', [1], 1); performanceCheck(false, 'Invalid type rejected'); }
    catch (InvalidArgumentException $exception) { performanceCheck(true, 'Invalid type rejected'); }
    $post = (new ReflectionClass(PostService::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(PostService::class, 'engagement'))->setValue($post, $engagement);
    $items = [['announcement_id'=>3, 'title'=>'third'], ['announcement_id'=>1, 'title'=>'first'], ['announcement_id'=>0, 'title'=>'invalid']];
    $db->resetCounts();
    $attached = (new ReflectionMethod(PostService::class, 'attachEngagement'))->invoke($post, 'announcement', 'announcement_id', $items, 1);
    performanceCheck($db->prepares === 4, 'Actual feed attachment uses batched model');
    performanceCheck(array_column($attached, 'title') === ['third', 'first', 'invalid'], 'Card order and unrelated fields preserved');
    performanceCheck($attached[1]['love_count'] === 1 && $attached[1]['user_reaction'] === 'Love', 'Rendered reaction fields preserved');
    performanceCheck($attached[2] === $items[2], 'Invalid card handling preserved');
    $survey = new Survey($db);
    $db->query('INSERT INTO survey_response (survey_id,user_id) VALUES (1,1),(2,2),(3,1)');
    foreach ([0, 1, 2, 99] as $user) {
        $set = $survey->getRespondedSurveyIds([1,2,3,4], $user);
        foreach ([1,2,3,4] as $id) {
            performanceCheck(isset($set[$id]) === $survey->hasResponded($id, $user), 'Survey participation parity');
        }
    }
    $db->resetCounts();
    $survey->getRespondedSurveyIds(range(1,100), 1);
    performanceCheck($db->prepares === 1, '100 surveys use one participation query');
    $db->resetCounts();
    foreach (range(1,100) as $id) { $survey->hasResponded($id, 1); }
    performanceCheck($db->prepares === 100, 'Measured original survey path uses 100 queries');
    $db->resetCounts();
    performanceCheck($survey->getRespondedSurveyIds([], 1) === [] && $survey->getRespondedSurveyIds([1], 0) === [] && $db->prepares === 0, 'Empty/guest participation performs no queries');
    $release = new ContentReleaseService($db);
    $dispatcher = new PerformanceDispatcher();
    (new ReflectionProperty(ContentReleaseService::class, 'publicationDispatcher'))->setValue($release, $dispatcher);
    $idle = static function (string $label) use ($db, $release, $dispatcher): void {
        $db->resetCounts(); $calls = $dispatcher->calls;
        $result = $release->processPendingReleasesIfDue();
        performanceCheck($result['total_released'] === 0 && count($db->queries) === 1 && str_starts_with($db->queries[0], 'SELECT ')
            && $db->prepares === 0 && $db->transactions === 0 && $dispatcher->calls === $calls, $label);
    };
    $db->resetCounts();
    $release->processPendingReleases();
    performanceCheck(count($db->queries) === 7 && $db->transactions === 1 && $dispatcher->calls === 1, 'Original idle path reads seven due sets and starts transaction/dispatch');
    $idle('Idle request uses one SELECT, no transaction, writes or dispatcher');
    $sources = ['event'=>['events','event_id'], 'announcement'=>['announcements','announcement_id'], 'document'=>['documents','document_id'], 'survey'=>['survey','survey_id']];
    foreach ($sources as $type => [$table,$key]) {
        $status = $type === 'survey' ? 'Published' : 'active';
        $extra = match ($type) { 'announcement'=>",content,type", 'document'=>",description,file_name,file_type", default=>'' };
        $values = match ($type) { 'announcement'=>",'Fixture','announcement'", 'document'=>",'Fixture','fixture.pdf','pdf'", default=>'' };
        $db->query("INSERT INTO $table ($key,title,workflow_status,status,user_id,send_notification,release_mode,scheduled_publish_at $extra)
            VALUES (10,'Future','scheduled','$status',1,1,'scheduled',DATE_ADD(NOW(),INTERVAL 1 DAY) $values)");
    }
    $idle('Future content does not trigger publishing');
    foreach ($sources as $type => [$table,$key]) {
        $db->query("UPDATE $table SET scheduled_publish_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE $key=10");
        $r = $release->processPendingReleasesIfDue();
        performanceCheck($r['total_released'] === 1, 'Due ' . $type . ' triggers real release');
        performanceCheck($db->query("SELECT workflow_status FROM $table WHERE $key=10")->fetch_assoc()['workflow_status'] === 'published', 'Due content saved');
        performanceCheck((int)$db->query("SELECT COUNT(*) n FROM publication_notification_outbox WHERE content_type='$type' AND content_id=10")->fetch_assoc()['n'] === 1, 'Release queues durable notification');
        $db->query("UPDATE publication_notification_outbox SET delivery_status='Completed'");
    }
    $db->query('UPDATE events SET event_date=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE event_id=10');
    foreach (['announcement','document','survey'] as $type) {
        [$table,$key] = $sources[$type];
        $db->query("UPDATE $table SET workflow_status='scheduled',release_mode='calendar',calendar_event_id=10 WHERE $key=10");
        performanceCheck($release->processPendingReleasesIfDue()['total_released'] === 1, 'Calendar due check: ' . $type);
    }
    $idle('Completed outbox and published content are idle');
    $db->query("UPDATE publication_notification_outbox SET delivery_status='Pending',available_at=DATE_ADD(NOW(),INTERVAL 1 DAY)");
    $idle('Future retry is idle');
    $db->query("UPDATE publication_notification_outbox SET available_at=DATE_SUB(NOW(),INTERVAL 1 SECOND)");
    $before = $dispatcher->calls;
    $release->processPendingReleasesIfDue();
    performanceCheck($dispatcher->calls === $before + 1, 'Due retry is processed even with no new publication');
    $db->query("UPDATE publication_notification_outbox SET delivery_status='Processing',locked_until=DATE_ADD(NOW(),INTERVAL 1 DAY)");
    $idle('Live processing lease is not claimed');
    $db->query("UPDATE publication_notification_outbox SET locked_until=DATE_SUB(NOW(),INTERVAL 1 SECOND)");
    $before = $dispatcher->calls;
    $release->processPendingReleasesIfDue();
    performanceCheck($dispatcher->calls === $before + 1, 'Expired processing lease still triggers recovery');
    echo "PASS: $checks request performance checks; temporary tables only, no external notifications.\n";
    echo "Measured: 100-card engagement 800 -> 4 queries; 100-survey flags 100 -> 1; idle request publishing 1 SELECT, 0 transactions.\n";
} finally { $db->close(); }
