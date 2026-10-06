<?php
require_once __DIR__ . '/../app/models/PublicCatalogCache.php';
require_once __DIR__ . '/../config/database.php';
function resilienceCheck(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
$path = __DIR__ . '/resilience-' . bin2hex(random_bytes(8)) . '.json';
try {
    $cache = new PublicCatalogCache($path);
    $cache->remember(fn(): array => ['public' => 'original']);
    file_put_contents($path, json_encode(['expires' => 0, 'data' => ['old' => true]]));
    try { $cache->remember(function (): array { throw new RuntimeException('loader failed'); }); throw new LogicException('Failure swallowed'); }
    catch (RuntimeException $error) { resilienceCheck($error->getMessage() === 'loader failed', 'Loader errors must propagate'); }
    resilienceCheck($cache->remember(fn(): array => ['recovered' => true]) === ['recovered' => true], 'Failed loader must release lock and allow recovery');
    $unavailable = new PublicCatalogCache($path . '/missing/file.json');
    resilienceCheck($unavailable->remember(fn(): array => ['fallback' => true]) === ['fallback' => true], 'Unavailable storage must fall back');
    foreach (['../accounts', 'personal-responses'] as $name) {
        try { PublicCatalogCache::directory($name); throw new LogicException('Unsafe name accepted'); }
        catch (InvalidArgumentException $error) {}
    }
    try { PublicCatalogCache::directory('calendar-holidays', 300, 1999); throw new LogicException('Invalid year accepted'); }
    catch (InvalidArgumentException $error) {}
    $pathProperty = new ReflectionProperty(PublicCatalogCache::class, 'path');
    $first = $pathProperty->getValue(PublicCatalogCache::directory('public-catalog'));
    $oldMode = getenv('OLSHCO_APP_ENV');
    $settings = databaseConfiguration();
    $old = [];
    foreach (['OLSHCO_DB_HOST' => 'host', 'OLSHCO_DB_USER' => 'username', 'OLSHCO_DB_PASSWORD' => 'password', 'OLSHCO_DB_PORT' => 'port', 'OLSHCO_DB_NAME' => 'database'] as $env => $field) {
        $old[$env] = getenv($env); putenv($env . '=' . $settings[$field]);
    }
    try {
        putenv('OLSHCO_APP_ENV=test');
        putenv('OLSHCO_DB_NAME=cache_isolation_fixture');
        $second = $pathProperty->getValue(PublicCatalogCache::directory('public-catalog'));
        resilienceCheck($first !== $second, 'Different database names must never share a cache');
    } finally {
        putenv($oldMode === false ? 'OLSHCO_APP_ENV' : 'OLSHCO_APP_ENV=' . $oldMode);
        foreach ($old as $env => $value) { putenv($value === false ? $env : $env . '=' . $value); }
    }
    echo "PASS: cache storage fallback, loader failure recovery, namespace isolation and key validation.\n";
} finally { if (is_file($path)) { unlink($path); } }
