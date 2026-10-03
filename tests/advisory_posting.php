<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Announcement.php';
require_once __DIR__ . '/../app/services/GovernmentAdvisoryIntakeService.php';
$checks = 0;
function advisoryCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException('FAIL: ' . $label);
    $checks++;
}
class PostingAdvisoryFixture extends GovernmentAdvisoryIntakeService {
    public int $fetches = 0;
    public bool $failFetch = false;
    public bool $failReview = false;
    public function previewAnnouncement(array $data): array {
        $this->fetches++;
        if ($this->failFetch) throw new RuntimeException('Fixture fetch failure');
        $key = (string) ($data['fixture_key'] ?? 'one');
        return [
            'government_source_id'=>1, 'source_url'=>'https://example.gov.ph/'.$key,
            'source_url_hash'=>hash('sha256',$key), 'source_page_mode'=>'Specific',
            'external_reference'=>$key, 'title'=>'Verified title', 'summary'=>'Verified school summary',
            'extracted_text'=>'Verified text', 'content_hash'=>hash('sha256','text'.$key),
            'advisory_type'=>'Other', 'geographic_scope'=>'Nationwide', 'scope_value'=>null,
            'issued_at'=>null, 'effective_from'=>null, 'effective_until'=>null,
            'relevance_score'=>50, 'relevance_level'=>'Medium', 'recommendation'=>'Review',
            'matched_rule_ids'=>[], 'relevance_reasons'=>[], 'fetched_at'=>date('Y-m-d H:i:s')
        ];
    }
    public function review(int $id, string $decision, ?string $notes, int $reviewer): array {
        if ($this->failReview) throw new RuntimeException('Fixture review failure');
        return parent::review($id,$decision,$notes,$reviewer);
    }
}
$db = openDatabaseConnection();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    foreach (['government_advisory','government_source','user'] as $table) {
        $ddl = $db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
        $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $ddl, 1);
        $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $ddl);
        $ddl = preg_replace('/,\n\)/', "\n)", $ddl);
        $db->query($ddl);
    }
    $db->query("INSERT INTO government_source (government_source_id,source_name,base_url,allowed_host) VALUES (1,'Fixture','https://example.gov.ph','example.gov.ph')");
    $service = new PostingAdvisoryFixture(new GovernmentAdvisory($db));
    $count = fn() => (int) $db->query('SELECT COUNT(*) n FROM government_advisory')->fetch_assoc()['n'];
    foreach (['Faculty','Student','Parent','Guest'] as $role) {
        $_SESSION = ['role'=>$role,'user_id'=>1];
        try { $service->prepareAnnouncement(['review_confirmed'=>'1'],1); throw new LogicException('Role accepted'); }
        catch (InvalidArgumentException $e) { advisoryCheck($count()===0 && $service->fetches===0,'Denied role before fetch/write'); }
    }
    $_SESSION = ['role'=>'Admin','user_id'=>1];
    foreach ([0,2] as $id) {
        try { $service->prepareAnnouncement(['review_confirmed'=>'1'],$id); throw new LogicException('ID accepted'); }
        catch (InvalidArgumentException $e) { advisoryCheck($count()===0,'Invalid actor rejected'); }
    }
    try { $service->prepareAnnouncement([],1); throw new LogicException('Missing review accepted'); }
    catch (InvalidArgumentException $e) { advisoryCheck($service->fetches===0,'Explicit review required'); }
    $item = $service->prepareAnnouncement(['review_confirmed'=>'1','title'=>'Tampered client text'],1);
    advisoryCheck($count()===1 && $service->fetches===1,'Verified intake persisted once');
    advisoryCheck($item['title']==='Verified title','Server verified text used');
    advisoryCheck($item['review_status']==='Relevant' && (int)$item['reviewed_by']===1,'Admin review recorded');
    advisoryCheck(empty($item['linked_content_id']),'Preparation does not publish content');
    advisoryCheck($service->getConversionCandidate((int)$item['government_advisory_id'])['title']==='Verified title','Existing conversion accepts prepared advisory');
    $service->failReview = true;
    try { $service->prepareAnnouncement(['review_confirmed'=>'1','fixture_key'=>'two'],1); throw new LogicException('Review failure ignored'); }
    catch (RuntimeException $e) { advisoryCheck($count()===1,'Review failure rolls back intake'); }
    $service->failReview = false; $service->failFetch = true;
    try { $service->prepareAnnouncement(['review_confirmed'=>'1','fixture_key'=>'three'],1); throw new LogicException('Fetch failure ignored'); }
    catch (RuntimeException $e) { advisoryCheck($count()===1,'Fetch failure leaves records unchanged'); }
    $service->failFetch = false;
    $again = $service->prepareAnnouncement(['review_confirmed'=>'1','fixture_key'=>'two'],1);
    advisoryCheck($count()===2 && $again['review_status']==='Relevant','Retry after rollback succeeds');
    $fetcher = new class extends TrustedGovernmentSourceFetcher {
        public int $calls = 0;
        public bool $fail = false;
        public function __construct() {}
        public function fetch(string $url, ?string $manualTitle = null): array {
            $this->calls++;
            if ($this->fail) throw new InvalidArgumentException('Fixture invalid or unavailable source');
            return [];
        }
    };
    (new ReflectionProperty(GovernmentAdvisoryIntakeService::class,'fetcher'))->setValue($service,$fetcher);
    $attachment = ['post_type'=>'announcement','government_advisory_id'=>$again['government_advisory_id'],'government_advisory_url'=>'https://example.gov.ph/two'];
    advisoryCheck($service->validateAnnouncementAttachment([],1)===0,'Ordinary post needs no source');
    foreach ([['government_advisory_id'=>0],['government_advisory_id'=>-1],['government_advisory_id'=>[]],
        ['government_advisory_url'=>'https://example.gov.ph/changed'],['government_advisory_url'=>[]],
        ['post_type'=>'event'],['edit_id'=>99]] as $change) {
        try { $service->validateAnnouncementAttachment(array_replace($attachment,$change),1); throw new LogicException('Invalid attachment accepted'); }
        catch (InvalidArgumentException $e) { advisoryCheck(true,'Invalid/unattached/mismatched source blocks saving'); }
    }
    advisoryCheck($fetcher->calls===0,'Invalid attachment rejected before network');
    $_SESSION['role']='Faculty';
    try { $service->validateAnnouncementAttachment($attachment,1); throw new LogicException('Faculty source accepted'); }
    catch (InvalidArgumentException $e) { advisoryCheck(true,'Faculty cannot bypass source authorization'); }
    $_SESSION['role']='Admin';
    advisoryCheck($service->validateAnnouncementAttachment($attachment,1)===(int)$again['government_advisory_id'] && $fetcher->calls===1,'Verified source rechecked before save');
    $fetcher->fail=true;
    try { $service->validateAnnouncementAttachment($attachment,1); throw new LogicException('Unavailable source accepted'); }
    catch (InvalidArgumentException $e) { advisoryCheck(true,'Unavailable source prevents save'); }
    $fetcher->fail=false;
    require_once __DIR__ . '/../app/services/PostService.php';
    require_once __DIR__ . '/../config/logging.php';
    $_SESSION = ['role'=>'Admin','user_id'=>1];
    $postService = new PostService($db);
    (new ReflectionProperty(PostService::class,'advisories'))->setValue($postService,$service);
    try {
        $postService->create(['post_type'=>'announcement','government_advisory_url'=>'https://example.gov.ph/unattached'],[],1);
        throw new LogicException('PostService accepted unverified source');
    } catch (InvalidArgumentException $e) {
        advisoryCheck(str_contains($e->getMessage(),'Check and attach'),'Real save entry rejects unverified intent before content writes: '.$e->getMessage());
    }
    $service->markConverted((int)$item['government_advisory_id'],'announcement',99,1);
    $announcements = new class($db) extends Announcement {
        public int $queries = 0;
        protected function prepare($sql) { $this->queries++; return parent::prepare($sql); }
    };
    advisoryCheck($announcements->attachGovernmentSources([])===[] && $announcements->queries===0,'Empty feed skips source lookup');
    $rows = [['announcement_id'=>99,'title'=>'My title','content'=>'My own caption'],['announcement_id'=>100,'title'=>'Ordinary']];
    $linked = $announcements->attachGovernmentSources($rows);
    advisoryCheck($linked[0]['government_source_url']==='https://example.gov.ph/one','Converted source attached separately');
    advisoryCheck($linked[0]['title']==='My title' && $linked[0]['content']==='My own caption','Author text stays untouched');
    advisoryCheck($linked[1]===$rows[1],'Ordinary announcement has no advisory tag');
    $announcements->queries=0;
    advisoryCheck(count($announcements->attachGovernmentSources(array_fill(0,100,$rows[0])))===100 && $announcements->queries===1,'One source query for 100 cards');
    $db->query("UPDATE government_advisory SET source_url='javascript:alert(1)' WHERE linked_content_id=99");
    advisoryCheck(!isset($announcements->attachGovernmentSources($rows)[0]['government_source_url']),'Unsafe stored source URL omitted');
    $db->query("UPDATE government_advisory SET source_url='https://user:pass@example.gov.ph/' WHERE linked_content_id=99");
    advisoryCheck(!isset($announcements->attachGovernmentSources($rows)[0]['government_source_url']),'Credential URL omitted');
    $db->query("UPDATE government_advisory SET source_url='https://example.gov.ph/one',review_status='Relevant' WHERE linked_content_id=99");
    advisoryCheck(!isset($announcements->attachGovernmentSources($rows)[0]['government_source_url']),'Unconverted intake cannot label posts');
    $db->query("UPDATE government_advisory SET review_status='Converted' WHERE linked_content_id=99");

    try { $service->getConversionCandidate((int)$item['government_advisory_id']); throw new LogicException('Converted advisory reusable'); }
    catch (RuntimeException $e) { advisoryCheck(true,'Converted advisory cannot be reused'); }
    foreach ([0,-1,999999] as $id) {
        try { $service->getConversionCandidate($id); throw new LogicException('Invalid advisory accepted'); }
        catch (InvalidArgumentException | RuntimeException $e) { advisoryCheck(true,'Invalid/missing candidate rejected'); }
    }
    // Exercise the real preview rules: only the network fetch is stubbed.
    $realService = new GovernmentAdvisoryIntakeService(new GovernmentAdvisory($db));
    $sourceFetcher = new class extends TrustedGovernmentSourceFetcher {
        public bool $fail = false;
        public function __construct() {}
        public function fetch(string $url, ?string $manualTitle = null): array {
            if ($this->fail) throw new InvalidArgumentException('Unavailable official page');
            return ['source'=>['government_source_id'=>1], 'effective_url'=>$url,
                'title'=>'Official live forecast', 'summary'=>'Current official weather forecast',
                'extracted_text'=>'Forecast', 'content_hash'=>hash('sha256','forecast')];
        }
    };
    (new ReflectionProperty(GovernmentAdvisoryIntakeService::class,'fetcher'))->setValue($realService,$sourceFetcher);
    $link = ['source_url'=>'https://example.gov.ph/live-forecast','review_confirmed'=>'1'];
    $first = $realService->prepareAnnouncement($link,1);
    $preview = $realService->previewAnnouncement($link);
    advisoryCheck($preview['external_reference']===null,'Reused page needs no invented official reference');
    $second = $realService->prepareAnnouncement($link,1);
    advisoryCheck($first['government_advisory_id']!==$second['government_advisory_id'] && $second['review_status']==='Relevant','Same live URL can support separate announcements');
    advisoryCheck($second['source_url']===$link['source_url'],'Live source URL retained');
    try { $realService->preview($link); throw new LogicException('Legacy metadata checks bypassed'); }
    catch (InvalidArgumentException $e) { advisoryCheck(str_contains($e->getMessage(),'official reference'),'Legacy import rules preserved'); }
    $sourceFetcher->fail=true;
    try { $realService->previewAnnouncement($link); throw new LogicException('Unavailable link accepted'); }
    catch (InvalidArgumentException $e) { advisoryCheck(true,'Link-only preview preserves fetch failures'); }
    $_SESSION['role']='Faculty';
    try { $realService->previewAnnouncement($link); throw new LogicException('Faculty accepted'); }
    catch (InvalidArgumentException $e) { advisoryCheck(true,'Link-only preview requires Admin'); }
    $_SESSION['role']='Admin';
    require_once __DIR__ . '/../app/controllers/GovernmentAdvisoryController.php';
    $controllerService = new class extends GovernmentAdvisoryIntakeService {
        public int $prepared = 0;
        public function __construct() {}
        public function prepareAnnouncement(array $data, int $userId): array {
            $this->prepared++;
            return ['government_advisory_id'=>42,'title'=>'Fixture','summary'=>'Summary','source_url'=>'https://example.gov.ph/'];
        }
        public function getConversionCandidate(int $id): array {
            return ['government_advisory_id'=>$id,'title'=>'Fixture','summary'=>'Summary','source_url'=>'https://example.gov.ph/'];
        }
    };
    $controller = (new ReflectionClass(GovernmentAdvisoryController::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty($controller,'service'))->setValue($controller,$controllerService);
    $_SESSION = ['role'=>'Admin','user_id'=>1,'csrf_token'=>str_repeat('a',64)];
    $_POST = ['csrf_token'=>str_repeat('a',64),'source_url'=>'https://example.gov.ph/','review_confirmed'=>'1'];
    $invoke = static function () use ($controller): array {
        ob_start(); $controller->prepareAnnouncement(); $json=ob_get_clean();
        return json_decode($json,true,512,JSON_THROW_ON_ERROR);
    };
    $_SERVER['REQUEST_METHOD']='GET';
    advisoryCheck($invoke()['status']==='error' && $controllerService->prepared===0,'GET cannot prepare');
    $_SERVER['REQUEST_METHOD']='POST'; $_POST['csrf_token']='invalid';
    advisoryCheck($invoke()['status']==='error' && $controllerService->prepared===0,'Invalid CSRF cannot prepare');
    $_POST['csrf_token']=str_repeat('a',64);
    advisoryCheck($invoke()['advisory']['government_advisory_id']===42 && $controllerService->prepared===1,'POST returns editor JSON');
    advisoryCheck($invoke()['advisory']['government_advisory_id']===42 && $controllerService->prepared===1,'Retry reuses prepared candidate');
    $_POST['source_url']='https://example.gov.ph/another';
    advisoryCheck($invoke()['status']==='success' && $controllerService->prepared===2,'Changed source does not reuse stale candidate');
    echo "PASS: $checks advisory posting checks; temporary tables and mocked source fetch only.\n";
} finally { $db->close(); }
