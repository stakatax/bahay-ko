<?php

$viewData =
    isset($viewData) &&
    is_array($viewData)
    ? $viewData
    : [];

$isSurveyPage = ($page ?? '') === 'student_profile_survey';

$interests =
    $viewData['interests']
    ?? [];

$profile =
    is_array(
        $viewData['profile']
            ?? null
    )
    ? $viewData['profile']
    : null;

$flashType =
    trim(
        (string) (
            $viewData['flash_type']
            ?? ''
        )
    );

$flashMessage =
    trim(
        (string) (
            $viewData['flash_message']
            ?? ''
        )
    );

$oldInterestIds =
    is_array(
        $viewData['old_interest_ids']
            ?? null
    )
    ? array_map(
        'intval',
        $viewData['old_interest_ids']
    )
    : [];


$surveySections =
    is_array(
        $viewData['sections']
            ?? null
    )
    ? $viewData['sections']
    : [];

$surveyResponses =
    is_array(
        $viewData['responses']
            ?? null
    )
    ? $viewData['responses']
    : [];

$surveyConsents =
    is_array(
        $viewData['consents']
            ?? null
    )
    ? $viewData['consents']
    : [];

$oldSurveyResponses =
    is_array(
        $viewData['old_survey_responses']
            ?? null
    )
    ? $viewData['old_survey_responses']
    : [];

$oldSurveyConsents =
    is_array(
        $viewData['old_survey_consents']
            ?? null
    )
    ? $viewData['old_survey_consents']
    : [];

$surveyProgress =
    max(
        0,
        min(
            100,
            (int) (
                $viewData['progress_percentage']
                ?? 0
            )
        )
    );

$surveyCurrentStep =
    trim(
        (string) (
            $viewData['flash_current_step']
            ?? ''
        )
    );

if ($surveyCurrentStep === '') {
    $surveyCurrentStep =
        trim(
            (string) (
                $viewData['current_step']
                ?? ''
            )
        );
}

$surveyCompleted =
    (
        $profile['survey_completion_status']
        ?? ''
    ) === 'Completed';

$surveyLastSavedAt =
    trim(
        (string) (
            $profile['survey_last_saved_at']
            ?? ''
        )
    );

$escape =
    static fn(
        mixed $value
    ): string =>
    htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );

$selectedInterestIds = [];

$selectedInterestWeights = [];

if (
    $flashType === 'error'
) {
    $selectedInterestIds =
        $oldInterestIds;
} elseif ($profile !== null) {
    foreach (
        $profile['interests']
            ?? []
        as $profileInterest
    ) {
        $interestId =
            (int) (
                $profileInterest['interest_id']
                ?? 0
            );

        if ($interestId <= 0) {
            continue;
        }

        $selectedInterestIds[] =
            $interestId;

        $selectedInterestWeights[$interestId] = (int) (
            $profileInterest['preference_weight']
            ?? 3
        );
    }
}

$selectedInterestIds =
    array_values(
        array_unique(
            $selectedInterestIds
        )
    );

$profileCompleted =
    (
        $profile['completion_status']
        ?? ''
    ) === 'Completed';

$profileVersion =
    max(
        0,
        (int) (
            $profile['profile_version']
            ?? 0
        )
    );

$interestIcons = [
    'academic-updates' =>
    'fa-solid fa-graduation-cap',

    'scholarships-financial-aid' =>
    'fa-solid fa-hand-holding-dollar',

    'school-events' =>
    'fa-regular fa-calendar',

    'clubs-organizations' =>
    'fa-solid fa-people-group',

    'sports-recreation' =>
    'fa-solid fa-medal',

    'career-college-opportunities' =>
    'fa-solid fa-briefcase',

    'health-wellness' =>
    'fa-solid fa-heart-pulse',

    'safety-emergency' =>
    'fa-solid fa-shield-halved',

    'policies-memoranda' =>
    'fa-solid fa-file-signature',

    'community-outreach' =>
    'fa-solid fa-handshake-angle'
];

?>

<section class="app-page student-profile-page">

    <!-- ======================================
         PAGE HEADER
    ======================================= -->

    <header class="page-header student-profile-header">

        <div class="page-header-copy">

            <h1>
                <?= $isSurveyPage ? 'School profile survey' : 'My Student Profile' ?>
            </h1>

            <p>
                <?= $isSurveyPage
                    ? 'Complete or update your school profile answers.'
                    : 'Choose your interests or open your school profile survey.' ?>
            </p>

            <nav class="student-profile-task-links" aria-label="Profile tasks">
                <?php if ($isSurveyPage): ?>
                    <a href="index.php?page=student_profile">Return to my interests</a>
                <?php else: ?>
                    <a href="index.php?page=student_profile_survey">Open school profile survey <span aria-hidden="true">&#8594;</span></a>
                <?php endif; ?>
            </nav>

        </div>

        <?php if (!$isSurveyPage): ?>
        <div class="student-profile-status">

            <span class="student-profile-status-icon">

                <i class="<?= $profileCompleted
                                ? 'fa-solid fa-circle-check'
                                : 'fa-solid fa-wand-magic-sparkles'
                            ?>"></i>

            </span>

            <div>

                <strong>
                    <?= $profileCompleted
                        ? 'Interests saved'
                        : 'Choose your interests'
                    ?>
                </strong>

                <small>
                    <?= $profileCompleted
                        ? 'You can update these anytime.'
                        : 'Choose at least three interests'
                    ?>
                </small>

            </div>

        </div>

        <?php endif; ?>
    </header>

    <!-- ======================================
         FLASH MESSAGE
    ======================================= -->

    <?php if ($flashMessage !== ''): ?>

        <div
            class="student-profile-alert <?= htmlspecialchars(
                                                $flashType,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
            data-student-profile-alert
            role="alert">

            <i class="<?= $flashType === 'success'
                            ? 'fa-solid fa-circle-check'
                            : 'fa-solid fa-circle-exclamation'
                        ?>"></i>

            <span>
                <?= htmlspecialchars(
                    $flashMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

            <button
                type="button"
                data-dismiss-student-profile-alert
                aria-label="Dismiss message">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

    <?php endif; ?>

    <!-- ======================================
         PROFILE SURVEY
    ======================================= -->

    <?php if (!$isSurveyPage): ?>
    <form
        method="post"
        action="index.php?page=student_profile_save"
        class="page-card student-profile-form"
        id="studentProfileForm"
        novalidate>

        <?= csrfInput() ?>

        <div class="student-profile-form-header">

            <div class="student-profile-form-heading">

                <span class="student-profile-form-icon">

                    <i class="fa-solid fa-sliders"></i>

                </span>

                <div>

                    <span class="page-eyebrow">
                        Interest Survey
                    </span>

                    <h2>
                        What would you like to see?
                    </h2>

                    <p>
                        Select at least three topics. You may also
                        adjust how strongly each topic should
                        influence your personalized feed.
                    </p>

                </div>

            </div>

            <div
                class="student-profile-selection-summary"
                aria-live="polite">

                <strong data-interest-selection-count>
                    <?= number_format(
                        count(
                            $selectedInterestIds
                        )
                    ) ?>
                </strong>

                <span>
                    selected
                </span>

                <small data-interest-selection-requirement>
                    <?= count(
                        $selectedInterestIds
                    ) >= 3
                        ? 'Minimum reached'
                        : (
                            3 -
                            count(
                                $selectedInterestIds
                            )
                        )
                        . ' more required'
                    ?>
                </small>

            </div>

        </div>

        <div
            class="student-profile-progress"
            aria-hidden="true">

            <span
                data-interest-progress
                style="width: <?= min(
                                    100,
                                    (
                                        count(
                                            $selectedInterestIds
                                        ) / 3
                                    ) * 100
                                ) ?>%">
            </span>

        </div>

        <?php if ($interests === []): ?>

            <div class="student-profile-empty">

                <i class="fa-solid fa-layer-group"></i>

                <h3>
                    Interests are unavailable
                </h3>

                <p>
                    The interest catalog has not been configured.
                    Please contact the System Administrator.
                </p>

            </div>

        <?php else: ?>

            <div class="student-interest-grid">

                <?php foreach (
                    $interests as $interest
                ): ?>

                    <?php

                    $interestId =
                        (int) (
                            $interest['interest_id']
                            ?? 0
                        );

                    $interestName =
                        trim(
                            (string) (
                                $interest['interest_name']
                                ?? 'Interest'
                            )
                        );

                    $interestSlug =
                        trim(
                            (string) (
                                $interest['interest_slug']
                                ?? ''
                            )
                        );

                    $interestDescription =
                        trim(
                            (string) (
                                $interest['description']
                                ?? ''
                            )
                        );

                    $isSelected =
                        in_array(
                            $interestId,
                            $selectedInterestIds,
                            true
                        );

                    $preferenceWeight =
                        (int) (
                            $selectedInterestWeights[$interestId]
                            ?? 3
                        );

                    $interestIcon =
                        $interestIcons[$interestSlug]
                        ?? 'fa-solid fa-star';

                    ?>

                    <article
                        class="student-interest-card <?= $isSelected
                                                            ? 'is-selected'
                                                            : ''
                                                        ?>"
                        data-interest-card>

                        <label class="student-interest-choice">

                            <input
                                type="checkbox"
                                name="interest_ids[]"
                                value="<?= $interestId ?>"
                                data-interest-checkbox
                                <?= $isSelected
                                    ? 'checked'
                                    : ''
                                ?>>

                            <span class="student-interest-check">

                                <i class="fa-solid fa-check"></i>

                            </span>

                            <span class="student-interest-icon">

                                <i class="<?= htmlspecialchars(
                                                $interestIcon,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"></i>

                            </span>

                            <span class="student-interest-copy">

                                <strong>
                                    <?= htmlspecialchars(
                                        $interestName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <small>
                                    <?= htmlspecialchars(
                                        $interestDescription,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </small>

                            </span>

                        </label>

                        <label class="student-interest-weight">

                            <span>
                                Preference level
                            </span>

                            <select
                                name="interest_weights[<?= $interestId ?>]"
                                data-interest-weight
                                <?= !$isSelected
                                    ? 'disabled'
                                    : ''
                                ?>>

                                <option
                                    value="2"
                                    <?= $preferenceWeight === 2
                                        ? 'selected'
                                        : ''
                                    ?>>
                                    Interested
                                </option>

                                <option
                                    value="3"
                                    <?= $preferenceWeight === 3
                                        ? 'selected'
                                        : ''
                                    ?>>
                                    Very interested
                                </option>

                                <option
                                    value="5"
                                    <?= $preferenceWeight === 5
                                        ? 'selected'
                                        : ''
                                    ?>>
                                    High priority
                                </option>

                            </select>

                        </label>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <div
            class="student-profile-validation"
            id="studentProfileValidation"
            role="alert"
            hidden>

            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                Select at least three interests before saving.
            </span>

        </div>

        <footer class="student-profile-form-footer">

            <div class="student-profile-privacy-note">

                <i class="fa-solid fa-shield-halved"></i>

                <span>

                    <strong>
                        Your academic access will not change
                    </strong>

                    <small>
                        Interests influence content ordering only.
                        Required, emergency, and officially targeted
                        content remains visible.
                    </small>

                </span>

            </div>

            <div class="student-profile-form-actions">

                <button
                    type="button"
                    class="app-button secondary"
                    data-clear-interest-selection
                    <?= $selectedInterestIds === []
                        ? 'disabled'
                        : ''
                    ?>>

                    <i class="fa-solid fa-rotate-left"></i>

                    Clear
                </button>

                <button
                    type="submit"
                    class="app-button primary"
                    data-save-student-profile
                    <?= count(
                        $selectedInterestIds
                    ) < 3
                        ? 'disabled'
                        : ''
                    ?>>

                    <i class="fa-solid fa-floppy-disk"></i>

                    <?= $profileCompleted
                        ? 'Update My Interests'
                        : 'Save My Interests'
                    ?>
                </button>

            </div>

        </footer>

    </form>
    <?php endif; ?>


    <!-- ======================================
         EXPANDED STUDENT PROFILE SURVEY
    ======================================= -->

    <?php if ($isSurveyPage): ?>
    <section
        id="expandedProfileSurvey"
        class="page-card student-survey-card"
        data-student-survey>

        <header class="student-survey-header">

            <div class="student-survey-heading">

                <span class="student-survey-heading-icon">

                    <i class="fa-solid fa-clipboard-list"></i>

                </span>

                <div>

                    <span class="page-eyebrow">
                        Student Access Profile
                    </span>

                    <h2>
                        School profile survey
                    </h2>

                    <p>
                        Help the school understand students’
                        device access, connectivity, learning
                        environment, and optional support needs.
                    </p>

                </div>

            </div>

            <div class="student-survey-status">

                <strong>
                    <?= $surveyCompleted
                        ? 'Completed'
                        : $surveyProgress . '% complete'
                    ?>
                </strong>

                <small>
                    <?= $surveyCompleted
                        ? 'You may update your answers at any time.'
                        : 'Your progress can be saved and continued later.'
                    ?>
                </small>

            </div>

        </header>

        <div
            class="student-survey-progress"
            role="progressbar"
            aria-label="Survey completion"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="<?= $surveyProgress ?>">

            <span style="width: <?= $surveyProgress ?>%"></span>

        </div>

        <?php if ($surveySections === []): ?>

            <div class="student-profile-empty">

                <i class="fa-solid fa-clipboard-question"></i>

                <h3>
                    Survey unavailable
                </h3>

                <p>
                    The Student profile survey has not been
                    configured. Please contact the System
                    Administrator.
                </p>

            </div>

        <?php else: ?>

            <form
                method="post"
                action="index.php?page=student_profile_survey_save"
                class="student-survey-form"
                data-student-survey-form
                novalidate>

                <?= csrfInput() ?>

                <input
                    type="hidden"
                    name="current_step"
                    value="<?= $escape(
                                $surveyCurrentStep
                            ) ?>"
                    data-survey-current-step>

                <div class="student-survey-feedback">
                    <strong data-survey-position></strong>
                    <span data-survey-live-progress></span>
                    <p data-survey-save-state role="status">Next changes sections without saving. Choose Save and continue later to keep your answers.</p>
                </div>

                <div class="student-survey-layout">

                    <nav
                        class="student-survey-navigation"
                        aria-label="Survey sections">

                        <?php foreach (
                            $surveySections
                            as $sectionIndex => $section
                        ): ?>

                            <?php

                            $sectionKey =
                                (string) (
                                    $section['section_key']
                                    ?? ''
                                );

                            $sectionLabel =
                                (string) (
                                    $section['section_label']
                                    ?? 'Survey section'
                                );

                            $isActiveSection =
                                $sectionKey ===
                                $surveyCurrentStep
                                ||
                                (
                                    $surveyCurrentStep === '' &&
                                    $sectionIndex === 0
                                );

                            ?>

                            <button
                                type="button"
                                class="student-survey-nav-item<?= $isActiveSection
                                                                    ? ' is-active'
                                                                    : ''
                                                                ?>"
                                data-survey-section-button="<?= $escape(
                                                                $sectionKey
                                                            ) ?>"
                                aria-current="<?= $isActiveSection
                                                    ? 'step'
                                                    : 'false'
                                                ?>">

                                <span>
                                    <?= str_pad(
                                        (string) (
                                            $sectionIndex + 1
                                        ),
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    ) ?>
                                </span>

                                <strong>
                                    <?= $escape(
                                        $sectionLabel
                                    ) ?>
                                </strong>

                            </button>

                        <?php endforeach; ?>

                    </nav>

                    <div class="student-survey-workspace">

                        <?php foreach (
                            $surveySections
                            as $sectionIndex => $section
                        ): ?>

                            <?php

                            $sectionKey =
                                (string) (
                                    $section['section_key']
                                    ?? ''
                                );

                            $sectionLabel =
                                (string) (
                                    $section['section_label']
                                    ?? 'Survey section'
                                );

                            $isActiveSection =
                                $sectionKey ===
                                $surveyCurrentStep
                                ||
                                (
                                    $surveyCurrentStep === '' &&
                                    $sectionIndex === 0
                                );

                            $sectionConsentDefinitions = [];

                            foreach (
                                $section['questions']
                                    ?? []
                                as $sectionQuestion
                            ) {
                                $sectionConsentKey =
                                    trim(
                                        (string) (
                                            $sectionQuestion['consent_key']
                                            ?? ''
                                        )
                                    );

                                $sectionConsentDefinition =
                                    $sectionQuestion['consent_definition']
                                    ?? null;

                                if (
                                    $sectionConsentKey !== '' &&
                                    is_array(
                                        $sectionConsentDefinition
                                    )
                                ) {
                                    $sectionConsentDefinitions[$sectionConsentKey] =
                                        $sectionConsentDefinition;
                                }
                            }

                            ?>

                            <section
                                class="student-survey-section<?= $isActiveSection
                                                                    ? ' is-active'
                                                                    : ''
                                                                ?>"
                                data-survey-section="<?= $escape(
                                                            $sectionKey
                                                        ) ?>"
                                <?= $isActiveSection
                                    ? ''
                                    : 'hidden'
                                ?>>

                                <header class="student-survey-section-header">

                                    <span>
                                        Section
                                        <?= number_format(
                                            $sectionIndex + 1
                                        ) ?>
                                        of
                                        <?= number_format(
                                            count(
                                                $surveySections
                                            )
                                        ) ?>
                                    </span>

                                    <h3>
                                        <?= $escape(
                                            $sectionLabel
                                        ) ?>
                                    </h3>

                                    <p>
                                        Required questions are marked with
                                        an asterisk. Sensitive sections are
                                        optional and controlled by consent.
                                    </p>

                                </header>

                                <?php foreach (
                                    $sectionConsentDefinitions
                                    as $consentKey => $consentDefinition
                                ): ?>

                                    <?php

                                    $savedConsent =
                                        $surveyConsents[$consentKey]
                                        ?? null;

                                    $consentVersion =
                                        (string) (
                                            $consentDefinition['consent_version']
                                            ?? ''
                                        );

                                    if (
                                        array_key_exists(
                                            $consentKey,
                                            $oldSurveyConsents
                                        )
                                    ) {
                                        $consentGranted =
                                            !empty($oldSurveyConsents[$consentKey]);
                                    } else {
                                        $consentGranted =
                                            $savedConsent !== null &&
                                            !empty($savedConsent['consent_granted']) &&
                                            (
                                                $savedConsent['consent_version']
                                                ?? ''
                                            ) === $consentVersion;
                                    }

                                    ?>

                                    <article class="student-survey-consent">

                                        <input
                                            type="hidden"
                                            name="survey_consents[<?= $escape(
                                                                        $consentKey
                                                                    ) ?>]"
                                            value="0">

                                        <label>

                                            <input
                                                type="checkbox"
                                                name="survey_consents[<?= $escape(
                                                                            $consentKey
                                                                        ) ?>]"
                                                value="1"
                                                data-survey-consent="<?= $escape(
                                                                            $consentKey
                                                                        ) ?>"
                                                <?= $consentGranted
                                                    ? 'checked'
                                                    : ''
                                                ?>>

                                            <span class="student-survey-consent-icon">

                                                <i class="fa-solid fa-shield-heart"></i>

                                            </span>

                                            <span>

                                                <strong>
                                                    <?= $escape(
                                                        $consentDefinition['title']
                                                            ?? 'Optional data consent'
                                                    ) ?>
                                                </strong>

                                                <small>
                                                    <?= $escape(
                                                        $consentDefinition['description']
                                                            ?? 'Allow the school to process these optional responses for student support.'
                                                    ) ?>
                                                </small>

                                            </span>

                                        </label>

                                    </article>

                                <?php endforeach; ?>

                                <div class="student-survey-question-list">

                                    <?php foreach (
                                        $section['questions']
                                            ?? []
                                        as $question
                                    ): ?>

                                        <?php

                                        $questionKey =
                                            (string) (
                                                $question['question_key']
                                                ?? ''
                                            );

                                        $questionText =
                                            (string) (
                                                $question['question_text']
                                                ?? 'Survey question'
                                            );

                                        $helpText =
                                            trim(
                                                (string) (
                                                    $question['help_text']
                                                    ?? ''
                                                )
                                            );

                                        $responseType =
                                            (string) (
                                                $question['response_type']
                                                ?? ''
                                            );

                                        $options =
                                            is_array(
                                                $question['options']
                                                    ?? null
                                            )
                                            ? $question['options']
                                            : [];

                                        if (
                                            array_key_exists(
                                                $questionKey,
                                                $oldSurveyResponses
                                            )
                                        ) {
                                            $responseValue =
                                                $oldSurveyResponses[$questionKey];
                                        } else {
                                            $responseValue =
                                                $question['response']['value']
                                                ?? null;
                                        }

                                        $questionConsentKey =
                                            trim(
                                                (string) (
                                                    $question['consent_key']
                                                    ?? ''
                                                )
                                            );

                                        $questionConsentGranted =
                                            empty($question['is_sensitive'])
                                            ||
                                            (
                                                $questionConsentKey !== '' &&
                                                (
                                                    array_key_exists(
                                                        $questionConsentKey,
                                                        $oldSurveyConsents
                                                    )
                                                    ? !empty($oldSurveyConsents[$questionConsentKey])
                                                    : !empty($question['consent_granted'])
                                                )
                                            );

                                        ?>

                                        <fieldset
                                            class="student-survey-question"
                                            data-survey-question
                                            data-required="<?= !empty($question['is_required'])
                                                                ? '1'
                                                                : '0'
                                                            ?>"
                                            <?= $questionConsentKey !== ''
                                                ? 'data-consent-required="'
                                                . $escape(
                                                    $questionConsentKey
                                                )
                                                . '"'
                                                : ''
                                            ?>
                                            <?= !$questionConsentGranted
                                                ? 'disabled'
                                                : ''
                                            ?>>

                                            <legend>

                                                <?= $escape(
                                                    $questionText
                                                ) ?>

                                                <?php if (
                                                    !empty($question['is_required'])
                                                ): ?>

                                                    <span
                                                        aria-label="Required">
                                                        *
                                                    </span>

                                                <?php endif; ?>

                                            </legend>

                                            <?php if ($helpText !== ''): ?>

                                                <p>
                                                    <?= $escape(
                                                        $helpText
                                                    ) ?>
                                                </p>

                                            <?php endif; ?>

                                            <?php if (
                                                $responseType ===
                                                'SingleChoice'
                                            ): ?>

                                                <div class="student-survey-options">

                                                    <?php foreach (
                                                        $options
                                                        as $option
                                                    ): ?>

                                                        <label class="student-survey-option">

                                                            <input
                                                                type="radio"
                                                                name="survey_responses[<?= $escape(
                                                                                            $questionKey
                                                                                        ) ?>]"
                                                                value="<?= $escape(
                                                                            $option
                                                                        ) ?>"
                                                                <?= (string) $responseValue ===
                                                                    (string) $option
                                                                    ? 'checked'
                                                                    : ''
                                                                ?>>

                                                            <span>
                                                                <?= $escape(
                                                                    $option
                                                                ) ?>
                                                            </span>

                                                        </label>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php elseif (
                                                $responseType ===
                                                'MultipleChoice'
                                            ): ?>

                                                <?php

                                                $selectedValues =
                                                    is_array(
                                                        $responseValue
                                                    )
                                                    ? $responseValue
                                                    : [];

                                                ?>

                                                <div class="student-survey-options">

                                                    <?php foreach (
                                                        $options
                                                        as $option
                                                    ): ?>

                                                        <label class="student-survey-option">

                                                            <input
                                                                type="checkbox"
                                                                name="survey_responses[<?= $escape(
                                                                                            $questionKey
                                                                                        ) ?>][]"
                                                                value="<?= $escape(
                                                                            $option
                                                                        ) ?>"
                                                                <?= in_array(
                                                                    $option,
                                                                    $selectedValues,
                                                                    true
                                                                )
                                                                    ? 'checked'
                                                                    : ''
                                                                ?>>

                                                            <span>
                                                                <?= $escape(
                                                                    $option
                                                                ) ?>
                                                            </span>

                                                        </label>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php elseif (
                                                $responseType ===
                                                'LongText'
                                            ): ?>

                                                <textarea
                                                    name="survey_responses[<?= $escape(
                                                                                $questionKey
                                                                            ) ?>]"
                                                    maxlength="1000"
                                                    rows="5"
                                                    placeholder="Enter an optional response"><?= $escape(
                                                                                                    $responseValue
                                                                                                        ?? ''
                                                                                                ) ?></textarea>

                                            <?php else: ?>

                                                <input
                                                    type="text"
                                                    name="survey_responses[<?= $escape(
                                                                                $questionKey
                                                                            ) ?>]"
                                                    value="<?= $escape(
                                                                $responseValue
                                                                    ?? ''
                                                            ) ?>"
                                                    maxlength="150"
                                                    placeholder="Enter your response">

                                            <?php endif; ?>

                                        </fieldset>

                                    <?php endforeach; ?>

                                </div>

                            </section>

                        <?php endforeach; ?>

                        <div
                            class="student-survey-validation"
                            data-survey-validation
                            role="alert"
                            hidden>

                            <i class="fa-solid fa-circle-exclamation"></i>

                            <span>
                                Complete the required questions in this section.
                            </span>

                        </div>

                        <footer class="student-survey-actions">

                            <button
                                type="button"
                                class="app-button secondary"
                                data-survey-previous>

                                <i class="fa-solid fa-arrow-left"></i>

                                Previous
                            </button>

                            <button
                                type="submit"
                                name="survey_action"
                                value="save"
                                class="app-button secondary"
                                formnovalidate>

                                <i class="fa-regular fa-floppy-disk"></i>

                                Save and continue later
                            </button>

                            <button
                                type="button"
                                class="app-button primary"
                                data-survey-next>

                                Next

                                <i class="fa-solid fa-arrow-right"></i>

                            </button>

                            <button
                                type="submit"
                                name="survey_action"
                                value="complete"
                                class="app-button primary"
                                data-survey-complete>

                                <i class="fa-solid fa-circle-check"></i>

                                <?= $surveyCompleted
                                    ? 'Update Completed Survey'
                                    : 'Complete Survey'
                                ?>
                            </button>

                        </footer>

                        <div class="student-survey-save-note">

                            <i class="fa-solid fa-lock"></i>

                            <span>

                                Required operational answers support
                                school planning. Optional sensitive
                                answers are stored only when you grant
                                the corresponding consent.

                                <?php if (
                                    $surveyLastSavedAt !== ''
                                ): ?>

                                    Last saved:
                                    <?= $escape(
                                        date(
                                            'M d, Y g:i A',
                                            strtotime(
                                                $surveyLastSavedAt
                                            )
                                        )
                                    ) ?>.

                                <?php endif; ?>

                            </span>

                        </div>

                    </div>

                </div>

            </form>

        <?php endif; ?>

    </section>

    <?php endif; ?>
</section>
