<?php
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$user = $viewData['user'];
$assignment = $viewData['assignment'];
$placement = $viewData['draft'] ?? $user;
$hasPlacementChange = false;
foreach (['department_id','education_level_id','academic_program_id','grade_level_id','section_id'] as $field) {
    if ((int) ($placement[$field] ?? 0) !== (int) ($user[$field] ?? 0)) $hasPlacementChange = true;
}
?>
<section class="app-page student-profile-management-page student-profile-account-page">
    <header class="page-header"><div><span class="page-eyebrow">Student profile update · Step 1 of 3</span>
        <h1>Confirm your account details</h1>
        <p><?= $escape($assignment['cycle_name']) ?> — confirm your placement, then complete the questionnaire.</p></div></header>
    <?php if (!empty($assignment['due_at'])): ?><p>Due <?= $escape(date('M j, Y, g:i A', strtotime($assignment['due_at']))) ?><?= strtotime($assignment['due_at']) < time() ? ' — Overdue. You can still submit while this update is open.' : '' ?></p><?php endif; ?>
    <?php if ($viewData['error'] !== ''): ?><p role="alert" class="student-profile-flash error"><?= $escape($viewData['error']) ?></p><?php endif; ?>
    <section class="student-profile-workspace-card">
        <div class="student-profile-card-header"><div><h2><?= $escape(trim($user['first_name'] . ' ' . $user['last_name'])) ?></h2>
        <p>Student ID: <?= $escape($user['studID']) ?> · Email: <?= $escape($user['email']) ?></p>
        <p>Identity and login details are school-controlled. Contact the school if these need correcting.</p></div></div>
        <form class="student-profile-form-grid student-profile-account-form" method="post" action="index.php?page=student_profile_account_save" data-placement-form>
            <?= csrfInput() ?>
            <input type="hidden" name="assignment_id" value="<?= (int) $assignment['student_profile_cycle_assignment_id'] ?>">
            <p class="full">Current placement: <?= $escape(implode(' · ', array_filter([$user['education_level_name'], $user['academic_program_name'], $user['grade_level_name'], $user['section_name']]))) ?></p>
            <label class="full"><span>Are your academic details still correct?</span><select data-placement-change><option value="same">Yes, still correct</option><option value="change" <?= $hasPlacementChange ? 'selected' : '' ?>>No, update my placement</option></select></label>
            <div class="full student-profile-form-grid" data-placement-fields>
                <?php foreach (['department_id'=>['School division','departments','department_id','department_name'], 'education_level_id'=>['Education level','levels','education_level_id','education_level_name'], 'academic_program_id'=>['Program or strand','programs','academic_program_id','program_name'], 'grade_level_id'=>['Grade or year','grades','grade_level_id','grade_level_name'], 'section_id'=>['Section','sections','section_id','section_name']] as $field => [$label,$collection,$id,$name]): ?>
                <label><span><?= $escape($label) ?></span><select name="<?= $field ?>" data-current="<?= $escape($user[$field] ?? '') ?>" <?= $field !== 'academic_program_id' ? 'required' : '' ?>>
                    <option value="">Choose <?= $escape(strtolower($label)) ?></option>
                    <?php foreach ($viewData[$collection] as $record): ?>
                    <option value="<?= (int) $record[$id] ?>" data-division="<?= (int) ($record['department_id'] ?? 0) ?>" data-level="<?= (int) ($record['education_level_id'] ?? 0) ?>" data-grade="<?= (int) ($record['grade_level_id'] ?? 0) ?>" data-program="<?= (int) ($record['academic_program_id'] ?? 0) ?>" <?= (int) ($placement[$field] ?? 0) === (int) $record[$id] ? 'selected' : '' ?>><?= $escape($record[$name]) ?></option>
                    <?php endforeach; ?>
                </select></label>
                <?php endforeach; ?>
            </div>
            <p class="full">Your selections are saved as progress. Any placement change takes effect only when you submit the completed questionnaire and is recorded for school review.</p>
            <button type="submit" class="app-button primary">Save details and continue</button>
            <a href="index.php?page=news&urgent_only=1" class="app-button secondary">Read urgent announcements</a>
        </form>
    </section>
</section>
