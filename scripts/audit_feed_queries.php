<?php
// Read-only query plans for the exact candidate loaders used by Home.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
foreach (['Announcement', 'Event', 'Document', 'Survey'] as $name) { require_once __DIR__ . '/../app/models/' . $name . '.php'; }
class FeedAuditConnection extends mysqli {
    public array $captured = [];
    public function prepare(string $query): mysqli_stmt|false { $this->captured[] = $query; return parent::prepare($query); }
}
$config = databaseConfiguration();
$db = new FeedAuditConnection($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
$models = ['announcements' => new Announcement($db), 'events' => new Event($db), 'documents' => new Document($db), 'survey' => new Survey($db)];
foreach ($models as $table => $model) {
    $db->captured = [];
    $rows = $table === 'announcements' ? $model->getRecent(100, false) : ($table === 'events' ? $model->getRecent(100, [], true) : $model->getRecent(100));
    $query = $db->captured[0];
    $parameters = $table === 'events' ? [1, 0, 0, 0, 0, 0, 0, 100] : [100];
    $explain = $db->prepare('EXPLAIN ' . $query);
    $explain->bind_param(str_repeat('i', count($parameters)), ...$parameters);
    $explain->execute();
    $plan = $explain->get_result()->fetch_all(MYSQLI_ASSOC); $explain->close();
    echo $table, ': candidates=', count($rows), PHP_EOL;
    foreach ($plan as $step) {
        echo json_encode(array_intersect_key($step, array_flip(['table', 'type', 'key', 'rows', 'Extra']))), PHP_EOL;
    }
}
