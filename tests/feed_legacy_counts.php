<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/PostService.php';
require_once __DIR__ . '/../config/database.php';
class LegacyCountConnection extends mysqli {
    public string $sql = '';
    public function prepare(string $sql): mysqli_stmt|false { $this->sql=$sql; return parent::prepare($sql); }
}
class OriginalAnnouncementFeed extends Announcement {
    public function getRecent(int $limit=20, bool $includeLegacyEngagement=true): array {
        return parent::getRecent($limit, true);
    }
}
$checks=0;
function legacyCheck(bool $ok,string $label):void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: '.$label); }
    $checks++;
}
$c=databaseConfiguration();
$db=new LegacyCountConnection($c['host'],$c['username'],$c['password'],$c['database'],$c['port']);
$db->set_charset('utf8mb4');
try {
    $model=new Announcement($db);
    $old=$model->getRecent(100); $oldSql=$db->sql;
    $new=$model->getRecent(100,false); $newSql=$db->sql;
    $keys=array_flip(['view_count','reaction_count','comment_count','acknowledgment_count','like_count','love_count','care_count','wow_count']);
    legacyCheck(array_map(fn($row)=>array_diff_key($row,$keys),$old) === array_map(fn($row)=>array_diff_key($row,$keys),$new),
        'Candidate fields, order and eligibility identical');
    legacyCheck($model->getRecent(100,true)===$old,'Default caller behavior preserved');
    foreach ([$oldSql,$newSql] as $index=>$sql) {
        $plan=$db->query('EXPLAIN '.str_replace('LIMIT ?','LIMIT 100',$sql))->fetch_all(MYSQLI_ASSOC);
        $dependent=count(array_filter($plan,fn($row)=>$row['select_type']==='DEPENDENT SUBQUERY'));
        legacyCheck($dependent===($index===0?8:0),'Legacy dependent subqueries 8 -> 0');
    }
    $roles=$db->query("SELECT MIN(u.user_id) user_id,r.role_prefix FROM user u JOIN role r ON r.role_id=u.role_id
        WHERE u.status='Active' GROUP BY r.role_prefix")->fetch_all(MYSQLI_ASSOC);
    $roles[]=['user_id'=>0,'role_prefix'=>'Guest'];
    foreach ($roles as $actor) {
        if (!in_array($actor['role_prefix'],['Admin','Faculty','Student','Parent','Guest'],true)) { continue; }
        $_SESSION=['user_id'=>(int)$actor['user_id'],'role'=>$actor['role_prefix']];
        $original=new PostService();
        (new ReflectionProperty(PostService::class,'announcement'))->setValue($original,new OriginalAnnouncementFeed());
        $optimized=new PostService();
        legacyCheck($original->getNewsFeed()===$optimized->getNewsFeed(),'Exact complete feed parity: '.$actor['role_prefix']);
    }
    $oldTimes=[]; $newTimes=[];
    for($i=0;$i<20;$i++) {
        foreach(($i%2===0?[true,false]:[false,true]) as $legacy) {
            $start=hrtime(true);$model->getRecent(100,$legacy);$ms=(hrtime(true)-$start)/1e6;
            if($legacy){$oldTimes[]=$ms;}else{$newTimes[]=$ms;}
        }
    }
    sort($oldTimes);sort($newTimes);
    echo json_encode(['announcement_query_median_ms'=>['before'=>round(($oldTimes[9]+$oldTimes[10])/2,3),
        'after'=>round(($newTimes[9]+$newTimes[10])/2,3)],'runs_each'=>20]),PHP_EOL;
    echo "PASS: $checks legacy-count checks; SELECT/EXPLAIN only, no data changes.\n";
} finally { $db->close(); }
