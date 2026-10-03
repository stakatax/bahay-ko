<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/request-input.php';
$checks = 0;
$valid = [
    [['page'=>'login_action'], ['identifier'=>'account@example.com','password'=>'  Keep spaces!  ','csrf_token'=>'fixture']],
    [['page'=>'register_action'], ['student_id'=>'12-56789','child_student_id'=>'S-001','first_name'=>"Jos? O?Neil",'section_id'=>'12']],
    [['page'=>'post_store'], ['target_roles'=>['Student','Parent'],'content_interest_ids'=>['1'],'audience_scopes'=>[['department_id'=>'1','education_level_id'=>'2','academic_program_id'=>'']],'announcement_content'=>'<p>School update</p>']],
    [['page'=>'survey_store'], ['questions'=>[['question'=>'Device?','question_type'=>'Multiple Choice','choices'=>['Phone','Computer']]]]],
    [['page'=>'student_profile_save'], ['interest_ids'=>['1'],'interest_weights'=>['1'=>'2']]],
    [['page'=>'student_profile_survey_save'], ['survey_responses'=>['device_access'=>['Phone','Computer']],'survey_consents'=>['support'=>'1']]],
    [['page'=>'notification_update_category_preferences'], ['categories'=>['content_updates'=>['system_enabled'=>'1','email_enabled'=>'0']]]],
    [['page'=>'survey_submit_response'], ['answers'=>['1'=>['2','3']],'survey_id'=>'1']],
    [['page'=>'student_profile_question_create'], ['options'=>['Phone','Computer']]],
    [['page'=>'student_profile_question_update'], ['options'=>[]]],
    [['page'=>'news','open_id'=>'00012'], []],
    [['page'=>'password_reset','token'=>'invalid-link'], []]
];
foreach ($valid as [$query,$post]) {
    $original = [$query,$post]; validateRequestInput($query,$post);
    if ([$query,$post] !== $original) throw new RuntimeException('Valid input changed.');
    $checks++;
}
foreach ([
    [['page'=>['news']], []],
    [['page'=>'news','open_id'=>['1']], []],
    [['page'=>'login_action'], ['password'=>['crafted']]],
    [['page'=>'register_action'], ['first_name'=>['crafted']]],
    [['page'=>'academic_update'], ['entity_id'=>'12abc']],
    [['page'=>'manage_user_change_status'], ['user_id'=>'1.5']],
    [['page'=>'news','open_id'=>'1e2'], []],
    [['page'=>'news','open_id'=>'999999999999999999999'], []],
    [['page'=>'post_store'], ['announcement_title'=>"Bad\0text"]],
    [['page'=>'post_store'], ['announcement_title'=>"\xFF"]],
    [['page'=>'login_action'], ['answers'=>['1']]],
    [['page'=>'post_store'], ['audience_scopes'=>[['department_id'=>['1']]]]],
    [['page'=>'post_store'], ['audience_scopes'=>[['department_id'=>'1abc']]]],
    [['page'=>'post_store'], ['target_department'=>'12abc']],
    [['page'=>'student_profile_cycle_create'], ['survey_version'=>'1.5']],
    [['page'=>'survey_store'], ['questions'=>[['question'=>['crafted']]]]],
    [['page'=>'survey_submit_response'], ['answers'=>[[[[['too deep']]]]]]]
] as [$query,$post]) {
    try { validateRequestInput($query,$post); throw new RuntimeException('Malformed input accepted.'); }
    catch (InvalidArgumentException $exception) { $checks++; }
}
echo "PASS: $checks request input checks; no writes or deliveries.\n";
