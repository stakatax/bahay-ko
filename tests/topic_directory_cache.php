<?php
require_once __DIR__ . '/../app/models/ContentInterest.php';
require_once __DIR__ . '/../config/database.php';
class TopicCountingConnection extends mysqli {
    public int $reads = 0;
    public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): mysqli_result|bool { $this->reads++; return parent::query($query, $result_mode); }
}
$config = databaseConfiguration();
$db = new TopicCountingConnection($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
$model = new ContentInterest($db);
$expected = $model->getActiveInterests();
if ($model->getDisplayInterests() !== $expected || $db->reads !== 2) { throw new RuntimeException('Injected connections must bypass shared cache'); }
$property = new ReflectionProperty(ContentInterest::class, 'sharedTopicCache'); $property->setValue($model, true);
$first = $model->getDisplayInterests(); $reads = $db->reads;
if ($model->getDisplayInterests() !== $first || $db->reads !== $reads || $first !== $expected) { throw new RuntimeException('Warm display must avoid query with identical data'); }
$model->getActiveInterests();
if ($db->reads !== $reads + 1) { throw new RuntimeException('Validation must query live directory'); }
echo "PASS: topic display cache, exact parity, injected-connection bypass and live validation.\n";
