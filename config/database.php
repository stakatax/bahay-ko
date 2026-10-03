<?php

function resolveDatabaseConfiguration(array $environment, array $local = []): array
{
    $mode = strtolower(trim((string) ($environment['OLSHCO_APP_ENV'] ?? $local['environment'] ?? 'production')));
    if (!in_array($mode, ['development', 'test', 'production'], true)) {
        throw new RuntimeException('Invalid application environment.');
    }
    $mapping = ['host'=>'OLSHCO_DB_HOST', 'username'=>'OLSHCO_DB_USER', 'password'=>'OLSHCO_DB_PASSWORD', 'database'=>'OLSHCO_DB_NAME', 'port'=>'OLSHCO_DB_PORT'];
    $hasEnvironment = count(array_intersect(array_values($mapping), array_keys($environment))) > 0;
    // Never mix an incomplete deployment configuration with local credentials.
    $configuration = $hasEnvironment || $mode === 'production' ? [] : $local;
    foreach ($mapping as $key => $variable) {
        if (array_key_exists($variable, $environment)) {
            $configuration[$key] = $environment[$variable];
        }
    }
    foreach (['host', 'username', 'password', 'database'] as $field) {
        if (!isset($configuration[$field]) || !is_string($configuration[$field])) {
            throw new RuntimeException('Database configuration is incomplete.');
        }
        if ($field !== 'password') {
            $configuration[$field] = trim($configuration[$field]);
            if ($configuration[$field] === '') {
                throw new RuntimeException('Database configuration is incomplete.');
            }
        }
    }
    $port = filter_var($configuration['port'] ?? 3306, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1, 'max_range'=>65535]]);
    if ($port === false) {
        throw new RuntimeException('Invalid database port.');
    }
    if ($mode === 'production' && (strtolower($configuration['username']) === 'root' || trim($configuration['password']) === '')) {
        throw new RuntimeException('Production requires a dedicated database account and password.');
    }
    return [
        'host'=>$configuration['host'], 'username'=>$configuration['username'],
        'password'=>$configuration['password'], 'database'=>$configuration['database'],
        'port'=>$port
    ];
}

function databaseConfiguration(): array
{
    $environment = [];
    foreach (['OLSHCO_APP_ENV','OLSHCO_DB_HOST','OLSHCO_DB_USER','OLSHCO_DB_PASSWORD','OLSHCO_DB_NAME','OLSHCO_DB_PORT'] as $key) {
        $value = getenv($key);
        if ($value !== false) {
            $environment[$key] = $value;
        }
    }
    $local = [];
    $path = __DIR__ . '/database.local.php';
    $hasDatabaseEnvironment = count(array_intersect(['OLSHCO_DB_HOST','OLSHCO_DB_USER','OLSHCO_DB_PASSWORD','OLSHCO_DB_NAME','OLSHCO_DB_PORT'], array_keys($environment))) > 0;
    if (!$hasDatabaseEnvironment && strtolower(trim($environment['OLSHCO_APP_ENV'] ?? '')) !== 'production') {
        if (is_file($path)) {
            $local = require $path;
            if (!is_array($local)) {
                throw new RuntimeException('Invalid local database configuration.');
            }
        }
    }
    return resolveDatabaseConfiguration($environment, $local);
}

function openDatabaseConnection(): mysqli
{
    $configuration = databaseConfiguration();
    $driver = new mysqli_driver();
    $previousMode = $driver->report_mode;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $connection = new mysqli(
            $configuration['host'], $configuration['username'], $configuration['password'],
            $configuration['database'], $configuration['port']
        );
        $connection->set_charset('utf8mb4');
        return $connection;
    } finally {
        mysqli_report($previousMode);
    }
}
