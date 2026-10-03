<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Notification.php';
$db=openDatabaseConnection();
$db->query("CREATE TEMPORARY TABLE role (role_id INT PRIMARY KEY, role_prefix VARCHAR(20))");
$db->query("CREATE TEMPORARY TABLE user (user_id INT PRIMARY KEY, role_id INT, status VARCHAR(20))");
$db->query("CREATE TEMPORARY TABLE notification_category_preference (user_id INT, notification_category VARCHAR(40), system_enabled TINYINT, email_enabled TINYINT, browser_push_enabled TINYINT, PRIMARY KEY(user_id,notification_category))");
$model=new Notification($db);$checks=0;
function categoryCheck(bool $ok,string $why):void { global $checks; if(!$ok)throw new RuntimeException($why);$checks++; }
foreach(['Admin','Faculty','Student','Parent'] as $i=>$role){
 $id=$i+1;$s=$db->prepare('INSERT INTO role VALUES (?,?)');$s->bind_param('is',$id,$role);$s->execute();
 $db->query("INSERT INTO user VALUES ($id,$id,'Active')");
 $db->query("INSERT INTO notification_category_preference VALUES ($id,'workflow',1,1,1)");
 $model->updateCategoryPreferences($id,['workflow'=>['system_enabled'=>false,'email_enabled'=>false,'browser_push_enabled'=>false], 'content_updates'=>['system_enabled'=>true]]);
 $r=$db->query("SELECT system_enabled,email_enabled,browser_push_enabled FROM notification_category_preference WHERE user_id=$id AND notification_category='workflow'")->fetch_assoc();
 $expected=in_array($role,['Admin','Faculty'],true)?0:1;
 categoryCheck(array_sum(array_map('intval',$r))===$expected*3,'Workflow restriction for '.$role);
 $r=$db->query("SELECT system_enabled FROM notification_category_preference WHERE user_id=$id AND notification_category='content_updates'")->fetch_assoc();
 categoryCheck((int)$r['system_enabled']===1,'Content preferences remain editable for '.$role);
}
foreach([0,99,5] as $id){
 if($id===5)$db->query("INSERT INTO user VALUES (5,3,'Inactive')");
 try { $model->updateCategoryPreferences($id,[]); throw new LogicException('Invalid actor accepted'); }
 catch(InvalidArgumentException|RuntimeException $e){ categoryCheck(true,'Invalid/inactive actor rejected'); }
}
echo "Notification category roles: {$checks} checks passed; temporary tables only.\n";
