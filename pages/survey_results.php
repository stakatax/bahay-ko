<?php

$viewData =
    isset($viewData) &&
    is_array($viewData)
    ? $viewData
    : [];

$survey =
    is_array(
        $viewData['survey']
            ?? null
    )
    ? $viewData['survey']
    : [];

$results =
    is_array(
        $viewData['results']
            ?? null
    )
    ? $viewData['results']
    : [];

$questions =
    is_array(
        $results['questions']
            ?? null
    )
    ? $results['questions']
    : [];

$privacySuppressed = !empty($results['privacy_suppressed']);
$minimumGroupSize = (int) ($results['minimum_group_size'] ?? 5);

$responseCount =
    (int) (
        $results['response_count']
        ?? 0
    );

$surveyTitle =
    trim(
        (string) (
            $survey['title']
            ?? 'Survey Results'
        )
    );

$surveyDescription =
    trim(
        (string) (
            $survey['description']
            ?? ''
        )
    );

    $requestedReturnDestination =
    isset($_GET['return_to']) &&
    !is_array($_GET['return_to'])
    ? strtolower(
        trim(
            (string) $_GET['return_to']
        )
    )
    : '';

$returnDestinations = [
    'news' => [
        'url' =>
            'index.php?page=news',

        'label' =>
            'Information Hub'
    ],

    'content_workspace' => [
        'url' =>
            'index.php?page=content_workspace',

        'label' =>
            'Content Workspace'
    ],

    'notifications' => [
        'url' =>
            'index.php?page=notifications',

        'label' =>
            'Notifications'
    ]
];

$returnDestination =
    $returnDestinations[
        $requestedReturnDestination
    ]
    ?? $returnDestinations[
        'content_workspace'
    ];

$escape =
    static function (
        mixed $value
    ): string {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    };

?>

<section class="app-page survey-results-page">

    <header class="page-header survey-results-header">

        <div class="page-header-copy">

            <span class="page-eyebrow">
                Survey Analytics
            </span>

            <h1>
                <?= $escape(
                    $surveyTitle
                ) ?>
            </h1>

            <?php if (
                $surveyDescription !== ''
            ): ?>

                <p>
                    <?= $escape(
                        $surveyDescription
                    ) ?>
                </p>

            <?php else: ?>

                <p>
                    Review aggregated responses and
                    question-level results.
                </p>

            <?php endif; ?>

        </div>

        <div class="page-actions">

<a
    href="<?= $escape(
                $returnDestination['url']
            ) ?>"
    class="app-button secondary">

    <i class="fa-solid fa-arrow-left"></i>

    <?= $escape(
        $returnDestination['label']
    ) ?>

</a>

        </div>

    </header>

    <section class="survey-results-summary">

        <article class="page-card survey-summary-card">

            <span class="survey-summary-icon">

                <i class="fa-solid fa-users"></i>

            </span>

            <div>

                <small>
                    Submitted Responses
                </small>

                <strong>
                    <?= $privacySuppressed ? 'Protected' : number_format(
                        $responseCount
                    ) ?>
                </strong>

            </div>

        </article>

        <article class="page-card survey-summary-card">

            <span class="survey-summary-icon questions">

                <i class="fa-solid fa-list-check"></i>

            </span>

            <div>

                <small>
                    Survey Questions
                </small>

                <strong>
                    <?= number_format(
                        count(
                            $questions
                        )
                    ) ?>
                </strong>

            </div>

        </article>

    </section>

    <?php if (
        empty($questions)
    ): ?>

        <section class="page-card survey-results-empty">

            <i class="fa-solid fa-square-poll-horizontal"></i>

            <h2>
                No survey questions available
            </h2>

            <p>
                This survey does not currently contain
                questions that can be analyzed.
            </p>

        </section>

    <?php elseif ($privacySuppressed): ?>

        <section class="page-card survey-results-empty">
            <h2>Results protected for privacy</h2>
            <p>Results become available after at least <?= $minimumGroupSize ?> distinct respondents submit answers.</p>
        </section>

    <?php elseif (
        $responseCount === 0
    ): ?>

        <section class="page-card survey-results-empty">

            <i class="fa-regular fa-chart-bar"></i>

            <h2>
                No responses yet
            </h2>

            <p>
                Results will appear here after eligible
                participants submit the survey.
            </p>

        </section>

    <?php else: ?>

        <section class="survey-question-results">

            <?php foreach (
                $questions as $questionIndex =>
                $question
            ): ?>

                <?php

                $questionType =
                    trim(
                        (string) (
                            $question['question_type']
                            ?? 'Question'
                        )
                    );

                $questionText =
                    trim(
                        (string) (
                            $question['question_text']
                            ?? $question['question']
                            ?? 'Untitled question'
                        )
                    );

                $questionResponseCount =
                    (int) (
                        $question['response_count']
                        ?? 0
                    );

                $choices =
                    is_array(
                        $question['choices']
                            ?? null
                    )
                    ? $question['choices']
                    : [];

                $textAnswers =
                    is_array(
                        $question['text_answers']
                            ?? null
                    )
                    ? $question['text_answers']
                    : [];

                ?>

                <article class="page-card survey-question-card">

                    <header class="survey-question-header">

                        <span class="survey-question-number">

                            <?= str_pad(
                                (string) (
                                    $questionIndex + 1
                                ),
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) ?>

                        </span>

                        <div>

                            <span class="page-eyebrow">

                                <?= $escape(
                                    $questionType
                                ) ?>

                            </span>

                            <h2>
                                <?= $escape(
                                    $questionText
                                ) ?>
                            </h2>

                        </div>

                    </header>

                    <?php if (!empty($question['privacy_suppressed'])): ?>

                        <div class="survey-question-empty">
                            Results protected for privacy. At least <?= $minimumGroupSize ?> respondents
                            are required for this question and each nonempty choice group.
                        </div>

                    <?php elseif (
                        $questionType ===
                        'Multiple Choice' ||
                        $questionType ===
                        'Checkbox'
                    ): ?>

                        <div class="survey-choice-results">

                            <?php foreach (
                                $choices as $choice
                            ): ?>

                                <?php

                                $choiceCount =
                                    (int) (
                                        $choice['response_count']
                                        ?? 0
                                    );

                                $choicePercentage =
                                    $responseCount > 0
                                    ? min(
                                        100,
                                        round(
                                            (
                                                $choiceCount /
                                                $responseCount
                                            ) * 100,
                                            1
                                        )
                                    )
                                    : 0;

                                ?>

                                <div class="survey-choice-result">

                                    <div class="survey-choice-heading">

                                        <strong>
                                            <?= $escape(
                                                $choice['choice_text']
                                                    ?? $choice['choice']
                                                    ?? 'Choice'
                                            ) ?>
                                        </strong>

                                        <span>

                                            <?= number_format(
                                                $choiceCount
                                            ) ?>

                                            ·

                                            <?= $escape(
                                                $choicePercentage
                                            ) ?>%

                                        </span>

                                    </div>

                                    <div class="survey-result-track">

                                        <span
                                            style="--result-width: <?= $escape(
                                                                        $choicePercentage
                                                                    ) ?>%;">
                                        </span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php elseif (
                        $questionType ===
                        'Rating'
                    ): ?>

                        <?php

                        $averageRating =
                            $question['average_rating']
                            ?? null;

                        ?>

                        <div class="survey-rating-result">

                            <span>

                                <i class="fa-solid fa-star"></i>

                            </span>

                            <div>

                                <small>
                                    Average Rating
                                </small>

                                <strong>

                                    <?= $averageRating !== null
                                        ? number_format(
                                            (float) $averageRating,
                                            2
                                        )
                                        : '—'
                                    ?>

                                </strong>

                                <p>
                                    Based on
                                    <?= number_format(
                                        $questionResponseCount
                                    ) ?>
                                    submitted
                                    <?= $questionResponseCount === 1
                                        ? 'rating'
                                        : 'ratings'
                                    ?>.
                                </p>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="survey-text-results">

                            <?php if (
                                empty($textAnswers)
                            ): ?>

                                <div class="survey-question-empty">
                                    No answers were submitted for this question.
                                </div>

                            <?php else: ?>

                                <?php foreach (
                                    $textAnswers as $answer
                                ): ?>

                                    <article class="survey-text-answer">

                                        <p>
                                            <?= $escape(
                                                $answer['answer']
                                                    ?? ''
                                            ) ?>
                                        </p>

                                    </article>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>

</section>