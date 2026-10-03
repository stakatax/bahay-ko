<?php

require_once __DIR__ . '/BaseModel.php';

class Survey extends BaseModel
{
    public const RESULTS_MINIMUM_GROUP_SIZE = 5;

    /* ==========================================
       CREATE SURVEY
    ========================================== */

    public function create(
        array $data
    ): int {
        $title = trim(
            (string) (
                $data['title']
                ?? ''
            )
        );

        $description = trim(
            (string) (
                $data['description']
                ?? ''
            )
        );

        $userId =
            (int) (
                $data['user_id']
                ?? 0
            );

        $workflowStatus = strtolower(
            trim(
                (string) (
                    $data['workflow_status']
                    ?? 'draft'
                )
            )
        );

        $releaseMode = strtolower(
            trim(
                (string) (
                    $data['release_mode']
                    ?? 'immediate'
                )
            )
        );

        $scheduledPublishAt =
            !empty($data['scheduled_publish_at'])
            ? trim(
                (string) $data['scheduled_publish_at']
            )
            : null;

        $calendarEventId =
            !empty($data['calendar_event_id'])
            ? (int) $data['calendar_event_id']
            : null;

        $opensAt =
            !empty($data['opens_at'])
            ? trim(
                (string) $data['opens_at']
            )
            : null;

        $closesAt =
            !empty($data['closes_at'])
            ? trim(
                (string) $data['closes_at']
            )
            : null;

        $allowComments =
            !empty($data['allow_comments'])
            ? 1
            : 0;

        $allowReactions =
            !empty($data['allow_reactions'])
            ? 1
            : 0;

        $requireAcknowledgment =
            !empty($data['require_acknowledgment'])
            ? 1
            : 0;

        $sendNotification =
            !empty($data['send_notification'])
            ? 1
            : 0;

        /* ======================================
           VALIDATION
        ====================================== */

        if ($title === '') {
            throw new InvalidArgumentException(
                'Survey title is required.'
            );
        }

        if ($description === '') {
            throw new InvalidArgumentException(
                'Survey description is required.'
            );
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'A valid survey creator is required.'
            );
        }

        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException(
                'Survey title cannot exceed 255 characters.'
            );
        }

        if (
            mb_strlen($description) >
            5000
        ) {
            throw new InvalidArgumentException(
                'Survey description cannot exceed 5,000 characters.'
            );
        }

        $allowedWorkflowStatuses = [
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'scheduled',
            'published',
            'archived'
        ];

        if (
            !in_array(
                $workflowStatus,
                $allowedWorkflowStatuses,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid survey workflow status.'
            );
        }

        $allowedReleaseModes = [
            'immediate',
            'scheduled',
            'calendar'
        ];

        if (
            !in_array(
                $releaseMode,
                $allowedReleaseModes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid survey release mode.'
            );
        }

        if (
            $releaseMode !==
            'scheduled'
        ) {
            $scheduledPublishAt =
                null;
        }

        if (
            $releaseMode !==
            'calendar'
        ) {
            $calendarEventId =
                null;
        }

        if (
            $opensAt !== null &&
            $closesAt !== null
        ) {
            $openTimestamp =
                strtotime($opensAt);

            $closeTimestamp =
                strtotime($closesAt);

            if (
                $openTimestamp === false ||
                $closeTimestamp === false
            ) {
                throw new InvalidArgumentException(
                    'Invalid survey availability period.'
                );
            }

            if (
                $closeTimestamp <=
                $openTimestamp
            ) {
                throw new InvalidArgumentException(
                    'Survey closing time must be later than opening time.'
                );
            }
        }

        $isPublished =
            $workflowStatus ===
            'published';

        $publishedAt =
            $isPublished
            ? date(
                'Y-m-d H:i:s'
            )
            : null;

        /*
         * Existing survey.status uses:
         * Draft / Published / Archived
         */
        $status = match ($workflowStatus) {
            'published' =>
            'Published',

            'archived' =>
            'Archived',

            default =>
            'Draft'
        };

        /* ======================================
           INSERT SURVEY
        ====================================== */

        $stmt =
            $this->conn->prepare("
                INSERT INTO survey
                (
                    title,
                    description,
                    status,
                    workflow_status,
                    release_mode,
                    scheduled_publish_at,
                    calendar_event_id,
                    published_at,
                    opens_at,
                    closes_at,
                    allow_comments,
                    send_notification,
                    created_at,
                    user_id,
                    allow_reactions,
                    require_acknowledgment
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW(),
                    ?,
                    ?,
                    ?
                )
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey insert: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssssssisssiiiii',
            $title,
            $description,
            $status,
            $workflowStatus,
            $releaseMode,
            $scheduledPublishAt,
            $calendarEventId,
            $publishedAt,
            $opensAt,
            $closesAt,
            $allowComments,
            $sendNotification,
            $userId,
            $allowReactions,
            $requireAcknowledgment
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save survey: '
                    . $error
            );
        }

        $surveyId =
            (int) $this->conn
                ->insert_id;

        $stmt->close();

        return $surveyId;
    }

    /* ==========================================
   UPDATE SURVEY
========================================== */

    public function update(
        int $surveyId,
        array $data,
        int $userId
    ): bool {
        if (
            $surveyId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid survey or user ID.'
            );
        }

        $title = trim(
            (string) (
                $data['title']
                ?? ''
            )
        );

        $description = trim(
            (string) (
                $data['description']
                ?? ''
            )
        );

        $workflowStatus = strtolower(
            trim(
                (string) (
                    $data['workflow_status']
                    ?? 'draft'
                )
            )
        );

        $releaseMode = strtolower(
            trim(
                (string) (
                    $data['release_mode']
                    ?? 'immediate'
                )
            )
        );

        $scheduledPublishAt =
            !empty($data['scheduled_publish_at'])
            ? trim(
                (string) $data['scheduled_publish_at']
            )
            : null;

        $calendarEventId =
            !empty($data['calendar_event_id'])
            ? (int) $data['calendar_event_id']
            : null;

        $opensAt =
            !empty($data['opens_at'])
            ? trim(
                (string) $data['opens_at']
            )
            : null;

        $closesAt =
            !empty($data['closes_at'])
            ? trim(
                (string) $data['closes_at']
            )
            : null;

        $allowComments =
            !empty($data['allow_comments'])
            ? 1
            : 0;

        $allowReactions =
            !empty($data['allow_reactions'])
            ? 1
            : 0;

        $requireAcknowledgment =
            !empty($data['require_acknowledgment'])
            ? 1
            : 0;

        $sendNotification =
            !empty($data['send_notification'])
            ? 1
            : 0;

        /* ======================================
       VALIDATION
    ======================================= */

        if ($title === '') {
            throw new InvalidArgumentException(
                'Survey title is required.'
            );
        }

        if ($description === '') {
            throw new InvalidArgumentException(
                'Survey description is required.'
            );
        }

        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException(
                'Survey title cannot exceed 255 characters.'
            );
        }

        if (
            mb_strlen($description) >
            5000
        ) {
            throw new InvalidArgumentException(
                'Survey description cannot exceed 5,000 characters.'
            );
        }

        $allowedWorkflowStatuses = [
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'scheduled',
            'published',
            'archived'
        ];

        if (
            !in_array(
                $workflowStatus,
                $allowedWorkflowStatuses,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid survey workflow status.'
            );
        }

        $allowedReleaseModes = [
            'immediate',
            'scheduled',
            'calendar'
        ];

        if (
            !in_array(
                $releaseMode,
                $allowedReleaseModes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid survey release mode.'
            );
        }

        if (
            $releaseMode === 'scheduled' &&
            $scheduledPublishAt === null
        ) {
            throw new InvalidArgumentException(
                'Scheduled release date is required.'
            );
        }

        if (
            $releaseMode === 'calendar' &&
            (
                $calendarEventId === null ||
                $calendarEventId <= 0
            )
        ) {
            throw new InvalidArgumentException(
                'Calendar event reference is required.'
            );
        }

        if (
            $releaseMode !==
            'scheduled'
        ) {
            $scheduledPublishAt =
                null;
        }

        if (
            $releaseMode !==
            'calendar'
        ) {
            $calendarEventId =
                null;
        }

        if (
            $opensAt !== null &&
            $closesAt !== null
        ) {
            $openTimestamp =
                strtotime(
                    $opensAt
                );

            $closeTimestamp =
                strtotime(
                    $closesAt
                );

            if (
                $openTimestamp === false ||
                $closeTimestamp === false
            ) {
                throw new InvalidArgumentException(
                    'Invalid survey availability period.'
                );
            }

            if (
                $closeTimestamp <=
                $openTimestamp
            ) {
                throw new InvalidArgumentException(
                    'Survey closing time must be later than opening time.'
                );
            }
        }

        $isPublished =
            $workflowStatus ===
            'published';

        $publishedAt =
            $isPublished
            ? date(
                'Y-m-d H:i:s'
            )
            : null;

        $status = match ($workflowStatus) {
            'published' =>
            'Published',

            'archived' =>
            'Archived',

            default =>
            'Draft'
        };

        /* ======================================
       UPDATE SURVEY
    ======================================= */

        $stmt =
            $this->conn->prepare("
            UPDATE survey

            SET
                title = ?,
                description = ?,
                status = ?,
                workflow_status = ?,
                release_mode = ?,
                scheduled_publish_at = ?,
                calendar_event_id = ?,
                published_at = ?,
                opens_at = ?,
                closes_at = ?,
                allow_comments = ?,
                send_notification = ?,
                allow_reactions = ?,
                require_acknowledgment = ?,
                reviewed_by = NULL,
                reviewed_at = NULL,
                review_notes = NULL

            WHERE survey_id = ?
              AND user_id = ?
              AND workflow_status IN (
                    'draft',
                    'rejected'
              )

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssssssisssiiiiii',
            $title,
            $description,
            $status,
            $workflowStatus,
            $releaseMode,
            $scheduledPublishAt,
            $calendarEventId,
            $publishedAt,
            $opensAt,
            $closesAt,
            $allowComments,
            $sendNotification,
            $allowReactions,
            $requireAcknowledgment,
            $surveyId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update survey: '
                    . $error
            );
        }

        /*
 * A successful update may affect zero rows when the
 * submitted Survey values already match the database.
 */

        $stmt->close();

        return true;
    }


    /* ==========================================
       SAVE SURVEY TARGETS
    ========================================== */

    public function saveTargets(
        int $surveyId,
        array $targets
    ): void {
        if ($surveyId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        $deleteStmt =
            $this->conn->prepare("
                DELETE FROM survey_target
                WHERE survey_id = ?
            ");

        if (!$deleteStmt) {
            throw new RuntimeException(
                'Unable to prepare survey target cleanup: '
                    . $this->conn->error
            );
        }

        $deleteStmt->bind_param(
            'i',
            $surveyId
        );

        if (!$deleteStmt->execute()) {
            $error =
                $deleteStmt->error;

            $deleteStmt->close();

            throw new RuntimeException(
                'Unable to clear survey targets: '
                    . $error
            );
        }

        $deleteStmt->close();

        if (empty($targets)) {
            return;
        }

        $insertStmt =
            $this->conn->prepare("
                INSERT INTO survey_target
                (
                    survey_id,
                    role_id,
                    department_id,
                    education_level_id,
                    academic_program_id,
                    grade_level_id,
                    section_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

        if (!$insertStmt) {
            throw new RuntimeException(
                'Unable to prepare survey target insert: '
                    . $this->conn->error
            );
        }

        $insertedTargets = [];

        foreach ($targets as $target) {
            $roleId =
                !empty($target['role_id'])
                ? (int) $target['role_id']
                : null;

            $departmentId =
                !empty($target['department_id'])
                ? (int) $target['department_id']
                : null;

            $educationLevelId =
                !empty($target['education_level_id'])
                ? (int) $target['education_level_id']
                : null;

            $academicProgramId =
                !empty($target['academic_program_id'])
                ? (int) $target['academic_program_id']
                : null;

            $gradeLevelId =
                !empty($target['grade_level_id'])
                ? (int) $target['grade_level_id']
                : null;

            $sectionId =
                !empty($target['section_id'])
                ? (int) $target['section_id']
                : null;

            if (
                $roleId === null &&
                $departmentId === null &&
                $educationLevelId === null &&
                $academicProgramId === null &&
                $gradeLevelId === null &&
                $sectionId === null
            ) {
                continue;
            }

            $targetSignature =
                implode(
                    ':',
                    [
                        $roleId ?? 0,
                        $departmentId ?? 0,
                        $educationLevelId ?? 0,
                        $academicProgramId ?? 0,
                        $gradeLevelId ?? 0,
                        $sectionId ?? 0
                    ]
                );

            if (
                isset(
                    $insertedTargets[$targetSignature]
                )
            ) {
                continue;
            }

            $insertStmt->bind_param(
                'iiiiiii',
                $surveyId,
                $roleId,
                $departmentId,
                $educationLevelId,
                $academicProgramId,
                $gradeLevelId,
                $sectionId
            );

            if (!$insertStmt->execute()) {
                $error =
                    $insertStmt->error;

                $insertStmt->close();

                throw new RuntimeException(
                    'Unable to save survey target: '
                        . $error
                );
            }

            $insertedTargets[$targetSignature] = true;
        }

        $insertStmt->close();
    }

    /* ==========================================
   GET SURVEY TARGETS
========================================== */

    public function getTargets(
        int $surveyId
    ): array {
        if ($surveyId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                st.role_id,
                st.department_id,
                st.education_level_id,
                st.academic_program_id,
                st.grade_level_id,
                st.section_id,
                r.role_prefix

            FROM survey_target st

            LEFT JOIN role r
                ON r.role_id =
                   st.role_id

            WHERE st.survey_id = ?

            ORDER BY st.target_id ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey targets: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load survey targets: '
                    . $error
            );
        }

        $targets =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $targets;
    }


    /* ==========================================
       CREATE SURVEY QUESTION
    ========================================== */

    public function createQuestion(
        int $surveyId,
        array $data
    ): int {
        if ($surveyId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        $question = trim(
            (string) (
                $data['question']
                ?? ''
            )
        );

        $questionType =
            $this->normalizeQuestionType(
                (string) (
                    $data['question_type']
                    ?? 'Short Text'
                )
            );

        $isRequired =
            !empty($data['is_required'])
            ? 1
            : 0;

        $displayOrder =
            max(
                1,
                (int) (
                    $data['display_order']
                    ?? 1
                )
            );

        if ($question === '') {
            throw new InvalidArgumentException(
                'Survey question is required.'
            );
        }

        if (
            mb_strlen($question) >
            2000
        ) {
            throw new InvalidArgumentException(
                'Survey question cannot exceed 2,000 characters.'
            );
        }

        $ratingMin = null;
        $ratingMax = null;

        if (
            $questionType ===
            'Rating'
        ) {
            $ratingMin =
                (int) (
                    $data['rating_min']
                    ?? 1
                );

            $ratingMax =
                (int) (
                    $data['rating_max']
                    ?? 5
                );

            if (
                $ratingMin < 1 ||
                $ratingMax > 10 ||
                $ratingMin >= $ratingMax
            ) {
                throw new InvalidArgumentException(
                    'Rating range must be between 1 and 10.'
                );
            }
        }

        $stmt =
            $this->conn->prepare("
                INSERT INTO survey_question
                (
                    survey_id,
                    question,
                    question_type,
                    is_required,
                    display_order,
                    rating_min,
                    rating_max
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey question insert: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'issiiii',
            $surveyId,
            $question,
            $questionType,
            $isRequired,
            $displayOrder,
            $ratingMin,
            $ratingMax
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save survey question: '
                    . $error
            );
        }

        $questionId =
            (int) $this->conn
                ->insert_id;

        $stmt->close();

        return $questionId;
    }




    /* ==========================================
       SAVE SURVEY QUESTION CHOICES
    ========================================== */

    public function saveChoices(
        int $questionId,
        array $choices
    ): void {
        if ($questionId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey question ID.'
            );
        }

        $deleteStmt =
            $this->conn->prepare("
                DELETE FROM survey_choice
                WHERE question_id = ?
            ");

        if (!$deleteStmt) {
            throw new RuntimeException(
                'Unable to prepare survey choice cleanup: '
                    . $this->conn->error
            );
        }

        $deleteStmt->bind_param(
            'i',
            $questionId
        );

        if (!$deleteStmt->execute()) {
            $error =
                $deleteStmt->error;

            $deleteStmt->close();

            throw new RuntimeException(
                'Unable to clear survey choices: '
                    . $error
            );
        }

        $deleteStmt->close();

        $cleanChoices = [];

        foreach ($choices as $choice) {
            $choiceText =
                trim(
                    is_array($choice)
                        ? (string) (
                            $choice['choice_text']
                            ?? ''
                        )
                        : (string) $choice
                );

            if ($choiceText === '') {
                continue;
            }

            if (
                mb_strlen($choiceText) >
                255
            ) {
                throw new InvalidArgumentException(
                    'Survey choices cannot exceed 255 characters.'
                );
            }

            if (
                !in_array(
                    $choiceText,
                    $cleanChoices,
                    true
                )
            ) {
                $cleanChoices[] =
                    $choiceText;
            }
        }

        if (empty($cleanChoices)) {
            return;
        }

        $insertStmt =
            $this->conn->prepare("
                INSERT INTO survey_choice
                (
                    question_id,
                    choice_text,
                    display_order
                )
                VALUES (?, ?, ?)
            ");

        if (!$insertStmt) {
            throw new RuntimeException(
                'Unable to prepare survey choice insert: '
                    . $this->conn->error
            );
        }

        foreach (
            $cleanChoices as
            $index => $choiceText
        ) {
            $displayOrder =
                $index + 1;

            $insertStmt->bind_param(
                'isi',
                $questionId,
                $choiceText,
                $displayOrder
            );

            if (!$insertStmt->execute()) {
                $error =
                    $insertStmt->error;

                $insertStmt->close();

                throw new RuntimeException(
                    'Unable to save survey choice: '
                        . $error
                );
            }
        }

        $insertStmt->close();
    }


    /* ==========================================
       NORMALIZE QUESTION TYPE
    ========================================== */

    private function normalizeQuestionType(
        string $questionType
    ): string {
        $normalized =
            strtolower(
                trim(
                    $questionType
                )
            );

        return match ($normalized) {
            'text' =>
            'Text',

            'short text',
            'short_text',
            'short answer',
            'short_answer' =>
            'Short Text',

            'long text',
            'long_text',
            'long answer',
            'long_answer',
            'paragraph' =>
            'Long Text',

            'multiple choice',
            'multiple_choice',
            'single choice',
            'single_choice' =>
            'Multiple Choice',

            'checkbox',
            'checkboxes',
            'multiple selection',
            'multiple_selection' =>
            'Checkbox',

            'rating',
            'rating scale',
            'rating_scale' =>
            'Rating',

            'yes/no',
            'yes_no',
            'yes no',
            'boolean' =>
            'Yes/No',

            default =>
            throw new InvalidArgumentException(
                'Invalid survey question type.'
            )
        };
    }


    /* ==========================================
       GET SURVEY QUESTIONS + CHOICES
    ========================================== */

    public function getQuestions(
        int $surveyId
    ): array {
        if ($surveyId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    question_id,
                    survey_id,
                    question,
                    question_type,
                    is_required,
                    display_order,
                    rating_min,
                    rating_max

                FROM survey_question

                WHERE survey_id = ?

                ORDER BY
                    display_order ASC,
                    question_id ASC
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey question list: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load survey questions: '
                    . $error
            );
        }

        $questions =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        if (empty($questions)) {
            return [];
        }

        $choiceStmt =
            $this->conn->prepare("
                SELECT
                    choice_id,
                    question_id,
                    choice_text,
                    display_order

                FROM survey_choice

                WHERE question_id = ?

                ORDER BY
                    display_order ASC,
                    choice_id ASC
            ");

        if (!$choiceStmt) {
            throw new RuntimeException(
                'Unable to prepare survey choices: '
                    . $this->conn->error
            );
        }

        foreach (
            $questions as &$question
        ) {
            $questionId =
                (int) (
                    $question['question_id']
                    ?? 0
                );

            $choiceStmt->bind_param(
                'i',
                $questionId
            );

            if (!$choiceStmt->execute()) {
                $error =
                    $choiceStmt->error;

                $choiceStmt->close();

                throw new RuntimeException(
                    'Unable to load survey choices: '
                        . $error
                );
            }

            $question['choices'] =
                $choiceStmt
                ->get_result()
                ->fetch_all(
                    MYSQLI_ASSOC
                );
        }

        unset($question);

        $choiceStmt->close();

        return $questions;
    }

    /* ==========================================
   REPLACE SURVEY QUESTIONS
========================================== */

    public function replaceQuestions(
        int $surveyId,
        array $questions
    ): void {
        if ($surveyId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        if (empty($questions)) {
            throw new InvalidArgumentException(
                'A survey must contain at least one question.'
            );
        }

        /* ======================================
       PROTECT EXISTING RESPONSES
    ======================================= */

        $responseStmt =
            $this->conn->prepare("
            SELECT COUNT(*) AS total

            FROM survey_response

            WHERE survey_id = ?
        ");

        if (!$responseStmt) {
            throw new RuntimeException(
                'Unable to prepare survey response check: '
                    . $this->conn->error
            );
        }

        $responseStmt->bind_param(
            'i',
            $surveyId
        );

        if (!$responseStmt->execute()) {
            $error =
                $responseStmt->error;

            $responseStmt->close();

            throw new RuntimeException(
                'Unable to check survey responses: '
                    . $error
            );
        }

        $responseRow =
            $responseStmt
            ->get_result()
            ->fetch_assoc();

        $responseStmt->close();

        if (
            (int) (
                $responseRow['total']
                ?? 0
            ) > 0
        ) {
            throw new RuntimeException(
                'Survey questions cannot be replaced after responses have been submitted.'
            );
        }

        $ownsTransaction = empty($this->conn->query('SELECT @@in_transaction AS active')->fetch_assoc()['active']);
        if ($ownsTransaction) $this->conn->begin_transaction();
        else $this->conn->query('SAVEPOINT survey_questions');

        try {
            /*
         * survey_choice rows are deleted
         * automatically through ON DELETE CASCADE.
         */
            $deleteStmt =
                $this->conn->prepare("
                DELETE FROM survey_question
                WHERE survey_id = ?
            ");

            if (!$deleteStmt) {
                throw new RuntimeException(
                    'Unable to prepare survey question cleanup: '
                        . $this->conn->error
                );
            }

            $deleteStmt->bind_param(
                'i',
                $surveyId
            );

            if (!$deleteStmt->execute()) {
                $error =
                    $deleteStmt->error;

                $deleteStmt->close();

                throw new RuntimeException(
                    'Unable to clear survey questions: '
                        . $error
                );
            }

            $deleteStmt->close();

            foreach (
                array_values($questions) as
                $index => $questionData
            ) {
                if (!is_array($questionData)) {
                    throw new InvalidArgumentException(
                        'Invalid survey question data.'
                    );
                }

                $questionType =
                    $this->normalizeQuestionType(
                        (string) (
                            $questionData['question_type']
                            ?? 'Short Text'
                        )
                    );

                $choices =
                    is_array(
                        $questionData['choices']
                            ?? null
                    )
                    ? $questionData['choices']
                    : [];

                if (
                    in_array(
                        $questionType,
                        [
                            'Multiple Choice',
                            'Checkbox'
                        ],
                        true
                    )
                ) {
                    $validChoices = [];

                    foreach ($choices as $choice) {
                        $choiceText =
                            trim(
                                is_array($choice)
                                    ? (string) (
                                        $choice['choice_text']
                                        ?? ''
                                    )
                                    : (string) $choice
                            );

                        if (
                            $choiceText !== '' &&
                            !in_array(
                                $choiceText,
                                $validChoices,
                                true
                            )
                        ) {
                            $validChoices[] =
                                $choiceText;
                        }
                    }

                    if (
                        count($validChoices) < 2
                    ) {
                        throw new InvalidArgumentException(
                            'Multiple Choice and Checkbox questions require at least two choices.'
                        );
                    }

                    $choices =
                        $validChoices;
                } else {
                    $choices = [];
                }

                $questionData['question_type'] =
                    $questionType;

                $questionData['display_order'] =
                    $index + 1;

                $questionId =
                    $this->createQuestion(
                        $surveyId,
                        $questionData
                    );

                if ($questionId <= 0) {
                    throw new RuntimeException(
                        'A survey question could not be saved.'
                    );
                }

                if (!empty($choices)) {
                    $this->saveChoices(
                        $questionId,
                        $choices
                    );
                }
            }

            if ($ownsTransaction) $this->conn->commit();
            else $this->conn->query('RELEASE SAVEPOINT survey_questions');
        } catch (Throwable $exception) {
            if ($ownsTransaction) $this->conn->rollback();
            else $this->conn->query('ROLLBACK TO SAVEPOINT survey_questions');

            throw $exception;
        }
    }


    /* ==========================================
       SURVEY WORKFLOW LIST
    ========================================== */

    public function getByWorkflowStatus(
        string $workflowStatus,
        ?int $userId = null,
        int $limit = 100
    ): array {
        $allowedStatuses = [
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'scheduled',
            'published',
            'archived'
        ];

        if (
            !in_array(
                $workflowStatus,
                $allowedStatuses,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid survey workflow status.'
            );
        }

        $limit = max(
            1,
            min(
                $limit,
                250
            )
        );

        $sql = "
            SELECT
                s.survey_id,
                s.title,
                s.description,

                s.status,
                s.workflow_status,

                s.release_mode,
                s.scheduled_publish_at,
                s.calendar_event_id,

                s.published_at,
                s.opens_at,
                s.closes_at,

                s.reviewed_by,
                s.reviewed_at,
                s.review_notes,

                s.allow_comments,
                s.allow_reactions,
                s.require_acknowledgment,
                s.send_notification,

                s.created_at,
                s.user_id,

                linked_event.title
                    AS linked_event_title,

                linked_event.event_date
                    AS linked_event_date,

                TRIM(
                    CONCAT(
                        COALESCE(
                            creator.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            creator.last_name,
                            ''
                        )
                    )
                ) AS author_name,

                TRIM(
                    CONCAT(
                        COALESCE(
                            reviewer.first_name,
                            ''
                        ),
                        ' ',
                        COALESCE(
                            reviewer.last_name,
                            ''
                        )
                    )
                ) AS reviewer_name,

                (
                    SELECT COUNT(*)

                    FROM survey_response sr

                    WHERE sr.survey_id =
                        s.survey_id
                ) AS response_count

            FROM survey s

            LEFT JOIN user creator
                ON creator.user_id =
                    s.user_id

            LEFT JOIN user reviewer
                ON reviewer.user_id =
                    s.reviewed_by

            LEFT JOIN events linked_event
                ON linked_event.event_id =
                    s.calendar_event_id

            WHERE s.workflow_status = ?
        ";

        if (
            $userId !== null &&
            $userId > 0
        ) {
            $sql .= "
                AND s.user_id = ?
            ";
        }

        $sql .= "
            ORDER BY
                s.created_at DESC

            LIMIT ?
        ";

        $stmt =
            $this->conn->prepare(
                $sql
            );

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey workflow list: '
                    . $this->conn->error
            );
        }

        if (
            $userId !== null &&
            $userId > 0
        ) {
            $stmt->bind_param(
                'sii',
                $workflowStatus,
                $userId,
                $limit
            );
        } else {
            $stmt->bind_param(
                'si',
                $workflowStatus,
                $limit
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load survey workflow list: '
                    . $error
            );
        }

        $surveys =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $surveys;
    }

    /* ==========================================
       FIND SURVEY FOR CONTENT WORKSPACE
    ========================================== */

    public function findWorkflowItemById(
        int $surveyId
    ): ?array {
        if ($surveyId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    s.*,

                    linked_event.title
                        AS linked_event_title,

                    linked_event.event_date
                        AS linked_event_date,

                    TRIM(
                        CONCAT(
                            COALESCE(
                                creator.first_name,
                                ''
                            ),
                            ' ',
                            COALESCE(
                                creator.last_name,
                                ''
                            )
                        )
                    ) AS author_name,

                    TRIM(
                        CONCAT(
                            COALESCE(
                                reviewer.first_name,
                                ''
                            ),
                            ' ',
                            COALESCE(
                                reviewer.last_name,
                                ''
                            )
                        )
                    ) AS reviewer_name,

                    (
                        SELECT COUNT(*)

                        FROM survey_response sr

                        WHERE sr.survey_id =
                            s.survey_id
                    ) AS response_count

                FROM survey s

                LEFT JOIN user creator
                    ON creator.user_id =
                        s.user_id

                LEFT JOIN user reviewer
                    ON reviewer.user_id =
                        s.reviewed_by

                LEFT JOIN events linked_event
                    ON linked_event.event_id =
                        s.calendar_event_id

                WHERE s.survey_id = ?

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey workspace details: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load survey workspace details: '
                    . $error
            );
        }

        $survey =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$survey) {
            return null;
        }

        $survey['questions'] =
            $this->getQuestions(
                $surveyId
            );

        return $survey;
    }

    /* ==========================================
       SUBMIT SURVEY FOR REVIEW
    ========================================== */

    public function submitForReview(
        int $surveyId,
        int $userId
    ): bool {
        if (
            $surveyId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid survey or user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
                UPDATE survey

                SET
                    workflow_status =
                        'pending_review',

                    status =
                        'Draft',

                    published_at =
                        NULL,

                    reviewed_by =
                        NULL,

                    reviewed_at =
                        NULL,

                    review_notes =
                        NULL

                WHERE survey_id = ?
                  AND user_id = ?
                  AND workflow_status IN (
                        'draft',
                        'rejected'
                  )
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey review submission: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $surveyId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to submit survey for review: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }


    /* ==========================================
       APPROVE SURVEY
    ========================================== */

    public function approve(
        int $surveyId,
        int $reviewerId
    ): bool {
        if (
            $surveyId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid survey or reviewer ID.'
            );
        }

        $survey =
            $this->findWorkflowItemById(
                $surveyId
            );

        if (
            !$survey ||
            (
                $survey['workflow_status']
                ?? ''
            ) !== 'pending_review'
        ) {
            return false;
        }

        $releaseMode =
            strtolower(
                trim(
                    (string) (
                        $survey['release_mode']
                        ?? 'immediate'
                    )
                )
            );

        $workflowStatus =
            in_array(
                $releaseMode,
                [
                    'scheduled',
                    'calendar'
                ],
                true
            )
            ? 'scheduled'
            : 'published';

        $status =
            $workflowStatus ===
            'published'
            ? 'Published'
            : 'Draft';

        $publishedAt =
            $workflowStatus ===
            'published'
            ? date(
                'Y-m-d H:i:s'
            )
            : null;

        $stmt =
            $this->conn->prepare("
                UPDATE survey

                SET
                    workflow_status = ?,
                    status = ?,
                    published_at = ?,
                    reviewed_by = ?,
                    reviewed_at = NOW(),
                    review_notes = NULL

                WHERE survey_id = ?
                  AND workflow_status =
                      'pending_review'
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey approval: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'sssii',
            $workflowStatus,
            $status,
            $publishedAt,
            $reviewerId,
            $surveyId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to approve survey: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
       REJECT SURVEY
    ========================================== */

    public function reject(
        int $surveyId,
        int $reviewerId,
        string $reviewNotes
    ): bool {
        if (
            $surveyId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid survey or reviewer ID.'
            );
        }

        $reviewNotes =
            trim(
                $reviewNotes
            );

        if ($reviewNotes === '') {
            throw new InvalidArgumentException(
                'A rejection reason is required.'
            );
        }

        if (
            mb_strlen(
                $reviewNotes
            ) > 1000
        ) {
            throw new InvalidArgumentException(
                'The rejection reason cannot exceed 1,000 characters.'
            );
        }

        $stmt =
            $this->conn->prepare("
                UPDATE survey

                SET
                    workflow_status =
                        'rejected',

                    status =
                        'Draft',

                    published_at =
                        NULL,

                    reviewed_by =
                        ?,

                    reviewed_at =
                        NOW(),

                    review_notes =
                        ?

                WHERE survey_id = ?
                  AND workflow_status =
                      'pending_review'
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey rejection: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'isi',
            $reviewerId,
            $reviewNotes,
            $surveyId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to reject survey: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
       ARCHIVE SURVEY
    ========================================== */

    public function archive(
        int $surveyId
    ): bool {
        if ($surveyId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
                UPDATE survey

                SET
                    workflow_status =
                        'archived',

                    status =
                        'Archived'

                WHERE survey_id = ?
                  AND workflow_status IN (
                        'published',
                        'scheduled'
                  )
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey archive: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to archive survey: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }


    /* ==========================================
       RESTORE SURVEY TO DRAFT
    ========================================== */

    public function restoreToDraft(
        int $surveyId,
        int $userId
    ): bool {
        if (
            $surveyId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid survey or user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
                UPDATE survey

                SET
                    workflow_status =
                        'draft',

                    status =
                        'Draft',

                    published_at =
                        NULL,

                    reviewed_by =
                        NULL,

                    reviewed_at =
                        NULL,

                    review_notes =
                        NULL

                WHERE survey_id = ?
                  AND user_id = ?
                  AND workflow_status IN (
                        'rejected',
                        'archived'
                  )
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey draft restoration: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $surveyId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to restore survey to draft: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }


    /* ==========================================
   COUNT PUBLISHED SURVEYS
========================================== */

    public function countPublished(): int
    {
        $stmt =
            $this->conn->prepare("
            SELECT COUNT(*) AS total
            FROM survey
            WHERE status = 'Published'
              AND workflow_status = 'published'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare published survey count: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to count published surveys: '
                    . $error
            );
        }

        $row =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return (int) (
            $row['total']
            ?? 0
        );
    }


    /* ==========================================
       FIND PUBLISHED SURVEY
    ========================================== */

    public function findPublishedById(
        int $surveyId
    ): ?array {
        if ($surveyId <= 0) {
            return null;
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    s.*,

                    TRIM(
                        CONCAT(
                            COALESCE(
                                u.first_name,
                                ''
                            ),
                            ' ',
                            COALESCE(
                                u.last_name,
                                ''
                            )
                        )
                    ) AS author_name

                FROM survey s

                LEFT JOIN user u
                    ON u.user_id =
                        s.user_id

                WHERE s.survey_id = ?
                  AND s.status =
                        'Published'
                  AND s.workflow_status =
                        'published'

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare published survey lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load published survey: '
                    . $error
            );
        }

        $survey =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$survey) {
            return null;
        }

        $survey['questions'] =
            $this->getQuestions(
                $surveyId
            );

        return $survey;
    }

    /* ==========================================
   CHECK USER SURVEY ELIGIBILITY
========================================== */

    public function canUserParticipate(
        int $surveyId,
        array $user
    ): bool {
        if ($surveyId <= 0) {
            return false;
        }

        /*
     * Administrators may access all surveys
     * for supervision and testing.
     */
        $roleName =
            trim(
                (string) (
                    $user['role']
                    ?? ''
                )
            );

        if ($roleName === 'Admin') {
            return true;
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                role_id,
                department_id,
                education_level_id,
                academic_program_id,
                grade_level_id,
                section_id

            FROM survey_target

            WHERE survey_id = ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey eligibility check: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load survey eligibility rules: '
                    . $error
            );
        }

        $targets =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        /*
     * No target rows means schoolwide.
     */
        if (empty($targets)) {
            return true;
        }

        $userRoleId =
            (int) (
                $user['role_id']
                ?? 0
            );

        $userDepartmentId =
            (int) (
                $user['department_id']
                ?? 0
            );

        $userEducationLevelId =
            (int) (
                $user['education_level_id']
                ?? 0
            );

        $userAcademicProgramId =
            (int) (
                $user['academic_program_id']
                ?? 0
            );

        $userGradeLevelId =
            (int) (
                $user['grade_level_id']
                ?? 0
            );

        $userSectionId =
            (int) (
                $user['section_id']
                ?? 0
            );

        foreach ($targets as $target) {
            $targetRoleId =
                (int) (
                    $target['role_id']
                    ?? 0
                );

            $targetDepartmentId =
                (int) (
                    $target['department_id']
                    ?? 0
                );

            $targetEducationLevelId =
                (int) (
                    $target['education_level_id']
                    ?? 0
                );

            $targetAcademicProgramId =
                (int) (
                    $target['academic_program_id']
                    ?? 0
                );

            $targetGradeLevelId =
                (int) (
                    $target['grade_level_id']
                    ?? 0
                );

            $targetSectionId =
                (int) (
                    $target['section_id']
                    ?? 0
                );

            $roleMatches =
                $targetRoleId === 0 ||
                $targetRoleId ===
                $userRoleId;

            $departmentMatches =
                $targetDepartmentId === 0 ||
                $targetDepartmentId ===
                $userDepartmentId;

            $educationLevelMatches =
                $targetEducationLevelId === 0 ||
                $targetEducationLevelId ===
                $userEducationLevelId;

            $academicProgramMatches =
                $targetAcademicProgramId === 0 ||
                $targetAcademicProgramId ===
                $userAcademicProgramId;

            $gradeLevelMatches =
                $targetGradeLevelId === 0 ||
                $targetGradeLevelId ===
                $userGradeLevelId;

            $sectionMatches =
                $targetSectionId === 0 ||
                $targetSectionId ===
                $userSectionId;

            if (
                $roleMatches &&
                $departmentMatches &&
                $educationLevelMatches &&
                $academicProgramMatches &&
                $gradeLevelMatches &&
                $sectionMatches
            ) {
                return true;
            }
        }

        return false;
    }


    /* ==========================================
       SURVEY RESPONSE AVAILABILITY
    ========================================== */

    public function isOpenForResponses(
        array $survey
    ): bool {
        if (
            strtolower(
                (string) (
                    $survey['workflow_status']
                    ?? ''
                )
            ) !== 'published'
        ) {
            return false;
        }

        if (
            strcasecmp(
                (string) (
                    $survey['status']
                    ?? ''
                ),
                'Published'
            ) !== 0
        ) {
            return false;
        }

        $now = time();

        if (
            !empty($survey['opens_at'])
        ) {
            $opensAt =
                strtotime(
                    (string) $survey['opens_at']
                );

            if (
                $opensAt !== false &&
                $now < $opensAt
            ) {
                return false;
            }
        }

        if (
            !empty($survey['closes_at'])
        ) {
            $closesAt =
                strtotime(
                    (string) $survey['closes_at']
                );

            if (
                $closesAt !== false &&
                $now > $closesAt
            ) {
                return false;
            }
        }

        return true;
    }


    /* ==========================================
       CHECK EXISTING RESPONSE
    ========================================== */

    /** Returns a set of answered survey IDs for a single visible feed. */
    public function getRespondedSurveyIds(array $surveyIds, int $userId): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $surveyIds), static fn(int $id): bool => $id > 0)));
        if ($userId <= 0 || $ids === []) { return []; }
        $responded = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = $this->conn->prepare("SELECT survey_id FROM survey_response WHERE user_id = ? AND survey_id IN ({$placeholders})");
            if (!$stmt) { throw new RuntimeException('Unable to check survey participation.'); }
            try {
                $values = [$userId, ...$chunk];
                $stmt->bind_param(str_repeat('i', count($values)), ...$values);
                if (!$stmt->execute()) { throw new RuntimeException('Unable to check survey participation.'); }
                foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                    $responded[(int) $row['survey_id']] = true;
                }
            } finally { $stmt->close(); }
        }
        return $responded;
    }
    public function hasResponded(
        int $surveyId,
        int $userId
    ): bool {
        if (
            $surveyId <= 0 ||
            $userId <= 0
        ) {
            return false;
        }

        $stmt =
            $this->conn->prepare("
                SELECT
                    response_id

                FROM survey_response

                WHERE survey_id = ?
                  AND user_id = ?

                LIMIT 1
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey response lookup: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $surveyId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to check survey participation: '
                    . $error
            );
        }

        $exists =
            $stmt
            ->get_result()
            ->num_rows > 0;

        $stmt->close();

        return $exists;
    }


    /* ==========================================
       SUBMIT COMPLETE SURVEY RESPONSE
    ========================================== */

    public function submitResponse(
        int $surveyId,
        int $userId,
        array $answers
    ): int {
        if (
            $surveyId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid survey or user ID.'
            );
        }

        $survey =
            $this->findPublishedById(
                $surveyId
            );

        if (!$survey) {
            throw new RuntimeException(
                'Survey is unavailable.'
            );
        }

        if (
            !$this->isOpenForResponses(
                $survey
            )
        ) {
            throw new RuntimeException(
                'This survey is not currently accepting responses.'
            );
        }

        if (
            $this->hasResponded(
                $surveyId,
                $userId
            )
        ) {
            throw new RuntimeException(
                'You have already submitted a response to this survey.'
            );
        }

        $questions =
            $survey['questions']
            ?? [];

        if (empty($questions)) {
            throw new RuntimeException(
                'This survey does not contain any questions.'
            );
        }

        $this->conn
            ->begin_transaction();

        try {
            $responseStmt =
                $this->conn->prepare("
                    INSERT INTO survey_response
                    (
                        survey_id,
                        user_id,
                        started_at,
                        submitted_at
                    )
                    VALUES (
                        ?,
                        ?,
                        NOW(),
                        NOW()
                    )
                ");

            if (!$responseStmt) {
                throw new RuntimeException(
                    'Unable to prepare survey response: '
                        . $this->conn->error
                );
            }

            $responseStmt->bind_param(
                'ii',
                $surveyId,
                $userId
            );

            if (
                !$responseStmt->execute()
            ) {
                $error =
                    $responseStmt->error;

                $responseStmt->close();

                throw new RuntimeException(
                    'Unable to create survey response: '
                        . $error
                );
            }

            $responseId =
                (int) $this->conn
                    ->insert_id;

            $responseStmt->close();

            $answerStmt =
                $this->conn->prepare("
                    INSERT INTO survey_answer
                    (
                        response_id,
                        question_id,
                        user_id,
                        answer,
                        submitted_at
                    )
                    VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        NOW()
                    )
                ");

            if (!$answerStmt) {
                throw new RuntimeException(
                    'Unable to prepare survey answer: '
                        . $this->conn->error
                );
            }

            $choiceStmt =
                $this->conn->prepare("
                    INSERT INTO survey_answer_choice
                    (
                        answer_id,
                        choice_id
                    )
                    VALUES (?, ?)
                ");

            if (!$choiceStmt) {
                $answerStmt->close();

                throw new RuntimeException(
                    'Unable to prepare survey choice answer: '
                        . $this->conn->error
                );
            }

            foreach (
                $questions as $question
            ) {
                $questionId =
                    (int) (
                        $question['question_id']
                        ?? 0
                    );

                $questionType =
                    (string) (
                        $question['question_type']
                        ?? ''
                    );

                $isRequired =
                    !empty($question['is_required']);

                $submittedValue =
                    $answers[$questionId]
                    ?? $answers[(string) $questionId]
                    ?? null;

                if (
                    $questionType ===
                    'Multiple Choice' ||
                    $questionType ===
                    'Checkbox'
                ) {
                    $selectedIds =
                        is_array(
                            $submittedValue
                        )
                        ? $submittedValue
                        : (
                            $submittedValue ===
                            null ||
                            $submittedValue ===
                            ''
                            ? []
                            : [
                                $submittedValue
                            ]
                        );

                    $selectedIds =
                        array_values(
                            array_unique(
                                array_filter(
                                    array_map(
                                        'intval',
                                        $selectedIds
                                    ),
                                    static fn(
                                        int $id
                                    ): bool =>
                                    $id > 0
                                )
                            )
                        );

                    if (
                        $isRequired &&
                        empty($selectedIds)
                    ) {
                        throw new InvalidArgumentException(
                            'Please answer all required survey questions.'
                        );
                    }

                    if (
                        $questionType ===
                        'Multiple Choice' &&
                        count(
                            $selectedIds
                        ) > 1
                    ) {
                        throw new InvalidArgumentException(
                            'A single-choice question accepts only one answer.'
                        );
                    }

                    $validChoiceIds =
                        array_map(
                            static fn(
                                array $choice
                            ): int =>
                            (int) (
                                $choice['choice_id']
                                ?? 0
                            ),
                            $question['choices']
                                ?? []
                        );

                    foreach (
                        $selectedIds as
                        $selectedId
                    ) {
                        if (
                            !in_array(
                                $selectedId,
                                $validChoiceIds,
                                true
                            )
                        ) {
                            throw new InvalidArgumentException(
                                'Invalid survey choice submitted.'
                            );
                        }
                    }

                    if (
                        empty($selectedIds)
                    ) {
                        continue;
                    }

                    $answerText =
                        null;

                    $answerStmt
                        ->bind_param(
                            'iiis',
                            $responseId,
                            $questionId,
                            $userId,
                            $answerText
                        );

                    if (
                        !$answerStmt
                            ->execute()
                    ) {
                        throw new RuntimeException(
                            'Unable to save survey answer: '
                                . $answerStmt
                                ->error
                        );
                    }

                    $answerId =
                        (int) $this->conn
                            ->insert_id;

                    foreach (
                        $selectedIds as
                        $selectedId
                    ) {
                        $choiceStmt
                            ->bind_param(
                                'ii',
                                $answerId,
                                $selectedId
                            );

                        if (
                            !$choiceStmt
                                ->execute()
                        ) {
                            throw new RuntimeException(
                                'Unable to save selected survey choice: '
                                    . $choiceStmt
                                    ->error
                            );
                        }
                    }

                    continue;
                }

                $answerText =
                    trim(
                        (string) (
                            $submittedValue
                            ?? ''
                        )
                    );

                if (
                    $isRequired &&
                    $answerText === ''
                ) {
                    throw new InvalidArgumentException(
                        'Please answer all required survey questions.'
                    );
                }

                if (
                    $answerText === ''
                ) {
                    continue;
                }

                if (
                    $questionType ===
                    'Rating'
                ) {
                    if (
                        !is_numeric(
                            $answerText
                        )
                    ) {
                        throw new InvalidArgumentException(
                            'Invalid rating value.'
                        );
                    }

                    $ratingValue =
                        (int) $answerText;

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

                    if (
                        $ratingValue <
                        $ratingMin ||
                        $ratingValue >
                        $ratingMax
                    ) {
                        throw new InvalidArgumentException(
                            'Rating is outside the allowed range.'
                        );
                    }

                    $answerText =
                        (string) $ratingValue;
                }

                if (
                    $questionType ===
                    'Yes/No'
                ) {
                    $normalized =
                        strtolower(
                            $answerText
                        );

                    if (
                        !in_array(
                            $normalized,
                            [
                                'yes',
                                'no'
                            ],
                            true
                        )
                    ) {
                        throw new InvalidArgumentException(
                            'Yes/No questions only accept Yes or No.'
                        );
                    }

                    $answerText =
                        $normalized ===
                        'yes'
                        ? 'Yes'
                        : 'No';
                }

                if (
                    mb_strlen(
                        $answerText
                    ) > 5000
                ) {
                    throw new InvalidArgumentException(
                        'Survey answer cannot exceed 5,000 characters.'
                    );
                }

                $answerStmt
                    ->bind_param(
                        'iiis',
                        $responseId,
                        $questionId,
                        $userId,
                        $answerText
                    );

                if (
                    !$answerStmt
                        ->execute()
                ) {
                    throw new RuntimeException(
                        'Unable to save survey answer: '
                            . $answerStmt
                            ->error
                    );
                }
            }

            $choiceStmt->close();
            $answerStmt->close();

            $this->conn->commit();

            return $responseId;
        } catch (
            Throwable $exception
        ) {
            $this->conn->rollback();

            throw $exception;
        }
    }

    /* ==========================================
       SURVEY RESULTS
    ========================================== */

    public function getResults(
        int $surveyId
    ): array {
        if ($surveyId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        $questions =
            $this->getQuestions(
                $surveyId
            );

        $countStmt =
            $this->conn->prepare("
                SELECT
                    COUNT(DISTINCT user_id) AS response_count

                FROM survey_response

                WHERE survey_id = ?
            ");

        if (!$countStmt) {
            throw new RuntimeException(
                'Unable to prepare survey response count: '
                    . $this->conn->error
            );
        }

        $countStmt->bind_param(
            'i',
            $surveyId
        );

        if (!$countStmt->execute()) {
            $error =
                $countStmt->error;

            $countStmt->close();

            throw new RuntimeException(
                'Unable to load survey response count: '
                    . $error
            );
        }

        $countRow =
            $countStmt
            ->get_result()
            ->fetch_assoc();

        $responseCount =
            (int) (
                $countRow['response_count']
                ?? 0
            );

        $countStmt->close();

        // Count people who actually answered each question, not checkbox selections.
        $answerCounts = [];
        $answerStmt = $this->conn->prepare("
            SELECT sa.question_id, COUNT(DISTINCT sr.user_id) AS total
            FROM survey_answer sa
            INNER JOIN survey_response sr ON sr.response_id = sa.response_id
            WHERE sr.survey_id = ?
              AND (TRIM(COALESCE(sa.answer, '')) <> '' OR EXISTS (
                  SELECT 1 FROM survey_answer_choice sac WHERE sac.answer_id = sa.answer_id
              ))
            GROUP BY sa.question_id
        ");
        if (!$answerStmt) throw new RuntimeException('Unable to prepare survey privacy counts.');
        try {
            $answerStmt->bind_param('i', $surveyId);
            if (!$answerStmt->execute()) throw new RuntimeException('Unable to load survey privacy counts.');
            foreach ($answerStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                $answerCounts[(int) $row['question_id']] = (int) $row['total'];
            }
        } finally {
            $answerStmt->close();
        }
        $privacySuppressed = $responseCount > 0 && $responseCount < self::RESULTS_MINIMUM_GROUP_SIZE;

        foreach (
            $questions as &$question
        ) {
            $questionId =
                (int) (
                    $question['question_id']
                    ?? 0
                );

            $questionType =
                (string) (
                    $question['question_type']
                    ?? ''
                );

            $question['response_count'] = 0;

            $question['average_rating'] = null;

            $question['text_answers'] = [];

            $question['privacy_suppressed'] = $privacySuppressed
                || ($answerCounts[$questionId] ?? 0) < self::RESULTS_MINIMUM_GROUP_SIZE;
            if ($question['privacy_suppressed']) {
                $this->suppressResultQuestion($question);
                continue;
            }

            /* ==================================
               CHOICE QUESTIONS
            ================================== */

            if (
                $questionType ===
                'Multiple Choice' ||
                $questionType ===
                'Checkbox'
            ) {
                foreach (
                    $question['choices']
                    as &$choice
                ) {
                    $choiceId =
                        (int) (
                            $choice['choice_id']
                            ?? 0
                        );

                    $choiceStmt =
                        $this->conn
                        ->prepare("
                                SELECT
                                    COUNT(DISTINCT sr.user_id) AS total

                                FROM
                                    survey_answer_choice sac

                                INNER JOIN
                                    survey_answer sa
                                    ON sa.answer_id =
                                       sac.answer_id

                                INNER JOIN
                                    survey_response sr
                                    ON sr.response_id =
                                       sa.response_id

                                WHERE
                                    sr.survey_id = ?

                                    AND
                                    sa.question_id = ?

                                    AND
                                    sac.choice_id = ?
                            ");

                    if (!$choiceStmt) {
                        throw new RuntimeException(
                            'Unable to prepare survey choice result: '
                                . $this->conn->error
                        );
                    }

                    $choiceStmt
                        ->bind_param(
                            'iii',
                            $surveyId,
                            $questionId,
                            $choiceId
                        );

                    if (
                        !$choiceStmt
                            ->execute()
                    ) {
                        $error =
                            $choiceStmt
                            ->error;

                        $choiceStmt
                            ->close();

                        throw new RuntimeException(
                            'Unable to load survey choice result: '
                                . $error
                        );
                    }

                    $row =
                        $choiceStmt
                        ->get_result()
                        ->fetch_assoc();

                    $choice['response_count'] =
                        (int) (
                            $row['total']
                            ?? 0
                        );

                    $choiceStmt->close();
                }

                unset($choice);

                foreach ($question['choices'] as $choiceResult) {
                    if ($choiceResult['response_count'] > 0
                        && $choiceResult['response_count'] < self::RESULTS_MINIMUM_GROUP_SIZE) {
                        $this->suppressResultQuestion($question);
                        break;
                    }
                }
                continue;
            }

            /* ==================================
               RATING QUESTIONS
            ================================== */

            if (
                $questionType ===
                'Rating'
            ) {
                $ratingStmt =
                    $this->conn
                    ->prepare("
                            SELECT
                                COUNT(DISTINCT sr.user_id) AS total,

                                AVG(
                                    CAST(
                                        sa.answer
                                        AS DECIMAL(10,2)
                                    )
                                ) AS average_rating

                            FROM survey_answer sa

                            INNER JOIN
                                survey_response sr
                                ON sr.response_id =
                                   sa.response_id

                            WHERE
                                sr.survey_id = ?

                                AND
                                sa.question_id = ?

                                AND
                                sa.answer
                                    IS NOT NULL

                                AND
                                sa.answer <> ''
                        ");

                if (!$ratingStmt) {
                    throw new RuntimeException(
                        'Unable to prepare survey rating result: '
                            . $this->conn->error
                    );
                }

                $ratingStmt
                    ->bind_param(
                        'ii',
                        $surveyId,
                        $questionId
                    );

                if (
                    !$ratingStmt
                        ->execute()
                ) {
                    $error =
                        $ratingStmt
                        ->error;

                    $ratingStmt
                        ->close();

                    throw new RuntimeException(
                        'Unable to load survey rating result: '
                            . $error
                    );
                }

                $row =
                    $ratingStmt
                    ->get_result()
                    ->fetch_assoc();

                $question['response_count'] =
                    (int) (
                        $row['total']
                        ?? 0
                    );

                $question['average_rating'] =
                    $row['average_rating'] !== null
                    ? round(
                        (float) $row['average_rating'],
                        2
                    )
                    : null;

                $ratingStmt->close();

                continue;
            }

            /* ==================================
               TEXT / YES-NO QUESTIONS
            ================================== */

            $textStmt =
                $this->conn
                ->prepare("
                        SELECT
                            DISTINCT sr.user_id, sa.answer

                        FROM survey_answer sa

                        INNER JOIN
                            survey_response sr
                            ON sr.response_id =
                               sa.response_id

                        WHERE
                            sr.survey_id = ?

                            AND
                            sa.question_id = ?

                            AND
                            sa.answer
                                IS NOT NULL

                            AND
                            sa.answer <> ''

                        ORDER BY
                            sa.answer ASC
                    ");

            if (!$textStmt) {
                throw new RuntimeException(
                    'Unable to prepare survey text results: '
                        . $this->conn->error
                );
            }

            $textStmt
                ->bind_param(
                    'ii',
                    $surveyId,
                    $questionId
                );

            if (!$textStmt->execute()) {
                $error =
                    $textStmt->error;

                $textStmt->close();

                throw new RuntimeException(
                    'Unable to load survey text results: '
                        . $error
                );
            }

            $textAnswers =
                $textStmt
                ->get_result()
                ->fetch_all(
                    MYSQLI_ASSOC
                );

            $textStmt->close();

            $question['response_count'] =
                count(
                    $textAnswers
                );

            $question['text_answers'] = array_map(
                static fn(array $answer): array => ['answer' => $answer['answer']],
                $textAnswers
            );

            if ($questionType === 'Yes/No') {
                foreach (array_count_values(array_column($textAnswers, 'answer')) as $count) {
                    if ($count < self::RESULTS_MINIMUM_GROUP_SIZE) {
                        $this->suppressResultQuestion($question);
                        break;
                    }
                }
            }
        }

        unset($question);

        return [
            'privacy_suppressed' => $privacySuppressed,
            'minimum_group_size' => self::RESULTS_MINIMUM_GROUP_SIZE,
            'response_count' =>
            $privacySuppressed ? null : $responseCount,

            'questions' =>
            $questions
        ];
    }

    private function suppressResultQuestion(array &$question): void
    {
        $question['privacy_suppressed'] = true;
        $question['response_count'] = null;
        $question['average_rating'] = null;
        $question['text_answers'] = [];
        foreach ($question['choices'] as &$choice) {
            $choice['response_count'] = null;
        }
        unset($choice);
    }

    /* ==========================================
       GET SURVEYS DUE FOR RELEASE
    ========================================== */

    public function getDueForRelease(
        int $limit = 100
    ): array {
        $limit = max(
            1,
            min(
                $limit,
                250
            )
        );

        $stmt =
            $this->conn->prepare("
                SELECT
                    s.survey_id,
                    s.title,
                    s.release_mode,
                    s.scheduled_publish_at,
                    s.calendar_event_id

                FROM survey s

                LEFT JOIN events e
                    ON e.event_id =
                       s.calendar_event_id

                WHERE
                    s.workflow_status =
                        'scheduled'

                    AND
                    (
                        (
                            s.release_mode =
                                'scheduled'

                            AND
                            s.scheduled_publish_at
                                IS NOT NULL

                            AND
                            s.scheduled_publish_at
                                <= NOW()
                        )

                        OR

                        (
                            s.release_mode =
                                'calendar'

                            AND
                            e.event_id
                                IS NOT NULL

                            AND
                            e.status =
                                'active'

                            AND
                            e.workflow_status =
                                'published'

                            AND
                            e.event_date
                                <= NOW()
                        )
                    )

                ORDER BY
                    COALESCE(
                        s.scheduled_publish_at,
                        e.event_date
                    ) ASC

                LIMIT ?
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare due survey releases: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load due survey releases: '
                    . $error
            );
        }

        $surveys =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $surveys;
    }

    /* ==========================================
       PUBLISH DUE SURVEY
    ========================================== */

    public function publishDueSurvey(
        int $surveyId
    ): bool {
        if ($surveyId <= 0) {
            throw new InvalidArgumentException(
                'Invalid survey ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
                UPDATE survey

                SET
                    workflow_status =
                        'published',

                    status =
                        'Published',

                    published_at =
                        NOW()

                WHERE survey_id = ?
                  AND workflow_status =
                        'scheduled'
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey publication: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $surveyId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to publish survey: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
       GET RECENT PUBLISHED SURVEYS
    ========================================== */

    public function getRecent(
        int $limit = 10
    ): array {
        $limit = max(
            1,
            min(
                $limit,
                100
            )
        );

        $stmt =
            $this->conn->prepare("
                SELECT
                    s.survey_id,
                    s.title,
                    s.description,

                    s.status,
                    s.workflow_status,

                    s.release_mode,
                    s.scheduled_publish_at,
                    s.calendar_event_id,
                    s.published_at,

                    s.opens_at,
                    s.closes_at,

                    s.allow_comments,
                    s.allow_reactions,
                    s.require_acknowledgment,
                    s.send_notification,

                    s.created_at,
                    s.user_id,

                    TRIM(
                        CONCAT(
                            COALESCE(
                                u.first_name,
                                ''
                            ),
                            ' ',
                            COALESCE(
                                u.last_name,
                                ''
                            )
                        )
                    ) AS author_name,

                    (
                        SELECT COUNT(*)

                        FROM survey_response sr

                        WHERE sr.survey_id =
                            s.survey_id
                    ) AS response_count,

                    (
                        SELECT COUNT(*)

                        FROM survey_question sq

                        WHERE sq.survey_id =
                            s.survey_id
                    ) AS question_count

                FROM survey s

                LEFT JOIN user u
                    ON u.user_id =
                        s.user_id

                WHERE s.status =
                        'Published'

                  AND s.workflow_status =
                        'published'

                  AND (
                        s.opens_at IS NULL
                        OR
                        s.opens_at <= NOW()
                  )

                  AND (
                        s.closes_at IS NULL
                        OR
                        s.closes_at > NOW()
                  )

                ORDER BY
                    COALESCE(
                        s.published_at,
                        s.created_at
                    ) DESC

                LIMIT ?
            ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare survey feed: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $limit
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load survey feed: '
                    . $error
            );
        }

        $surveys =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $surveys;
    }
}
