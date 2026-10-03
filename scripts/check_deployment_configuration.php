<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// Configuration and filesystem inspection only: no DB connection or delivery calls.
ini_set('display_errors', '0');
$arguments = array_slice($argv, 1);
if ($arguments === ['--help']) {
    echo "Usage: php scripts/check_deployment_configuration.php [--production]\n";
    echo "Read-only; no database connections, file writes, email or push. Exit 1 on a failed check.\n";
    exit(0);
}
if ($arguments !== [] && $arguments !== ['--production']) {
    fwrite(STDERR, "Unknown option. Use --help.\n");
    exit(2);
}
$production = $arguments === ['--production'] || strtolower(trim((string) getenv('OLSHCO_APP_ENV'))) === 'production';
if ($production) { putenv('OLSHCO_APP_ENV=production'); }
$root = dirname(__DIR__);
$failures = 0;
$warnings = 0;
set_error_handler(static function (int $severity): bool {
    if (!(error_reporting() & $severity)) { return false; }
    throw new ErrorException('Configuration inspection failed.');
});
$check = static function (string $label, callable $inspect, string $hint) use (&$failures): void {
    $level = ob_get_level();
    ob_start();
    try { $ok = $inspect() === true; }
    catch (Throwable $exception) { $ok = false; }
    finally { while (ob_get_level() > $level) { ob_end_clean(); } }
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $label . ($ok ? '' : ' — ' . $hint) . PHP_EOL;
    if (!$ok) { $failures++; }
};
$warn = static function (string $message) use (&$warnings): void {
    $warnings++;
    echo 'WARN: ' . $message . PHP_EOL;
};
try {
    $check('PHP 8.2 or newer', static fn() => PHP_VERSION_ID >= 80200, 'Use the Composer-supported PHP runtime.');
    foreach (['mysqli', 'fileinfo', 'mbstring', 'openssl', 'curl', 'dom', 'Phar', 'session'] as $extension) {
        $check('PHP extension ' . $extension, static fn() => extension_loaded($extension), 'Enable it in both web and CLI PHP.');
    }
    $check('Composer dependencies', static function () use ($root): bool {
        require_once $root . '/vendor/autoload.php';
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer')
            && class_exists('Minishlink\\WebPush\\WebPush') && class_exists('Smalot\\PdfParser\\Parser');
    }, 'Install the committed composer.lock dependencies; run composer check-platform-reqs.');
    $check('Database configuration', static function () use ($root): bool {
        require_once $root . '/config/database.php';
        databaseConfiguration();
        return true;
    }, 'Provide complete OLSHCO_DB_* settings; production requires a dedicated account and password.');
    $check('Application security key', static function () use ($root, $production): bool {
        require_once $root . '/config/security.php';
        if ($production && strlen(trim((string) getenv('OLSHCO_APP_KEY'))) < 64) { return false; }
        return strlen(applicationSecurityKey()) >= 64;
    }, 'Set a stable random OLSHCO_APP_KEY of at least 64 characters.');
    $check('Trusted application URL', static function () use ($root, $production): bool {
        require_once $root . '/config/security.php';
        if ($production && trim((string) getenv('OLSHCO_APP_URL')) === '') { return false; }
        $parts = parse_url(applicationBaseUrl());
        return is_array($parts)
            && !isset($parts['user']) && !isset($parts['pass'])
            && !isset($parts['query']) && !isset($parts['fragment'])
            && (!$production || strtolower($parts['scheme'] ?? '') === 'https');
    }, 'Set OLSHCO_APP_URL to the canonical application directory URL; production requires HTTPS, with no credentials/query/fragment.');
    $emailEnabled = null;
    $check('Email configuration', static function () use ($root, &$emailEnabled): bool {
        require_once $root . '/config/email.php';
        $emailEnabled = emailConfiguration()['enabled'];
        return true;
    }, 'Provision config/email.local.php using its example; enabled SMTP settings must be complete.');
    if ($emailEnabled === false) {
        if ($production) {
            $check('Email delivery enabled', static fn() => false, 'Password recovery requires email; configure SMTP before release.');
        } else { $warn('Email is disabled; password-reset and notification email will not be delivered.'); }
    }
    $check('Web Push configuration', static function () use ($root): bool {
        require_once $root . '/config/push.php';
        $configuration = pushConfiguration();
        foreach (['public_key' => 65, 'private_key' => 32] as $name => $length) {
            $key = $configuration[$name];
            if (!is_string($key) || !preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $key)) { return false; }
            $decoded = base64_decode(strtr($key, '-_', '+/'), true);
            if ($decoded === false || strlen($decoded) !== $length) { return false; }
            if ($name === 'public_key' && $decoded[0] !== "\x04") { return false; }
        }
        $subject = $configuration['subject'];
        return is_string($subject) && (str_starts_with($subject, 'mailto:')
            ? filter_var(substr($subject, 7), FILTER_VALIDATE_EMAIL) !== false
            : (filter_var($subject, FILTER_VALIDATE_URL) !== false && parse_url($subject, PHP_URL_SCHEME) === 'https'));
    }, 'Provision config/push.local.php with a contact URI and correctly encoded VAPID keys.');
    foreach (['Assets/uploads', 'Assets/uploads/documents'] as $directory) {
        $check('Writable ' . $directory, static fn() => is_dir($root . '/' . $directory) && is_writable($root . '/' . $directory), 'Grant the application identity write access to this existing directory.');
    }
    $check('PHP temporary directory', static fn() => is_dir(sys_get_temp_dir()) && is_writable(sys_get_temp_dir()), 'Provide a writable temporary directory for uploads and the worker lock.');
    if (date_default_timezone_get() !== 'Asia/Manila') { $warn('PHP timezone is not Asia/Manila; align web, worker and database scheduling timezones.'); }
    if (!$production) { $warn('Local inspection only; run --production with the deployment environment before release.'); }
    echo "Summary: $failures failed, $warnings warnings. No connections, deliveries or persistent writes performed.\n";
} finally { restore_error_handler(); }
exit($failures > 0 ? 1 : 0);
