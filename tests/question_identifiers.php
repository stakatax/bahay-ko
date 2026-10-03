<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/StudentProfileManagementService.php';
class IdentifierFixture extends StudentProfileManagement {
    public array $rows = [];
    public string $status = 'Draft';
    public function __construct() {}
    public function findSurveyVersion(int $surveyVersion): ?array { return ['status'=>$this->status]; }
    public function findSurveySection(int $surveyVersion, string $sectionKey): ?array {
        return $sectionKey === 'access' ? ['section_label'=>'Access','section_sort_order'=>1] : null;
    }
    public function createSurveyQuestion(int $surveyVersion, array $data): array {
        $id = count($this->rows)+1;
        return $this->rows[$id] = $data + ['survey_version'=>$surveyVersion,'student_profile_question_id'=>$id];
    }
    public function findSurveyQuestion(int $questionId): ?array { return $this->rows[$questionId] ?? null; }
    public function updateSurveyQuestion(int $questionId, array $data): array {
        return $this->rows[$questionId] = array_replace($this->rows[$questionId],$data);
    }
}
$checks=0;
function verifyIdentifier(bool $condition, string $message): void {
    global $checks; if (!$condition) throw new RuntimeException($message); $checks++;
}
$model = new IdentifierFixture();
$service = (new ReflectionClass(StudentProfileManagementService::class))->newInstanceWithoutConstructor();
(new ReflectionProperty($service,'management'))->setValue($service,$model);
$data = ['section_key'=>'access','question_text'=>'Your device?','response_type'=>'ShortText','sort_order'=>10];
$first=$service->createQuestion(1,$data,1);
verifyIdentifier((bool)preg_match('/^question_[a-f0-9]{32}$/',$first['question_key']),'Generated valid identifier');
$second=$service->createQuestion(1,$data+['question_key'=>'tampered'],1);
verifyIdentifier($second['question_key']!==$first['question_key'] && $second['question_key']!=='tampered','Repeated wording gets independent identity');
$updated=$service->updateQuestion(1,array_replace($data,['question_text'=>'Updated wording','question_key'=>'tampered']),1);
verifyIdentifier($updated['question_key']===$first['question_key'] && $updated['question_text']==='Updated wording','Edit preserves identity despite tampering');
$model->rows[1]['question_key']='legacy_device';
verifyIdentifier($service->updateQuestion(1,$data,1)['question_key']==='legacy_device','Legacy identifier preserved without submitted key');
foreach (['Active','Retired'] as $status) {
    $model->status=$status;
    try { $service->createQuestion(1,$data,1); throw new LogicException('Non-draft create allowed'); }
    catch (RuntimeException $e) { verifyIdentifier(str_contains($e->getMessage(),'Only Draft'),'Draft-only creation preserved'); }
    try { $service->updateQuestion(1,$data,1); throw new LogicException('Non-draft edit allowed'); }
    catch (RuntimeException $e) { verifyIdentifier(str_contains($e->getMessage(),'Only Draft'),'Draft-only editing preserved'); }
}
$model->status='Draft';
try { $service->createQuestion(1,$data,0); throw new LogicException('Invalid actor allowed'); }
catch (RuntimeException $e) { verifyIdentifier(str_contains($e->getMessage(),'Administrator'),'Invalid actor rejected'); }
try { $service->createQuestion(1,array_replace($data,['is_sensitive'=>1]),1); throw new LogicException('Consent bypass'); }
catch (InvalidArgumentException $e) { verifyIdentifier(str_contains($e->getMessage(),'consent'),'Consent validation preserved'); }
echo "PASS: $checks identifier checks; in-memory model, no database writes.\n";
