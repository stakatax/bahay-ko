<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
function csrfInput(): string { return '<input name="csrf_token" value="fixture" type="hidden">'; }
function renderAdminProfile(array $versions): string {
    $viewData = ['questionnaires' => ['survey_versions' => $versions]];
    ob_start();
    require __DIR__ . '/../pages/student_profile_management.php';
    return ob_get_clean();
}
$html = renderAdminProfile([
    ['survey_version' => 3, 'status' => 'Draft', 'version_name' => 'Draft'],
    ['survey_version' => 2, 'status' => 'Active', 'version_name' => 'Current']
]);
$checks = [
    strpos($html, 'Profile update requests') < strpos($html, 'Questionnaire Versions'),
    str_contains($html, 'value="2" selected'),
    str_contains($html, 'Review students'),
    str_contains($html, 'student_profile_cycle_create'),
    str_contains($html, 'name="csrf_token"'),
    str_contains($html, 'data-cycle-scope-type'),
    !str_contains($html, 'data-confirm-title="Save this profile update draft?"'),
    str_contains(renderAdminProfile([]), 'choose a questionnaire'),
    !str_contains($html, 'name="section_id"')
];
foreach ($checks as $index => $ok) {
    if (!$ok) throw new RuntimeException('Admin flow check failed: ' . $index);
}
echo 'PASS: ' . count($checks) . " Administrator rendering checks; no database writes.\n";
