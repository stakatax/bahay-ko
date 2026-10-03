<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../app/services/PublicationTransaction.php';
require_once __DIR__.'/../app/services/PublicationNotificationDispatcher.php';
require_once __DIR__.'/../app/services/ContentReleaseService.php';
require_once __DIR__.'/../app/services/ContentWorkspaceService.php';
require_once __DIR__.'/../app/services/SurveyService.php';
require_once __DIR__.'/../app/services/PostService.php';

$checks=0;
function durableCheck(bool $ok,string $label):void { global $checks; if(!$ok)throw new RuntimeException('FAIL: '.$label);$checks++; }
function durableInject(object $object,string $property,mixed $value):void { (new ReflectionProperty($object,$property))->setValue($object,$value); }
function durableNew(string $class):object { return (new ReflectionClass($class))->newInstanceWithoutConstructor(); }
class PartialNotificationFixture extends Notification {
    public bool $fail=true;
    public function createForUser(int $userId,string $notificationType,string $title,string $message,?string $contentType=null,?int $contentId=null,?string $deduplicationKey=null,bool $inSystemVisible=true):bool {
        if($this->fail && $userId===3)throw new RuntimeException('Synthetic second-recipient failure');
        return parent::createForUser($userId,$notificationType,$title,$message,$contentType,$contentId,$deduplicationKey,$inSystemVisible);
    }
}
class DurableRedundancyFixture extends ContentRedundancyService {
    public function __construct() {}
    public function enforceSubmission(array $data): array { return []; }
}
class DurableInterestFixture extends ContentInterest {
    public function getActiveInterests(): array { return [['interest_id'=>1]]; }
    public function __construct() {}
    public function replaceAssignments(string $contentType,int $contentId,array $interestIds,int $assignedBy): array { return []; }
}
$db=openDatabaseConnection();
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try {
    $tables=['government_advisory','announcements','events','documents','survey','announcement_target','event_target','document_target','survey_target',
        'user','role','notification','notification_preference','notification_category_preference','publication_notification_outbox',
        'survey_question','survey_choice','survey_response','survey_answer','survey_answer_choice','actions','activity_log'];
    foreach($tables as $table){
        $ddl=$db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
        $ddl=preg_replace('/^CREATE TABLE /','CREATE TEMPORARY TABLE ',$ddl,1);
        $ddl=preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m','',$ddl);
        $ddl=preg_replace('/,\n\)/',"\n)",$ddl);
        $db->query($ddl);
    }
    $db->query("INSERT INTO role (role_id,role_prefix) VALUES (1,'Admin'),(2,'Student')");
    $db->query("INSERT INTO user (user_id,first_name,last_name,password,gender,age,status,role_id) VALUES
        (1,'Author','Fixture','unused','Other',20,'Active',1),
        (2,'First','Fixture','unused','Other',20,'Active',2),
        (3,'Second','Fixture','unused','Other',20,'Active',2),
        (4,'Inactive','Fixture','unused','Other',20,'Inactive',2)");
    $sources=['announcement'=>['announcements','announcement_id'],'event'=>['events','event_id'],'document'=>['documents','document_id'],'survey'=>['survey','survey_id']];
    $insert=function(string $type,int $id,string $workflow='published',bool $enabled=true)use($db,$sources):void{
        [$table,$key]=$sources[$type];$status=$type==='survey'?'Published':'active';
        $extra=match($type){'announcement'=>",content,type",'document'=>",description,file_name,file_type",default=>''};
        $values=match($type){'announcement'=>",'Fixture body','announcement'",'document'=>",'Fixture body','fixture.pdf','application/pdf'",default=>''};
        $stmt=$db->prepare("INSERT INTO $table ($key,title,workflow_status,status,user_id,send_notification $extra) VALUES (?,'Fixture',?,?,1,? $values)");
        $flag=(int)$enabled;$stmt->bind_param('issi',$id,$workflow,$status,$flag);$stmt->execute();$stmt->close();
    };
    $outbox=new PublicationNotificationOutbox($db);
    $notifications=new NotificationService($db);
    $partial=new PartialNotificationFixture($db);
    durableInject($notifications,'notification',$partial);
    $dispatcher=new PublicationNotificationDispatcher($outbox,$notifications);
    $count=fn(string $table)=> (int)$db->query('SELECT COUNT(*) AS n FROM '.$table)->fetch_assoc()['n'];

    foreach($sources as $type=>[$table,$key]){
        PublicationTransaction::run($db,$type,function()use($insert,$type){$insert($type,1);return 1;});
        durableCheck($count($table)===1,'Publication committed '.$type);
        durableCheck((int)$db->query("SELECT COUNT(*) AS n FROM publication_notification_outbox WHERE content_type='$type'")->fetch_assoc()['n']===1,'Queue committed '.$type);
    }
    $first=$dispatcher->dispatch(1,'announcement',1);
    durableCheck($first['failed']===1 && $count('notification')===1,'Partial delivery persists first recipient');
    $row=$db->query("SELECT * FROM publication_notification_outbox WHERE content_type='announcement'")->fetch_assoc();
    durableCheck($row['delivery_status']==='Pending' && (int)$row['attempt_count']===1,'Failure stays pending');
    durableCheck($row['last_error']==='RuntimeException (code 0)','Failure diagnostic omits sensitive message');
    durableCheck($outbox->claim('announcement',1)===null,'Backoff respected');
    $db->query("UPDATE notification SET is_read=1,read_at=NOW()");
    $db->query("UPDATE publication_notification_outbox SET available_at=NOW() WHERE content_type='announcement'");
    $partial->fail=false;
    $second=(new PublicationNotificationDispatcher($outbox,$notifications))->dispatch(1,'announcement',1);
    durableCheck($second['created']===1 && $second['duplicates']===1 && $second['completed']===1,'Fresh dispatcher recovers partial delivery');
    durableCheck($count('notification')===2,'No duplicate and no inactive/author recipient');
    durableCheck((int)$db->query('SELECT is_read FROM notification WHERE user_id=2')->fetch_assoc()['is_read']===1,'Retry preserves read state');
    durableCheck($dispatcher->dispatch(1,'announcement',1)['completed']===0,'Completed job not redelivered');
    $lease=$outbox->claim('event',1);
    durableCheck($lease!==null && $outbox->claim('event',1)===null,'Live claim excludes another claim');
    $db->query("UPDATE publication_notification_outbox SET locked_until=DATE_SUB(NOW(),INTERVAL 1 SECOND) WHERE content_type='event'");
    $newLease=$outbox->claim('event',1);
    durableCheck($newLease['lock_token']!==$lease['lock_token'],'Expired claim recovered');
    durableCheck(!$outbox->finish($lease,'Completed'),'Old worker cannot complete new claim');
    $outbox->retry($lease,new RuntimeException('stale'));
    durableCheck($outbox->claim('event',1)===null,'Old worker cannot release new claim');
    $outbox->finish($newLease,'Completed');
    $db->query("UPDATE documents SET workflow_status='archived' WHERE document_id=1");
    durableCheck($dispatcher->dispatch(1,'document',1)['cancelled']===1,'Withdrawn content cancelled');
    $db->query("UPDATE survey SET send_notification=0 WHERE survey_id=1");
    durableCheck($dispatcher->dispatch(1,'survey',1)['cancelled']===1,'Disabled delivery cancelled');
    foreach(['draft','pending_review','scheduled','rejected','archived'] as $index=>$state){
        PublicationTransaction::run($db,'survey',function()use($insert,$index,$state){$insert('survey',10+$index,$state);return 10+$index;});
    }
    durableCheck($count('publication_notification_outbox')===4,'Nonpublished states create no queue entries');
    PublicationTransaction::run($db,'survey',function()use($insert){$insert('survey',20,'published',false);return 20;});
    durableCheck($count('publication_notification_outbox')===4,'Notification opt-out creates no entry');
    // Queue insertion must fail atomically; temporary schema only.
    $db->query('ALTER TABLE publication_notification_outbox ADD CONSTRAINT fixture_reject CHECK (content_id <> 30)');
    foreach($sources as $type=>[$table,$key]){
        $failed=false;try{PublicationTransaction::run($db,$type,function()use($insert,$type){$insert($type,30);return 30;});}catch(Throwable $e){$failed=true;}
        durableCheck($failed && (int)$db->query("SELECT COUNT(*) AS n FROM $table WHERE $key=30")->fetch_assoc()['n']===0,'Outbox failure rolls back '.$type);
    }
    // Question replacement participates in the outer publication transaction.
    $survey=new Survey($db);$insert('survey',40,'draft');
    $db->query("INSERT INTO survey_question (question_id,survey_id,question,question_type) VALUES (1,40,'Original','Text')");
    $db->begin_transaction();
    $survey->replaceQuestions(40,[['question'=>'Replacement','question_type'=>'Text']]);
    durableCheck((int)$db->query('SELECT @@in_transaction AS active')->fetch_assoc()['active']===1,'Question replacement retains outer transaction');
    $db->rollback();
    durableCheck($db->query('SELECT question FROM survey_question WHERE survey_id=40')->fetch_assoc()['question']==='Original','Question replacement rolls back with publication');
    // Empty audience and category/master preferences still use existing rules.
    $db->query("INSERT INTO notification_preference (user_id,system_enabled,email_enabled,browser_push_enabled) VALUES (2,0,0,0)");
    $db->query("INSERT INTO notification_category_preference (user_id,notification_category,system_enabled) VALUES (3,'content_updates',0)");
    PublicationTransaction::run($db,'survey',function()use($insert){$insert('survey',50);return 50;});
    $r=$dispatcher->dispatch(1,'survey',50);
    durableCheck($r['eligible']===1 && $r['completed']===1,'Master-disabled user excluded');
    durableCheck((int)$db->query("SELECT in_system_visible FROM notification WHERE content_type='survey' AND content_id=50")->fetch_assoc()['in_system_visible']===0,'Category visibility preserved');
    $db->query("INSERT INTO survey_target (survey_id,role_id) VALUES (51,999)");
    PublicationTransaction::run($db,'survey',function()use($insert){$insert('survey',51);return 51;});
    $r=$dispatcher->dispatch(1,'survey',51);
    durableCheck($r['eligible']===0 && $r['completed']===1,'Empty eligible audience completes without retries');
    // Actual release worker: due rows and calendar dependencies, no external delivery.
    foreach($sources as $type=>[$table,$key]){
        $insert($type,60,'scheduled');
        $db->query("UPDATE $table SET release_mode='scheduled', scheduled_publish_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE $key=60");
        $insert($type,61,'scheduled');
        $db->query("UPDATE $table SET release_mode='scheduled', scheduled_publish_at=DATE_ADD(NOW(),INTERVAL 1 DAY) WHERE $key=61");
    }
    $db->query('UPDATE events SET event_date=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE event_id=60');
    foreach(['announcement','document','survey'] as $type){
        [$table,$key]=$sources[$type];$insert($type,62,'scheduled');
        $db->query("UPDATE $table SET release_mode='calendar',calendar_event_id=60 WHERE $key=62");
    }
    $release=durableNew(ContentReleaseService::class);durableInject($release,'conn',$db);durableInject($release,'publicationDispatcher',$dispatcher);
    $r=$release->processPendingReleases();
    durableCheck($r['total_released']===7,'Four scheduled plus three calendar releases');
    foreach($sources as $type=>[$table,$key]){
        durableCheck($db->query("SELECT workflow_status FROM $table WHERE $key=60")->fetch_assoc()['workflow_status']==='published','Due '.$type.' published');
        durableCheck($db->query("SELECT workflow_status FROM $table WHERE $key=61")->fetch_assoc()['workflow_status']==='scheduled','Future '.$type.' preserved');
    }
    durableCheck($release->processPendingReleases()['total_released']===0,'Scheduled rerun is idempotent');
    // Approval uses real models and atomic transaction, across all types.
    foreach($sources as $type=>[$table,$key]){
        $insert($type,70,'pending_review');
        $model=match($type){'announcement'=>new Announcement($db),'event'=>new Event($db),'document'=>new Document($db),'survey'=>$survey};
        $updated=PublicationTransaction::run($db,$type,fn()=>$model->approve(70,1),70);
        durableCheck($updated && $outbox->claim($type,70)!==null,'Approval queues '.$type);
        $insert($type,71,'pending_review');$db->query("UPDATE $table SET release_mode='scheduled' WHERE $key=71");
        PublicationTransaction::run($db,$type,fn()=>$model->approve(71,1),71);
        durableCheck($outbox->claim($type,71)===null,'Scheduled approval waits '.$type);
    }
    // Previously swallowed nonduplicate INSERT errors now propagate to the retry loop.
    $db->query('ALTER TABLE notification ADD CONSTRAINT fixture_notification_reject CHECK (content_id <> 80)');
    PublicationTransaction::run($db,'survey',function()use($insert){$insert('survey',80);return 80;});
    durableCheck($dispatcher->dispatch(1,'survey',80)['failed']===1,'Nonduplicate notification SQL failure remains retryable');
    durableCheck($db->query('SELECT workflow_status FROM survey WHERE survey_id=80')->fetch_assoc()['workflow_status']==='published','Delivery failure does not undo committed publication');
    foreach([['invalid',1],['survey',0],['survey',-1]] as [$type,$id]){
        $failed=false;try{$outbox->enqueue($type,$id);}catch(InvalidArgumentException $e){$failed=true;}
        durableCheck($failed,'Invalid queue source rejected');
    }
    // Real immediate service paths, using only isolated database writes.
    $conn=$db;
    $_SESSION=['user_id'=>1,'role'=>'Admin','name'=>'Fixture author'];
    $post=durableNew(PostService::class);
    $scope=new FacultyScopeService(new User($db));
    foreach(['announcement'=>new Announcement($db),'event'=>new Event($db),'document'=>new Document($db),'survey'=>$survey,
        'facultyScope'=>$scope,'redundancy'=>new DurableRedundancyFixture(),'contentInterest'=>new DurableInterestFixture(),
        'notifications'=>$notifications,'publicationDispatcher'=>$dispatcher] as $field=>$value) durableInject($post,$field,$value);
    $base=['content_interest_ids'=>[1],'workflow_action'=>'publish','release_mode'=>'immediate','audience_scope'=>'schoolwide','target_roles'=>['Student'],'send_notification'=>1];
    foreach(['announcement','event'] as $type){
        $data=$base+['post_type'=>$type,'announcement_title'=>'Immediate fixture','announcement_content'=>'Fixture body',
            'event_title'=>'Immediate fixture','event_description'=>'Fixture body','event_date'=>date('Y-m-d H:i:s',time()+86400)];
        $id=$post->create($data,[],1);
        durableCheck($id>0 && $outbox->isDeliverable($type,$id),'Real immediate create '.$type);
        durableCheck((int)$db->query("SELECT COUNT(*) AS n FROM publication_notification_outbox WHERE content_type='$type' AND content_id=$id")->fetch_assoc()['n']===1,'Immediate queue exists '.$type);
        [$table,$key]=$sources[$type];$db->query("UPDATE $table SET workflow_status='draft' WHERE $key=$id");
        $data['edit_id']=$id;
        durableCheck($post->create($data,[],1)===$id && $outbox->isDeliverable($type,$id),'Real edit to publish '.$type);
    }
    $advisoryValidator = new class extends GovernmentAdvisoryIntakeService {
        public function __construct() {}
        public function validateAnnouncementAttachment(array $data,int $userId): int { return (int)$data['government_advisory_id']; }
    };
    durableInject($post,'advisories',$advisoryValidator);
    $db->query("INSERT INTO government_advisory (government_advisory_id,government_source_id,source_url,source_url_hash,title,review_status)
        VALUES (901,1,'https://example.gov.ph/one',SHA2('one',256),'Fixture source','Relevant')");
    $attached=$base+['post_type'=>'announcement','announcement_title'=>'Author title','announcement_content'=>'Author caption','government_advisory_id'=>901];
    $attachedId=$post->create($attached,[],1);
    $linked=$db->query('SELECT linked_content_id,review_status FROM government_advisory WHERE government_advisory_id=901')->fetch_assoc();
    durableCheck((int)$linked['linked_content_id']===$attachedId && $linked['review_status']==='Converted','Source commits with real announcement publication');
    $saved=$db->query('SELECT title,content FROM announcements WHERE announcement_id='.$attachedId)->fetch_assoc();
    durableCheck($saved['title']==='Author title' && str_contains($saved['content'],'Author caption'),'Linked publication retains author writing');
    foreach ([901,999999] as $sourceId) {
        $beforeAnnouncements=(int)$db->query('SELECT COUNT(*) n FROM announcements')->fetch_assoc()['n'];
        $beforeOutbox=(int)$db->query('SELECT COUNT(*) n FROM publication_notification_outbox')->fetch_assoc()['n'];
        $attached['government_advisory_id']=$sourceId;
        try { $post->create($attached,[],1); throw new LogicException('Missing or consumed source accepted'); }
        catch (InvalidArgumentException $e) { durableCheck(true,'Attachment conflict aborts publication'); }
        durableCheck((int)$db->query('SELECT COUNT(*) n FROM announcements')->fetch_assoc()['n']===$beforeAnnouncements,'Source failure rolls back announcement');
        durableCheck((int)$db->query('SELECT COUNT(*) n FROM publication_notification_outbox')->fetch_assoc()['n']===$beforeOutbox,'Source failure rolls back notification outbox');
    }
    $service=durableNew(SurveyService::class);
    foreach(['survey'=>$survey,'facultyScope'=>$scope,'redundancy'=>new DurableRedundancyFixture(),
        'contentInterest'=>new DurableInterestFixture(),'notifications'=>$notifications,'publicationDispatcher'=>$dispatcher] as $field=>$value)durableInject($service,$field,$value);
    $data=$base+['survey_description'=>'Fixture description','survey_title'=>'Atomic survey fixture','questions'=>[['question'=>'Original question','question_type'=>'Text']]];
    $id=$service->createSurvey($data,1);
    durableCheck($outbox->isDeliverable('survey',$id),'Real survey create');
    $db->query("UPDATE survey SET workflow_status='draft' WHERE survey_id=$id");
    $data['questions'][0]['question']='Edited question';
    $service->updateSurvey($id,$data,1);
    durableCheck($db->query("SELECT question FROM survey_question WHERE survey_id=$id")->fetch_assoc()['question']==='Edited question','Real survey edit includes questions');
    $before=$count('survey');$data['questions'][0]['question']='';
    $failed=false;try{$service->createSurvey($data,1);}catch(InvalidArgumentException $e){$failed=true;}
    durableCheck($failed && $count('survey')===$before,'Invalid survey question rolls back publication');
    $workspace=durableNew(ContentWorkspaceService::class);
    foreach(['announcement'=>new Announcement($db),'event'=>new Event($db),'document'=>new Document($db),'survey'=>$survey,
        'notifications'=>$notifications,'publicationDispatcher'=>$dispatcher] as $field=>$value)durableInject($workspace,$field,$value);
    foreach($sources as $type=>[$table,$key]){
        $insert($type,900,'pending_review');$workspace->approve($type,900,1);
        durableCheck($outbox->isDeliverable($type,900),'Actual workspace approval '.$type);
    }
    $insert('survey',901,'pending_review');
    durableCheck($service->approveSurvey(901,1) && $outbox->isDeliverable('survey',901),'Legacy survey approval route queues delivery');
    // Scheduled queue failure must roll back the entire release, then retry later.
    $insert('event',30,'scheduled');
    $db->query("UPDATE events SET release_mode='scheduled',scheduled_publish_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE event_id=30");
    $failed=false;try{$release->processPendingReleases();}catch(RuntimeException $e){$failed=true;}
    durableCheck($failed && $db->query('SELECT workflow_status FROM events WHERE event_id=30')->fetch_assoc()['workflow_status']==='scheduled','Scheduled enqueue failure rolls back publication');
    $db->query("UPDATE documents SET workflow_status='published' WHERE document_id=1");
    PublicationTransaction::run($db,'document',fn()=>1);
    durableCheck($dispatcher->dispatch(1,'document',1)['completed']===1,'Cancelled publication can be restored and delivered');
    $insert('document',902,'draft');
    $db->query("UPDATE documents SET file_type='pdf',file_size=100,file_path='Assets/uploads/documents/fixture-not-opened.pdf' WHERE document_id=902");
    $documentData=$base+['post_type'=>'document','edit_id'=>902,'document_title'=>'Document fixture','document_description'=>'Fixture description'];
    durableCheck($post->create($documentData,[],1)===902 && $outbox->isDeliverable('document',902),'Actual document edit to publish preserves file metadata');
    $db->query('ALTER TABLE announcements AUTO_INCREMENT=10000');
    $db->query('ALTER TABLE publication_notification_outbox ADD CONSTRAINT fixture_service_reject CHECK (content_id <> 10000)');
    $beforeContent=$count('announcements');$beforeTargets=$count('announcement_target');
    $data=$base+['post_type'=>'announcement','announcement_title'=>'Rejected transaction fixture','announcement_content'=>'Fixture body'];
    $failed=false;try{$post->create($data,[],1);}catch(Throwable $e){$failed=true;}
    durableCheck($failed && $count('announcements')===$beforeContent && $count('announcement_target')===$beforeTargets,'Actual service queue failure rolls back content and targets');
    echo "PASS: $checks publication durability checks; temporary tables only, no email/push sent.\n";
} finally { $db->close(); }
