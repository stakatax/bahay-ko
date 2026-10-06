<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$temp = sys_get_temp_dir() . '/olshco-config-' . bin2hex(random_bytes(10));
$directories = ['', '/config', '/scripts', '/vendor', '/Assets', '/Assets/uploads', '/Assets/uploads/documents', '/app', '/app/models', '/app/services'];
$files = [];
$checks = 0;
function configurationCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
try {
    foreach ($directories as $directory) { mkdir($temp . $directory, 0700); }
    $write = static function (string $relative, string $contents) use ($temp, &$files): void {
        $path = $temp . '/' . $relative;
        file_put_contents($path, $contents);
        $files[$path] = true;
    };
    foreach (['database', 'security', 'email', 'push', 'push-endpoint'] as $name) {
        $write('config/' . $name . '.php', file_get_contents($root . '/config/' . $name . '.php'));
    }
    $write('scripts/check_deployment_configuration.php', file_get_contents($root . '/scripts/check_deployment_configuration.php'));
    $write('vendor/autoload.php', '<?php require_once ' . var_export($root . '/vendor/autoload.php', true) . ';');
    $environment = getenv();
    foreach (array_keys($environment) as $name) {
        if (str_starts_with($name, 'OLSHCO_')) { unset($environment[$name]); }
    }
    $run = static function (array $arguments = [], array $settings = [], string $entry = 'scripts/check_deployment_configuration.php') use ($temp, $environment): array {
        $process = proc_open(array_merge([PHP_BINARY, $temp . '/' . $entry], $arguments),
            [['pipe','r'], ['pipe','w'], ['pipe','w']], $pipes, $temp, array_merge($environment, $settings));
        if (!is_resource($process)) { throw new RuntimeException('Cannot run fixture preflight'); }
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $exit = proc_close($process);
        configurationCheck(!str_contains($out . $err, 'secret-fixture'), 'No fixture secret exposed');
        return [$exit, $out, $err];
    };
    [$exit, $out, $err] = $run(['--help']);
    configurationCheck($exit === 0 && str_contains($out, 'Read-only') && $err === '', 'Help has no config dependency');
    [$exit] = $run(['--unknown']);
    configurationCheck($exit === 2, 'Invalid option rejected');
    [$exit, $out, $err] = $run();
    configurationCheck($exit === 1 && $err === '', 'Missing configurations fail safely');
    foreach (['Database configuration', 'Application security key', 'Trusted application URL', 'Email configuration', 'Web Push configuration'] as $label) {
        configurationCheck(str_contains($out, 'FAIL: ' . $label), 'Missing ' . $label . ' reported');
    }
    // Loading the real delivery class must not read push credentials or open a DB connection.
    $write('app/services/BrowserPushDeliveryService.php', file_get_contents($root . '/app/services/BrowserPushDeliveryService.php'));
    $write('app/models/PushDelivery.php', '<?php class PushDelivery {}');
    $write('load.php', '<?php require __DIR__ . "/app/services/BrowserPushDeliveryService.php"; echo "LOADED\n"; try { new BrowserPushDeliveryService(); echo "UNEXPECTED\n"; } catch (RuntimeException $exception) { echo "FEATURE_REQUIRES_CONFIGURATION\n"; }');
    [$exit, $out, $err] = $run([], [], 'load.php');
    configurationCheck($exit === 0 && $err === '' && str_contains($out, 'LOADED'), 'Service include needs no push config');
    configurationCheck(str_contains($out, 'FEATURE_REQUIRES_CONFIGURATION') && !str_contains($out, 'UNEXPECTED'), 'Actual use still requires push config');
    $key = str_repeat('secret-fixture-', 5);
    $production = ['OLSHCO_APP_ENV' => 'production', 'OLSHCO_DB_HOST' => 'unreachable.example.invalid',
        'OLSHCO_DB_USER' => 'hub_runtime', 'OLSHCO_DB_PASSWORD' => 'secret-fixture-db', 'OLSHCO_DB_NAME' => 'fixture',
        'OLSHCO_APP_KEY' => $key, 'OLSHCO_APP_URL' => 'https://hub.example.invalid/school'];
    $write('config/email.local.php', '<?php return ' . var_export(['enabled' => true, 'host' => 'smtp.example.invalid', 'username' => 'sender@example.invalid',
        'password' => 'secret-fixture-smtp', 'from_email' => 'sender@example.invalid'], true) . ';');
    $public = rtrim(strtr(base64_encode("\x04" . str_repeat('p', 64)), '+/', '-_'), '=');
    $private = rtrim(strtr(base64_encode(str_repeat('k', 32)), '+/', '-_'), '=');
    $write('config/push.local.php', '<?php return ' . var_export(['subject' => 'mailto:admin@example.invalid', 'public_key' => $public, 'private_key' => $private], true) . ';');
    [$exit, $out, $err] = $run(['--production'], $production);
    configurationCheck($exit === 0 && $err === '' && str_contains($out, '0 failed'), 'Complete fixture production configuration succeeds without connecting');
    configurationCheck(!str_contains($out, $public) && !str_contains($out, $private), 'No VAPID keys exposed');
    foreach (['http://hub.example.invalid', 'https://secret-fixture-user:secret-fixture-pass@hub.example.invalid',
        'https://hub.example.invalid?secret-fixture-query', 'https://hub.example.invalid/#secret-fixture-fragment'] as $url) {
        [$exit, $out] = $run(['--production'], array_replace($production, ['OLSHCO_APP_URL' => $url]));
        configurationCheck($exit === 1 && str_contains($out, 'FAIL: Trusted application URL'), 'Unsafe production URL rejected');
    }
    $write('config/security.local.php', '<?php return ' . var_export(['application_key' => $key, 'application_url' => 'https://hub.example.invalid'], true) . ';');
    $missing = $production; unset($missing['OLSHCO_APP_KEY'], $missing['OLSHCO_APP_URL']);
    [$exit, $out] = $run(['--production'], $missing);
    configurationCheck($exit === 1 && str_contains($out, 'FAIL: Application security key') && str_contains($out, 'FAIL: Trusted application URL'), 'Production preflight requires explicit security environment');
    $write('config/email.local.php', '<?php return ["enabled" => false];');
    [$exit, $out] = $run(['--production'], $production);
    configurationCheck($exit === 1 && str_contains($out, 'FAIL: Email delivery enabled'), 'Disabled production password recovery flagged');
    [$exit, $out] = $run([], array_replace($production, ['OLSHCO_APP_ENV' => 'development']));
    configurationCheck($exit === 0 && str_contains($out, 'WARN: Email is disabled'), 'Local disabled email warns explicitly');
    $write('config/email.local.php', '<?php echo "secret-fixture-output"; throw new RuntimeException("secret-fixture-exception");');
    [$exit, $out, $err] = $run([], $production);
    configurationCheck($exit === 1 && $err === '' && str_contains($out, 'FAIL: Email configuration'), 'Throwing configuration cannot leak message or output');
    // CGI requests must stop before reading configuration.
    $cgi = dirname(PHP_BINARY) . '/php-cgi' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
    $process = proc_open([$cgi, '-d', 'cgi.force_redirect=0'], [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, $temp,
        array_merge($environment, ['REDIRECT_STATUS'=>'1', 'GATEWAY_INTERFACE'=>'CGI/1.1', 'REQUEST_METHOD'=>'GET',
            'SCRIPT_FILENAME'=>$temp . '/scripts/check_deployment_configuration.php', 'SCRIPT_NAME'=>'/check.php']));
    fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]); fclose($pipes[2]); proc_close($process);
    configurationCheck(str_contains($out, 'Status: 404') && !str_contains($out, 'PASS:') && $err === '', 'Preflight refuses HTTP access');
    echo "PASS: $checks deployment configuration checks; isolated fixtures, no connections or deliveries.\n";
} finally {
    foreach (array_keys($files) as $path) { if (is_file($path)) { unlink($path); } }
    foreach (array_reverse($directories) as $directory) { rmdir($temp . $directory); }
}
