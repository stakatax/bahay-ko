<?php

if (
    !isset($viewData) ||
    !is_array($viewData)
) {
    $viewData = [];
}

$questionnaireData = is_array($viewData['questionnaires'] ?? null)
    ? $viewData['questionnaires'] : [];
$cycleData = is_array($viewData['cycle_monitoring'] ?? null)
    ? $viewData['cycle_monitoring'] : [];
$academicData = is_array($viewData['academic_directory'] ?? null)
    ? $viewData['academic_directory'] : [];
$versions = $questionnaireData['survey_versions'] ?? [];
$selectedVersion = is_array($questionnaireData['selected_survey_version'] ?? null)
    ? $questionnaireData['selected_survey_version'] : null;
$questions = is_array($questionnaireData['questions'] ?? null)
    ? $questionnaireData['questions'] : [];
$questionSections = is_array($questionnaireData['sections'] ?? null)
    ? $questionnaireData['sections'] : [];
$consentDefinitions = is_array($questionnaireData['consent_definitions'] ?? null)
    ? $questionnaireData['consent_definitions'] : [];
$cycles = is_array($cycleData['cycles'] ?? null) ? $cycleData['cycles'] : [];
$selectedCycle = is_array($cycleData['selected_cycle'] ?? null)
    ? $cycleData['selected_cycle'] : null;
$assignments = is_array($cycleData['assignments'] ?? null)
    ? $cycleData['assignments'] : [];
$cyclePreview = is_array($viewData['cycle_preview'] ?? null)
    ? $viewData['cycle_preview'] : null;
$flash = is_array($viewData['flash'] ?? null) ? $viewData['flash'] : null;
$escape = static fn(mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
$selectedVersionId = (int) ($selectedVersion['survey_version'] ?? 0);
$selectedCycleId = (int) ($selectedCycle['student_profile_cycle_id'] ?? 0);
$canEditQuestions = ($selectedVersion['status'] ?? '') === 'Draft';
$questionsBySection = [];

foreach ($questions as $question) {
    $sectionKey = (string) ($question['section_key'] ?? 'other');

    if (!isset($questionsBySection[$sectionKey])) {
        $questionsBySection[$sectionKey] = [
            'label' => (string) ($question['section_label'] ?? 'Other'),
            'questions' => []
        ];
    }

    $questionsBySection[$sectionKey]['questions'][] = $question;
}

$responseLabels = [
    'SingleChoice' => 'Single choice',
    'MultipleChoice' => 'Multiple choice',
    'Boolean' => 'Yes or No',
    'ShortText' => 'Short text',
    'LongText' => 'Long text',
    'Number' => 'Number'
];

$profileAudienceDepartments = [];

foreach (
    $academicData['departments']
        ?? []
    as $department
) {
    $departmentCode =
        strtoupper(
            trim(
                (string) (
                    $department['department_code']
                    ?? ''
                )
            )
        );

    if (
        !in_array(
            $departmentCode,
            [
                'IBED',
                'COLLEGE'
            ],
            true
        )
    ) {
        continue;
    }

    if (
        (
            $department['status']
            ?? 'Active'
        ) !== 'Active'
    ) {
        continue;
    }

    $profileAudienceDepartments[$departmentCode] = [
        'department_id' =>
        (int) (
            $department['department_id']
            ?? 0
        ),

        'label' =>
        $departmentCode === 'IBED'
            ? 'IBED Students'
            : 'College Students',

        'icon' =>
        $departmentCode === 'IBED'
            ? 'fa-school'
            : 'fa-graduation-cap'
    ];
}

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section class="app-page student-profile-management-page">

    <header class="page-header student-profile-management-header">
        <div class="page-header-copy">
            <span class="page-eyebrow">Administration</span>
            <h1>Student Profile Management</h1>
            <p>
                Manage questionnaires, request profile updates, and track completion.
            </p>
        </div>
        <div class="student-profile-header-actions">
            <button type="button" class="app-button secondary" data-open-modal="draft">
                <i class="fa-solid fa-copy"></i> Copy questionnaire
            </button>
            <button type="button" class="app-button primary" data-open-modal="cycle">
                <i class="fa-solid fa-calendar-plus"></i> Request profile update
            </button>
        </div>
    </header>

    <?php if ($flash !== null): ?>
        <div
            class="student-profile-flash <?= ($flash['type'] ?? '') === 'error' ? 'error' : 'success' ?>"
            data-profile-flash
            role="<?= ($flash['type'] ?? '') === 'error' ? 'alert' : 'status' ?>">
            <i
                class="fa-solid <?= ($flash['type'] ?? '') === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>">
            </i>
            <span><?= $escape($flash['message'] ?? '') ?></span>
            <button
                type="button"
                data-dismiss-profile-flash
                aria-label="Dismiss message">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="student-profile-summary-grid">
        <article><i class="fa-solid fa-layer-group"></i><span>Questionnaire versions</span><strong><?= count($versions) ?></strong></article>
        <article><i class="fa-solid fa-list-check"></i><span>Selected questions</span><strong><?= count($questions) ?></strong></article>
        <article><i class="fa-solid fa-arrows-rotate"></i><span>Profile cycles</span><strong><?= count($cycles) ?></strong></article>
        <article><i class="fa-solid fa-user-check"></i><span>Selected assignments</span><strong><?= count($assignments) ?></strong></article>
    </div>

    <section class="student-profile-workspace-card">
        <header class="student-profile-card-header">
            <div>
                <span class="student-profile-card-eyebrow">
                    Questionnaire configuration
                </span>
                <h2>Questionnaire Versions</h2>
                <p>Select a version to review its categories and questions.</p>
            </div>
            <?php if ($selectedVersion !== null): ?>
                <span class="student-profile-status <?= strtolower($escape($selectedVersion['status'] ?? '')) ?>">
                    Version <?= $selectedVersionId ?> ·
                    <?= $escape($selectedVersion['status'] ?? '') ?>
                </span>
            <?php endif; ?>
        </header>

        <div class="student-profile-version-strip">
            <?php foreach ($versions as $version): ?>
                <?php $versionId = (int) ($version['survey_version'] ?? 0); ?>
                <a
                    href="index.php?page=student_profile_management&survey_version=<?= $versionId ?>&cycle_id=<?= $selectedCycleId ?>"
                    class="<?= $versionId === $selectedVersionId ? 'active' : '' ?>">
                    <strong><?= $escape($version['version_name'] ?? '') ?></strong>
                    <span>
                        Version <?= $versionId ?> ·
                        <?= $escape($version['status'] ?? '') ?> ·
                        <?= (int) ($version['question_count'] ?? 0) ?> questions
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="student-profile-directory-heading">
            <div>
                <span class="student-profile-card-eyebrow">
                    Question Directory
                </span>

                <h3>Configured Questions</h3>

                <p>
                    Search, filter, and manage the questions included
                    in this questionnaire version.
                </p>
            </div>

            <?php if ($canEditQuestions): ?>
                <button
                    type="button"
                    class="app-button primary"
                    data-open-question-create>
                    <i class="fa-solid fa-plus"></i>
                    Add Question
                </button>
            <?php endif; ?>
        </div>

        <div class="student-profile-question-toolbar">
            <div class="student-profile-search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="search"
                    placeholder="Search question text or key"
                    data-question-search>
            </div>

            <select
                data-question-section-filter
                aria-label="Filter by category">
                <option value="">All categories</option>
                <?php foreach ($questionSections as $section): ?>
                    <option value="<?= $escape($section['section_key'] ?? '') ?>">
                        <?= $escape($section['section_label'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select
                data-question-type-filter
                aria-label="Filter by response type">
                <option value="">All response types</option>
                <?php foreach ($responseLabels as $value => $label): ?>
                    <option value="<?= $value ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>

            <select
                data-question-status-filter
                aria-label="Filter by status">
                <option value="">All statuses</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>

        </div>

        <div class="student-profile-question-results">
            <span data-question-result-count><?= count($questions) ?> questions</span>
            <?php if (!$canEditQuestions): ?>
                <span>
                    <i class="fa-solid fa-lock"></i>
                    Active versions are read-only
                </span>
            <?php endif; ?>
        </div>

        <div class="student-profile-question-directory" data-question-directory>
            <?php foreach ($questionsBySection as $sectionKey => $sectionGroup): ?>
                <section class="student-profile-question-group" data-question-group="<?= $escape($sectionKey) ?>">
                    <header>
                        <div>
                            <i class="fa-solid fa-folder-open"></i>
                            <h3><?= $escape($sectionGroup['label']) ?></h3>
                        </div>
                        <span data-group-count>
                            <?= count($sectionGroup['questions']) ?>
                        </span>
                    </header>
                    <div>
                        <?php foreach ($sectionGroup['questions'] as $question): ?>
                            <?php
                            $questionId = (int) ($question['student_profile_question_id'] ?? 0);
                            $questionPayload = [
                                'question_id' => $questionId,
                                'survey_version' => (int) ($question['survey_version'] ?? 0),
                                'question_key' => (string) ($question['question_key'] ?? ''),
                                'section_key' => (string) ($question['section_key'] ?? ''),
                                'question_text' => (string) ($question['question_text'] ?? ''),
                                'help_text' => (string) ($question['help_text'] ?? ''),
                                'response_type' => (string) ($question['response_type'] ?? ''),
                                'options' => $question['options'] ?? [],
                                'is_required' => !empty($question['is_required']),
                                'is_sensitive' => !empty($question['is_sensitive']),
                                'consent_key' => (string) ($question['consent_key'] ?? ''),
                                'analytics_enabled' => !empty($question['analytics_enabled']),
                                'sort_order' => (int) ($question['sort_order'] ?? 0),
                                'status' => (string) ($question['status'] ?? '')
                            ];
                            $questionJson =
                                json_encode(
                                    $questionPayload,
                                    JSON_UNESCAPED_UNICODE |
                                        JSON_UNESCAPED_SLASHES |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT
                                );
                            ?>
                            <article
                                class="student-profile-question-row"
                                data-question-row
                                data-search="<?= $escape(strtolower(($question['question_text'] ?? '') . ' ' . ($question['question_key'] ?? ''))) ?>"
                                data-section="<?= $escape($sectionKey) ?>"
                                data-type="<?= $escape($question['response_type'] ?? '') ?>"
                                data-status="<?= $escape($question['status'] ?? '') ?>">
                                <span class="student-profile-question-order">
                                    <?= (int) ($question['sort_order'] ?? 0) ?>
                                </span>

                                <div class="student-profile-question-copy">
                                    <strong>
                                        <?= $escape($question['question_text'] ?? '') ?>
                                    </strong>
                                    <span>
                                        <?= $escape($question['question_key'] ?? '') ?> ·
                                        <?= $escape($responseLabels[$question['response_type'] ?? ''] ?? ($question['response_type'] ?? '')) ?>
                                    </span>
                                </div>

                                <span class="student-profile-status <?= strtolower($escape($question['status'] ?? '')) ?>">
                                    <?= $escape($question['status'] ?? '') ?>
                                </span>

                                <?php if ($canEditQuestions): ?>
                                    <div class="student-profile-row-actions">
                                        <button
                                            type="button"
                                            class="icon-button"
                                            data-edit-question
                                            data-question="<?= $escape($questionJson ?: '{}') ?>"
                                            aria-label="Edit question">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <form
                                            method="post"
                                            action="index.php?page=student_profile_question_change_status"
                                            data-confirm-form
                                            data-confirm-title="<?= ($question['status'] ?? '') === 'Active' ? 'Deactivate question?' : 'Activate question?' ?>"
                                            data-confirm-text="This change affects only Draft Version <?= $selectedVersionId ?>.">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="question_id" value="<?= $questionId ?>">
                                            <input type="hidden" name="survey_version" value="<?= $selectedVersionId ?>">
                                            <input type="hidden" name="confirm_status_change" value="1">
                                            <input
                                                type="hidden"
                                                name="new_status"
                                                value="<?= ($question['status'] ?? '') === 'Active' ? 'Inactive' : 'Active' ?>">

                                            <button
                                                type="submit"
                                                class="icon-button"
                                                aria-label="Change question status">
                                                <i class="fa-solid <?= ($question['status'] ?? '') === 'Active' ? 'fa-pause' : 'fa-play' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
            <div
                class="student-profile-empty-state"
                data-question-empty
                hidden>
                <i class="fa-solid fa-magnifying-glass"></i>
                <strong>No questions match these filters</strong>
                <span>Clear or adjust the search and filter controls.</span>
            </div>
        </div>
    </section>

    <section class="student-profile-workspace-card">
        <header class="student-profile-card-header">
            <div>
                <span class="student-profile-card-eyebrow">
                    Update scheduling
                </span>
                <h2>Profile update requests</h2>
                <p>
                    Create a Draft, verify its recipients,
                    then activate it when ready.
                </p>
            </div>

            <?php if ($selectedCycle !== null): ?>
                <span class="student-profile-status <?= strtolower($escape($selectedCycle['status'] ?? '')) ?>">
                    <?= $escape($selectedCycle['status'] ?? '') ?>
                </span>
            <?php endif; ?>
        </header>

        <div class="student-profile-cycle-layout">
            <nav
                class="student-profile-cycle-list"
                aria-label="Student profile cycles">
                <?php foreach ($cycles as $cycle): ?>
                    <?php $cycleId = (int) ($cycle['student_profile_cycle_id'] ?? 0); ?>
                    <a
                        href="index.php?page=student_profile_management&survey_version=<?= $selectedVersionId ?>&cycle_id=<?= $cycleId ?>"
                        class="<?= $cycleId === $selectedCycleId ? 'active' : '' ?>">
                        <strong><?= $escape($cycle['cycle_name'] ?? '') ?></strong>
                        <span>
                            <?= $escape($cycle['status'] ?? '') ?> ·
                            Version <?= (int) ($cycle['survey_version'] ?? 0) ?>
                        </span>
                        <small>
                            <?= (int) ($cycle['completed_count'] ?? 0) ?> completed of
                            <?= (int) ($cycle['assignment_count'] ?? 0) ?>
                        </small>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="student-profile-cycle-detail">
                <?php if ($selectedCycle === null): ?>
                    <div class="student-profile-empty-state">
                        <i class="fa-solid fa-calendar"></i>
                        <strong>No update request selected</strong>
                    </div>
                <?php else: ?>
                    <header>
                        <div>
                            <span>Selected update request</span>
                            <h3><?= $escape($selectedCycle['cycle_name'] ?? '') ?></h3>
                            <?php
                            $cycleQuestionnaireName = 'Version ' . (int) ($selectedCycle['survey_version'] ?? 0);
                            foreach ($versions as $version) {
                                if ((int) $version['survey_version'] === (int) ($selectedCycle['survey_version'] ?? 0)) {
                                    $cycleQuestionnaireName = (string) $version['version_name'];
                                    break;
                                }
                            }
                            $cycleDateLabel = static function ($value, string $fallback): string {
                                $timestamp = is_string($value) && $value !== '' ? strtotime($value) : false;
                                return $timestamp === false ? $fallback : date('M j, Y, g:i A', $timestamp);
                            };
                            ?>
                            <p><?= $escape($cycleQuestionnaireName) ?> &middot; <?= $escape(($selectedCycle['academic_year'] ?? '') ?: 'Academic year not specified') ?></p>
                        </div>

                        <div class="student-profile-cycle-actions">
                            <?php if (($selectedCycle['status'] ?? '') === 'Draft' && $cyclePreview !== null): ?>
                                <form
                                    method="post"
                                    action="index.php?page=student_profile_cycle_activate"
                                    data-confirm-form
                                    data-confirm-title="Activate this profile update?"
                                    data-confirm-text="<?= (int) ($cyclePreview['target_count'] ?? 0) ?> Student(s) will be assigned and notified.">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="cycle_id" value="<?= $selectedCycleId ?>">
                                    <input type="hidden" name="confirmed" value="1">
                                    <button
                                        type="submit"
                                        class="app-button primary"
                                        <?= (int) ($cyclePreview['target_count'] ?? 0) <= 0 ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-bolt"></i>
                                        Activate update
                                    </button>
                                </form>
                            <?php elseif (($selectedCycle['status'] ?? '') === 'Active' && empty($selectedCycle['is_default'])): ?>
                                <form
                                    method="post"
                                    action="index.php?page=student_profile_cycle_close"
                                    data-confirm-form
                                    data-confirm-title="Close this profile update?"
                                    data-confirm-text="Students will no longer be required to complete this cycle.">
                                    <?= csrfInput() ?>
                                    <input type="hidden" name="cycle_id" value="<?= $selectedCycleId ?>">
                                    <input type="hidden" name="confirmed" value="1">
                                    <button type="submit" class="app-button danger">
                                        <i class="fa-solid fa-lock"></i>
                                        Close update
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </header>

                    <?php
                    $visibleStudents =
                        ($selectedCycle['status'] ?? '') === 'Draft'
                        ? ($cyclePreview['targets'] ?? [])
                        : $assignments;
                    ?>
                    <dl class="student-profile-request-schedule">
                        <div><dt>Opens</dt><dd><?= $escape($cycleDateLabel($selectedCycle['opens_at'] ?? null, 'When activated')) ?></dd></div>
                        <div><dt>Due</dt><dd><?= $escape($cycleDateLabel($selectedCycle['due_at'] ?? null, 'No deadline')) ?></dd></div>
                    </dl>
                    <?php if (($selectedCycle['status'] ?? '') === 'Draft'): ?>
                        <p class="student-profile-request-guidance">
                            <strong><?= (int) ($cyclePreview['target_count'] ?? 0) ?> eligible students.</strong>
                            <?= $cyclePreview === null ? 'Recipient preview is unavailable.' : ((int) ($cyclePreview['target_count'] ?? 0) > 0
                                ? 'Review the list below. Activate the update to assign and notify these students.'
                                : 'No students match this request. Activation is unavailable.') ?>
                        </p>
                    <?php else: ?>
                        <div class="student-profile-cycle-metrics">
                            <article><span>Not started</span><strong><?= (int) ($cycleData['counts']['assigned'] ?? 0) ?></strong></article>
                            <article><span>In progress</span><strong><?= (int) ($cycleData['counts']['in_progress'] ?? 0) ?></strong></article>
                            <article><span>Completed</span><strong><?= (int) ($cycleData['counts']['completed'] ?? 0) ?></strong></article>
                        </div>
                    <?php endif; ?>
                    <div class="student-profile-recipient-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>Academic assignment</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($visibleStudents === []): ?>
                                    <tr><td colspan="4">No students to display for this update.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($visibleStudents as $student): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?= $escape(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))) ?>
                                            </strong>
                                            <span><?= $escape($student['email'] ?? '') ?></span>
                                        </td>
                                        <td><?= $escape($student['studID'] ?? '') ?></td>
                                        <td>
                                            <?= $escape(
                                                $student['section_name']
                                                    ?? $student['program_code']
                                                    ?? $student['grade_level_name']
                                                    ?? $student['education_level_name']
                                                    ?? 'Unassigned'
                                            ) ?>
                                        </td>
                                        <td><?= $escape($student['assignment_status'] ?? 'Will be assigned') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</section>

<div class="student-profile-modal" data-profile-modal="draft" hidden>
    <div class="student-profile-modal-backdrop" data-close-profile-modal></div>
    <section
        class="student-profile-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="draftModalTitle">
        <header>
            <div>
                <span>Questionnaire version</span>
                <h2 id="draftModalTitle">Copy questionnaire</h2>
            </div>
            <button type="button" data-close-profile-modal aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>
        <form
            method="post"
            action="index.php?page=student_profile_questionnaire_draft_create"
            data-confirm-form
            data-confirm-title="Create this questionnaire Draft?"
            data-confirm-text="All questions from the selected source version will be copied.">
            <?= csrfInput() ?>

            <div class="student-profile-form-grid">
                <label class="full">
                    <span>Source questionnaire</span>
                    <select name="source_version" required>
                        <option value="">Select a version</option>
                        <?php foreach ($versions as $version): ?>
                            <option value="<?= (int) ($version['survey_version'] ?? 0) ?>">
                                Version <?= (int) ($version['survey_version'] ?? 0) ?> —
                                <?= $escape($version['version_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="full">
                    <span>Draft name</span>
                    <input type="text" name="version_name" maxlength="150" required>
                </label>

                <label class="full">
                    <span>Description</span>
                    <textarea name="description" maxlength="1000" rows="3"></textarea>
                </label>
            </div>

            <footer>
                <button type="button" class="app-button secondary" data-close-profile-modal>
                    Cancel
                </button>
                <button type="submit" class="app-button primary">
                    Copy questionnaire
                </button>
            </footer>
        </form>
    </section>
</div>

<div class="student-profile-modal" data-profile-modal="question" hidden>
    <div class="student-profile-modal-backdrop" data-close-profile-modal></div>
    <section
        class="student-profile-modal-dialog large"
        role="dialog"
        aria-modal="true"
        aria-labelledby="questionModalTitle">
        <header>
            <div>
                <span>Draft questionnaire</span>
                <h2 id="questionModalTitle" data-question-modal-title>Add Question</h2>
            </div>
            <button type="button" data-close-profile-modal aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>
        <form
            method="post"
            action="index.php?page=student_profile_question_create"
            data-question-form
            data-confirm-form
            data-confirm-title="Save this question?"
            data-confirm-text="The question will be saved to Draft Version <?= $selectedVersionId ?>.">
            <?= csrfInput() ?>
            <input type="hidden" name="survey_version" value="<?= $selectedVersionId ?>">
            <input type="hidden" name="question_id" value="" data-question-id>
            <div class="student-profile-form-grid">
                <label class="full"><span>Question</span><textarea name="question_text" maxlength="500" rows="3" required></textarea></label>
                <label>
                    <span>Category</span>
                    <select name="section_key" required>
                        <?php foreach ($questionSections as $section): ?>
                            <option value="<?= $escape($section['section_key'] ?? '') ?>">
                                <?= $escape($section['section_label'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Category label and order are automatic.</small>
                </label>
                <label class="full"><span>Instructions (optional)</span><textarea name="help_text" maxlength="500" rows="2"></textarea></label>
                <label>
                    <span>Answer format</span>
                    <select name="response_type" required>
                        <?php foreach ($responseLabels as $value => $label): ?>
                            <option value="<?= $value ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="full student-profile-option-editor" data-option-editor>
                    <div>
                        <span>Answer choices</span>
                        <button
                            type="button"
                            class="app-button secondary compact"
                            data-add-option>
                            <i class="fa-solid fa-plus"></i>
                            Add option
                        </button>
                    </div>
                    <div data-option-list></div>
                    <small>Choice questions require at least two distinct options.</small>
                </div>
                <div class="full student-profile-question-settings">
                    <h3>Question settings</h3>
                    <p>Required and sensitive-answer settings apply to this question.</p>
                </div>
                <label class="student-profile-check"><input type="checkbox" name="is_required" value="1"><span>Required question</span></label>
                <label class="student-profile-check"><input type="checkbox" name="analytics_enabled" value="1" checked><span>Include in analytics</span></label>
                <label class="student-profile-check"><input type="checkbox" name="is_sensitive" value="1" data-sensitive-toggle><span>Sensitive question</span></label>
                <label data-consent-field hidden>
                    <span>Consent definition</span>
                    <select name="consent_key">
                        <option value="">Select consent</option>
                        <?php foreach ($consentDefinitions as $consent): ?>
                            <option value="<?= $escape($consent['consent_key'] ?? '') ?>">
                                <?= $escape($consent['title'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Display order</span><input type="number" name="sort_order" min="0" max="9999" value="10" required></label>
            </div>
            <footer>
                <button type="button" class="app-button secondary" data-close-profile-modal>
                    Cancel
                </button>
                <button type="submit" class="app-button primary">
                    Save Question
                </button>
            </footer>
        </form>
    </section>
</div>

<div class="student-profile-modal" data-profile-modal="cycle" hidden>
    <div class="student-profile-modal-backdrop" data-close-profile-modal></div>
    <section
        class="student-profile-modal-dialog large"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cycleModalTitle">
        <header>
            <div>
                <span>Update scheduling</span>
                <h2 id="cycleModalTitle">Request profile update</h2>
            </div>
            <button type="button" data-close-profile-modal aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>
        <form
            method="post"
            action="index.php?page=student_profile_cycle_create"
            data-cycle-form
            data-confirm-form
            data-confirm-title="Save this profile update draft?"
            data-confirm-text="Review the recipients next. Students are assigned and notified only when you activate the update.">
            <?= csrfInput() ?>
            <div class="student-profile-form-grid">
                <label class="full"><span>Request name</span><input type="text" name="cycle_name" maxlength="150" placeholder="First semester profile update" required></label>
                <label>
                    <span>Questionnaire</span>
                    <select name="survey_version" required>
                        <option value="">Select a version</option>
                        <?php foreach ($versions as $version): ?>
                            <?php if (in_array($version['status'] ?? '', ['Draft', 'Active'], true)): ?>
                                <option value="<?= (int) ($version['survey_version'] ?? 0) ?>">
                                    Version <?= (int) ($version['survey_version'] ?? 0) ?> —
                                    <?= $escape($version['version_name'] ?? '') ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </label>
                <fieldset class="full student-profile-scope-picker">
                    <legend>
                        Who must complete this profile update?
                    </legend>

                    <p class="student-profile-field-help">
                        Only students in the selected group will be assigned this update.
                    </p>

                    <input
                        type="hidden"
                        name="scope_type"
                        value="AllStudents"
                        data-cycle-scope-type>

                    <input
                        type="hidden"
                        name="scope_id"
                        value="0"
                        data-cycle-scope-id>

                    <div>
                        <label>
                            <input
                                type="radio"
                                name="profile_audience"
                                value="all_students"
                                data-scope-type="AllStudents"
                                data-scope-id="0"
                                checked>

                            <span>
                                <i class="fa-solid fa-users"></i>
                                All Active Students
                            </span>
                        </label>

                        <?php foreach ($profileAudienceDepartments as $department): ?>
                            <?php if ($department['department_id'] > 0): ?>
                                <label>
                                    <input
                                        type="radio"
                                        name="profile_audience"
                                        value="<?= (int) $department['department_id'] ?>"
                                        data-scope-type="Department"
                                        data-scope-id="<?= (int) $department['department_id'] ?>">

                                    <span>
                                        <i class="fa-solid <?= $escape($department['icon']) ?>"></i>
                                        <?= $escape($department['label']) ?>
                                    </span>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <div class="full student-profile-request-heading"><h3>Schedule</h3></div>
                <label><span>Academic year</span><input type="text" name="academic_year" maxlength="30" placeholder="2027-2028"></label>
                <label><span>For which period?</span><select name="update_period" required>
                        <option value="SchoolYear">Whole school year</option>
                        <option value="FirstSemester" selected>First semester</option>
                        <option value="SecondSemester">Second semester</option>
                        <option value="Summer">Summer</option>
                        <option value="Custom">Other</option>
                    </select></label>
                <details class="full student-profile-schedule-dates">
                    <summary>Set dates (optional)</summary>
                    <p>Without dates, this update opens when activated and has no deadline.</p>
                    <div class="student-profile-form-grid">
                        <label><span>Opening date</span><input type="datetime-local" name="opens_at"><small>Leave blank to open when activated.</small></label>
                        <label><span>Due date</span><input type="datetime-local" name="due_at"><small>Leave blank for no deadline.</small></label>
                    </div>
                </details>

            </div>
            <footer>
                <button type="button" class="app-button secondary" data-close-profile-modal>
                    Cancel
                </button>
                <button type="submit" class="app-button primary">
                    Save draft and preview
                </button>
            </footer>
        </form>
    </section>
</div>