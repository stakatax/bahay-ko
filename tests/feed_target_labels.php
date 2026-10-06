<?php
if (PHP_SAPI !== 'cli') { exit(1); }
require_once __DIR__ . '/../app/models/ContentAudience.php';
require_once __DIR__ . '/../config/database.php';
class LabelTestConnection extends mysqli {
    public int $reads = 0;
    public function prepare(string $sql): mysqli_stmt|false { $this->reads++; return parent::prepare($sql); }
}
function labelCheck(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
$config = databaseConfiguration();
$db = new LabelTestConnection($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
try {
    foreach (['announcement_target', 'event_target', 'document_target', 'survey_target'] as $table) {
        $ddl = $db->query('SHOW CREATE TABLE `' . $table . '`')->fetch_row()[1];
        $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $ddl, 1);
        $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $ddl);
        $ddl = preg_replace('/,\n\)/', "\n)", $ddl); $db->query($ddl);
    }
    $role = $db->query("SELECT role_id, role_prefix FROM role ORDER BY role_id LIMIT 1")->fetch_assoc();
    foreach (['announcement', 'event', 'document', 'survey'] as $type) {
        $db->query("INSERT INTO {$type}_target ({$type}_id, role_id) VALUES (1," . (int) $role['role_id'] . ")");
    }
    $model = new ContentAudience($db); $db->reads = 0;
    $targetSets = $model->getTargetSets(array_fill_keys(['announcement', 'event', 'document', 'survey'], [1, 1, 0, -1]));
    labelCheck($db->reads === 1, 'All target types use one live query');
    foreach ($targetSets as $type => $map) {
        $original = $db->prepare("SELECT {$type}_id AS content_id, role_id, department_id, education_level_id, academic_program_id, grade_level_id, section_id FROM {$type}_target WHERE {$type}_id IN (?)");
        $id = 1; $original->bind_param('i', $id); $original->execute();
        $expected = $original->get_result()->fetch_all(MYSQLI_ASSOC); $original->close();
        labelCheck($map === [1 => $expected], 'Live mixed target rows match original lookup');
        labelCheck($map === $model->getTargetMap($type, [1]), 'Single-type target wrapper matches mixed map');
    }
    $db->reads = 0;
    labelCheck($model->getTargetSets(['announcement' => [], 'event' => [0, -1]]) === ['announcement' => [], 'event' => []] && $db->reads === 0, 'Empty live target sets avoid queries');
    $db->reads = 0;
    $targetLarge = $model->getTargetSets(['announcement' => range(1, 300), 'event' => range(1, 300)]);
    labelCheck($db->reads === 2 && $targetLarge['announcement'] === $targetSets['announcement'] && $targetLarge['event'] === $targetSets['event'], 'Live target chunks preserve maps');
    try { $model->getTargetSets(['invalid' => [1]]); throw new LogicException('Invalid live target type accepted'); }
    catch (InvalidArgumentException $error) {}
    $db->reads = 0;
    $mixed = $model->getLabelSets(array_fill_keys(['announcement', 'event', 'document', 'survey'], [1, 1, 0, -1]));
    labelCheck($db->reads === 1, 'All eligible types use one label query');
    foreach ($mixed as $type => $map) {
        labelCheck($map === [1 => [$role['role_prefix']]], 'Type/ID labels remain separated and normalized');
        labelCheck($map === $model->getLabelSets([$type => [1]])[$type], 'Mixed and single-type labels match');
    }
    $db->reads = 0;
    labelCheck($model->getLabelSets(['announcement' => [], 'event' => [0, -1]]) === ['announcement' => [], 'event' => []] && $db->reads === 0, 'Empty label sets avoid queries');
    $db->reads = 0;
    $large = $model->getLabelSets(['announcement' => range(1, 300), 'event' => range(1, 300)]);
    labelCheck($db->reads === 2 && $large['announcement'] === $mixed['announcement'] && $large['event'] === $mixed['event'], 'Chunk boundaries preserve labels');
    try { $model->getLabelSets(['invalid' => [1]]); throw new LogicException('Invalid type accepted'); }
    catch (InvalidArgumentException $error) {}
    echo "PASS: mixed target labels, query reduction, normalization, empty sets, type isolation and chunking; temporary tables only.\n";
} finally { $db->close(); }
