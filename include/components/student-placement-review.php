<?php
$placementFields = ['department_id'=>['departments','department_name','Division'],
    'education_level_id'=>['education_levels','education_level_name','Education level'],
    'academic_program_id'=>['academic_programs','program_name','Program / strand'],
    'grade_level_id'=>['grade_levels','grade_level_name','Grade / year'],
    'section_id'=>['sections','section_name','Section']];
$placementLabel = static function (?array $placement) use ($placementFields,$academicData): string {
    $labels = [];
    foreach ($placementFields as $field => [$collection,$name]) {
        $id = (int) ($placement[$field] ?? 0);
        if (!$id) continue;
        $label = '#' . $id;
        foreach ($academicData[$collection] ?? [] as $row) {
            if ((int) $row[$field] === $id) { $label = $row[$name]; break; }
        }
        $labels[] = $label;
    }
    return implode(' · ', $labels);
};
?>
<details class="student-profile-questionnaire-settings" id="placement-changes" <?= ($viewData['placement_review_message'] ?? '') !== '' ? 'open' : '' ?>>
    <summary>Review student-reported placement changes</summary>
    <section class="student-profile-workspace-card">
        <header class="student-profile-card-header"><div><h2>Placement changes</h2><p>Changes take effect on submission. Verify them against school records.</p></div></header>
        <?php if (($viewData['placement_review_message'] ?? '') !== ''): ?><p role="status"><?= $escape($viewData['placement_review_message']) ?></p><?php endif; ?>
        <?php if (empty($viewData['placement_changes'])): ?><p>No student-reported placement changes yet.</p><?php endif; ?>
        <?php foreach ($viewData['placement_changes'] ?? [] as $change): ?>
            <article class="student-profile-question-row">
                <div class="student-profile-question-copy"><strong><?= $escape($change['first_name'].' '.$change['last_name']) ?> · <?= $escape($change['studID']) ?></strong>
                    <p><?= $escape($change['cycle_name']) ?> · <?= $escape($change['created_at']) ?> · <?= $escape($change['review_status']) ?></p>
                    <p>Previous: <?= $escape($placementLabel(json_decode($change['previous_placement_json'],true))) ?></p>
                    <p>Reported: <?= $escape($placementLabel(json_decode($change['reported_placement_json'],true))) ?></p>
                    <?php if ($change['review_status'] === 'Unreviewed'): ?>
                    <form method="post" action="index.php?page=student_profile_placement_review" class="student-profile-form-grid" data-confirm-form data-confirm-title="Save placement review?" data-confirm-text="Correction also changes the student's content access.">
                        <?= csrfInput() ?><input type="hidden" name="change_id" value="<?= (int) $change['student_profile_placement_change_id'] ?>">
                        <label class="full"><span>School verification notes</span><textarea name="review_notes" maxlength="600" required></textarea></label>
                        <label class="full"><span>Review action</span><select name="review_action"><option value="verify">Verify reported placement</option><option value="correct">Correct placement using the fields below</option></select></label>
                        <details class="full"><summary>Correction fields (only used for Correct placement)</summary><div class="student-profile-form-grid">
                            <?php $reported = json_decode($change['reported_placement_json'], true); foreach ($placementFields as $field => [$collection,$name,$label]): ?>
                            <label><span><?= $escape($label) ?></span><select name="<?= $field ?>"><option value="">Not selected / not applicable</option>
                                <?php foreach ($academicData[$collection] ?? [] as $row): ?>
                                <option value="<?= (int) $row[$field] ?>" <?= (int) ($reported[$field] ?? 0) === (int) $row[$field] ? 'selected' : '' ?>><?= $escape($row[$name]) ?></option>
                                <?php endforeach; ?>
                            </select></label>
                            <?php endforeach; ?>
                        </div></details>
                        <button type="submit" class="app-button primary">Save review</button>
                    </form>
                    <?php else: ?><p><?= $escape($change['review_notes'] ?? '') ?></p><?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <p>Showing up to 100 most recent placement changes.</p>
    </section>
</details>
