<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../app/controllers/SurveyController.php';

class PrivacySurveyFixture extends Survey {
    public function __construct(mysqli $connection) { $this->conn = $connection; }
    public function findWorkflowItemById(int $surveyId): ?array {
        return $surveyId === 1 ? ['survey_id'=>1,'user_id'=>7,'title'=>'Fixture survey'] : null;
    }
}
class PrivacyRedirect extends RuntimeException {}
class PrivacyControllerFixture extends SurveyController {
    public function __construct() {}
    protected function redirect(string $url): void { throw new PrivacyRedirect($url); }
}
$checks = 0;
function privacyCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException('FAIL: '.$label);
    $checks++;
}
function renderPrivacy(array $results): string {
    $viewData = ['survey'=>['title'=>'Fixture'], 'results'=>$results];
    ob_start();
    try { include __DIR__.'/../pages/survey_results.php'; return ob_get_contents(); }
    finally { ob_end_clean(); }
}
$db = openDatabaseConnection();
try {
    // All DDL must succeed before any fixture inserts occur. No permanent writes.
    foreach (['survey_question','survey_choice','survey_response','survey_answer','survey_answer_choice'] as $table) {
        $ddl = $db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_assoc()['Create Table'];
        $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $ddl, 1);
        $ddl = preg_replace('/^\s*CONSTRAINT[^\n]*\n?/m', '', $ddl);
        $ddl = preg_replace('/,\n\)/', "\n)", $ddl);
        $db->query($ddl);
    }
    $types = ['Text','Short Text','Long Text','Rating','Multiple Choice','Checkbox','Yes/No'];
    $stmt = $db->prepare('INSERT INTO survey_question (question_id,survey_id,question,question_type) VALUES (?,1,?,?)');
    foreach ($types as $i=>$type) { $id=$i+1; $stmt->bind_param('iss',$id,$type,$type); $stmt->execute(); }
    $stmt->close();
    $db->query("INSERT INTO survey_choice (choice_id,question_id,choice_text) VALUES (1,5,'A'),(2,5,'B'),(3,6,'A'),(4,6,'B')");
    $model = new PrivacySurveyFixture($db);
    $empty = $model->getResults(1);
    privacyCheck($empty['response_count'] === 0 && !$empty['privacy_suppressed'], 'Empty survey');
    privacyCheck(str_contains(renderPrivacy($empty),'No responses yet'), 'Empty page');
    foreach (range(1,10) as $person) {
        $db->query("INSERT INTO survey_response (response_id,survey_id,user_id) VALUES ($person,1,$person)");
        foreach (range(1,7) as $question) {
            $answerId=$person*10+$question;
            $answer=$question===4 ? '4' : ($question===7 ? 'Yes' : 'PRIVATE_SENTINEL');
            $stmt=$db->prepare('INSERT INTO survey_answer (answer_id,response_id,question_id,user_id,answer) VALUES (?,?,?,?,?)');
            $stmt->bind_param('iiiis',$answerId,$person,$question,$person,$answer);$stmt->execute();$stmt->close();
            if ($question===5 || $question===6) {
                $choice=$question===5 ? 1 : 3;
                $db->query("INSERT INTO survey_answer_choice (answer_id,choice_id) VALUES ($answerId,$choice)");
            }
        }
        $result=$model->getResults(1);
        if ($person<5) {
            privacyCheck($result['privacy_suppressed'] && $result['response_count']===null,'Small survey count suppressed');
            privacyCheck(!str_contains(json_encode($result),'PRIVATE_SENTINEL'),'Small answers absent from payload');
            privacyCheck(!str_contains(renderPrivacy($result),'PRIVATE_SENTINEL') && str_contains(renderPrivacy($result),'Results protected'),'Small answers absent from HTML');
            foreach ($result['questions'] as $q) privacyCheck($q['privacy_suppressed'] && $q['response_count']===null && $q['average_rating']===null && $q['text_answers']===[], 'All small question types protected');
        } else {
            privacyCheck(!$result['privacy_suppressed'] && $result['response_count']===$person,'Threshold survey released');
            foreach ($result['questions'] as $q) privacyCheck(!$q['privacy_suppressed'],'Threshold question released');
            privacyCheck($result['questions'][3]['average_rating']===4.0,'Rating preserved');
            foreach ($result['questions'][0]['text_answers'] as $answer) privacyCheck(array_keys($answer)===['answer'],'No identity or timestamp in returned text');
        }
    }
    $db->query('DELETE FROM survey_answer WHERE question_id=1 AND response_id>4');
    $result=$model->getResults(1);
    privacyCheck($result['questions'][0]['privacy_suppressed'],'Optional small question protected in large survey');
    privacyCheck(!$result['questions'][1]['privacy_suppressed'],'Other question unaffected');
    $db->query('UPDATE survey_answer_choice SET choice_id=2 WHERE answer_id=105');
    $result=$model->getResults(1);
    privacyCheck($result['questions'][4]['privacy_suppressed'],'Nine/one choice split fully suppressed');
    foreach ($result['questions'][4]['choices'] as $choice) privacyCheck($choice['response_count']===null,'Complement count removed');
    $db->query('UPDATE survey_answer_choice SET choice_id=2 WHERE answer_id IN (65,75,85,95)');
    $result=$model->getResults(1);
    privacyCheck(!$result['questions'][4]['privacy_suppressed'],'Five/five choice split visible');
    $db->query("UPDATE survey_answer SET answer='No' WHERE question_id=7 AND response_id=10");
    privacyCheck($model->getResults(1)['questions'][6]['privacy_suppressed'],'Small Yes/No group protected');
    $db->query('DELETE FROM survey_answer WHERE question_id=6 AND response_id>4');
    $db->query('INSERT INTO survey_answer_choice (answer_id,choice_id) VALUES (16,4),(26,4),(36,4),(46,4)');
    privacyCheck($model->getResults(1)['questions'][5]['privacy_suppressed'],'Eight checkbox selections from four people remain protected');
    $db->query("INSERT INTO survey_answer (answer_id,response_id,question_id,user_id,answer) VALUES (999,1,1,1,'PRIVATE_SENTINEL')");
    privacyCheck($model->getResults(1)['questions'][0]['privacy_suppressed'],'Duplicate answer cannot inflate respondent threshold');
    $db->query("UPDATE survey_answer SET answer='<script>unsafe</script>' WHERE question_id=2 AND response_id=1");
    $html=renderPrivacy($model->getResults(1));
    privacyCheck(str_contains($html,'&lt;script&gt;unsafe&lt;/script&gt;') && !str_contains($html,'<script>unsafe</script>'),'Visible text escaped');
    privacyCheck(!str_contains($html,'<time>'),'No response timestamps in HTML');
    foreach ([0,-1] as $id) {
        $denied=false;try{$model->getResults($id);}catch(InvalidArgumentException $e){$denied=true;}
        privacyCheck($denied,'Invalid model ID rejected');
    }
    // Use actual service/controller methods; inject only the temporary-table model.
    $service=(new ReflectionClass(SurveyService::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(SurveyService::class,'survey'))->setValue($service,$model);
    $controller=new PrivacyControllerFixture();
    (new ReflectionProperty(SurveyController::class,'service'))->setValue($controller,$service);
    $_GET=['survey_id'=>1];
    foreach (['Admin','Faculty'] as $role) {
        $_SESSION=['user_id'=>7,'role'=>$role];
        $result=$controller->results();
        privacyCheck($result['results']['questions'][0]['privacy_suppressed'],$role.' cannot bypass suppression');
    }
    $_SESSION=['user_id'=>8,'role'=>'Faculty'];
    $denied=false;try{$controller->results();}catch(RuntimeException $e){$denied=true;}
    privacyCheck($denied,'Faculty non-owner denied');
    foreach (['Student','Parent','Guest'] as $role) {
        $_SESSION=['user_id'=>7,'role'=>$role];
        $denied=false;try{$controller->results();}catch(PrivacyRedirect $e){$denied=true;}
        privacyCheck($denied,$role.' denied');
    }
    $_SESSION=['user_id'=>7,'role'=>'Admin'];
    foreach ([null,'',0,-1,[],999] as $id) {
        $_GET=['survey_id'=>$id];$denied=false;
        try{$controller->results();}catch(RuntimeException|InvalidArgumentException $e){$denied=true;}
        privacyCheck($denied,'Invalid/missing survey rejected');
    }
    echo "PASS: $checks survey privacy checks; temporary tables only.\n";
} finally { $db->close(); }
