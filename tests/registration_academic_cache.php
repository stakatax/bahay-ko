<?php
require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/models/AcademicStructure.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/PublicCatalogCache.php';
class RegistrationCountingConnection extends mysqli {
    public int $academicReads = 0;
    public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): mysqli_result|bool {
        if (preg_match('/FROM\s+(department|education_level|academic_program|grade_level|section)\b/i', $query)) { $this->academicReads++; }
        return parent::query($query, $result_mode);
    }
}
$config = databaseConfiguration();
$db = new RegistrationCountingConnection($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
$service = new AuthService();
$user = new ReflectionProperty(AuthService::class, 'user'); $user->setValue($service, new User($db));
$namespace = hash('sha256', realpath(__DIR__ . '/../app/models') . '|' . $config['host'] . '|' . $config['port'] . '|' . $config['database']);
$cache = new PublicCatalogCache(sys_get_temp_dir() . '/olshco-registration-academics-' . $namespace . '.json'); $cache->clear();
$first = $service->getRegistrationOptions();
if ($db->academicReads !== 5) { throw new RuntimeException('Cold lookup must read five academic lists'); }
$second = $service->getRegistrationOptions();
if ($db->academicReads !== 5 || $first !== $second) { throw new RuntimeException('Warm registration academic lists must avoid queries and preserve options'); }
$model = new AcademicStructure(); $model->beginTransaction(); $model->commit();
$service->getRegistrationOptions();
if ($db->academicReads !== 10) { throw new RuntimeException('Academic commit must invalidate registration cache'); }
echo "PASS: registration options parity, five-to-zero warm academic queries and shared commit invalidation.\n";
