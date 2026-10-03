<?php

$surveyData =
    $viewData['survey']
    ?? [];

$isOpen =
    !empty($viewData['is_open']);

$hasResponded =
    !empty($viewData['has_responded']);

?>

<section class="app-page">

    <header class="page-header">

        <div class="page-header-copy">

            <span class="page-eyebrow">
                Survey Participation
            </span>

            <h1>
                <?= htmlspecialchars(
                    $surveyData['title']
                        ?? 'Survey',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h1>

            <p>
                <?= htmlspecialchars(
                    $surveyData['description']
                        ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </div>

    </header>

    <div class="app-card">

        <?php if ($hasResponded): ?>

            <p>
                You have already submitted a response to this survey.
            </p>

        <?php elseif (!$isOpen): ?>

            <p>
                This survey is currently unavailable for responses.
            </p>

        <?php else: ?>
            <?php
            $questions =
                $surveyData['questions']
                ?? [];
            ?>

            <?php if (empty($questions)): ?>

                <p>
                    This survey does not contain any questions.
                </p>

            <?php else: ?>

                <form
                    method="POST"
                    action="index.php?page=survey_submit_response"
                    id="survey-response-form">

                    <?= csrfInput() ?>

                    <input
                        type="hidden"
                        name="survey_id"
                        value="<?= (int) (
                                    $surveyData['survey_id']
                                    ?? 0
                                ) ?>">

                    <div class="survey-question-list">

                        <?php foreach (
                            $questions
                            as $index => $question
                        ): ?>

                            <?php
                            $questionId =
                                (int) (
                                    $question['question_id']
                                    ?? 0
                                );

                            $questionType =
                                trim(
                                    (string) (
                                        $question['question_type']
                                        ?? ''
                                    )
                                );

                            $isRequired =
                                !empty($question['is_required']);

                            $choices =
                                $question['choices']
                                ?? [];
                            ?>

                            <section
                                class="survey-question"
                                data-question-id="<?= $questionId ?>">

                                <div class="survey-question-heading">

                                    <span class="survey-question-number">
                                        Question
                                        <?= $index + 1 ?>
                                    </span>

                                    <?php if ($isRequired): ?>

                                        <span class="survey-required">
                                            Required
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <h2 class="survey-question-text">
                                    <?= htmlspecialchars(
                                        (string) (
                                            $question['question']
                                            ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </h2>

                                <?php if (
                                    $questionType === 'Short Text'
                                ): ?>

                                    <input
                                        type="text"
                                        name="answers[<?= $questionId ?>]"
                                        class="survey-input"
                                        <?= $isRequired
                                            ? 'required'
                                            : ''
                                        ?>>

                                <?php elseif (
                                    $questionType === 'Long Text'
                                ): ?>

                                    <textarea
                                        name="answers[<?= $questionId ?>]"
                                        class="survey-textarea"
                                        rows="5"
                                        <?= $isRequired
                                            ? 'required'
                                            : ''
                                        ?>></textarea>

                                <?php elseif (
                                    $questionType === 'Multiple Choice'
                                ): ?>

                                    <div class="survey-options">

                                        <?php foreach (
                                            $choices
                                            as $choice
                                        ): ?>

                                            <?php
                                            $choiceId =
                                                (int) (
                                                    $choice['choice_id']
                                                    ?? 0
                                                );
                                            ?>

                                            <label class="survey-option">

                                                <input
                                                    type="radio"
                                                    name="answers[<?= $questionId ?>]"
                                                    value="<?= $choiceId ?>"
                                                    <?= $isRequired
                                                        ? 'required'
                                                        : ''
                                                    ?>>

                                                <span>
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $choice['choice_text']
                                                            ?? ''
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>

                                            </label>

                                        <?php endforeach; ?>

                                    </div>

                                <?php elseif (
                                    $questionType === 'Checkbox'
                                ): ?>

                                    <div class="survey-options">

                                        <?php foreach (
                                            $choices
                                            as $choice
                                        ): ?>

                                            <?php
                                            $choiceId =
                                                (int) (
                                                    $choice['choice_id']
                                                    ?? 0
                                                );
                                            ?>

                                            <label class="survey-option">

                                                <input
                                                    type="checkbox"
                                                    name="answers[<?= $questionId ?>][]"
                                                    value="<?= $choiceId ?>">

                                                <span>
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $choice['choice_text']
                                                            ?? ''
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>

                                            </label>

                                        <?php endforeach; ?>

                                    </div>

                                <?php elseif (
                                    $questionType === 'Rating'
                                ): ?>

                                    <?php
                                    $ratingMin =
                                        (int) (
                                            $question['rating_min']
                                            ?? 1
                                        );

                                    $ratingMax =
                                        (int) (
                                            $question['rating_max']
                                            ?? 5
                                        );
                                    ?>

                                    <div class="survey-rating">

                                        <?php for (
                                            $rating = $ratingMin;
                                            $rating <= $ratingMax;
                                            $rating++
                                        ): ?>

                                            <label class="survey-rating-option">

                                                <input
                                                    type="radio"
                                                    name="answers[<?= $questionId ?>]"
                                                    value="<?= $rating ?>"
                                                    <?= $isRequired
                                                        ? 'required'
                                                        : ''
                                                    ?>>

                                                <span>
                                                    <?= $rating ?>
                                                </span>

                                            </label>

                                        <?php endfor; ?>

                                    </div>

                                <?php elseif (
                                    $questionType === 'Yes/No'
                                ): ?>

                                    <div class="survey-options">

                                        <label class="survey-option">

                                            <input
                                                type="radio"
                                                name="answers[<?= $questionId ?>]"
                                                value="Yes"
                                                <?= $isRequired
                                                    ? 'required'
                                                    : ''
                                                ?>>

                                            <span>Yes</span>

                                        </label>

                                        <label class="survey-option">

                                            <input
                                                type="radio"
                                                name="answers[<?= $questionId ?>]"
                                                value="No"
                                                <?= $isRequired
                                                    ? 'required'
                                                    : ''
                                                ?>>

                                            <span>No</span>

                                        </label>

                                    </div>

                                <?php else: ?>

                                    <p>
                                        Unsupported question type:
                                        <?= htmlspecialchars(
                                            $questionType,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>

                                <?php endif; ?>

                            </section>

                        <?php endforeach; ?>

                    </div>

                    <div class="survey-submit-area">

                        <button
                            type="submit"
                            class="btn btn-primary">

                            Submit Response

                        </button>

                    </div>

                </form>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</section>