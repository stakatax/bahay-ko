<?php
require_once __DIR__ . '/../app/models/AcademicStructure.php';
require_once __DIR__ . '/../app/models/PublicCatalogCache.php';
require_once __DIR__ . '/../config/database.php';
function catalogCheck(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
$path = __DIR__ . '/catalog-cache-' . bin2hex(random_bytes(6)) . '.json';
try {
    $cache = new PublicCatalogCache($path);
    $calls = 0;
    $load = static function () use (&$calls): array { $calls++; return ['levels' => [1, 2]]; };
    $expected = $cache->remember($load);
    catalogCheck($cache->remember($load) === $expected && $calls === 1, 'Warm request avoids loader');
    $cache->clear(); $cache->remember($load);
    catalogCheck($calls === 2, 'Invalidation reloads');
    file_put_contents($path, '{broken'); $cache->remember($load);
    catalogCheck($calls === 3, 'Corrupt cache falls back');
    file_put_contents($path, json_encode(['expires' => time()-1, 'data' => []])); $cache->remember($load);
    catalogCheck($calls === 4, 'Expiration reloads');
    class CatalogCountingConnection extends mysqli {
        public int $reads = 0;
        public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): mysqli_result|bool { $this->reads++; return parent::query($query, $result_mode); }
    }
    $config = databaseConfiguration();
    $db = new CatalogCountingConnection($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
    $model = new AcademicStructure($db);
    $cache->clear();
    $property = new ReflectionProperty(AcademicStructure::class, 'catalogCache'); $property->setValue($model, $cache);
    $first = $model->getPublicCatalog();
    catalogCheck($db->reads === 3, 'Cold catalog reads three queries');
    catalogCheck($model->getPublicCatalog() === $first && $db->reads === 3, 'Warm catalog is identical with zero new queries');
    $model->beginTransaction(); $model->getPublicCatalog();
    catalogCheck($db->reads === 6, 'Transaction bypasses cache');
    $model->commit(); $model->getPublicCatalog();
    catalogCheck($db->reads === 9, 'Commit invalidates cache');
    $model->beginTransaction(); $model->rollback(); $model->getPublicCatalog();
    catalogCheck($db->reads === 9, 'Rollback preserves committed cache');
    echo "PASS: catalog cache hit, expiry, corruption, invalidation, transaction behavior and 3-to-0 warm query reduction.\n";
} finally { if (is_file($path)) { unlink($path); } }
