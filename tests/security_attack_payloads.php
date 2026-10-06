<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/services/TrustedGovernmentSourceFetcher.php';
$checks = 0;
function attackCheck(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
// Real prepared login lookup; read-only, no login attempts or account lockouts.
$user = new User();
foreach (["' OR 1=1 -- ", "' UNION SELECT NULL -- ", "admin'/*", "' OR '1'='1", "' AND SLEEP(2) -- "] as $payload) {
    attackCheck(!$user->findByIdentifier($payload), 'SQL payload cannot select an account');
}
class AttackSourceFixture extends GovernmentAdvisory {
    public function __construct() {}
    public function findActiveSourceByHost(string $host): ?array {
        return in_array($host, ['approved.example', 'www.approved.example'], true)
            ? ['government_source_id' => 1] : null;
    }
}
$fetcher = new TrustedGovernmentSourceFetcher(new AttackSourceFixture());
$redirect = new ReflectionMethod($fetcher, 'validatedRedirect');
foreach (['https://127.0.0.1/', 'https://169.254.169.254/latest/meta-data/', 'https://[::1]/', '//evil.example/',
    'http://approved.example/', 'file:///etc/passwd', 'https://user:pass@approved.example/', 'https://approved.example:8443/'] as $payload) {
    try { $redirect->invoke($fetcher, 'https://approved.example/start', $payload, 1); }
    catch (InvalidArgumentException|RuntimeException $exception) { $checks++; continue; }
    throw new RuntimeException('FAIL: unsafe advisory redirect accepted');
}
attackCheck($redirect->invoke($fetcher, 'https://approved.example/folder/start', '../notice', 1)
    === 'https://approved.example/notice', 'Relative trusted redirect remains supported');
attackCheck($redirect->invoke($fetcher, 'https://approved.example/start', 'https://www.approved.example/notice', 1)
    === 'https://www.approved.example/notice', 'Approved alias redirect remains supported');
$host = new ReflectionMethod($fetcher, 'assertPublicHost');
foreach (['127.0.0.1', '169.254.169.254', '10.0.0.1', '192.168.1.1', '100.64.0.1', '198.18.0.1', '192.0.0.1'] as $payload) {
    try { $host->invoke($fetcher, $payload); }
    catch (RuntimeException $exception) { $checks++; continue; }
    throw new RuntimeException('FAIL: restricted advisory IP accepted');
}
echo "PASS: $checks SQL/SSRF payload checks; read-only account lookup, no outbound HTTP or account changes.\n";
