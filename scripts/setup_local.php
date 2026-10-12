<?php
// Local development only. Never imports a schema or changes an existing account.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/bootstrap_fresh_install.php';

$connection = null;
try {
    $arguments = array_slice($argv, 1);
    if ($arguments === [] || $arguments === ['--help']) {
        echo "Usage: php scripts/setup_local.php --configure | --check | --apply --confirm-reference-sha256=HASH\n";
        echo "See docs/local-development-setup.md. Only local olshco_development is permitted.\n";
        exit(0);
    }
    $root = dirname(__DIR__);
    if ($arguments === ['--configure']) {
        if (strtolower((string) (getenv('OLSHCO_APP_ENV') ?: 'development')) === 'production') {
            throw new RuntimeException('Local configuration is not permitted in production.');
        }
        $files = [
            'database.local.php' => array_merge(require $root . '/config/database.local.php.example', ['database' => 'olshco_development']),
            'security.local.php' => ['application_key' => bin2hex(random_bytes(32)), 'application_url' => 'http://localhost/' . rawurlencode(basename($root))],
            'email.local.php' => require $root . '/config/email.local.php.example',
            'push.local.php' => require $root . '/config/push.local.php.example',
        ];
        foreach ($files as $name => $value) {
            $path = $root . '/config/' . $name;
            // Exclusive create: never overwrite the operator's settings or secrets.
            $handle = @fopen($path, 'x');
            if ($handle === false) {
                if (!is_file($path)) throw new RuntimeException('Cannot create local configuration.');
                echo 'Kept existing config/', $name, PHP_EOL;
                continue;
            }
            try {
                $content = "<?php\nreturn " . var_export($value, true) . ";\n";
                if (fwrite($handle, $content) !== strlen($content)) throw new RuntimeException('Cannot finish local configuration.');
            } finally { fclose($handle); }
            @chmod($path, 0600);
            echo 'Created config/', $name, PHP_EOL;
        }
        foreach (['Assets/uploads/documents', 'Assets/uploads/profile-photos', 'Assets/uploads/posts', 'Assets/uploads/document-covers', 'Assets/uploads/announcement-audio', 'logs'] as $directory) {
            $path = $root . '/' . $directory;
            if (!is_dir($path) && !mkdir($path, 0770, true)) throw new RuntimeException('Cannot create runtime directory.');
        }
        echo "Local files ready. Edit database.local.php if needed; import the schema into a NEW EMPTY olshco_development database. Email/push start disabled.\n";
        exit(0);
    }
    $apply = count($arguments) === 2 && $arguments[0] === '--apply'
        && str_starts_with($arguments[1], '--confirm-reference-sha256=');
    if (!$apply && $arguments !== ['--check']) throw new RuntimeException('Unsupported arguments.');
    $path = $root . '/database/bootstrap-reference.json';
    $hash = hash_file('sha256', $path);
    if ($apply && !hash_equals($hash, substr($arguments[1], strlen('--confirm-reference-sha256=')))) {
        throw new RuntimeException('Reference checksum does not match.');
    }
    $config = databaseConfiguration();
    if ($config['database'] !== 'olshco_development'
        || !in_array($config['host'], ['localhost', '127.0.0.1', '::1'], true)) {
        throw new RuntimeException('Configure only local olshco_development; existing application databases are refused.');
    }
    $connection = openDatabaseConnection();
    $bootstrap = new FreshInstallBootstrap($connection, json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR), true);
    if (!$apply) {
        echo json_encode($bootstrap->inspect(), JSON_PRETTY_PRINT), PHP_EOL, 'Reference SHA-256: ', $hash, PHP_EOL;
        exit(0);
    }
    $administrator = [];
    foreach (['first_name', 'last_name', 'email', 'password', 'birthdate', 'gender'] as $field) {
        $administrator[$field] = getenv('OLSHCO_BOOTSTRAP_ADMIN_' . strtoupper($field));
    }
    FreshInstallBootstrap::validateAdministrator($administrator);
    if ((int) $connection->query("SELECT GET_LOCK('olshco_local_setup',0) AS acquired")->fetch_assoc()['acquired'] !== 1) {
        throw new RuntimeException('Another local setup is running.');
    }
    try {
        $connection->begin_transaction();
        $result = $bootstrap->stage($administrator);
        $connection->commit();
        echo 'PASS: ', json_encode($result), PHP_EOL;
    } catch (Throwable $exception) { $connection->rollback(); throw $exception; }
    finally { $connection->query("SELECT RELEASE_LOCK('olshco_local_setup')"); }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception instanceof mysqli_sql_exception
        ? "Local setup database check failed. Check the imported schema, local settings and database service.\n"
        : $exception->getMessage() . PHP_EOL);
    exit(1);
} finally { if ($connection instanceof mysqli) $connection->close(); }
