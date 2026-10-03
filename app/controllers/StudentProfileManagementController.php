<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/StudentProfileManagementService.php';

require_once __DIR__
    . '/../services/StudentProfileCycleService.php';

require_once __DIR__
    . '/../services/AcademicManagementService.php';

class StudentProfileManagementController extends BaseController
{
    private StudentProfileManagementService $questionnaires;

    private StudentProfileCycleService $cycles;

    private AcademicManagementService $academics;

    public function __construct()
    {
        $this->questionnaires =
            new StudentProfileManagementService();

        $this->cycles =
            new StudentProfileCycleService();

        $this->academics =
            new AcademicManagementService();
    }

    /* ==========================================
       ADMINISTRATOR PAGE
    ========================================== */

    public function index(): array
    {
        $this->requireRole(
            'Admin'
        );

        $flash =
            $_SESSION['student_profile_management_flash']
            ?? null;

        unset(
            $_SESSION['student_profile_management_flash']
        );

        $selectedVersion =
            max(
                0,
                (int) (
                    $_GET['survey_version']
                    ?? 0
                )
            );

        $selectedCycleId =
            max(
                0,
                (int) (
                    $_GET['cycle_id']
                    ?? 0
                )
            );

        try {
            $questionnaireDirectory =
                $this->questionnaires
                ->getDirectory(
                    $selectedVersion > 0
                        ? $selectedVersion
                        : null
                );

            $cycleDirectory =
                $this->cycles
                ->getMonitoringDirectory(
                    $selectedCycleId > 0
                        ? $selectedCycleId
                        : null
                );

            $academicDirectory =
                $this->academics
                ->getDirectory();

            $cyclePreview = null;

            if (
                ($cycleDirectory['selected_cycle']['status'] ?? '') ===
                'Draft'
            ) {
                $cyclePreview =
                    $this->cycles
                    ->previewCycle(
                        (int) $cycleDirectory['selected_cycle_id']
                    );
            }

            return [
                'questionnaires' =>
                $questionnaireDirectory,

                'cycle_monitoring' =>
                $cycleDirectory,

                'academic_directory' =>
                $academicDirectory,

                'cycle_preview' =>
                $cyclePreview,

                'flash' =>
                $flash
            ];
        } catch (Throwable $exception) {
            error_log(
                'Student Profile Management page error: '
                    . publicErrorMessage($exception)
            );

            return [
                'questionnaires' => [
                    'survey_versions' => [],
                    'selected_survey_version' => null,
                    'questions' => [],
                    'sections' => [],
                    'consent_definitions' => [],

                    'counts' => [
                        'versions' => 0,
                        'draft_versions' => 0,
                        'questions' => 0
                    ]
                ],

                'cycle_monitoring' => [
                    'cycles' => [],
                    'selected_cycle' => null,
                    'selected_cycle_id' => 0,
                    'scopes' => [],
                    'assignments' => [],

                    'counts' => [
                        'cycles' => 0,
                        'assignments' => 0,
                        'assigned' => 0,
                        'in_progress' => 0,
                        'completed' => 0,
                        'exempt' => 0
                    ]
                ],

                'academic_directory' => [
                    'departments' => [],
                    'education_levels' => [],
                    'academic_programs' => [],
                    'grade_levels' => [],
                    'sections' => []
                ],

                'cycle_preview' => null,

                'flash' => [
                    'type' =>
                    'error',

                    'message' =>
                    'Student Profile Management could not be loaded.'
                ]
            ];
        }
    }

    /* ==========================================
       CREATE QUESTIONNAIRE DRAFT
    ========================================== */

    public function createDraftVersion(): void
    {
        $this->requireRole(
            'Admin'
        );

        $sourceVersion =
            max(
                0,
                (int) (
                    $_POST['source_version']
                    ?? 0
                )
            );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $draft =
                $this->questionnaires
                ->createDraftVersion(
                    $sourceVersion,
                    (string) (
                        $_POST['version_name']
                        ?? ''
                    ),
                    isset($_POST['description'])
                        ? (string) $_POST['description']
                        : null,
                    $adminId
                );

            $draftVersion =
                (int) (
                    $draft['survey_version']
                    ?? 0
                );

            try {
                $this->log(
                    'CREATE_STUDENT_PROFILE_QUESTIONNAIRE_DRAFT',
                    'Created Student profile questionnaire draft version '
                        . $draftVersion
                        . ': '
                        . (
                            $draft['version_name']
                            ?? 'Unnamed questionnaire'
                        )
                        . '.'
                );
            } catch (Throwable $loggingException) {
                /*
                 * Draft creation remains successful
                 * if general activity logging fails.
                 */
            }

            $this->setFlash(
                'success',
                'Questionnaire draft version '
                    . $draftVersion
                    . ' created successfully.'
            );

            $this->redirect(
                $this->buildRedirect(
                    $draftVersion,
                    0
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception),
                [
                    'source_version' =>
                    $sourceVersion,

                    'version_name' =>
                    trim(
                        (string) (
                            $_POST['version_name']
                            ?? ''
                        )
                    ),

                    'description' =>
                    trim(
                        (string) (
                            $_POST['description']
                            ?? ''
                        )
                    )
                ],
                'create_draft'
            );

            $this->redirect(
                $this->buildRedirect(
                    $sourceVersion,
                    0
                )
            );
        }
    }

    /* ==========================================
       CREATE QUESTION
    ========================================== */

    public function createQuestion(): void
    {
        $this->requireRole(
            'Admin'
        );

        $surveyVersion =
            max(
                0,
                (int) (
                    $_POST['survey_version']
                    ?? 0
                )
            );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $question =
                $this->questionnaires
                ->createQuestion(
                    $surveyVersion,
                    $this->questionInput(
                        $_POST
                    ),
                    $adminId
                );

            try {
                $this->log(
                    'CREATE_STUDENT_PROFILE_QUESTION',
                    'Created Student profile question '
                        . (
                            $question['question_key']
                            ?? 'unknown'
                        )
                        . ' in questionnaire version '
                        . $surveyVersion
                        . '.'
                );
            } catch (Throwable $loggingException) {
                // Question creation remains successful.
            }

            $this->setFlash(
                'success',
                'Question created successfully.'
            );

            $this->redirect(
                $this->buildRedirect(
                    $surveyVersion,
                    0
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception),
                $this->questionInput(
                    $_POST
                ),
                'create_question'
            );

            $this->redirect(
                $this->buildRedirect(
                    $surveyVersion,
                    0
                )
            );
        }
    }

    /* ==========================================
       UPDATE QUESTION
    ========================================== */

    public function updateQuestion(): void
    {
        $this->requireRole(
            'Admin'
        );

        $questionId =
            max(
                0,
                (int) (
                    $_POST['question_id']
                    ?? 0
                )
            );

        $surveyVersion =
            max(
                0,
                (int) (
                    $_POST['survey_version']
                    ?? 0
                )
            );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            /*
             * Explicitly normalize checkboxes because
             * unchecked HTML checkboxes are not submitted.
             */
            $questionData =
                $this->questionInput(
                    $_POST
                );

            $question =
                $this->questionnaires
                ->updateQuestion(
                    $questionId,
                    $questionData,
                    $adminId
                );

            $surveyVersion =
                (int) (
                    $question['survey_version']
                    ?? $surveyVersion
                );

            try {
                $this->log(
                    'UPDATE_STUDENT_PROFILE_QUESTION',
                    'Updated Student profile question '
                        . (
                            $question['question_key']
                            ?? $questionId
                        )
                        . ' in questionnaire version '
                        . $surveyVersion
                        . '.'
                );
            } catch (Throwable $loggingException) {
                // Question update remains successful.
            }

            $this->setFlash(
                'success',
                'Question updated successfully.'
            );

            $this->redirect(
                $this->buildRedirect(
                    $surveyVersion,
                    0
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception),
                $this->questionInput(
                    $_POST
                ),
                'edit_question'
            );

            $this->redirect(
                $this->buildRedirect(
                    $surveyVersion,
                    0
                )
            );
        }
    }

    /* ==========================================
       CHANGE QUESTION STATUS
    ========================================== */

    public function changeQuestionStatus(): void
    {
        $this->requireRole(
            'Admin'
        );

        $questionId =
            max(
                0,
                (int) (
                    $_POST['question_id']
                    ?? 0
                )
            );

        $surveyVersion =
            max(
                0,
                (int) (
                    $_POST['survey_version']
                    ?? 0
                )
            );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            if (
                (
                    $_POST['confirm_status_change']
                    ?? ''
                ) !== '1'
            ) {
                throw new RuntimeException(
                    'Confirm the question status change before continuing.'
                );
            }

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $question =
                $this->questionnaires
                ->changeQuestionStatus(
                    $questionId,
                    (string) (
                        $_POST['new_status']
                        ?? ''
                    ),
                    $adminId
                );

            $surveyVersion =
                (int) (
                    $question['survey_version']
                    ?? $surveyVersion
                );

            $newStatus =
                (string) (
                    $question['status']
                    ?? ''
                );

            try {
                $this->log(
                    'CHANGE_STUDENT_PROFILE_QUESTION_STATUS',
                    'Changed Student profile question '
                        . (
                            $question['question_key']
                            ?? $questionId
                        )
                        . ' to '
                        . $newStatus
                        . ' in questionnaire version '
                        . $surveyVersion
                        . '.'
                );
            } catch (Throwable $loggingException) {
                // Status change remains successful.
            }

            $this->setFlash(
                'success',
                'Question marked as '
                    . $newStatus
                    . '.'
            );

            $this->redirect(
                $this->buildRedirect(
                    $surveyVersion,
                    0
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception)
            );

            $this->redirect(
                $this->buildRedirect(
                    $surveyVersion,
                    0
                )
            );
        }
    }

    /* ==========================================
       CREATE STUDENT PROFILE CYCLE
    ========================================== */

    public function createCycle(): void
    {
        $this->requireRole(
            'Admin'
        );

        $surveyVersion =
            max(
                0,
                (int) (
                    $_POST['survey_version']
                    ?? 0
                )
            );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            $cycleData =
                $this->cycleInput(
                    $_POST
                );

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $scopeType =
                trim(
                    (string) (
                        $cycleData['scope_type']
                        ?? ''
                    )
                );

            $scopeId =
                max(
                    0,
                    (int) (
                        $cycleData['scope_id']
                        ?? 0
                    )
                );

            $scopeFieldMap = [
                'Department' =>
                'department_id',

                'EducationLevel' =>
                'education_level_id',

                'AcademicProgram' =>
                'academic_program_id',

                'GradeLevel' =>
                'grade_level_id',

                'Section' =>
                'section_id'
            ];

            if ($scopeType === 'AllStudents') {
                $scopes = [
                    [
                        'scope_type' =>
                        'AllStudents'
                    ]
                ];
            } else {
                $scopeField =
                    $scopeFieldMap[$scopeType]
                    ?? null;

                if (
                    $scopeField === null ||
                    $scopeId <= 0
                ) {
                    throw new InvalidArgumentException(
                        'Select a valid Student cycle target.'
                    );
                }

                $scopes = [
                    [
                        'scope_type' =>
                        $scopeType,

                        $scopeField =>
                        $scopeId
                    ]
                ];
            }

            $cycle =
                $this->cycles
                ->createCycle(
                    $cycleData,
                    $scopes,
                    $adminId
                );

            $cycleId =
                (int) (
                    $cycle['student_profile_cycle_id']
                    ?? 0
                );

            try {
                $this->log(
                    'CREATE_STUDENT_PROFILE_CYCLE',
                    'Created Student profile cycle draft #'
                        . $cycleId
                        . ': '
                        . (
                            $cycle['cycle_name']
                            ?? 'Unnamed cycle'
                        )
                        . '.'
                );
            } catch (Throwable $loggingException) {
                // Cycle creation remains successful.
            }

            $this->setFlash(
                'success',
                'Student profile cycle draft created successfully.'
            );

            $this->redirect(
                $this->buildRedirect(
                    $surveyVersion,
                    $cycleId,
                    true
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception),
                $this->cycleInput(
                    $_POST
                ),
                'create_cycle'
            );

            $this->redirect(
                $this->buildRedirect(
                    $surveyVersion,
                    0
                )
            );
        }
    }

    public function activateCycle(): void
    {
        $this->requireRole(
            'Admin'
        );

        $cycleId =
            max(
                0,
                (int) (
                    $_POST['cycle_id']
                    ?? 0
                )
            );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            if (
                ($_POST['confirmed'] ?? '') !==
                '1'
            ) {
                throw new RuntimeException(
                    'Confirm cycle activation before continuing.'
                );
            }

            $cycle =
                $this->cycles
                ->activateCycle(
                    $cycleId,
                    (int) (
                        $_SESSION['user_id']
                        ?? 0
                    )
                );

            $notifications =
                $cycle['notifications']
                ?? [];

            $this->setFlash(
                'success',
                'Cycle activated for '
                    . (int) ($cycle['target_count'] ?? 0)
                    . ' Student(s). '
                    . (int) ($notifications['created'] ?? 0)
                    . ' notification(s) created. Any remaining reminders will be retried by the notification worker.'
            );

            $this->redirect(
                $this->buildRedirect(
                    (int) ($cycle['survey_version'] ?? 0),
                    $cycleId
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception)
            );

            $this->redirect(
                $this->buildRedirect(
                    0,
                    $cycleId,
                    true
                )
            );
        }
    }

    public function closeCycle(): void
    {
        $this->requireRole(
            'Admin'
        );

        $cycleId =
            max(
                0,
                (int) (
                    $_POST['cycle_id']
                    ?? 0
                )
            );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();

            if (
                ($_POST['confirmed'] ?? '') !==
                '1'
            ) {
                throw new RuntimeException(
                    'Confirm cycle closing before continuing.'
                );
            }

            $cycle =
                $this->cycles
                ->closeCycle(
                    $cycleId,
                    (int) (
                        $_SESSION['user_id']
                        ?? 0
                    )
                );

            $this->setFlash(
                'success',
                'Student profile cycle closed successfully.'
            );

            $this->redirect(
                $this->buildRedirect(
                    (int) ($cycle['survey_version'] ?? 0),
                    $cycleId
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception)
            );

            $this->redirect(
                $this->buildRedirect(
                    0,
                    $cycleId
                )
            );
        }
    }

    /* ==========================================
       FLASH AND REDIRECT
    ========================================== */

    private function setFlash(
        string $type,
        string $message,
        array $oldInput = [],
        ?string $openForm = null
    ): void {
        $_SESSION['student_profile_management_flash'] = [
            'type' =>
            $type,

            'message' =>
            $message,

            'old_input' =>
            $oldInput,

            'open_form' =>
            $openForm
        ];
    }

    private function buildRedirect(
        int $surveyVersion = 0,
        int $cycleId = 0,
        bool $previewCycle = false
    ): string {
        $query = [
            'page' =>
            'student_profile_management'
        ];

        if ($surveyVersion > 0) {
            $query['survey_version'] =
                $surveyVersion;
        }

        if ($cycleId > 0) {
            $query['cycle_id'] =
                $cycleId;
        }

        if ($previewCycle) {
            $query['preview_cycle'] =
                1;
        }

        return 'index.php?'
            . http_build_query(
                $query
            );
    }

    private function questionInput(
        array $input
    ): array {
        return [
            'section_key' =>
            trim((string) ($input['section_key'] ?? '')),

            'question_text' =>
            trim((string) ($input['question_text'] ?? '')),

            'help_text' =>
            trim((string) ($input['help_text'] ?? '')),

            'response_type' =>
            trim((string) ($input['response_type'] ?? '')),

            'options' =>
            is_array($input['options'] ?? null)
                ? $input['options']
                : [],

            'is_required' =>
            isset($input['is_required'])
                ? 1
                : 0,

            'is_sensitive' =>
            isset($input['is_sensitive'])
                ? 1
                : 0,

            'consent_key' =>
            trim((string) ($input['consent_key'] ?? '')),

            'analytics_enabled' =>
            isset($input['analytics_enabled'])
                ? 1
                : 0,

            'sort_order' =>
            max(0, (int) ($input['sort_order'] ?? 0))
        ];
    }

    private function cycleInput(
        array $input
    ): array {
        if (array_key_exists('update_period', $input)) {
            $periods = [
                'SchoolYear' => ['SchoolYear', 'NotApplicable'],
                'FirstSemester' => ['Semester', 'FirstSemester'],
                'SecondSemester' => ['Semester', 'SecondSemester'],
                'Summer' => ['Semester', 'Summer'],
                'Custom' => ['Custom', 'Custom']
            ];
            $period = is_string($input['update_period']) ? $input['update_period'] : '';
            // Unknown choices remain invalid for the service's existing validation.
            [$input['cycle_type'], $input['academic_term']] = $periods[$period] ?? ['', ''];
        }
        return [
            'cycle_name' =>
            trim((string) ($input['cycle_name'] ?? '')),

            'academic_year' =>
            trim((string) ($input['academic_year'] ?? '')),

            'cycle_type' =>
            trim((string) ($input['cycle_type'] ?? '')),

            'academic_term' =>
            trim((string) ($input['academic_term'] ?? '')),

            'survey_version' =>
            max(0, (int) ($input['survey_version'] ?? 0)),

            'opens_at' =>
            trim((string) ($input['opens_at'] ?? '')),

            'due_at' =>
            trim((string) ($input['due_at'] ?? '')),

            'scope_type' =>
            trim((string) ($input['scope_type'] ?? '')),

            'scope_id' =>
            max(0, (int) ($input['scope_id'] ?? 0))
        ];
    }
}
