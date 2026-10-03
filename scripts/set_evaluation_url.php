<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$url = $argv[1] ?? '';
$localPreflight = $url === '--local-preflight';
$clear = $url === '--clear-url';
if ($localPreflight) $url = 'https://evaluation-preflight.invalid';
if ($clear) $url = '';
if (!$localPreflight && !$clear && !preg_match('~\Ahttps://[a-z0-9]+(?:-[a-z0-9]+)*\.trycloudflare\.com\z~D', $url)) {
    fwrite(STDERR, "A generated HTTPS Quick Tunnel URL is required.\n");
    exit(1);
}
$path = __DIR__ . '/../deployment/tunnel/evaluation/private/environment.php';
$settings = require $path;
if (!is_array($settings) || ($settings['OLSHCO_DB_NAME'] ?? '') !== 'olshco_evaluation') {
    fwrite(STDERR, "Dedicated evaluation settings are required.\n");
    exit(1);
}
$settings['OLSHCO_APP_URL'] = $url;
$temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
file_put_contents($temporary, "<?php\n// Private evaluation settings.\nreturn " . var_export($settings, true) . ";\n");
if (!rename($temporary, $path)) {
    unlink($temporary);
    fwrite(STDERR, "Cannot update evaluation URL.\n");
    exit(1);
}
echo "Evaluation public URL updated; original configuration unchanged.\n";
