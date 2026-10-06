<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../app/services/PostService.php';
require_once __DIR__.'/../config/database.php';
class InterestTestConnection extends mysqli {
    public int $reads=0;
    public function prepare(string $sql):mysqli_stmt|false { $this->reads++; return parent::prepare($sql); }
}
$checks=0;
function interestCheck(bool $ok,string $label):void { global $checks; if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks++; }
$c=databaseConfiguration();$db=new InterestTestConnection($c['host'],$c['username'],$c['password'],$c['database'],$c['port']);$db->set_charset('utf8mb4');
try {
    foreach(['student_profile','student_profile_interest','content_interest','content_interest_assignment'] as $table) {
        $ddl=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
        $ddl=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1);
        $ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);
        $ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->query($ddl);
    }
    $db->query("INSERT INTO content_interest (interest_id,interest_name,interest_slug,status,sort_order) VALUES
        (1,'One','one','Active',2),(2,'Two','two','Active',1),(3,'Hidden','hidden','Inactive',0)");
    $db->query("INSERT INTO student_profile (student_profile_id,user_id,completion_status,personalization_enabled) VALUES (1,1,'Completed',1)");
    $db->query("INSERT INTO student_profile_interest (student_profile_id,interest_id,preference_weight) VALUES (1,1,5),(1,2,3),(1,3,4)");
    $db->query("INSERT INTO content_interest_assignment (content_type,content_id,interest_id) VALUES
        ('announcement',1,1),('announcement',1,2),('announcement',1,3),('event',1,2),('document',2,1),('survey',1,1)");
    $topics = new ContentInterest($db);
    $db->reads = 0;
    $sets = ['announcement' => [1, '1', 0, -1], 'event' => [1], 'document' => [2], 'survey' => [1]];
    $mixed = $topics->getAssignmentSets($sets);
    interestCheck($db->reads === 1, 'Mixed feed topics use one query');
    interestCheck(array_column($mixed['announcement'][1], 'interest_id') === [2, 1], 'Topic sort and inactive filtering preserved');
    interestCheck(array_column($mixed['event'][1], 'interest_id') === [2], 'Same numeric ID in different types stays separate');
    foreach ($sets as $type => $ids) {
        interestCheck($mixed[$type] === $topics->getAssignmentMap($type, $ids), 'Mixed/single topic parity');
    }
    $db->reads = 0;
    interestCheck($topics->getAssignmentSets(['announcement' => [], 'event' => [0, -1]]) === ['announcement' => [], 'event' => []] && $db->reads === 0, 'Empty/invalid topic sets avoid queries');
    $db->reads = 0;
    $large = $topics->getAssignmentSets(['announcement' => range(1, 300), 'event' => range(1, 300)]);
    interestCheck($db->reads === 2 && $large['announcement'] === $mixed['announcement'] && $large['event'] === $mixed['event'], 'Mixed topic chunks preserve maps');
    try { $topics->getAssignmentSets(['invalid' => [1]]); interestCheck(false, 'Invalid topic content type rejected'); }
    catch (InvalidArgumentException $error) { interestCheck(true, 'Invalid topic content type rejected'); }
    $model=new StudentProfile($db);
    $service=(new ReflectionClass(PostService::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(PostService::class,'studentProfile'))->setValue($service,$model);
    $weights=new ReflectionMethod(PostService::class,'getCurrentUserInterestWeights');
    foreach(['NotStarted','InProgress','Completed'] as $status) {
        foreach([0,1] as $enabled) {
            $db->query("UPDATE student_profile SET completion_status='$status',personalization_enabled=$enabled");
            $db->reads=0;$profile=$model->findByUserId(1);
            interestCheck($db->reads===2,'Previous existing-profile path takes two queries');
            $expected=[];
            if($profile['completion_status']==='Completed' && $profile['personalization_enabled']) {
                foreach($profile['interests'] as $i){$expected[(int)$i['interest_id']]=max(1,min(5,(int)$i['preference_weight']));}
            }
            $_SESSION=['user_id'=>1,'role'=>'Student'];$db->reads=0;
            interestCheck($weights->invoke($service)===$expected,'Exact preferences and order parity');
            interestCheck($db->reads===1,'New path takes one query');
        }
    }
    interestCheck($weights->invoke($service)===[1=>5,2=>3],'Inactive interests excluded');
    foreach(['Admin','Faculty','Parent','Guest'] as $role) {
        $_SESSION=['user_id'=>1,'role'=>$role];$db->reads=0;
        interestCheck($weights->invoke($service)===[] && $db->reads===0,'Other roles do not read Student interests');
    }
    foreach([0,-1,999] as $id){$_SESSION=['user_id'=>$id,'role'=>'Student'];interestCheck($weights->invoke($service)===[],'Missing/invalid profile returns no weights');}
    $_SESSION=['user_id'=>1,'role'=>'Student'];
    $db->query('DELETE FROM student_profile_interest');
    interestCheck($weights->invoke($service)===[],'Empty preferences supported');
    echo "PASS: $checks feed-interest checks; fixture writes use temporary tables only.\n";
} finally {$db->close();}
