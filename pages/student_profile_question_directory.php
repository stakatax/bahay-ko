<?php

/** @var array $viewData */
$questionnaireDirectory =
    is_array(
        $viewData['questionnaires']
            ?? null
    )
    ? $viewData['questionnaires']
    : [];

$selectedVersion =
    is_array(
        $questionnaireDirectory['selected_survey_version']
            ?? null
    )
    ? $questionnaireDirectory['selected_survey_version']
    : null;

$questions =
    is_array(
        $questionnaireDirectory['questions']
            ?? null
    )
    ? $questionnaireDirectory['questions']
    : [];

$consentDefinitions =
    is_array(
        $questionnaireDirectory['consent_definitions']
            ?? null
    )
    ? $questionnaireDirectory['consent_definitions']
    : [];

$escape =
    static fn(
        mixed $value
    ): string =>
    htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );

$selectedVersionStatus =
    (string) (
        $selectedVersion['status']
        ?? ''
    );

$canModifyQuestions =
    $selectedVersionStatus === 'Draft';

?>

<section class="student-profile-question-directory">

    <div class="student-profile-question-directory-heading">

        <div>
            <span>Question Directory</span>
            <h3><?= count($questions) ?> configured questions</h3>
        </div>

        <strong>
            <?= $canModifyQuestions ? 'Draft editing enabled' : 'Read only' ?>
        </strong>

    </div>

    <?php if ($questions === []): ?>

        <p class="student-profile-question-empty">
            This questionnaire version does not contain any questions.
        </p>

    <?php else: ?>

        <div class="student-profile-question-list">

            <?php foreach ($questions as $question): ?>

                <?php

                $questionId =
                    (int) (
                        $question['student_profile_question_id']
                        ?? 0
                    );

                $questionStatus =
                    (string) (
                        $question['status']
                        ?? ''
                    );

                $optionsText =
                    implode(
                        PHP_EOL,
                        $question['options']
                            ?? []
                    );

                ?>

                <details class="student-profile-question-item">

                    <summary>

                        <span class="student-profile-question-order">
                            <?= (int) ($question['sort_order'] ?? 0) ?>
                        </span>

                        <span class="student-profile-question-summary-copy">

                            <strong>
                                <?= $escape($question['question_text'] ?? '') ?>
                            </strong>

                            <small>
                                <?= $escape($question['section_label'] ?? '') ?>
                                · <?= $escape($question['response_type'] ?? '') ?>
                                · <?= $escape($question['question_key'] ?? '') ?>
                            </small>

                        </span>

                        <span class="student-profile-question-status student-profile-question-status--<?= strtolower($escape($questionStatus)) ?>">
                            <?= $escape($questionStatus) ?>
                        </span>

                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>

                    </summary>

                    <div class="student-profile-question-item-body">

                        <?php if (!$canModifyQuestions): ?>

                            <p>
                                Active questionnaire versions are locked to protect existing Student responses and cycle history.
                            </p>

                        <?php else: ?>

                            <form
                                method="post"
                                action="index.php?page=student_profile_question_update"
                                class="student-profile-management-form student-profile-question-edit-form">

                                <?= csrfInput() ?>

                                <input type="hidden" name="question_id" value="<?= $questionId ?>">
                                <input type="hidden" name="survey_version" value="<?= (int) ($selectedVersion['survey_version'] ?? 0) ?>">
                                <input type="hidden" name="question_key" value="<?= $escape($question['question_key'] ?? '') ?>">

                                <div>
                                    <label>Question Key</label>
                                    <input type="text" value="<?= $escape($question['question_key'] ?? '') ?>" disabled>
                                    <small>The stable question key cannot be changed after creation.</small>
                                </div>

                                <div>
                                    <label for="sectionKey<?= $questionId ?>">Section Key</label>
                                    <input type="text" id="sectionKey<?= $questionId ?>" name="section_key" value="<?= $escape($question['section_key'] ?? '') ?>" required>
                                </div>

                                <div>
                                    <label for="sectionLabel<?= $questionId ?>">Section Label</label>
                                    <input type="text" id="sectionLabel<?= $questionId ?>" name="section_label" value="<?= $escape($question['section_label'] ?? '') ?>" required>
                                </div>

                                <div>
                                    <label for="sectionOrder<?= $questionId ?>">Section Order</label>
                                    <input type="number" id="sectionOrder<?= $questionId ?>" name="section_sort_order" min="0" max="9999" value="<?= (int) ($question['section_sort_order'] ?? 0) ?>" required>
                                </div>

                                <div>
                                    <label for="questionText<?= $questionId ?>">Question</label>
                                    <textarea id="questionText<?= $questionId ?>" name="question_text" maxlength="500" rows="3" required><?= $escape($question['question_text'] ?? '') ?></textarea>
                                </div>

                                <div>
                                    <label for="helpText<?= $questionId ?>">Help Text</label>
                                    <textarea id="helpText<?= $questionId ?>" name="help_text" maxlength="500" rows="3"><?= $escape($question['help_text'] ?? '') ?></textarea>
                                </div>

                                <div>
                                    <label for="responseType<?= $questionId ?>">Response Type</label>
                                    <select id="responseType<?= $questionId ?>" name="response_type" required>
                                        <?php foreach (['SingleChoice', 'MultipleChoice', 'Boolean', 'ShortText', 'LongText', 'Number'] as $responseType): ?>
                                            <option value="<?= $responseType ?>" <?= ($question['response_type'] ?? '') === $responseType ? 'selected' : '' ?>>
                                                <?= $escape($responseType) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="questionOptions<?= $questionId ?>">Response Options</label>
                                    <textarea id="questionOptions<?= $questionId ?>" name="options" rows="4" placeholder="One option per line"><?= $escape($optionsText) ?></textarea>
                                </div>

                                <div>
                                    <label for="questionOrder<?= $questionId ?>">Question Order</label>
                                    <input type="number" id="questionOrder<?= $questionId ?>" name="sort_order" min="0" max="9999" value="<?= (int) ($question['sort_order'] ?? 0) ?>" required>
                                </div>

                                <div>
                                    <label for="consentKey<?= $questionId ?>">Consent Definition</label>
                                    <select id="consentKey<?= $questionId ?>" name="consent_key">
                                        <option value="">Not applicable</option>
                                        <?php foreach ($consentDefinitions as $consent): ?>
                                            <option value="<?= $escape($consent['consent_key'] ?? '') ?>" <?= ($question['consent_key'] ?? '') === ($consent['consent_key'] ?? '') ? 'selected' : '' ?>>
                                                <?= $escape($consent['title'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <label>
                                    <input type="checkbox" name="is_required" value="1" <?= !empty($question['is_required']) ? 'checked' : '' ?>>
                                    Required question
                                </label>

                                <label>
                                    <input type="checkbox" name="is_sensitive" value="1" <?= !empty($question['is_sensitive']) ? 'checked' : '' ?>>
                                    Sensitive question requiring consent
                                </label>

                                <label>
                                    <input type="checkbox" name="analytics_enabled" value="1" <?= !empty($question['analytics_enabled']) ? 'checked' : '' ?>>
                                    Include in analytics
                                </label>

                                <button type="submit">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Save Question
                                </button>

                            </form>

                            <form
                                method="post"
                                action="index.php?page=student_profile_question_change_status"
                                class="student-profile-question-status-form"
                                onsubmit="return confirm('Change this question status?');">

                                <?= csrfInput() ?>
                                <input type="hidden" name="question_id" value="<?= $questionId ?>">
                                <input type="hidden" name="survey_version" value="<?= (int) ($selectedVersion['survey_version'] ?? 0) ?>">
                                <input type="hidden" name="confirm_status_change" value="1">
                                <input type="hidden" name="new_status" value="<?= $questionStatus === 'Active' ? 'Inactive' : 'Active' ?>">

                                <button type="submit">
                                    <i class="fa-solid <?= $questionStatus === 'Active' ? 'fa-circle-pause' : 'fa-circle-play' ?>"></i>
                                    <?= $questionStatus === 'Active' ? 'Deactivate Question' : 'Activate Question' ?>
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </details>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>