<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/StudentProfileService.php';

class StudentProfileController extends BaseController
{
    private StudentProfileService $service;

    public function __construct()
    {
        $this->service =
            new StudentProfileService();
    }

    /* ==========================================
       STUDENT PROFILE SURVEY PAGE
    ========================================== */

    public function index(): array
    {
        $this->requireRole(
            'Student'
        );

        $userId =
            (int) (
                $_SESSION['user_id']
                ?? 0
            );

        $profileData =
            $this->service
            ->getProfileData(
                $userId
            );

        $surveyData =
            $this->service
            ->getSurveyData(
                $userId
            );

        $flash =
            $_SESSION['student_profile_flash']
            ?? [];

        unset(
            $_SESSION['student_profile_flash']
        );

        return array_merge(
            $profileData,
            $surveyData,
            [
                'flash_type' =>
                (string) (
                    $flash['type']
                    ?? ''
                ),

                'flash_message' =>
                (string) (
                    $flash['message']
                    ?? ''
                ),

                'old_interest_ids' =>
                is_array(
                    $flash['interest_ids']
                        ?? null
                )
                    ? $flash['interest_ids']
                    : [],

                'old_survey_responses' =>
                is_array(
                    $flash['survey_responses']
                        ?? null
                )
                    ? $flash['survey_responses']
                    : [],

                'old_survey_consents' =>
                is_array(
                    $flash['survey_consents']
                        ?? null
                )
                    ? $flash['survey_consents']
                    : [],

                'flash_current_step' =>
                trim(
                    (string) (
                        $flash['current_step']
                        ?? ''
                    )
                )
            ]
        );
    }

    /* ==========================================
       SAVE STUDENT PROFILE SURVEY
    ========================================== */

    public function save(): void
    {
        $this->requireRole(
            'Student'
        );

        $interestIds =
            $_POST['interest_ids']
            ?? [];

        if (!is_array($interestIds)) {
            $interestIds = [];
        }

        $interestWeights =
            $_POST['interest_weights']
            ?? [];

        if (!is_array($interestWeights)) {
            $interestWeights = [];
        }

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $userId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $profile =
                $this->service
                ->saveProfile(
                    $userId,
                    $interestIds,
                    $interestWeights
                );

            try {
                $this->log(
                    'UPDATE_STUDENT_PROFILE',
                    'Updated Student personalization profile version '
                        . (
                            (int) (
                                $profile['profile_version']
                                ?? 1
                            )
                        )
                        . '.',
                    $userId
                );
            } catch (Throwable $loggingException) {
                /*
                 * A successful profile update remains
                 * successful if general logging fails.
                 */
            }

            $this->setFlash(
                'success',
                'Your interests were saved successfully.'
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception),
                $interestIds
            );
        }

        $this->redirect(
            'index.php?page=student_profile'
        );
    }


    /* ==========================================
       SAVE EXPANDED STUDENT SURVEY
    ========================================== */

    public function saveSurvey(): void
    {
        $this->requireRole(
            'Student'
        );

        $submittedResponses =
            $_POST['survey_responses']
            ?? [];

        if (!is_array($submittedResponses)) {
            $submittedResponses = [];
        }

        $submittedConsents =
            $_POST['survey_consents']
            ?? [];

        if (!is_array($submittedConsents)) {
            $submittedConsents = [];
        }

        $currentStep =
            trim(
                (string) (
                    $_POST['current_step']
                    ?? ''
                )
            );

        $complete =
            (
                $_POST['survey_action']
                ?? ''
            ) === 'complete';

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $userId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $result =
                $this->service
                ->saveSurvey(
                    $userId,
                    $submittedResponses,
                    $submittedConsents,
                    $currentStep,
                    $complete
                );

            $_SESSION['student_survey_required'] =
                $this->service
                ->requiresSurveyCompletion(
                    $userId
                );

            $surveyVersion =
                (int) (
                    $result['survey_version']
                    ?? 1
                );

            $cycleName =
                trim(
                    (string) (
                        $result['cycle_name']
                        ?? ''
                    )
                );

            try {
                $this->log(
                    $complete
                        ? 'COMPLETE_STUDENT_SURVEY'
                        : 'SAVE_STUDENT_SURVEY',

                    $complete
                        ? 'Completed Student profile survey version '
                        . $surveyVersion
                        . (
                            $cycleName !== ''
                            ? ' for cycle "'
                            . $cycleName
                            . '".'
                            : '.'
                        )
                        : 'Saved Student profile survey version '
                        . $surveyVersion
                        . (
                            $cycleName !== ''
                            ? ' progress for cycle "'
                            . $cycleName
                            . '".'
                            : ' progress.'
                        ),

                    $userId
                );
            } catch (Throwable $loggingException) {
                /*
                 * Survey saving remains successful
                 * if general activity logging fails.
                 */
            }

            $this->setSurveyFlash(
                'success',
                $complete
                    ? 'Your Student profile survey was completed successfully.'
                    : 'Your survey progress was saved successfully.',
                [],
                [],
                (string) (
                    $result['current_step']
                    ?? ''
                )
            );
        } catch (Throwable $exception) {
            $this->setSurveyFlash(
                'error',
                publicErrorMessage($exception),
                $submittedResponses,
                $submittedConsents,
                $currentStep
            );
        }

        $this->redirect(
            'index.php?page=student_profile_survey'
        );
    }

    private function setSurveyFlash(
        string $type,
        string $message,
        array $responses,
        array $consents,
        string $currentStep
    ): void {
        $_SESSION['student_profile_flash'] = [
            'type' =>
            $type,

            'message' =>
            $message,

            'survey_responses' =>
            $responses,

            'survey_consents' =>
            $consents,

            'current_step' =>
            trim($currentStep)
        ];
    }

    /* ==========================================
       FLASH SESSION
    ========================================== */

    private function setFlash(
        string $type,
        string $message,
        array $interestIds = []
    ): void {
        $_SESSION['student_profile_flash'] = [
            'type' =>
            $type,

            'message' =>
            $message,

            'interest_ids' =>
            array_values(
                array_unique(
                    array_map(
                        static fn(
                            mixed $interestId
                        ): int =>
                        (int) $interestId,
                        $interestIds
                    )
                )
            )
        ];
    }
}
