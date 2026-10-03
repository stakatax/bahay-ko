<?php

require_once __DIR__
    . '/../models/StudentProfileManagement.php';

class StudentProfileManagementService
{
    private StudentProfileManagement $management;

    public function __construct()
    {
        $this->management =
            new StudentProfileManagement();
    }

    /* ==========================================
       MANAGEMENT DIRECTORY
    ========================================== */

    public function getDirectory(
        ?int $selectedVersion = null
    ): array {
        $versions =
            $this->management
            ->getSurveyVersions();

        if (
            $selectedVersion === null ||
            $selectedVersion <= 0
        ) {
            $selectedVersion =
                (int) (
                    $versions[0]['survey_version']
                    ?? 0
                );
        }

        $selectedVersionRecord =
            $selectedVersion > 0
            ? $this->management
            ->findSurveyVersion(
                $selectedVersion
            )
            : null;

        return [
            'survey_versions' =>
            $versions,

            'selected_survey_version' =>
            $selectedVersionRecord,

            'questions' =>
            $selectedVersionRecord !== null
                ? $this->management
                ->getSurveyQuestions(
                    $selectedVersion
                )
                : [],

            'sections' =>
            $selectedVersionRecord !== null
                ? $this->management
                ->getSurveySections(
                    $selectedVersion
                )
                : [],

            'consent_definitions' =>
            $this->management
                ->getConsentDefinitions(),

            'counts' => [
                'versions' =>
                count($versions),

                'draft_versions' =>
                count(
                    array_filter(
                        $versions,
                        static fn(
                            array $version
                        ): bool => (
                            $version['status']
                            ?? ''
                        ) === 'Draft'
                    )
                ),

                'questions' =>
                (int) (
                    $selectedVersionRecord['question_count']
                    ?? 0
                )
            ]
        ];
    }

    /* ==========================================
       CREATE DRAFT VERSION
    ========================================== */

    public function createDraftVersion(
        int $sourceVersion,
        string $versionName,
        ?string $description,
        int $adminId
    ): array {
        if ($sourceVersion <= 0) {
            throw new InvalidArgumentException(
                'Select a valid source questionnaire version.'
            );
        }

        if ($adminId <= 0) {
            throw new RuntimeException(
                'A valid Administrator is required.'
            );
        }

        $versionName =
            $this->cleanRequiredText(
                $versionName,
                'Questionnaire version name',
                150
            );

        $description =
            $this->cleanOptionalText(
                $description,
                1000
            );

        try {
            return $this->management
                ->createDraftSurveyVersion(
                    $sourceVersion,
                    $versionName,
                    $description,
                    $adminId
                );
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1062) {
                throw new InvalidArgumentException(
                    'That questionnaire draft conflicts with an existing version.'
                );
            }

            throw new RuntimeException(
                'Unable to create the questionnaire draft: '
                    . $exception->getMessage()
            );
        }
    }

    /* ==========================================
       QUESTION CONFIGURATION
    ========================================== */

    public function createQuestion(
        int $surveyVersion,
        array $data,
        int $adminId
    ): array {
        $this->validateAdministrator(
            $adminId
        );

        $this->requireDraftVersion(
            $surveyVersion
        );

        // Internal identity is independent of wording and browser input.
        $data['question_key'] = 'question_' . bin2hex(random_bytes(16));

        $cleanData =
            $this->normalizeQuestionData(
                $surveyVersion,
                $data,
                true
            );

        try {
            return $this->management
                ->createSurveyQuestion(
                    $surveyVersion,
                    $cleanData
                );
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1062) {
                throw new InvalidArgumentException(
                    'That question key already exists in this questionnaire version.'
                );
            }

            throw new RuntimeException(
                'Unable to create the questionnaire question: '
                    . $exception->getMessage()
            );
        }
    }

    public function updateQuestion(
        int $questionId,
        array $data,
        int $adminId
    ): array {
        $this->validateAdministrator(
            $adminId
        );

        $question =
            $this->management
            ->findSurveyQuestion(
                $questionId
            );

        if (!$question) {
            throw new RuntimeException(
                'The questionnaire question could not be found.'
            );
        }

        $this->requireDraftVersion(
            (int) $question['survey_version']
        );

        $data['question_key'] = (string) $question['question_key'];

        $cleanData =
            $this->normalizeQuestionData(
                (int) $question['survey_version'],
                array_merge(
                    $question,
                    $data
                ),
                false
            );

        return $this->management
            ->updateSurveyQuestion(
                $questionId,
                $cleanData
            );
    }

    public function changeQuestionStatus(
        int $questionId,
        string $status,
        int $adminId
    ): array {
        $this->validateAdministrator(
            $adminId
        );

        $question =
            $this->management
            ->findSurveyQuestion(
                $questionId
            );

        if (!$question) {
            throw new RuntimeException(
                'The questionnaire question could not be found.'
            );
        }

        $this->requireDraftVersion(
            (int) $question['survey_version']
        );

        $status =
            ucfirst(
                strtolower(
                    trim($status)
                )
            );

        if (
            !in_array(
                $status,
                [
                    'Active',
                    'Inactive'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Select a valid question status.'
            );
        }

        return $this->management
            ->setSurveyQuestionStatus(
                $questionId,
                $status
            );
    }

    private function requireDraftVersion(
        int $surveyVersion
    ): array {
        $version =
            $this->management
            ->findSurveyVersion(
                $surveyVersion
            );

        if (!$version) {
            throw new RuntimeException(
                'The questionnaire version could not be found.'
            );
        }

        if (
            (
                $version['status']
                ?? ''
            ) !== 'Draft'
        ) {
            throw new RuntimeException(
                'Only Draft questionnaire versions may be modified.'
            );
        }

        return $version;
    }

    private function normalizeQuestionData(
        int $surveyVersion,
        array $data,
        bool $creating
    ): array {
        $responseTypes = [
            'SingleChoice',
            'MultipleChoice',
            'Boolean',
            'ShortText',
            'LongText',
            'Number'
        ];

        $questionKey =
            $this->cleanRequiredText(
                $data['question_key']
                    ?? '',
                'Question key',
                100
            );

        if (
            !preg_match(
                '/^[a-z][a-z0-9_]{2,99}$/',
                $questionKey
            )
        ) {
            throw new InvalidArgumentException(
                'Question key must use lowercase letters, numbers, and underscores, beginning with a letter.'
            );
        }

        $sectionKey =
            $this->cleanRequiredText(
                $data['section_key']
                    ?? '',
                'Section key',
                50
            );

        if (
            !preg_match(
                '/^[a-z][a-z0-9_]{1,49}$/',
                $sectionKey
            )
        ) {
            throw new InvalidArgumentException(
                'Section key must use lowercase letters, numbers, and underscores.'
            );
        }

        $section =
            $this->management
            ->findSurveySection(
                $surveyVersion,
                $sectionKey
            );

        if (!$section) {
            throw new InvalidArgumentException(
                'Select a category from the current questionnaire version.'
            );
        }

        $responseType =
            trim(
                (string) (
                    $data['response_type']
                    ?? ''
                )
            );

        if (
            !in_array(
                $responseType,
                $responseTypes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Select a supported question response type.'
            );
        }

        $options =
            $this->normalizeOptions(
                $data['options']
                    ?? []
            );

        if (
            in_array(
                $responseType,
                [
                    'SingleChoice',
                    'MultipleChoice'
                ],
                true
            ) &&
            count($options) < 2
        ) {
            throw new InvalidArgumentException(
                'Choice questions require at least two distinct options.'
            );
        }

        if (
            !in_array(
                $responseType,
                [
                    'SingleChoice',
                    'MultipleChoice'
                ],
                true
            )
        ) {
            $options = [];
        }

        $isSensitive =
            !empty($data['is_sensitive']
                ?? false);

        $consentKey =
            $this->cleanOptionalText(
                $data['consent_key']
                    ?? null,
                100
            );

        if ($isSensitive) {
            if ($consentKey === null) {
                throw new InvalidArgumentException(
                    'Sensitive questions require a consent definition.'
                );
            }

            if (
                !$this->management
                    ->hasConsentDefinition(
                        $consentKey
                    )
            ) {
                throw new InvalidArgumentException(
                    'The selected consent definition does not exist.'
                );
            }
        } else {
            $consentKey = null;
        }

        return [
            'question_key' =>
            $questionKey,

            'section_key' =>
            $sectionKey,

            'section_label' =>
            (string) $section['section_label'],

            'section_sort_order' =>
            (int) $section['section_sort_order'],

            'question_text' =>
            $this->cleanRequiredText(
                $data['question_text']
                    ?? '',
                'Question text',
                500
            ),

            'help_text' =>
            $this->cleanOptionalText(
                $data['help_text']
                    ?? null,
                500
            ),

            'response_type' =>
            $responseType,

            'options' =>
            $options,

            'is_required' =>
            !empty($data['is_required']
                ?? false)
                ? 1
                : 0,

            'is_sensitive' =>
            $isSensitive
                ? 1
                : 0,

            'consent_key' =>
            $consentKey,

            'analytics_enabled' =>
            !empty($data['analytics_enabled']
                ?? false)
                ? 1
                : 0,

            'sort_order' =>
            max(
                0,
                min(
                    9999,
                    (int) (
                        $data['sort_order']
                        ?? 0
                    )
                )
            )
        ];
    }

    private function normalizeOptions(
        mixed $options
    ): array {
        if (is_string($options)) {
            $options =
                preg_split(
                    '/\r\n|\r|\n/',
                    $options
                );
        }

        if (!is_array($options)) {
            return [];
        }

        $cleanOptions = [];

        foreach ($options as $option) {
            $option =
                $this->cleanText(
                    $option
                );

            if ($option === '') {
                continue;
            }

            if (mb_strlen($option) > 150) {
                throw new InvalidArgumentException(
                    'Each response option must not exceed 150 characters.'
                );
            }

            $cleanOptions[$option] =
                $option;
        }

        $cleanOptions =
            array_values(
                $cleanOptions
            );

        if (count($cleanOptions) > 30) {
            throw new InvalidArgumentException(
                'A question may contain no more than 30 response options.'
            );
        }

        return $cleanOptions;
    }

    private function validateAdministrator(
        int $adminId
    ): void {
        if ($adminId <= 0) {
            throw new RuntimeException(
                'A valid Administrator is required.'
            );
        }
    }

    /* ==========================================
       INPUT HELPERS
    ========================================== */

    private function cleanRequiredText(
        mixed $value,
        string $label,
        int $maximumLength
    ): string {
        $cleanValue =
            $this->cleanText(
                $value
            );

        if ($cleanValue === '') {
            throw new InvalidArgumentException(
                "{$label} is required."
            );
        }

        if (
            mb_strlen($cleanValue) >
            $maximumLength
        ) {
            throw new InvalidArgumentException(
                "{$label} must not exceed {$maximumLength} characters."
            );
        }

        return $cleanValue;
    }

    private function cleanOptionalText(
        mixed $value,
        int $maximumLength
    ): ?string {
        $cleanValue =
            $this->cleanText(
                $value
            );

        if ($cleanValue === '') {
            return null;
        }

        if (
            mb_strlen($cleanValue) >
            $maximumLength
        ) {
            throw new InvalidArgumentException(
                "Description must not exceed {$maximumLength} characters."
            );
        }

        return $cleanValue;
    }

    private function cleanText(
        mixed $value
    ): string {
        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string) $value
            )
        );
    }
}
