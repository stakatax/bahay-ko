<?php

require_once __DIR__
    . '/../models/StudentProfileCycle.php';

require_once __DIR__
    . '/../models/StudentProfileManagement.php';

require_once __DIR__
    . '/NotificationService.php';

class StudentProfileCycleService
{
    private StudentProfileCycle $cycles;

    private StudentProfileManagement $management;

    private NotificationService $notifications;

    public function __construct()
    {
        $this->cycles =
            new StudentProfileCycle();

        $this->management =
            new StudentProfileManagement();

        $this->notifications =
            new NotificationService();
    }

    public function createCycle(
        array $data,
        array $scopes,
        int $adminId
    ): array {
        $this->validateAdministrator(
            $adminId
        );

        $surveyVersion =
            (int) (
                $data['survey_version']
                ?? 0
            );

        $version =
            $this->management
            ->findSurveyVersion(
                $surveyVersion
            );

        if (!$version) {
            throw new RuntimeException(
                'The selected questionnaire version could not be found.'
            );
        }

        if (
            !in_array(
                $version['status']
                    ?? '',
                [
                    'Draft',
                    'Active'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'A retired questionnaire version cannot be assigned.'
            );
        }

        if (
            (int) (
                $version['active_question_count']
                ?? 0
            ) <= 0
        ) {
            throw new RuntimeException(
                'The selected questionnaire has no active questions.'
            );
        }

        $cycleTypes = [
            'Initial',
            'SchoolYear',
            'Semester',
            'Custom'
        ];

        $academicTerms = [
            'NotApplicable',
            'FirstSemester',
            'SecondSemester',
            'Summer',
            'Custom'
        ];

        $cycleType =
            trim(
                (string) (
                    $data['cycle_type']
                    ?? ''
                )
            );

        $academicTerm =
            trim(
                (string) (
                    $data['academic_term']
                    ?? ''
                )
            );

        if (
            !in_array(
                $cycleType,
                $cycleTypes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Select a valid Student profile cycle type.'
            );
        }

        if (
            !in_array(
                $academicTerm,
                $academicTerms,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Select a valid academic term.'
            );
        }

        $opensAt =
            $this->normalizeDateTime(
                $data['opens_at']
                    ?? null,
                'Opening date'
            );

        $dueAt =
            $this->normalizeDateTime(
                $data['due_at']
                    ?? null,
                'Due date'
            );

        if (
            $opensAt !== null &&
            $dueAt !== null &&
            strtotime($dueAt) <=
            strtotime($opensAt)
        ) {
            throw new InvalidArgumentException(
                'The due date must be later than the opening date.'
            );
        }

        $cleanScopes =
            $this->normalizeScopes(
                $scopes
            );

        return $this->cycles
            ->createDraftCycle(
                [
                    'cycle_name' =>
                    $this->cleanRequiredText(
                        $data['cycle_name']
                            ?? '',
                        'Cycle name',
                        150
                    ),

                    'academic_year' =>
                    $this->cleanOptionalText(
                        $data['academic_year']
                            ?? null,
                        30
                    ),

                    'cycle_type' =>
                    $cycleType,

                    'academic_term' =>
                    $academicTerm,

                    'survey_version' =>
                    $surveyVersion,

                    'opens_at' =>
                    $opensAt,

                    'due_at' =>
                    $dueAt
                ],
                $cleanScopes,
                $adminId
            );
    }

    public function previewCycle(
        int $cycleId
    ): array {
        $cycle =
            $this->cycles
            ->findCycle(
                $cycleId
            );

        if (!$cycle) {
            throw new RuntimeException(
                'The Student profile cycle could not be found.'
            );
        }

        $targets =
            $this->cycles
            ->previewCycleTargets(
                $cycleId
            );

        return [
            'cycle' =>
            $cycle,

            'scopes' =>
            $this->cycles
                ->getCycleScopes(
                    $cycleId
                ),

            'targets' =>
            $targets,

            'target_count' =>
            count($targets)
        ];
    }

    public function activateCycle(
        int $cycleId,
        int $adminId
    ): array {
        $this->validateAdministrator(
            $adminId
        );

        $cycle = $this->cycles->activateCycle($cycleId, $adminId);
        // Read committed assignments, never the earlier recipient preview.
        // Missing rows remain recoverable by the notification worker after failures.
        try {
            $notificationResult = $this->notifications->recoverStudentProfileNotifications(100, $cycleId);
        } catch (Throwable $exception) {
            error_log('Profile reminder creation deferred for cycle #' . $cycleId
                . ' (' . get_class($exception) . ', code ' . (int) $exception->getCode() . ').');
            $notificationResult = ['eligible'=>0, 'created'=>0, 'duplicates'=>0, 'failed'=>1];
        }

        $cycle['notifications'] =
            $notificationResult;

        return $cycle;
    }

    public function getMonitoringDirectory(
        ?int $selectedCycleId = null
    ): array {
        $cycles =
            $this->cycles
            ->getCycles();

        if (
            $selectedCycleId === null ||
            $selectedCycleId <= 0
        ) {
            foreach ($cycles as $cycle) {
                if (
                    ($cycle['status'] ?? '') ===
                    'Active' &&
                    empty($cycle['is_default'])
                ) {
                    $selectedCycleId =
                        (int) $cycle['student_profile_cycle_id'];

                    break;
                }
            }
        }

        if (
            (
                $selectedCycleId === null ||
                $selectedCycleId <= 0
            ) &&
            $cycles !== []
        ) {
            $selectedCycleId =
                (int) (
                    $cycles[0]['student_profile_cycle_id']
                    ?? 0
                );
        }

        $selectedCycle = null;
        $scopes = [];
        $assignments = [];

        if (
            $selectedCycleId !== null &&
            $selectedCycleId > 0
        ) {
            $selectedCycle =
                $this->cycles
                ->findCycle(
                    $selectedCycleId
                );

            if (!$selectedCycle) {
                throw new RuntimeException(
                    'The selected Student profile cycle could not be found.'
                );
            }

            $scopes =
                $this->cycles
                ->getCycleScopes(
                    $selectedCycleId
                );

            $assignments =
                $this->cycles
                ->getCycleAssignments(
                    $selectedCycleId
                );
        }

        return [
            'cycles' =>
            $cycles,

            'selected_cycle' =>
            $selectedCycle,

            'selected_cycle_id' =>
            $selectedCycleId,

            'scopes' =>
            $scopes,

            'assignments' =>
            $assignments,

            'counts' => [
                'cycles' =>
                count($cycles),

                'assignments' =>
                count($assignments),

                'assigned' =>
                count(
                    array_filter(
                        $assignments,
                        static fn(
                            array $assignment
                        ): bool => (
                            $assignment['assignment_status']
                            ?? ''
                        ) === 'Assigned'
                    )
                ),

                'in_progress' =>
                count(
                    array_filter(
                        $assignments,
                        static fn(
                            array $assignment
                        ): bool => (
                            $assignment['assignment_status']
                            ?? ''
                        ) === 'InProgress'
                    )
                ),

                'completed' =>
                count(
                    array_filter(
                        $assignments,
                        static fn(
                            array $assignment
                        ): bool => (
                            $assignment['assignment_status']
                            ?? ''
                        ) === 'Completed'
                    )
                ),

                'exempt' =>
                count(
                    array_filter(
                        $assignments,
                        static fn(
                            array $assignment
                        ): bool => (
                            $assignment['assignment_status']
                            ?? ''
                        ) === 'Exempt'
                    )
                )
            ]
        ];
    }

    public function closeCycle(
        int $cycleId,
        int $adminId
    ): array {
        $this->validateAdministrator(
            $adminId
        );

        return $this->cycles
            ->closeCycle(
                $cycleId,
                $adminId
            );
    }

    private function normalizeScopes(
        array $scopes
    ): array {
        if ($scopes === []) {
            throw new InvalidArgumentException(
                'Select at least one Student cycle scope.'
            );
        }

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

        $cleanScopes = [];

        foreach ($scopes as $scope) {
            if (!is_array($scope)) {
                continue;
            }

            $scopeType =
                trim(
                    (string) (
                        $scope['scope_type']
                        ?? ''
                    )
                );

            if ($scopeType === 'AllStudents') {
                $cleanScopes['AllStudents'] = [
                    'scope_type' =>
                    'AllStudents',
                    'department_id' => null,
                    'education_level_id' => null,
                    'academic_program_id' => null,
                    'grade_level_id' => null,
                    'section_id' => null
                ];

                continue;
            }

            $field =
                $scopeFieldMap[$scopeType]
                ?? null;

            if ($field === null) {
                throw new InvalidArgumentException(
                    'Select a supported Student cycle scope.'
                );
            }

            $scopeId =
                (int) (
                    $scope[$field]
                    ?? 0
                );

            if ($scopeId <= 0) {
                throw new InvalidArgumentException(
                    "Select a valid {$scopeType} scope."
                );
            }

            $cleanScope = [
                'scope_type' =>
                $scopeType,
                'department_id' => null,
                'education_level_id' => null,
                'academic_program_id' => null,
                'grade_level_id' => null,
                'section_id' => null
            ];

            $cleanScope[$field] =
                $scopeId;

            $cleanScopes[$scopeType . ':' . $scopeId] = $cleanScope;
        }

        if ($cleanScopes === []) {
            throw new InvalidArgumentException(
                'Select at least one valid Student cycle scope.'
            );
        }

        if (isset($cleanScopes['AllStudents'])) {
            return [
                $cleanScopes['AllStudents']
            ];
        }

        return array_values(
            $cleanScopes
        );
    }

    private function normalizeDateTime(
        mixed $value,
        string $label
    ): ?string {
        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return null;
        }

        try {
            $date =
                new DateTimeImmutable(
                    $value
                );
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                "{$label} is invalid."
            );
        }

        return $date->format(
            'Y-m-d H:i:s'
        );
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

    private function cleanRequiredText(
        mixed $value,
        string $label,
        int $maximumLength
    ): string {
        $value =
            $this->cleanText(
                $value
            );

        if ($value === '') {
            throw new InvalidArgumentException(
                "{$label} is required."
            );
        }

        if (mb_strlen($value) > $maximumLength) {
            throw new InvalidArgumentException(
                "{$label} must not exceed {$maximumLength} characters."
            );
        }

        return $value;
    }

    private function cleanOptionalText(
        mixed $value,
        int $maximumLength
    ): ?string {
        $value =
            $this->cleanText(
                $value
            );

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $maximumLength) {
            throw new InvalidArgumentException(
                "The value must not exceed {$maximumLength} characters."
            );
        }

        return $value;
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
