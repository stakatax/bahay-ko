<?php
// Shared by Administrator Faculty creation and assignment correction forms.
$facultyAssignmentValues = $facultyAssignmentValues ?? [];
?>
<div class="faculty-provision-grid faculty-field-wide" data-faculty-assignment-fields>
    <label>
        <span>School Division *</span>
        <select name="department_id" required>
            <option value="">Select School Division</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= (int) $department['department_id'] ?>"
                    data-division="<?= $escape(strtoupper($department['department_code'] ?? '')) ?>"
                    <?= (int) ($facultyAssignmentValues['department_id'] ?? 0) === (int) $department['department_id'] ? 'selected' : '' ?>>
                    <?= $escape($department['department_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>Education Level *</span>
        <select name="education_level_id" required>
            <option value="">Select Education Level</option>
            <?php foreach ($educationLevels as $education): ?>
                <option value="<?= (int) $education['education_level_id'] ?>"
                    data-department-id="<?= (int) $education['department_id'] ?>"
                    <?= (int) ($facultyAssignmentValues['education_level_id'] ?? 0) === (int) $education['education_level_id'] ? 'selected' : '' ?>>
                    <?= $escape($education['education_level_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label data-faculty-program-field>
        <span>College Program *</span>
        <select name="academic_program_id">
            <option value="">Select College Program</option>
            <?php foreach ($academicPrograms as $program): ?>
                <?php if (($program['program_type'] ?? '') !== 'Program') { continue; } ?>
                <option value="<?= (int) $program['academic_program_id'] ?>"
                    data-education-level-id="<?= (int) $program['education_level_id'] ?>"
                    <?= (int) ($facultyAssignmentValues['academic_program_id'] ?? 0) === (int) $program['academic_program_id'] ? 'selected' : '' ?>>
                    <?= $escape($program['program_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
</div>
