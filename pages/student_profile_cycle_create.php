<?php

/** @var array $viewData */
$cycleQuestionnaires = is_array($viewData['questionnaires'] ?? null)
    ? $viewData['questionnaires'] : [];
$cycleAcademicDirectory = is_array($viewData['academic_directory'] ?? null)
    ? $viewData['academic_directory'] : [];
$cycleFlash = is_array($viewData['flash'] ?? null)
    ? $viewData['flash'] : [];
$cycleOldInput = ($cycleFlash['open_form'] ?? '') === 'create_cycle' &&
    is_array($cycleFlash['old_input'] ?? null)
    ? $cycleFlash['old_input'] : [];
$cycleEscape = static fn(mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
$cycleVersions = $cycleQuestionnaires['survey_versions'] ?? [];
$scopeCollections = [
    'Department' => ['label' => 'School Divisions', 'records' => $cycleAcademicDirectory['departments'] ?? [], 'id' => 'department_id', 'name' => 'department_name', 'code' => 'department_code'],
    'EducationLevel' => ['label' => 'Education Levels', 'records' => $cycleAcademicDirectory['education_levels'] ?? [], 'id' => 'education_level_id', 'name' => 'education_level_name', 'code' => null],
    'AcademicProgram' => ['label' => 'Programs and Strands', 'records' => $cycleAcademicDirectory['academic_programs'] ?? [], 'id' => 'academic_program_id', 'name' => 'program_name', 'code' => 'program_code'],
    'GradeLevel' => ['label' => 'Grade and Year Levels', 'records' => $cycleAcademicDirectory['grade_levels'] ?? [], 'id' => 'grade_level_id', 'name' => 'grade_level_name', 'code' => null],
    'Section' => ['label' => 'Sections', 'records' => $cycleAcademicDirectory['sections'] ?? [], 'id' => 'section_id', 'name' => 'section_name', 'code' => null]
];

?>

<details class="student-profile-cycle-create" <?= ($cycleFlash['open_form'] ?? '') === 'create_cycle' ? 'open' : '' ?>>
    <summary>
        <span><i class="fa-solid fa-calendar-plus"></i> Create Student Profile Cycle</span>
        <small>Configure timing, questionnaire, and exact recipients</small>
        <i class="fa-solid fa-chevron-down"></i>
    </summary>

    <form method="post" action="index.php?page=student_profile_cycle_create" class="student-profile-management-form" data-student-profile-cycle-form>
        <?= csrfInput() ?>
        <h3>New Profile Update Cycle</h3>

        <div>
            <label for="profileCycleName">Cycle Name</label>
            <input type="text" id="profileCycleName" name="cycle_name" maxlength="150" value="<?= $cycleEscape($cycleOldInput['cycle_name'] ?? '') ?>" placeholder="SY 2027-2028 First Semester Profile Update" required>
        </div>

        <div>
            <label for="profileCycleYear">Academic Year</label>
            <input type="text" id="profileCycleYear" name="academic_year" maxlength="30" value="<?= $cycleEscape($cycleOldInput['academic_year'] ?? '') ?>" placeholder="2027-2028">
        </div>

        <div>
            <label for="profileCycleType">Cycle Type</label>
            <select id="profileCycleType" name="cycle_type" required>
                <?php foreach (['SchoolYear' => 'School year', 'Semester' => 'Semester', 'Custom' => 'Custom'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($cycleOldInput['cycle_type'] ?? 'Semester') === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="profileAcademicTerm">Academic Term</label>
            <select id="profileAcademicTerm" name="academic_term" required>
                <?php foreach (['NotApplicable' => 'Not applicable', 'FirstSemester' => 'First semester', 'SecondSemester' => 'Second semester', 'Summer' => 'Summer', 'Custom' => 'Custom'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($cycleOldInput['academic_term'] ?? 'FirstSemester') === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="profileCycleQuestionnaire">Questionnaire Version</label>
            <select id="profileCycleQuestionnaire" name="survey_version" required>
                <option value="">Select a questionnaire</option>
                <?php foreach ($cycleVersions as $version): ?>
                    <?php if (in_array($version['status'] ?? '', ['Draft', 'Active'], true)): ?>
                        <option value="<?= (int) ($version['survey_version'] ?? 0) ?>" <?= (int) ($cycleOldInput['survey_version'] ?? 0) === (int) ($version['survey_version'] ?? 0) ? 'selected' : '' ?>>
                            Version <?= (int) ($version['survey_version'] ?? 0) ?> — <?= $cycleEscape($version['version_name'] ?? '') ?> (<?= $cycleEscape($version['status'] ?? '') ?>)
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="profileCycleScopeType">Recipient Scope</label>
            <select id="profileCycleScopeType" name="scope_type" required data-cycle-scope-type>
                <option value="AllStudents">All active Students</option>
                <option value="Department">School division</option>
                <option value="EducationLevel">Education level</option>
                <option value="AcademicProgram">Program or strand</option>
                <option value="GradeLevel">Grade or year level</option>
                <option value="Section" <?= ($cycleOldInput['scope_type'] ?? '') === 'Section' ? 'selected' : '' ?>>Section</option>
            </select>
        </div>

        <div data-cycle-scope-target>
            <label for="profileCycleScopeId">Specific Target</label>
            <select id="profileCycleScopeId" name="scope_id" data-cycle-scope-id>
                <option value="">Select the exact target</option>
                <?php foreach ($scopeCollections as $scopeType => $collection): ?>
                    <optgroup label="<?= $cycleEscape($collection['label']) ?>" data-scope-group="<?= $scopeType ?>">
                        <?php foreach ($collection['records'] as $record): ?>
                            <?php if (($record['status'] ?? 'Active') === 'Active'): ?>
                                <?php
                                $scopeRecordId = (int) ($record[$collection['id']] ?? 0);
                                $scopeRecordName = (string) ($record[$collection['name']] ?? '');
                                $scopeRecordCode = $collection['code'] !== null ? trim((string) ($record[$collection['code']] ?? '')) : '';
                                ?>
                                <option value="<?= $scopeRecordId ?>" data-scope-type="<?= $scopeType ?>" <?= ($cycleOldInput['scope_type'] ?? '') === $scopeType && (int) ($cycleOldInput['scope_id'] ?? 0) === $scopeRecordId ? 'selected' : '' ?>>
                                    <?= $cycleEscape($scopeRecordCode !== '' ? $scopeRecordCode . ' — ' . $scopeRecordName : $scopeRecordName) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <small>Only active Students matching this academic record will be assigned.</small>
        </div>

        <div>
            <label for="profileCycleOpensAt">Opens At</label>
            <input type="datetime-local" id="profileCycleOpensAt" name="opens_at" value="<?= $cycleEscape($cycleOldInput['opens_at'] ?? '') ?>">
            <small>Leave empty to open when the cycle is activated.</small>
        </div>

        <div>
            <label for="profileCycleDueAt">Due At</label>
            <input type="datetime-local" id="profileCycleDueAt" name="due_at" value="<?= $cycleEscape($cycleOldInput['due_at'] ?? '') ?>">
        </div>

        <button type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Draft Cycle</button>
    </form>
</details>