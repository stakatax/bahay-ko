<?php
// Read-only local profiler. Counts cover injected models, not all request SQL.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/PostService.php';
require_once __DIR__ . '/../config/database.php';
class HomeProfileConnection extends mysqli {
    public int $statements = 0;
    public string $announcementFeedSql = '';
    public function prepare(string $sql): mysqli_stmt|false {
        $this->statements++;
        if (preg_match('/FROM\s+announcements\s+a\b/i', $sql)) { $this->announcementFeedSql = $sql; }
        return parent::prepare($sql);
    }
    public function query(string $sql, int $mode = MYSQLI_STORE_RESULT): mysqli_result|bool {
        $this->statements++; return parent::query($sql, $mode);
    }
}
$config = databaseConfiguration();
$lookup = openDatabaseConnection();
$actors = $lookup->query("SELECT MIN(u.user_id) user_id,r.role_prefix FROM user u
    JOIN role r ON r.role_id=u.role_id WHERE u.status='Active' GROUP BY r.role_prefix")->fetch_all(MYSQLI_ASSOC);
$lookup->close();
foreach ($actors as $actor) {
    if (!in_array($actor['role_prefix'], ['Admin','Faculty','Student','Parent'], true)) { continue; }
    $_SESSION = ['user_id'=>(int)$actor['user_id'], 'role'=>$actor['role_prefix']];
    $service = (new ReflectionClass(PostService::class))->newInstanceWithoutConstructor();
    $connections = [];
    try {
        foreach (['event'=>Event::class,'announcement'=>Announcement::class,'document'=>Document::class,
            'survey'=>Survey::class,'engagement'=>ContentEngagement::class,
            'contentInterest'=>ContentInterest::class,'studentProfile'=>StudentProfile::class] as $property=>$class) {
            $db = new HomeProfileConnection($config['host'],$config['username'],$config['password'],$config['database'],$config['port']);
            $db->set_charset('utf8mb4');
            $model = (new ReflectionClass($class))->newInstanceWithoutConstructor();
            (new ReflectionProperty(BaseModel::class,'conn'))->setValue($model,$db);
            (new ReflectionProperty(PostService::class,$property))->setValue($service,$model);
            $connections[$property] = $db;
        }
        $times = [];
        for ($run=0; $run<5; $run++) {
            foreach ($connections as $db) { $db->statements=0; }
            $start=hrtime(true); $feed=$service->getNewsFeed(); $times[]=(hrtime(true)-$start)/1e6;
        }
        sort($times); $counts=[];
        foreach ($connections as $name=>$db) { $counts[$name]=$db->statements; }
        echo json_encode(['role'=>$actor['role_prefix'],
            'visible'=>array_map('count',array_intersect_key($feed,array_flip(['announcements','events','documents','surveys']))),
            'median_ms'=>round($times[2],2), 'range_ms'=>[round($times[0],2),round($times[4],2)],
            'instrumented_model_statements'=>$counts]), PHP_EOL;
        if ($actor['role_prefix']==='Admin') {
            $db=$connections['announcement'];
            $sql=str_replace('LIMIT ?', 'LIMIT 100', $db->announcementFeedSql);
            foreach ($db->query('EXPLAIN '.$sql)->fetch_all(MYSQLI_ASSOC) as $row) {
                echo json_encode(['announcement_plan'=>array_intersect_key($row,
                    array_flip(['select_type','table','type','key','rows','Extra']))]),PHP_EOL;
            }
        }
    } finally {
        foreach ($connections as $db) { $db->close(); }
    }
}
