<?php
if (PHP_SAPI !== 'cli') { exit(1); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/PostService.php';
class FeedProfileStatement extends mysqli_stmt {
    public function __construct(private FeedProfileConnection $database, private string $sql) {
        $start = hrtime(true); parent::__construct($database, $sql);
        $database->record($sql, 'prepare', (hrtime(true)-$start)/1e6);
    }
    public function execute(?array $params = null): bool {
        $start = hrtime(true);
        try { return parent::execute($params); }
        finally { $this->database->record($this->sql, 'execute', (hrtime(true)-$start)/1e6); }
    }
}
class FeedProfileConnection extends mysqli {
    public array $timings = [];
    public function record(string $sql, string $phase, float $ms): void {
        $key = preg_replace('/\s+/', ' ', trim($sql));
        $this->timings[] = ['sql' => $key, 'phase' => $phase, 'ms' => $ms];
    }
    public function prepare(string $query): mysqli_stmt|false { return new FeedProfileStatement($this, $query); }
    public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT): mysqli_result|bool {
        $start = hrtime(true);
        try { return parent::query($query, $result_mode); }
        finally { $this->record($query, 'query', (hrtime(true)-$start)/1e6); }
    }
}
$config = databaseConfiguration();
$db = new FeedProfileConnection($config['host'], $config['username'], $config['password'], $config['database'], $config['port']); $db->set_charset('utf8mb4');
$actors = $db->query("SELECT MIN(u.user_id) user_id, r.role_prefix FROM user u JOIN role r ON r.role_id=u.role_id WHERE u.status='Active' AND r.role_prefix IN ('Admin','Faculty','Student','Parent') GROUP BY r.role_prefix")->fetch_all(MYSQLI_ASSOC);
$service = new PostService($db); $results = [];
foreach ($actors as $actor) {
    $_SESSION = ['user_id' => (int) $actor['user_id'], 'role' => $actor['role_prefix']];
    for ($iteration = 0; $iteration < 5; $iteration++) {
        $db->timings = []; $start = hrtime(true); $feed = $service->getNewsFeed(); $total = (hrtime(true)-$start)/1e6;
        $databaseMs = array_sum(array_column($db->timings, 'ms'));
        $results[] = ['role' => $actor['role_prefix'], 'total_ms' => $total, 'database_ms' => $databaseMs, 'other_ms' => max(0, $total-$databaseMs), 'executions' => count(array_filter($db->timings, fn(array $row): bool => $row['phase'] !== 'prepare')), 'timings' => $db->timings, 'fingerprint' => hash('sha256', serialize($feed))];
    }
}
echo json_encode($results, JSON_THROW_ON_ERROR);
