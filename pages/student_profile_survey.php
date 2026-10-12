<?php

if (isset($viewData['placement_state'])) {
    $placementState = $viewData['placement_state'];
    $escapePlacement = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $draft = $placementState['draft'] ?? $placementState['user'];
    $selectedLabels = [];
    foreach (['levels'=>['education_level_id','education_level_name'], 'programs'=>['academic_program_id','program_name'],
        'grades'=>['grade_level_id','grade_level_name'], 'sections'=>['section_id','section_name']] as $collection => [$id,$name]) {
        foreach ($placementState[$collection] as $row) {
            if ((int) $row[$id] === (int) ($draft[$id] ?? 0)) $selectedLabels[] = $row[$name];
        }
    }
    $placementSummary = implode(' · ', $selectedLabels);
    $originalSummary = implode(' · ', array_filter([$placementState['user']['education_level_name'],
        $placementState['user']['academic_program_name'], $placementState['user']['grade_level_name'], $placementState['user']['section_name']]));
    ?>
    <section class="page-card student-profile-placement-summary">
        <span class="page-eyebrow">Student profile update · Step 2 of 3</span>
        <h2>Complete your questionnaire</h2>
        <p>Confirmed placement: <?= $escapePlacement($placementSummary) ?></p>
        <?php if ($placementSummary !== $originalSummary): ?><p>Previous placement: <?= $escapePlacement($originalSummary) ?></p><?php endif; ?>
        <p>Review your answers before submitting. Any placement change takes effect with your final submission.</p>
        <a href="index.php?page=student_profile_account&review_details=1">Review account details</a> ·
        <a href="index.php?page=news&urgent_only=1">Read urgent announcements</a>
    </section>
    <script type="application/json" id="profilePlacementSummary"><?= json_encode(['previous'=>$originalSummary,'reported'=>$placementSummary], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <?php
}
// Share the existing survey renderer and validation markup without duplicating it.
require __DIR__ . '/student_profile.php';
