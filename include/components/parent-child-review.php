<?php
$childRecord = $selectedRegistration['child_record'] ?? null;
if ($childRecord):
$childSectionLabel = 'Unavailable academic placement';
foreach ($viewData['child_sections'] ?? [] as $option) {
    if ((int)$option['section_id'] === (int)$childRecord['section_id']) { $childSectionLabel = $option['label']; break; }
}
?>
<section class="account-review-section child-review-section">
    <h3>Child without a login account</h3>
    <p class="child-review-intro">Details submitted by the Parent.</p>
    <dl class="account-review-grid">
        <?php foreach (['Child name'=>$childRecord['child_name'], 'Student ID'=>$childRecord['child_student_id'] ?: 'Not supplied',
            'Grade and section'=>$childSectionLabel, 'Relationship'=>$childRecord['relationship'],
            'Reason'=>ParentChildRecord::REASONS[$childRecord['reason']] ?? $childRecord['reason'],
            'Explanation'=>$childRecord['reason_details'] ?: 'None supplied', 'Verification status'=>$childRecord['status'],
            'Last verification note'=>$childRecord['review_notes'] ?? 'Not yet reviewed'] as $label=>$value): ?>
        <div><dt><?= $escape($label) ?></dt><dd><?= $escape($value) ?></dd></div>
        <?php endforeach; ?>
    </dl>
    <p class="child-review-guidance">Check enrollment and the relationship against school records.
        <?php if ($selectedRegistration['status'] === 'Pending'): ?>
        Record your checks in the approval note below, then approve or reject the registration.
        <?php else: ?>
        Use relationship management below only when verification needs to change.
        <?php endif; ?>
        Do not include private supporting documents.</p>
    <?php if ($selectedRegistration['status'] === 'Active' && !in_array($childRecord['status'], ['Linked','Rejected'], true)): ?>
    <details class="child-review-management">
        <summary>Manage verified child relationship</summary>
    <form method="post" action="index.php?page=parent_child_update" class="account-approval-form child-review-form">
        <?= csrfInput() ?>
        <input type="hidden" name="registration_user_id" value="<?= (int)$selectedRegistration['user_id'] ?>">
        <label for="reviewChildName">Child full name
            <input id="reviewChildName" name="child_name" maxlength="200" value="<?= $escape($childRecord['child_name']) ?>" required>
        </label>
        <label for="reviewChildId">School Student ID (if available)
            <input id="reviewChildId" name="child_student_id" maxlength="50" value="<?= $escape($childRecord['child_student_id']) ?>">
        </label>
        <label for="reviewChildSection">Grade and section
            <select id="reviewChildSection" name="child_section_id" required>
                <option value="">Select a verified class</option>
                <?php foreach ($viewData['child_sections'] ?? [] as $option): ?>
                <option value="<?= (int)$option['section_id'] ?>" <?= (int)$option['section_id']===(int)$childRecord['section_id'] ? 'selected' : '' ?>><?= $escape($option['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label for="reviewChildAction">Action
            <select id="reviewChildAction" name="child_action">
                <option value="update"><?= $selectedRegistration['status']==='Pending' ? 'Correct application details (account stays Pending)' : 'Verify current enrollment and save details' ?></option>
                <?php if ($selectedRegistration['status']==='Active'): ?>
                <option value="revoke">Revoke this child verification</option>
                <?php if ($childRecord['status']==='Verified'): ?><option value="link">Link an existing active Student account</option><?php endif; ?>
                <?php endif; ?>
            </select>
        </label>
        <label for="linkStudentId">Student ID to link (only for Link action)
            <input id="linkStudentId" name="link_student_id" maxlength="50">
        </label>
        <p class="child-review-help">When linking, verify that the account belongs to this child. Its current academic placement will determine Parent access.
            Revocation removes access supplied by this record. Existing independently verified links are unaffected.</p>
        <label for="childReviewNote">Verification note
            <textarea id="childReviewNote" name="review_notes" maxlength="1000" rows="3" required></textarea>
        </label>
        <label for="confirmChildReview" class="child-review-confirmation"><input type="checkbox" id="confirmChildReview" name="confirm_child_review" value="1" required>
            <span>I checked the school records and confirm the selected action.</span></label>
        <button class="app-button primary" type="submit">Save child verification</button>
    </form>
    </details>
    <?php endif; ?>
    <?php $childHistory = json_decode($childRecord['review_history'] ?? '[]', true) ?: []; ?>
    <?php if ($childHistory): ?>
    <details><summary>Verification history</summary>
        <ul><?php foreach (array_reverse($childHistory) as $entry): ?>
            <li><?= $escape(($entry['at'] ?? '') . ' - Admin #' . ($entry['admin_id'] ?? '') . ' - ' . ($entry['action'] ?? '') . ': ' . ($entry['note'] ?? '')) ?></li>
        <?php endforeach; ?></ul>
    </details>
    <?php endif; ?>
</section>
<?php endif; ?>
