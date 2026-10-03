<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../app/services/AccountApprovalService.php';
require_once __DIR__.'/../config/database.php';
$db=openDatabaseConnection();$checks=0;
function badgeCheck(bool $ok,string $label):void {global $checks;if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks++;}
try {
    foreach(['user','role','department','education_level','academic_program','grade_level','section','parent_student'] as $table){
        $ddl=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
        $ddl=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1);
        $ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);
        $ddl=preg_replace('/,\n\)/',"\n)",$ddl);$db->query($ddl);
    }
    $service=new AccountApprovalService($db);$user=new User($db);
    badgeCheck($service->countPendingRegistrations()===0,'Empty queue');
    $db->query("INSERT INTO role (role_id,role_prefix) VALUES (1,'Admin'),(2,'Student'),(3,'Parent'),(4,'Faculty')");
    $stmt=$db->prepare("INSERT INTO user (user_id,first_name,last_name,password,gender,age,status,role_id)
        VALUES (?,'Fixture','User','not-a-password','Other',20,?,?)");
    foreach([[1,'Pending',2],[2,'Pending',3],[3,'Pending',1],[4,'Pending',4],[5,'Active',2],
        [6,'Rejected',3],[7,'Inactive',2],[8,'Pending',3],[9,'Pending',999]] as $row){$stmt->bind_param('isi',...$row);$stmt->execute();}
    $db->query("INSERT INTO parent_student (parent_user_id,student_user_id,relationship,status) VALUES
        (2,5,'Guardian','Verified'),(2,7,'Guardian','Pending'),(2,1,'Guardian','Rejected')");
    badgeCheck($service->countPendingRegistrations()===5,'Same status/role filtering and Parent row multiplicity');
    foreach([-1,0,1,2,3,5,100,200,999] as $limit){
        badgeCheck($service->countPendingRegistrations($limit)===count($user->getPendingRegistrations($limit)),'Exact queue count parity with limit normalization');
    }
    for($id=10;$id<=220;$id++){$status='Pending';$role=2;$stmt->bind_param('isi',$id,$status,$role);$stmt->execute();}
    $stmt->close();
    foreach([1,99,100,101,200,999] as $limit){
        badgeCheck($service->countPendingRegistrations($limit)===count($user->getPendingRegistrations($limit)),'Large queue cap preserved');
    }
    badgeCheck($service->countPendingRegistrations()===100,'Badge remains capped at 100');
    $serviceUser=(new ReflectionProperty($service,'user'))->getValue($service);
    $notifications=(new ReflectionProperty($service,'notifications'))->getValue($service);
    badgeCheck($serviceUser->getDatabaseConnection()===$db,'User reuses request connection');
    badgeCheck((new ReflectionProperty($notifications,'conn'))->getValue($notifications)===$db,'Notification service reuses same connection');
    echo "PASS: $checks pending-badge checks; temporary tables only.\n";
}finally{$db->close();}
