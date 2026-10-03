<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/models/ParentChildRecord.php';
function csrfInput(): string { return '<input type="hidden" name="csrf_token" value="fixture">'; }
function childView(string $page,array $viewData): string {
    $_SESSION=[];$_GET=[];ob_start();
    try { require __DIR__ . '/../pages/'.$page.'.php'; return ob_get_contents(); }
    finally { ob_end_clean(); }
}
$checks=0;
function viewCheck(bool $ok,string $label):void { global $checks; if (!$ok) throw new RuntimeException('FAIL: '.$label); $checks++; }
$sections=[['section_id'=>1,'label'=>'IBED / Elementary / Grade 2 / A']];
$html=childView('register',['child_registration_available'=>true,'child_sections'=>$sections,'child_reasons'=>ParentChildRecord::REASONS]);
viewCheck(str_contains($html,'id="childNoAccount"'),'active registration checkbox rendered');
viewCheck(str_contains($html,'IBED / Elementary / Grade 2 / A'),'class labels rendered');
viewCheck(!str_contains(childView('register',[]),'id="childNoAccount"'),'new option hidden before migration');
$record=['child_name'=>'<script>alert(1)</script>','child_student_id'=>null,'section_id'=>1,'relationship'=>'Mother','reason'=>'assistance',
    'reason_details'=>'<img src=x onerror=alert(1)>','status'=>'Pending','review_notes'=>null,'review_history'=>null];
$registration=['user_id'=>55,'first_name'=>'Parent','last_name'=>'Fixture','role_prefix'=>'Parent','status'=>'Pending','created_at'=>'2026-09-26 00:00:00','child_record'=>$record];
$html=childView('account_approvals',['selected_registration'=>$registration,'child_sections'=>$sections]);
viewCheck(str_contains($html,'Child without a login account') && str_contains($html,'Child needs adult assistance'),'Admin sees new path and reason');
viewCheck(!str_contains($html,'<script>alert(1)</script>') && str_contains($html,'&lt;script&gt;'),'child name escaped');
viewCheck(!str_contains($html,'<img src=x') && str_contains($html,'&lt;img'),'explanation escaped');
viewCheck(str_contains($html,'id="accountApprovalForm"') && str_contains($html,'Required: enrollment'),'pending approval requires verification note');
viewCheck(!str_contains($html,'<dt>Linked Student</dt>'),'independent claim does not show missing-link error');
viewCheck(!str_contains($html,'class="account-approval-form child-review-form"') && !str_contains($html,'id="reviewChildName"'),'pending child details are read-only');
viewCheck(str_contains($html,'id="accountRejectionForm"'),'pending Parent retains rejection action');
$registration['status']='Active';$registration['child_record']['status']='Verified';
$html=childView('account_approvals',['selected_registration'=>$registration,'child_sections'=>$sections]);
viewCheck(!str_contains($html,'id="accountApprovalForm"'),'active Parent has no repeat approval action');
viewCheck(str_contains($html,'value="link"') && str_contains($html,'value="revoke"'),'active record can be linked or revoked');
$registration['child_record']['status']='Linked';
$html=childView('account_approvals',['selected_registration'=>$registration,'child_sections'=>$sections]);
viewCheck(!str_contains($html,'class="account-approval-form child-review-form"'),'linked record no longer grants editable fallback');
echo "PASS: $checks active-page rendering checks. Source rendering only; no browser layout claim.\n";
