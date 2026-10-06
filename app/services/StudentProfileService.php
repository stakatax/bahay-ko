<?php

require_once __DIR__
    . '/../models/StudentProfile.php';

class StudentProfileService
{
    private StudentProfile $profile;



    public function __construct()
    {
        $this->profile =
            new StudentProfile();
    }

    /* ==========================================
       PROFILE PAGE DATA
    ========================================== */

    public function getProfileData(
        int $userId
    ): array {
        $this->validateStudent(
            $userId
        );

        return [
            'interests' =>
            $this->profile
                ->getDisplayInterests(),

            'profile' =>
            $this->profile
                ->findByUserId(
                    $userId
                )
        ];
    }


    /* ==========================================
       EXPANDED PROFILE SURVEY DATA
    ========================================== */

    public function getSurveyData(
        int $userId
    ): array {
        $this->validateStudent(
            $userId
        );

        $profile =
            $this->profile
            ->ensureProfile(
                $userId
            );

        $assignment =
            $this->profile
            ->getCurrentSurveyAssignment(
                $userId
            );

        $surveyVersion =
            (int) (
                $assignment['survey_version']
                ?? 0
            );

        if ($surveyVersion <= 0) {
            throw new RuntimeException(
                'The assigned Student profile survey version is unavailable.'
            );
        }

        $studentProfileId =
            (int) (
                $profile['student_profile_id']
                ?? 0
            );

        $questions =
            $this->profile
            ->getActiveSurveyQuestions(
                $surveyVersion
            );

        $consentDefinitions =
            $this->profile
            ->getActiveSurveyConsentDefinitions();

        $responses =
            $this->profile
            ->getSurveyResponses(
                $studentProfileId,
                $surveyVersion
            );

        $consents =
            $this->profile
            ->getSurveyConsents(
                $studentProfileId
            );

        $sections = [];

        $requiredQuestionCount = 0;
        $answeredRequiredCount = 0;

        foreach ($questions as $question) {
            $sectionKey =
                (string) (
                    $question['section_key']
                    ?? ''
                );

            if ($sectionKey === '') {
                continue;
            }

            if (!isset($sections[$sectionKey])) {
                $sections[$sectionKey] = [
                    'section_key' =>
                    $sectionKey,

                    'section_label' =>
                    (string) (
                        $question['section_label']
                        ?? $sectionKey
                    ),

                    'section_sort_order' =>
                    (int) (
                        $question['section_sort_order']
                        ?? 0
                    ),

                    'questions' =>
                    []
                ];
            }

            $questionKey =
                (string) (
                    $question['question_key']
                    ?? ''
                );

            $response =
                $responses[$questionKey]
                ?? null;

            $question['response'] =
                $response;

            $consentKey =
                trim(
                    (string) (
                        $question['consent_key']
                        ?? ''
                    )
                );

            if ($consentKey !== '') {
                $definition =
                    $consentDefinitions[$consentKey]
                    ?? null;

                $savedConsent =
                    $consents[$consentKey]
                    ?? null;

                $currentConsentVersion =
                    (string) (
                        $definition['consent_version']
                        ?? ''
                    );

                $question['consent_definition'] =
                    $definition;

                $question['consent_granted'] =
                    $definition !== null &&
                    $savedConsent !== null &&
                    !empty($savedConsent['consent_granted']) &&
                    (
                        $savedConsent['consent_version']
                        ?? ''
                    ) ===
                    $currentConsentVersion;
            } else {
                $question['consent_definition'] =
                    null;

                $question['consent_granted'] =
                    true;
            }

            if (!empty($question['is_required'])) {
                $requiredQuestionCount++;

                $responseValue =
                    $response['value']
                    ?? null;

                $hasResponse =
                    !(
                        $responseValue === null ||
                        $responseValue === '' ||
                        $responseValue === []
                    );

                if ($hasResponse) {
                    $answeredRequiredCount++;
                }
            }

            $sections[$sectionKey]['questions'][] =
                $question;
        }

        $sections =
            array_values(
                $sections
            );

        usort(
            $sections,
            static fn(
                array $first,
                array $second
            ): int => (
                $first['section_sort_order']
                ?? 0
            )
                <=>
                (
                    $second['section_sort_order']
                    ?? 0
                )
        );

        $progressPercentage =
            $requiredQuestionCount > 0
            ? (int) round(
                (
                    $answeredRequiredCount /
                    $requiredQuestionCount
                ) * 100
            )
            : 100;

        $currentStep =
            trim(
                (string) (
                    $profile['survey_current_step']
                    ?? ''
                )
            );

        if (
            $currentStep === '' &&
            !empty($sections)
        ) {
            $currentStep =
                (string) (
                    $sections[0]['section_key']
                    ?? ''
                );
        }

        return [
            'profile' =>
            $profile,

            'sections' =>
            $sections,

            'responses' =>
            $responses,

            'consent_definitions' =>
            $consentDefinitions,

            'consents' =>
            $consents,

            'survey_version' =>
            $surveyVersion,

            'cycle_assignment' =>
            $assignment,

            'cycle_name' =>
            (string) (
                $assignment['cycle_name']
                ?? ''
            ),

            'cycle_due_at' =>
            $assignment['due_at']
                ?? null,

            'current_step' =>
            $currentStep,

            'required_question_count' =>
            $requiredQuestionCount,

            'answered_required_count' =>
            $answeredRequiredCount,

            'progress_percentage' =>
            $progressPercentage
        ];
    }


    /* ==========================================
       SURVEY COMPLETION REQUIREMENT
    ========================================== */

    public function requiresSurveyCompletion(
        int $userId
    ): bool {
        $this->validateStudent(
            $userId
        );

        $assignment =
            $this->profile
            ->getCurrentSurveyAssignment(
                $userId
            );

        return !in_array(
            (
                $assignment['assignment_status']
                ?? 'Assigned'
            ),
            [
                'Completed',
                'Exempt'
            ],
            true
        );
    }

    /* ==========================================
       SAVE EXPANDED SURVEY
    ========================================== */

    public function saveSurvey(
        int $userId,
        array $submittedResponses,
        array $submittedConsents,
        string $currentStep,
        bool $complete = false
    ): array {
        $this->validateStudent(
            $userId
        );

        $surveyData =
            $this->getSurveyData(
                $userId
            );

        $surveyVersion =
            (int) (
                $surveyData['survey_version']
                ?? 0
            );

        $assignment =
            $surveyData['cycle_assignment']
            ?? [];

        $cycleAssignmentId =
            (int) (
                $assignment['student_profile_cycle_assignment_id']
                ?? 0
            );

        if (
            $surveyVersion <= 0 ||
            $cycleAssignmentId <= 0
        ) {
            throw new RuntimeException(
                'The Student profile survey assignment is unavailable.'
            );
        }

        $questions = [];

        $validSections = [];

        foreach (
            $surveyData['sections']
                ?? []
            as $section
        ) {
            $sectionKey =
                (string) (
                    $section['section_key']
                    ?? ''
                );

            if ($sectionKey !== '') {
                $validSections[$sectionKey] =
                    true;
            }

            foreach (
                $section['questions']
                    ?? []
                as $question
            ) {
                $questionKey =
                    (string) (
                        $question['question_key']
                        ?? ''
                    );

                if ($questionKey !== '') {
                    $questions[$questionKey] =
                        $question;
                }
            }
        }

        if ($questions === []) {
            throw new RuntimeException(
                'No active Student profile survey questions are configured.'
            );
        }

        $currentStep =
            trim($currentStep);

        if (
            $currentStep === '' ||
            !isset($validSections[$currentStep])
        ) {
            $currentStep =
                (string) array_key_first(
                    $validSections
                );
        }

        $consentDefinitions =
            $surveyData['consent_definitions']
            ?? [];

        $savedConsents =
            $surveyData['consents']
            ?? [];

        $consentStates = [];

        $consentRecords = [];

        $profileWasCompleted =
            (
                $surveyData['profile']['survey_completion_status']
                ?? ''
            ) === 'Completed';

        $acceptanceSource =
            $profileWasCompleted
            ? 'ProfileUpdate'
            : 'Survey';

        foreach (
            $consentDefinitions
            as $consentKey => $definition
        ) {
            $consentKey =
                trim(
                    (string) $consentKey
                );

            if ($consentKey === '') {
                continue;
            }

            $consentVersion =
                trim(
                    (string) (
                        $definition['consent_version']
                        ?? ''
                    )
                );

            if ($consentVersion === '') {
                continue;
            }

            if (
                array_key_exists(
                    $consentKey,
                    $submittedConsents
                )
            ) {
                $consentGranted =
                    !empty($submittedConsents[$consentKey]);
            } else {
                $savedConsent =
                    $savedConsents[$consentKey]
                    ?? null;

                $consentGranted =
                    $savedConsent !== null &&
                    !empty($savedConsent['consent_granted']) &&
                    (
                        $savedConsent['consent_version']
                        ?? ''
                    ) === $consentVersion;
            }

            $consentStates[$consentKey] =
                $consentGranted;

            $consentRecords[] = [
                'consent_key' =>
                $consentKey,

                'consent_version' =>
                $consentVersion,

                'consent_granted' =>
                $consentGranted,

                'acceptance_source' =>
                $acceptanceSource
            ];
        }

        $existingResponses =
            $surveyData['responses']
            ?? [];

        $effectiveResponses = [];

        foreach (
            $existingResponses
            as $questionKey => $response
        ) {
            $effectiveResponses[$questionKey] =
                $response['value']
                ?? null;
        }

        $responsesToSave = [];

        $clearedQuestionIds = [];

        foreach (
            $questions
            as $questionKey => $question
        ) {
            $questionId =
                (int) (
                    $question['student_profile_question_id']
                    ?? 0
                );

            if ($questionId <= 0) {
                continue;
            }

            $isSensitive =
                !empty($question['is_sensitive']);

            $consentKey =
                trim(
                    (string) (
                        $question['consent_key']
                        ?? ''
                    )
                );

            $consentGranted =
                !$isSensitive ||
                (
                    $consentKey !== '' &&
                    !empty($consentStates[$consentKey])
                );

            if (!$consentGranted) {
                if (
                    array_key_exists(
                        $questionKey,
                        $effectiveResponses
                    ) ||
                    array_key_exists(
                        $questionKey,
                        $submittedResponses
                    )
                ) {
                    $clearedQuestionIds[] =
                        $questionId;
                }

                unset(
                    $effectiveResponses[$questionKey]
                );

                continue;
            }

            if (
                !array_key_exists(
                    $questionKey,
                    $submittedResponses
                )
            ) {
                continue;
            }

            $normalizedResponse =
                $this->normalizeSurveyResponse(
                    $question,
                    $submittedResponses[$questionKey]
                );

            if (
                !$this->hasSurveyResponse(
                    $normalizedResponse
                )
            ) {
                $clearedQuestionIds[] =
                    $questionId;

                unset(
                    $effectiveResponses[$questionKey]
                );

                continue;
            }

            $responsesToSave[$questionId] =
                $normalizedResponse;

            $effectiveResponses[$questionKey] =
                $normalizedResponse;
        }

        if ($complete) {
            $missingQuestions = [];

            foreach (
                $questions
                as $questionKey => $question
            ) {
                if (
                    empty($question['is_required'])
                ) {
                    continue;
                }

                $responseValue =
                    $effectiveResponses[$questionKey]
                    ?? null;

                if (
                    !$this->hasSurveyResponse(
                        $responseValue
                    )
                ) {
                    $missingQuestions[] =
                        (string) (
                            $question['question_text']
                            ?? $questionKey
                        );
                }
            }

            if ($missingQuestions !== []) {
                throw new InvalidArgumentException(
                    'Complete all required survey questions before submitting. Missing: '
                        . implode(
                            '; ',
                            array_slice(
                                $missingQuestions,
                                0,
                                3
                            )
                        )
                        . (
                            count($missingQuestions) > 3
                            ? '; and additional required questions.'
                            : '.'
                        )
                );
            }
        }

        $savedProfile =
            $this->profile
            ->saveSurveyProgress(
                $userId,
                $surveyVersion,
                $cycleAssignmentId,
                $responsesToSave,
                $consentRecords,
                $clearedQuestionIds,
                $currentStep,
                $complete
            );

        return [
            'profile' =>
            $savedProfile,

            'survey_version' =>
            $surveyVersion,

            'cycle_assignment_id' =>
            $cycleAssignmentId,

            'cycle_name' =>
            (string) (
                $assignment['cycle_name']
                ?? ''
            ),

            'completed' =>
            $complete,

            'saved_response_count' =>
            count($responsesToSave),

            'cleared_response_count' =>
            count(
                array_unique(
                    $clearedQuestionIds
                )
            ),

            'current_step' =>
            $complete
                ? null
                : $currentStep
        ];
    }

    /* ==========================================
       NORMALIZE SURVEY RESPONSE
    ========================================== */

    private function normalizeSurveyResponse(
        array $question,
        mixed $value
    ): mixed {
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

        if ($responseType === 'MultipleChoice') {
            if (!is_array($value)) {
                $value =
                    $value === null ||
                    $value === ''
                    ? []
                    : [$value];
            }

            $normalizedValues =
                array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                static fn(
                                    mixed $item
                                ): string =>
                                trim(
                                    (string) $item
                                ),
                                $value
                            ),
                            static fn(
                                string $item
                            ): bool =>
                            $item !== ''
                        )
                    )
                );

            foreach (
                $normalizedValues
                as $selectedValue
            ) {
                if (
                    !in_array(
                        $selectedValue,
                        $options,
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'An invalid survey option was selected.'
                    );
                }
            }

            $exclusiveValues = [
                'None',
                'Prefer not to say'
            ];

            if (
                count($normalizedValues) > 1 &&
                array_intersect(
                    $exclusiveValues,
                    $normalizedValues
                ) !== []
            ) {
                throw new InvalidArgumentException(
                    '“None” and “Prefer not to say” cannot be combined with other selections.'
                );
            }

            return $normalizedValues;
        }

        if (is_array($value)) {
            throw new InvalidArgumentException(
                'An invalid survey response was submitted.'
            );
        }

        $normalizedValue =
            trim(
                (string) $value
            );

        if ($responseType === 'SingleChoice') {
            if (
                $normalizedValue !== '' &&
                !in_array(
                    $normalizedValue,
                    $options,
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    'An invalid survey option was selected.'
                );
            }

            return $normalizedValue;
        }

        if ($responseType === 'ShortText') {
            if (
                mb_strlen(
                    $normalizedValue
                ) > 150
            ) {
                throw new InvalidArgumentException(
                    'A short survey response must not exceed 150 characters.'
                );
            }

            return $normalizedValue;
        }

        if ($responseType === 'LongText') {
            if (
                mb_strlen(
                    $normalizedValue
                ) > 1000
            ) {
                throw new InvalidArgumentException(
                    'A detailed survey response must not exceed 1,000 characters.'
                );
            }

            return $normalizedValue;
        }

        throw new InvalidArgumentException(
            'An unsupported Student survey response type was submitted.'
        );
    }

    private function hasSurveyResponse(
        mixed $value
    ): bool {
        if (is_array($value)) {
            return $value !== [];
        }

        return (
            $value !== null &&
            trim(
                (string) $value
            ) !== ''
        );
    }

    /* ==========================================
       SAVE PROFILE SURVEY
    ========================================== */

    public function saveProfile(
        int $userId,
        array $interestIds,
        array $interestWeights = []
    ): array {
        $this->validateStudent(
            $userId
        );

        $interestIds =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(
                                mixed $interestId
                            ): int =>
                            (int) $interestId,
                            $interestIds
                        ),
                        static fn(
                            int $interestId
                        ): bool =>
                        $interestId > 0
                    )
                )
            );

        if (count($interestIds) < 3) {
            throw new InvalidArgumentException(
                'Select at least three interests to personalize your feed.'
            );
        }

        $availableInterests =
            $this->profile
            ->getActiveInterests();

        $availableInterestIds = [];

        foreach (
            $availableInterests as $interest
        ) {
            $interestId =
                (int) (
                    $interest['interest_id']
                    ?? 0
                );

            if ($interestId > 0) {
                $availableInterestIds[$interestId] = true;
            }
        }

        $preferences = [];

        foreach (
            $interestIds as $interestId
        ) {
            if (
                !isset(
                    $availableInterestIds[$interestId]
                )
            ) {
                throw new InvalidArgumentException(
                    'One or more selected interests are inactive or unavailable.'
                );
            }

            $weight =
                (int) (
                    $interestWeights[$interestId]
                    ?? 3
                );

            if (
                $weight < 1 ||
                $weight > 5
            ) {
                throw new InvalidArgumentException(
                    'Interest preference levels must be between 1 and 5.'
                );
            }

            $preferences[] = [
                'interest_id' =>
                $interestId,

                'preference_weight' =>
                $weight
            ];
        }

        return $this->profile
            ->savePreferences(
                $userId,
                $preferences,
                true
            );
    }

    /* ==========================================
       STUDENT VALIDATION
    ========================================== */

    private function validateStudent(
        int $userId
    ): void {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'A valid authenticated Student is required.'
            );
        }

        if (
            !$this->profile
                ->isActiveStudent(
                    $userId
                )
        ) {
            throw new RuntimeException(
                'Only active Student accounts may manage a personalization profile.'
            );
        }
    }
}
