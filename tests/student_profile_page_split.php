<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
function csrfInput(): string { return '<input type="hidden" name="csrf_token" value="fixture">'; }
function renderProfileTask(string $page, array $viewData): string {
    ob_start();
    require __DIR__ . '/../pages/' . $page . '.php';
    return ob_get_clean();
}
$checks = 0;
function profileTaskCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
}
$data = ['sections'=>[
    ['section_key'=>'device_access','section_label'=>'Device access','questions'=>[]],
    ['section_key'=>'connectivity','section_label'=>'Connectivity','questions'=>[]]
], 'current_step'=>'connectivity', 'flash_type'=>'success', 'flash_message'=>'Progress saved'];
$interests = renderProfileTask('student_profile', $data);
profileTaskCheck(str_contains($interests, 'id="studentProfileForm"'), 'Interests form remains on profile page');
profileTaskCheck(!str_contains($interests, 'id="expandedProfileSurvey"'), 'Survey no longer crowds interests page');
profileTaskCheck(str_contains($interests, 'href="index.php?page=student_profile_survey"'), 'Dedicated survey entry available');
$survey = renderProfileTask('student_profile_survey', $data);
profileTaskCheck(!str_contains($survey, 'id="studentProfileForm"'), 'Survey page does not duplicate interests form');
profileTaskCheck(str_contains($survey, 'action="index.php?page=student_profile_survey_save"'), 'Existing save endpoint retained');
profileTaskCheck(str_contains($survey, 'name="csrf_token"'), 'Survey retains CSRF input');
profileTaskCheck(str_contains($survey, 'Progress saved'), 'Save feedback visible on survey page');
profileTaskCheck(str_contains($survey, 'data-survey-section-button="connectivity"'), 'Saved section navigation retained');
profileTaskCheck(str_contains($survey, 'href="index.php?page=student_profile"'), 'Return to interests available');
$empty = renderProfileTask('student_profile_survey', []);
profileTaskCheck(str_contains($empty, 'Survey unavailable'), 'Unconfigured survey has safe empty state');
echo "PASS: $checks profile page rendering checks; no database writes.\n";
