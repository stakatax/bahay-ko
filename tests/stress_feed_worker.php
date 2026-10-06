<?php
// SELECT-only feed workload; no authentication attempts or delivery dispatch.
if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--read-only') { exit(1); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/PostService.php';
$db = openDatabaseConnection();
$actors = $db->query("SELECT MIN(u.user_id) user_id, r.role_prefix FROM user u JOIN role r ON r.role_id=u.role_id WHERE u.status='Active' AND r.role_prefix IN ('Admin','Faculty','Student','Parent') GROUP BY r.role_prefix")->fetch_all(MYSQLI_ASSOC);
$service = new PostService($db);
$results = [];
foreach ($actors as $actor) {
    $_SESSION = ['user_id' => (int) $actor['user_id'], 'role' => $actor['role_prefix']];
    for ($iteration = 0; $iteration < 3; $iteration++) {
        $start = hrtime(true);
        $feed = $service->getNewsFeed();
        if (!isset($feed['announcements'], $feed['events'], $feed['documents'], $feed['surveys'])) { throw new RuntimeException('Invalid feed shape'); }
        $results[] = ['role' => $actor['role_prefix'], 'ms' => round((hrtime(true)-$start)/1e6, 2)];
    }
}
echo json_encode(['samples' => $results, 'peak_memory_mb' => round(memory_get_peak_usage(true)/1048576, 2)], JSON_THROW_ON_ERROR);
