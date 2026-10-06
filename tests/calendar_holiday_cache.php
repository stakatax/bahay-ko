<?php
require_once __DIR__ . '/../app/models/CalendarHoliday.php';
require_once __DIR__ . '/../config/database.php';
class HolidayCountingConnection extends mysqli {
    public int $reads = 0;
    public function prepare(string $query): mysqli_stmt|false { $this->reads++; return parent::prepare($query); }
}
$config = databaseConfiguration();
$db = new HolidayCountingConnection($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
$model = new CalendarHoliday($db);
$expected = $model->getActiveByYear(2026);
if ($model->getActiveByYear(2026) !== $expected || $db->reads !== 2) { throw new RuntimeException('Injected connections must bypass cache'); }
$property = new ReflectionProperty(CalendarHoliday::class, 'sharedHolidayCache'); $property->setValue($model, true);
$first = $model->getActiveByYear(2026); $reads = $db->reads;
if ($first !== $expected || $model->getActiveByYear(2026) !== $first || $db->reads !== $reads) { throw new RuntimeException('Warm lookup must avoid query with exact parity'); }
$map = $model->getActiveMapByYear(2026);
foreach ($expected as $holiday) { if (!in_array($holiday, $map[$holiday['holiday_date']] ?? [], true)) { throw new RuntimeException('Map must preserve every holiday'); } }
if ($db->reads !== $reads) { throw new RuntimeException('Map must reuse cached year'); }
$model->getActiveByYear(2027); $reads = $db->reads;
$model->getActiveByYear(2026); if ($db->reads !== $reads) { throw new RuntimeException('Separate year caches'); }
if ($model->getActiveByYear(1999) !== $model->getActiveByYear(2000) || $model->getActiveByYear(2101) !== $model->getActiveByYear(2100)) { throw new RuntimeException('Year bounds preserved'); }
echo "PASS: holiday cache parity, warm query avoidance, year separation, grouping and year bounds.\n";
