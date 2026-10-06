<?php
require_once __DIR__ . '/../app/models/StudentProfile.php';
require_once __DIR__ . '/../config/database.php';
class StudentInterestCountingConnection extends mysqli {
    public int $reads = 0;
    public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): mysqli_result|bool { $this->reads++; return parent::query($query, $result_mode); }
}
$config = databaseConfiguration();
$db = new StudentInterestCountingConnection($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
$model = new StudentProfile($db);
$expected = $model->getActiveInterests();
if ($model->getDisplayInterests() !== $expected || $db->reads !== 2) { throw new RuntimeException('Injected connection must bypass cache'); }
$property = new ReflectionProperty(StudentProfile::class, 'sharedInterestCache'); $property->setValue($model, true);
$first = $model->getDisplayInterests(); $reads = $db->reads;
if ($first !== $expected || $model->getDisplayInterests() !== $first || $db->reads !== $reads) { throw new RuntimeException('Warm display must avoid query with exact parity'); }
$model->getActiveInterests();
if ($db->reads !== $reads + 1) { throw new RuntimeException('Validation must stay live'); }
echo "PASS: Student interest display cache, exact parity, test isolation and live validation.\n";
