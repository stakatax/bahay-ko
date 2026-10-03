<?php

require_once __DIR__ . '/PublicationTransaction.php';
require_once __DIR__ . '/PublicationNotificationDispatcher.php';

require_once __DIR__ . '/FacultyScopeService.php';

require_once __DIR__
    . '/../models/Survey.php';

require_once __DIR__
    . '/../models/ContentInterest.php';

require_once __DIR__
    . '/NotificationService.php';
require_once __DIR__
    . '/ContentRedundancyService.php';

require_once __DIR__
    . '/../../config/logging.php';




class SurveyService
{
    private ?PublicationNotificationDispatcher $publicationDispatcher = null;
    private ?FacultyScopeService $facultyScope = null;
    private Survey $survey;
    private ContentInterest $contentInterest;

    private NotificationService $notifications;

    private ContentRedundancyService $redundancy;

    public function __construct(
        Survey $survey
    ) {
        $this->survey =
            $survey;

        $this->contentInterest =
            new ContentInterest();

        $this->notifications =
            new NotificationService();

        $this->redundancy =
            new ContentRedundancyService();
    }

    /* ==========================================
       CREATE SURVEY
    ========================================== */

    public function createSurvey(
        array $data,
        int $userId
    ): int {
        $targets = $this->buildTargets($data);

        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'A valid authenticated user is required.'
            );
        }


        $data['post_type'] =
            'survey';

        $redundancyAssessment =
            $this->redundancy
            ->enforceSubmission(
                $data
            );

        $contentInterestIds =
            $this->normalizeContentInterestIds(
                $data['content_interest_ids']
                    ?? []
            );

        $workflowAction =
            strtolower(
                trim(
                    (string) (
                        $data['workflow_action']
                        ?? 'draft'
                    )
                )
            );

        $releaseMode =
            strtolower(
                trim(
                    (string) (
                        $data['release_mode']
                        ?? 'immediate'
                    )
                )
            );

        $workflowStatus =
            match ($workflowAction) {
                'draft' =>
                'draft',

                'submit_review' =>
                'pending_review',

                'publish' =>
                in_array(
                    $releaseMode,
                    [
                        'scheduled',
                        'calendar'
                    ],
                    true
                )
                    ? 'scheduled'
                    : 'published',

                default =>
                throw new InvalidArgumentException(
                    'Invalid survey workflow action.'
                )
            };

        $surveyData = [
            'title' =>
            trim(
                (string) (
                    $data['survey_title']
                    ?? ''
                )
            ),

            'description' =>
            trim(
                (string) (
                    $data['survey_description']
                    ?? ''
                )
            ),

            'user_id' =>
            $userId,

            'workflow_status' =>
            $workflowStatus,

            'release_mode' =>
            $releaseMode,

            'scheduled_publish_at' =>
            $data['scheduled_publish_at']
                ?? null,

            'calendar_event_id' =>
            $data['calendar_event_id']
                ?? null,

            'opens_at' =>
            $data['opens_at']
                ?? null,

            'closes_at' =>
            $data['closes_at']
                ?? null,

            'allow_comments' =>
            !empty($data['allow_comments']),

            'allow_reactions' =>
            !empty($data['allow_reactions']),

            'require_acknowledgment' =>
            !empty($data['require_acknowledgment']),

            'send_notification' =>
            !empty($data['send_notification'])
        ];

        $surveyId = PublicationTransaction::run(
            $this->survey->getDatabaseConnection(), 'survey',
            function () use ($surveyData, $targets, $data) {
                $surveyId =
                    $this->survey->create(
                        $surveyData
                    );

                $this->survey->saveTargets(
                    $surveyId,
                    $targets
                );

                $questions =
                    $data['questions']
                    ?? [];

                $this->buildQuestions(
                    $surveyId,
                    $questions
                );
                return $surveyId;
            }
        );

        $this->contentInterest
            ->replaceAssignments(
                'survey',
                $surveyId,
                $contentInterestIds,
                $userId
            );

        $this->logRedundancyOverride(
            $redundancyAssessment,
            $surveyId
        );

        if ($workflowStatus === 'published') {
            $this->notifyPublishedSurveySafely(
                $surveyId
            );
        }

        if (
            $workflowStatus ===
            'pending_review'
        ) {
            $this->notifyReviewSubmissionSafely(
                $surveyId,
                $userId
            );
        }

        return $surveyId;
    }

    /* ==========================================
   UPDATE SURVEY
========================================== */

    public function updateSurvey(
        int $surveyId,
        array $data,
        int $userId
    ): int {
        $targets = $this->buildTargets($data);

        if (
            $surveyId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid survey or user ID.'
            );
        }

        $contentInterestIds =
            $this->normalizeContentInterestIds(
                $data['content_interest_ids']
                    ?? []
            );

        $existing =
            $this->survey
            ->findWorkflowItemById(
                $surveyId
            );

        if (!$existing) {
            throw new RuntimeException(
                'The survey could not be found.'
            );
        }

        if (
            (int) (
                $existing['user_id']
                ?? 0
            ) !== $userId
        ) {
            throw new RuntimeException(
                'You are not allowed to edit this survey.'
            );
        }

        $existingWorkflowStatus =
            strtolower(
                trim(
                    (string) (
                        $existing['workflow_status']
                        ?? ''
                    )
                )
            );

        if (
            !in_array(
                $existingWorkflowStatus,
                [
                    'draft',
                    'rejected'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only draft or rejected surveys can be edited.'
            );
        }

        if (
            (int) (
                $existing['response_count']
                ?? 0
            ) > 0
        ) {
            throw new RuntimeException(
                'A survey with submitted responses cannot be edited.'
            );
        }

        $data['post_type'] =
            'survey';

        $data['edit_id'] =
            $surveyId;

        $redundancyAssessment =
            $this->redundancy
            ->enforceSubmission(
                $data
            );

        $workflowAction =
            strtolower(
                trim(
                    (string) (
                        $data['workflow_action']
                        ?? 'draft'
                    )
                )
            );

        $releaseMode =
            strtolower(
                trim(
                    (string) (
                        $data['release_mode']
                        ?? 'immediate'
                    )
                )
            );

        $workflowStatus =
            match ($workflowAction) {
                'draft' =>
                'draft',

                'submit_review' =>
                'pending_review',

                'publish' =>
                in_array(
                    $releaseMode,
                    [
                        'scheduled',
                        'calendar'
                    ],
                    true
                )
                    ? 'scheduled'
                    : 'published',

                default =>
                throw new InvalidArgumentException(
                    'Invalid survey workflow action.'
                )
            };

        $surveyData = [
            'title' =>
            trim(
                (string) (
                    $data['survey_title']
                    ?? ''
                )
            ),

            'description' =>
            trim(
                (string) (
                    $data['survey_description']
                    ?? ''
                )
            ),

            'workflow_status' =>
            $workflowStatus,

            'release_mode' =>
            $releaseMode,

            'scheduled_publish_at' =>
            $data['scheduled_publish_at']
                ?? null,

            'calendar_event_id' =>
            $data['calendar_event_id']
                ?? null,

            'opens_at' =>
            $data['opens_at']
                ?? null,

            'closes_at' =>
            $data['closes_at']
                ?? null,

            'allow_comments' =>
            !empty($data['allow_comments']),

            'allow_reactions' =>
            !empty($data['allow_reactions']),

            'require_acknowledgment' =>
            !empty($data['require_acknowledgment']),

            'send_notification' =>
            !empty($data['send_notification'])
        ];

        $questions =
            $data['questions']
            ?? [];

        if (
            !is_array($questions) ||
            empty($questions)
        ) {
            throw new InvalidArgumentException(
                'A survey must contain at least one question.'
            );
        }

        $surveyId = PublicationTransaction::run(
            $this->survey->getDatabaseConnection(), 'survey',
            function () use ($surveyData, $targets, $surveyId, $userId, $questions) {
                $updated =
                    $this->survey->update(
                        $surveyId,
                        $surveyData,
                        $userId
                    );

                if (!$updated) {
                    throw new RuntimeException(
                        'The survey could not be updated.'
                    );
                }

                $this->survey->saveTargets(
                    $surveyId,
                    $targets
                );

                $this->survey->replaceQuestions(
                    $surveyId,
                    $questions
                );
                return $surveyId;
            }
        );

        $this->contentInterest
            ->replaceAssignments(
                'survey',
                $surveyId,
                $contentInterestIds,
                $userId
            );

        if ($workflowStatus === 'published') {
            $this->notifyPublishedSurveySafely(
                $surveyId
            );
        }

        if (
            $workflowStatus ===
            'pending_review'
        ) {
            $this->notifyReviewSubmissionSafely(
                $surveyId,
                $userId
            );
        }

        return $surveyId;
    }

    /* ==========================================
   SAFE PUBLISHED SURVEY NOTIFICATION
========================================== */


    private function logRedundancyOverride(
        array $assessment,
        int $surveyId
    ): void {
        $reason =
            trim(
                (string) (
                    $assessment['override_reason']
                    ?? ''
                )
            );

        if ($reason === '') {
            return;
        }

        $matchedRecords =
            array_map(
                static function (
                    array $match
                ): string {
                    return (
                        $match['content_type']
                        ?? 'survey'
                    )
                        . ' #'
                        . (
                            (int) (
                                $match['content_id']
                                ?? 0
                            )
                        );
                },
                $assessment['matches']
                    ?? []
            );

        try {
            global $conn;

            logActivity(
                $conn,
                'OVERRIDE_CONTENT_REDUNDANCY',
                'Saved survey #'
                    . $surveyId
                    . ' after reviewing similar records'
                    . (
                        $matchedRecords !== []
                        ? ': '
                        . implode(
                            ', ',
                            $matchedRecords
                        )
                        : ''
                    )
                    . '. Reason: '
                    . $reason
            );
        } catch (Throwable $exception) {
            /*
         * A successful Survey save remains
         * successful if audit logging fails.
         */
        }
    }

    private function notifyPublishedSurveySafely(
        int $surveyId
    ): void {
        try {
            $result =
                ($this->publicationDispatcher ?? new PublicationNotificationDispatcher())->dispatch(1, 'survey', $surveyId);

            error_log(
                'Survey notification delivery completed for '
                    . $surveyId
                    . ': '
                    . json_encode(
                        $result,
                        JSON_UNESCAPED_SLASHES
                    )
            );
        } catch (Throwable $exception) {
            /*
         * Notification failure must not make a
         * successfully saved Survey appear failed.
         */
            error_log(
                'Survey notification delivery failed for '
                    . $surveyId
                    . ': '
                    . $exception->getMessage()
            );
        }
    }

    /* ==========================================
   SAFE SURVEY REVIEW SUBMISSION
========================================== */

    private function notifyReviewSubmissionSafely(
        int $surveyId,
        int $submitterId
    ): void {
        try {
            $item =
                $this->survey
                ->findWorkflowItemById(
                    $surveyId
                );

            if (!$item) {
                throw new RuntimeException(
                    'Submitted Survey could not be reloaded.'
                );
            }

            $workflowStatus =
                strtolower(
                    trim(
                        (string) (
                            $item['workflow_status']
                            ?? ''
                        )
                    )
                );

            if (
                $workflowStatus !==
                'pending_review'
            ) {
                throw new RuntimeException(
                    'Submitted Survey is not pending review.'
                );
            }

            $authorId =
                (int) (
                    $item['user_id']
                    ?? 0
                );

            if (
                $authorId <= 0 ||
                $authorId !== $submitterId
            ) {
                throw new RuntimeException(
                    'Submitted Survey author mismatch.'
                );
            }

            $submitterName =
                trim(
                    (string) (
                        $item['author_name']
                        ?? (
                            $_SESSION['name']
                            ?? ''
                        )
                    )
                );

            $surveyTitle =
                trim(
                    (string) (
                        $item['title']
                        ?? ''
                    )
                );

            $submissionToken =
                'survey'
                . '|'
                . $surveyId
                . '|'
                . $submitterId
                . '|'
                . sprintf(
                    '%.6F',
                    microtime(true)
                );

            $result =
                $this->notifications
                ->notifyReviewSubmission(
                    $submitterId,
                    $submitterName,
                    'survey',
                    $surveyId,
                    $surveyTitle,
                    $submissionToken
                );

            error_log(
                'Survey review submission notification result: '
                    . $surveyId
                    . ' | '
                    . json_encode(
                        $result,
                        JSON_UNESCAPED_SLASHES
                    )
            );
        } catch (Throwable $exception) {
            /*
         * Notification failure must not make a
         * successfully submitted Survey appear failed.
         */
            error_log(
                'Survey review submission notification error: '
                    . $surveyId
                    . ' | '
                    . $exception->getMessage()
            );
        }
    }

    /* ==========================================
       CONTENT TOPICS
    ========================================== */

    private function normalizeContentInterestIds(
        mixed $interestIds
    ): array {
        if (!is_array($interestIds)) {
            $interestIds = [
                $interestIds
            ];
        }

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

        if ($interestIds === []) {
            throw new InvalidArgumentException(
                'Select at least one content topic.'
            );
        }

        $activeInterestIds = [];

        foreach (
            $this->contentInterest
                ->getActiveInterests()
            as $interest
        ) {
            $activeInterestIds[(int) (
                $interest['interest_id']
                ?? 0
            )] = true;
        }

        foreach (
            $interestIds as $interestId
        ) {
            if (
                !isset(
                    $activeInterestIds[$interestId]
                )
            ) {
                throw new InvalidArgumentException(
                    'One or more selected content topics are inactive or unavailable.'
                );
            }
        }

        return $interestIds;
    }

    /* ==========================================
   BUILD TARGETS
========================================== */

    private function buildTargets(
        array $data
    ): array {
        $data = ($this->facultyScope ??= new FacultyScopeService())->prepareSubmission(
            $data, (int) ($_SESSION['user_id'] ?? 0)
        );

        $selectedRoles =
            $data['target_roles']
            ?? [];

        if (!is_array($selectedRoles)) {
            $selectedRoles = [
                $selectedRoles
            ];
        }

        $selectedRoles =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn(
                                mixed $role
                            ): string =>
                            trim(
                                (string) $role
                            ),
                            $selectedRoles
                        ),
                        static fn(
                            string $role
                        ): bool =>
                        $role !== ''
                    )
                )
            );

        if ($selectedRoles === []) {
            throw new InvalidArgumentException(
                'Select at least one survey recipient group.'
            );
        }

        $roleIds = [];

        foreach ($selectedRoles as $roleName) {
            $roleId =
                $this->getRoleIdByName(
                    $roleName
                );

            if ($roleId === null) {
                throw new InvalidArgumentException(
                    'Invalid survey recipient role: '
                        . $roleName
                );
            }

            $roleIds[$roleName] = $roleId;
        }

        $audienceMode =
            strtolower(
                trim(
                    (string) (
                        $data['audience_scope']
                        ?? 'schoolwide'
                    )
                )
            );

        if (
            !in_array(
                $audienceMode,
                [
                    'schoolwide',
                    'custom'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Select a valid survey audience scope.'
            );
        }

        $rawScopes = [];

        if ($audienceMode === 'schoolwide') {
            $rawScopes[] = [];
        } else {
            $submittedScopes =
                $data['audience_scopes']
                ?? [];

            if (is_array($submittedScopes)) {
                $rawScopes =
                    array_values(
                        array_filter(
                            $submittedScopes,
                            'is_array'
                        )
                    );
            }

            /*
         * Backward compatibility with historical
         * single-scope Survey submissions.
         */
            if ($rawScopes === []) {
                $rawScopes[] = [
                    'department_id' =>
                    $data['target_department']
                        ?? null,

                    'education_level_id' =>
                    $data['target_education_level']
                        ?? null,

                    'academic_program_id' =>
                    $data['target_program']
                        ?? null,

                    'grade_level_id' =>
                    $data['target_grade_level']
                        ?? null,

                    'section_id' =>
                    $data['target_section']
                        ?? null
                ];
            }
        }

        $scopes = [];

        $scopeKeys = [];

        foreach ($rawScopes as $rawScope) {
            $departmentId =
                $this->nullablePositiveInt(
                    $rawScope['department_id']
                        ?? null
                );

            $educationLevelId =
                $this->nullablePositiveInt(
                    $rawScope['education_level_id']
                        ?? null
                );

            $academicProgramId =
                $this->nullablePositiveInt(
                    $rawScope['academic_program_id']
                        ?? null
                );

            $gradeLevelId =
                $this->nullablePositiveInt(
                    $rawScope['grade_level_id']
                        ?? null
                );

            $sectionId =
                $this->nullablePositiveInt(
                    $rawScope['section_id']
                        ?? null
                );

            if ($audienceMode === 'schoolwide') {
                $departmentId = null;
                $educationLevelId = null;
                $academicProgramId = null;
                $gradeLevelId = null;
                $sectionId = null;
            }

            $this->validateAcademicAudienceScope(
                $departmentId,
                $educationLevelId,
                $academicProgramId,
                $gradeLevelId,
                $sectionId
            );

            $scopeKey =
                implode(
                    ':',
                    [
                        $departmentId ?? 0,
                        $educationLevelId ?? 0,
                        $academicProgramId ?? 0,
                        $gradeLevelId ?? 0,
                        $sectionId ?? 0
                    ]
                );

            if (
                isset(
                    $scopeKeys[$scopeKey]
                )
            ) {
                continue;
            }

            $scopeKeys[$scopeKey] = true;

            $scopes[] = [
                'department_id' =>
                $departmentId,

                'education_level_id' =>
                $educationLevelId,

                'academic_program_id' =>
                $academicProgramId,

                'grade_level_id' =>
                $gradeLevelId,

                'section_id' =>
                $sectionId
            ];
        }

        if ($scopes === []) {
            throw new InvalidArgumentException(
                'Add at least one survey academic target.'
            );
        }

        $this->validateAcademicScopeOverlap(
            $scopes
        );

        $targets = [];

        $targetKeys = [];

        foreach ($selectedRoles as $roleName) {
            $roleId =
                (int) (
                    $roleIds[$roleName]
                    ?? 0
                );

            foreach ($scopes as $scope) {
                $targetKey =
                    implode(
                        ':',
                        [
                            $roleId,
                            $scope['department_id']
                                ?? 0,
                            $scope['education_level_id']
                                ?? 0,
                            $scope['academic_program_id']
                                ?? 0,
                            $scope['grade_level_id']
                                ?? 0,
                            $scope['section_id']
                                ?? 0
                        ]
                    );

                if (
                    isset(
                        $targetKeys[$targetKey]
                    )
                ) {
                    continue;
                }

                $targetKeys[$targetKey] = true;

                $targets[] = [
                    'role_id' =>
                    $roleId,

                    'department_id' =>
                    $scope['department_id'],

                    'education_level_id' =>
                    $scope['education_level_id'],

                    'academic_program_id' =>
                    $scope['academic_program_id'],

                    'grade_level_id' =>
                    $scope['grade_level_id'],

                    'section_id' =>
                    $scope['section_id']
                ];
            }
        }

        return $targets;
    }


    private function validateAcademicAudienceScope(
        ?int $departmentId,
        ?int $educationLevelId,
        ?int $academicProgramId,
        ?int $gradeLevelId,
        ?int $sectionId
    ): void {
        if (
            $departmentId === null &&
            $educationLevelId === null &&
            $academicProgramId === null &&
            $gradeLevelId === null &&
            $sectionId === null
        ) {
            return;
        }

        require __DIR__
            . '/../../config/dbconnect.php';

        $departmentValue =
            $departmentId ?? 0;

        $educationValue =
            $educationLevelId ?? 0;

        $programValue =
            $academicProgramId ?? 0;

        $gradeValue =
            $gradeLevelId ?? 0;

        $sectionValue =
            $sectionId ?? 0;

        $stmt = $conn->prepare("
        SELECT
            d.department_id

        FROM department d

        LEFT JOIN education_level el
            ON el.department_id =
                d.department_id
           AND el.status = 'Active'

        LEFT JOIN academic_program ap
            ON ap.education_level_id =
                el.education_level_id
           AND ap.status = 'Active'

        LEFT JOIN grade_level gl
            ON gl.education_level_id =
                el.education_level_id
           AND gl.status = 'Active'

        LEFT JOIN section s
            ON s.grade_level_id =
                gl.grade_level_id
           AND s.status = 'Active'

        LEFT JOIN academic_program sap
            ON sap.academic_program_id =
                s.academic_program_id
           AND sap.status = 'Active'

        WHERE d.status = 'Active'

          AND (
                ? = 0 OR
                d.department_id = ?
          )

          AND (
                ? = 0 OR
                el.education_level_id = ?
          )

          AND (
                ? = 0 OR
                ap.academic_program_id = ?
          )

          AND (
                ? = 0 OR
                gl.grade_level_id = ?
          )

          AND (
                ? = 0 OR
                s.section_id = ?
          )

          AND (
                s.section_id IS NULL OR
                s.academic_program_id IS NULL OR
                sap.academic_program_id IS NOT NULL
          )

          AND (
                ? = 0 OR
                ? = 0 OR
                s.academic_program_id = ?
          )

        LIMIT 1
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to validate the selected academic target: '
                    . $conn->error
            );
        }

        $stmt->bind_param(
            'iiiiiiiiiiiii',
            $departmentValue,
            $departmentValue,
            $educationValue,
            $educationValue,
            $programValue,
            $programValue,
            $gradeValue,
            $gradeValue,
            $sectionValue,
            $sectionValue,
            $sectionValue,
            $programValue,
            $programValue
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to validate the selected academic target: '
                    . $error
            );
        }

        $isValid =
            $stmt
            ->get_result()
            ->fetch_assoc() !== null;

        $stmt->close();

        if (!$isValid) {
            throw new InvalidArgumentException(
                'A selected academic target is inactive, unavailable, or does not belong to the chosen hierarchy.'
            );
        }
    }

    private function validateAcademicScopeOverlap(
        array $scopes
    ): void {
        $scopeCount =
            count(
                $scopes
            );

        for (
            $firstIndex = 0;
            $firstIndex < $scopeCount;
            $firstIndex++
        ) {
            for (
                $secondIndex =
                    $firstIndex + 1;
                $secondIndex < $scopeCount;
                $secondIndex++
            ) {
                $first =
                    $scopes[$firstIndex];

                $second =
                    $scopes[$secondIndex];

                $fields = [
                    'department_id',
                    'education_level_id',
                    'academic_program_id',
                    'grade_level_id',
                    'section_id'
                ];

                $firstContainsSecond =
                    true;

                $secondContainsFirst =
                    true;

                foreach ($fields as $field) {
                    $firstValue =
                        $first[$field]
                        ?? null;

                    $secondValue =
                        $second[$field]
                        ?? null;

                    if (
                        $firstValue !== null &&
                        $firstValue !==
                        $secondValue
                    ) {
                        $firstContainsSecond =
                            false;
                    }

                    if (
                        $secondValue !== null &&
                        $secondValue !==
                        $firstValue
                    ) {
                        $secondContainsFirst =
                            false;
                    }
                }

                if (
                    $firstContainsSecond ||
                    $secondContainsFirst
                ) {
                    throw new InvalidArgumentException(
                        'The selected academic targets overlap. Remove the duplicate or broader target.'
                    );
                }
            }
        }
    }


    private function nullablePositiveInt(
        mixed $value
    ): ?int {
        $number =
            (int) $value;

        return $number > 0
            ? $number
            : null;
    }

    /* ==========================================
   LOOKUP ROLE ID
========================================== */

    private function getRoleIdByName(
        string $roleName
    ): ?int {
        require __DIR__
            . '/../../config/dbconnect.php';

        $roleName =
            trim(
                $roleName
            );

        if ($roleName === '') {
            return null;
        }

        $stmt =
            $conn->prepare("
        SELECT role_id

        FROM role

        WHERE role_prefix = ?

        LIMIT 1
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare role lookup: '
                    . $conn->error
            );
        }

        $stmt->bind_param(
            's',
            $roleName
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load recipient role: '
                    . $error
            );
        }

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$row) {
            return null;
        }

        return (int) (
            $row['role_id']
            ?? 0
        );
    }

    /* ==========================================
       BUILD QUESTIONS
    ========================================== */

    private function buildQuestions(
        int $surveyId,
        array $questions
    ): void {
        foreach (
            $questions as
            $index => $question
        ) {
            if (!is_array($question)) {
                continue;
            }

            $questionData = [
                'question' =>
                trim(
                    (string) (
                        $question['question']
                        ?? ''
                    )
                ),

                'question_type' =>
                trim(
                    (string) (
                        $question['question_type']
                        ?? 'Short Text'
                    )
                ),

                'is_required' =>
                !empty($question['is_required']),

                'display_order' =>
                $index + 1,

                'rating_min' =>
                $question['rating_min']
                    ?? null,

                'rating_max' =>
                $question['rating_max']
                    ?? null
            ];

            $questionId =
                $this->survey
                ->createQuestion(
                    $surveyId,
                    $questionData
                );

            $choices =
                $question['choices']
                ?? [];

            if (
                is_array($choices) &&
                !empty($choices)
            ) {
                $this->survey
                    ->saveChoices(
                        $questionId,
                        $choices
                    );
            }
        }
    }

    /* ==========================================
       PARTICIPATION
    ========================================== */

    public function getSurveyForParticipation(
        int $surveyId,
        int $userId
    ): array {
        $survey =
            $this->survey
            ->findPublishedById(
                $surveyId
            );

        if (!$survey) {
            throw new RuntimeException(
                'Survey not found.'
            );
        }

        $currentUser = [
            'role' =>
            $_SESSION['role']
                ?? '',

            'role_id' =>
            $_SESSION['role_id']
                ?? null,

            'department_id' =>
            $_SESSION['department_id']
                ?? null,

            'education_level_id' =>
            $_SESSION['education_level_id']
                ?? null,

            'academic_program_id' =>
            $_SESSION['academic_program_id']
                ?? null,

            'grade_level_id' =>
            $_SESSION['grade_level_id']
                ?? null,

            'section_id' =>
            $_SESSION['section_id']
                ?? null
        ];

        if (
            !$this->survey
                ->canUserParticipate(
                    $surveyId,
                    $currentUser
                )
        ) {
            throw new RuntimeException(
                'You are not an intended recipient of this survey.'
            );
        }

        return [
            'survey' =>
            $survey,

            'is_open' =>
            $this->survey
                ->isOpenForResponses(
                    $survey
                ),

            'has_responded' =>
            $this->survey
                ->hasResponded(
                    $surveyId,
                    $userId
                )
        ];
    }

    public function submitSurveyResponse(
        int $surveyId,
        int $userId,
        array $answers
    ): int {
        $currentUser = [
            'role' =>
            $_SESSION['role']
                ?? '',

            'role_id' =>
            $_SESSION['role_id']
                ?? null,

            'department_id' =>
            $_SESSION['department_id']
                ?? null,

            'education_level_id' =>
            $_SESSION['education_level_id']
                ?? null,

            'academic_program_id' =>
            $_SESSION['academic_program_id']
                ?? null,

            'grade_level_id' =>
            $_SESSION['grade_level_id']
                ?? null,

            'section_id' =>
            $_SESSION['section_id']
                ?? null
        ];

        if (
            !$this->survey
                ->canUserParticipate(
                    $surveyId,
                    $currentUser
                )
        ) {
            throw new RuntimeException(
                'You are not an intended recipient of this survey.'
            );
        }

        $responseId =
            $this->survey
            ->submitResponse(
                $surveyId,
                $userId,
                $answers
            );

        try {
            $survey =
                $this->survey
                ->findWorkflowItemById(
                    $surveyId
                );

            if ($survey) {
                $surveyAuthorId =
                    (int) (
                        $survey['user_id']
                        ?? 0
                    );

                $surveyTitle =
                    trim(
                        (string) (
                            $survey['title']
                            ?? ''
                        )
                    );

                $this->notifications
                    ->notifyContentEngagement(
                        $surveyAuthorId,
                        $userId,
                        'survey_response',
                        'survey',
                        $surveyId,
                        $surveyTitle,
                        (string) $responseId
                    );
            }
        } catch (Throwable $exception) {
            /*
     * The committed survey response remains
     * successful if notification delivery fails.
     */
            error_log(
                'Survey response notification error: '
                    . $exception->getMessage()
            );
        }

        return $responseId;
    }

    /* ==========================================
       RESULTS
    ========================================== */

    public function getSurveyResults(
        int $surveyId
    ): array {
        $survey =
            $this->survey
            ->findWorkflowItemById(
                $surveyId
            );

        if (!$survey) {
            throw new RuntimeException(
                'Survey not found.'
            );
        }

        return [
            'survey' =>
            $survey,

            'results' =>
            $this->survey
                ->getResults(
                    $surveyId
                )
        ];
    }

    /* ==========================================
       WORKFLOW
    ========================================== */

    public function approveSurvey(
        int $surveyId,
        int $reviewerId
    ): bool {
        $updated = PublicationTransaction::run(
            $this->survey->getDatabaseConnection(), 'survey',
            fn() => $this->survey->approve($surveyId, $reviewerId), $surveyId
        );
        if ($updated) $this->notifyPublishedSurveySafely($surveyId);
        return $updated;
    }

    public function rejectSurvey(
        int $surveyId,
        int $reviewerId,
        string $reviewNotes
    ): bool {
        return $this->survey
            ->reject(
                $surveyId,
                $reviewerId,
                $reviewNotes
            );
    }

    public function archiveSurvey(
        int $surveyId
    ): bool {
        return $this->survey
            ->archive(
                $surveyId
            );
    }

    public function restoreSurvey(
        int $surveyId,
        int $userId
    ): bool {
        return $this->survey
            ->restoreToDraft(
                $surveyId,
                $userId
            );
    }
}
