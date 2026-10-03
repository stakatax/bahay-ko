<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/controllers/PostController.php';

$checks = 0;
function connectionCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
function connectionProperty(object $object, string $name): mixed {
    return (new ReflectionProperty($object, $name))->getValue($object);
}
function retainedConnections(object $root): int {
    $seen = new SplObjectStorage();
    $connections = [];
    $walk = function (object $object) use (&$walk, $seen, &$connections): void {
        if ($seen->contains($object)) { return; }
        $seen->attach($object);
        if ($object instanceof mysqli) { $connections[$object->thread_id] = true; return; }
        $reflection = new ReflectionObject($object);
        if ($reflection->isInternal()) { return; }
        foreach ($reflection->getProperties() as $property) {
            if (!$property->isStatic() && $property->isInitialized($object)) {
                $value = $property->getValue($object);
                if (is_object($value)) { $walk($value); }
            }
        }
    };
    $walk($root);
    return count($connections);
}
$controller = new PostController();
$service = connectionProperty($controller, 'service');
connectionCheck(retainedConnections($controller) === 7, 'Constructor retains seven connections, previously eleven');
foreach (['notifications', 'redundancy'] as $name) {
    connectionCheck(connectionProperty($service, $name) === null, 'Unused posting dependency deferred');
}
connectionCheck(connectionProperty($controller, 'governmentAdvisory') === null, 'Advisory conversion deferred');
$_SESSION = ['user_id'=>0];
$feed = $service->getNewsFeed(); // SELECT-only guest service read, not an authenticated route bypass.
connectionCheck(isset($feed['announcements'], $feed['events'], $feed['documents'], $feed['surveys']), 'Actual feed still loads');
connectionCheck(connectionProperty($service, 'notifications') === null && connectionProperty($service, 'redundancy') === null,
    'Feed does not initialize action-only dependencies');
echo 'Guest feed SHA256: ', hash('sha256', serialize($feed)), PHP_EOL;
$result = $service->assessContentRedundancy(['post_type'=>'announcement']);
connectionCheck($result['matches'] === [], 'Public preflight initializes dependency and handles empty content');
$redundancy = connectionProperty($service, 'redundancy');
$service->assessContentRedundancy(['post_type'=>'announcement']);
connectionCheck(connectionProperty($service, 'redundancy') === $redundancy, 'Repeated preflight reuses service');
try {
    $service->assessContentRedundancy(['post_type'=>'invalid']);
    throw new RuntimeException('Invalid type accepted');
} catch (InvalidArgumentException $e) { connectionCheck(true, 'Preflight validation preserved'); }
foreach ([[$service, 'getNotifications', NotificationService::class],
          [$controller, 'getGovernmentAdvisory', GovernmentAdvisoryIntakeService::class]] as [$object, $method, $class]) {
    $reflection = new ReflectionMethod($object, $method);
    $dependency = $reflection->invoke($object);
    connectionCheck($dependency instanceof $class, 'Deferred dependency initializes');
    connectionCheck($reflection->invoke($object) === $dependency, 'Deferred dependency reused');
}
$advisory = connectionProperty($controller, 'governmentAdvisory');
try { $advisory->getConversionCandidate(0); throw new RuntimeException('Invalid advisory ID accepted'); }
catch (InvalidArgumentException $e) { connectionCheck(true, 'Advisory ID validation preserved'); }
connectionCheck(retainedConnections($controller) === 11, 'All original connections available once actions need them');
$sharedConnection = openDatabaseConnection();
$sharedController = new PostController($sharedConnection);
connectionCheck(retainedConnections($sharedController) === 1, 'Feed controller retains one injected connection');
$sharedService = connectionProperty($sharedController, 'service');
foreach (['event','announcement','document','survey','engagement','contentInterest','studentProfile'] as $property) {
    connectionCheck(connectionProperty($sharedService, $property)->getDatabaseConnection() === $sharedConnection,
        'Feed model reuses supplied connection');
}
connectionCheck($sharedService->getNewsFeed() === $feed, 'Shared connection guest feed parity');
$actors = $sharedConnection->query("SELECT MIN(u.user_id) user_id,r.role_prefix FROM user u
    JOIN role r ON r.role_id=u.role_id WHERE u.status='Active' GROUP BY r.role_prefix")->fetch_all(MYSQLI_ASSOC);
foreach ($actors as $actor) {
    $_SESSION = ['user_id'=>(int)$actor['user_id'],'role'=>$actor['role_prefix']];
    connectionCheck((new PostService())->getNewsFeed() === $sharedService->getNewsFeed(), 'Exact sampled role feed parity');
}
$sharedConnection->close();
echo "PASS: $checks Home connection checks; database reads only, no external delivery.\n";
