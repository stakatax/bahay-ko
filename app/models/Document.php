<?php

require_once __DIR__ . '/BaseModel.php';

class Document extends BaseModel
{
    /* ==========================================
       CREATE DOCUMENT

       Supports:
       1. Legacy positional arguments
       2. New workflow-ready data array
    ========================================== */

    public function create(
        $dataOrFileName,
        $filePath = null,
        $fileType = null,
        $fileSize = null,
        $coverImagePath = null,
        $userId = null
    ): int {
        /*
         * Legacy compatibility:
         *
         * create(
         *     $fileName,
         *     $filePath,
         *     $fileType,
         *     $fileSize,
         *     $coverImagePath,
         *     $userId
         * )
         */
        if (!is_array($dataOrFileName)) {
            $data = [
                'title' =>
                pathinfo(
                    (string) $dataOrFileName,
                    PATHINFO_FILENAME
                ),

                'description' =>
                'Document file: '
                    . (string) $dataOrFileName,

                'file_name' =>
                $dataOrFileName,

                'file_path' =>
                $filePath,

                'file_type' =>
                $fileType,

                'file_size' =>
                $fileSize,

                'cover_image_path' =>
                $coverImagePath,

                'user_id' =>
                $userId,

                'status' =>
                'active',

                'workflow_status' =>
                'published',

                'release_mode' =>
                'immediate',

                'scheduled_publish_at' =>
                null,

                'calendar_event_id' =>
                null,

                'send_notification' =>
                1,

                'allow_reactions' =>
                1,

                'allow_comments' =>
                1,

                'require_acknowledgment' =>
                0
            ];
        } else {
            $data =
                $dataOrFileName;
        }

        $documentTitle = trim(
            (string) (
                $data['title']
                ?? ''
            )
        );

        $documentDescription = trim(
            (string) (
                $data['description']
                ?? ''
            )
        );

        if ($documentTitle === '') {
            throw new InvalidArgumentException(
                'Document title is required.'
            );
        }

        if ($documentDescription === '') {
            throw new InvalidArgumentException(
                'Document description is required.'
            );
        }

        if (
            mb_strlen(
                $documentTitle
            ) > 100
        ) {
            throw new InvalidArgumentException(
                'Document title cannot exceed 100 characters.'
            );
        }

        if (
            mb_strlen(
                $documentDescription
            ) > 5000
        ) {
            throw new InvalidArgumentException(
                'Document description cannot exceed 5,000 characters.'
            );
        }

        $documentFileName = trim(
            (string) (
                $data['file_name']
                ?? ''
            )
        );

        $documentFilePath = trim(
            (string) (
                $data['file_path']
                ?? ''
            )
        );

        $documentFileType = strtolower(
            trim(
                (string) (
                    $data['file_type']
                    ?? ''
                )
            )
        );

        $documentFileSize = (int) (
            $data['file_size']
            ?? 0
        );

        $documentCoverPath =
            !empty($data['cover_image_path'])
            ? trim(
                (string) $data['cover_image_path']
            )
            : null;

        $documentUserId = (int) (
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

        $sendNotification =
            !empty($data['send_notification'])
            ? 1
            : 0;

        $allowReactions =
            !empty($data['allow_reactions'])
            ? 1
            : 0;

        $allowComments =
            !empty($data['allow_comments'])
            ? 1
            : 0;

        $requireAcknowledgment =
            !empty($data['require_acknowledgment'])
            ? 1
            : 0;

        /* ======================================
           VALIDATION
        ======================================= */

        if ($documentTitle === '') {
            throw new InvalidArgumentException(
                'Document title is required.'
            );
        }

        if ($documentDescription === '') {
            throw new InvalidArgumentException(
                'Document description is required.'
            );
        }

        if (
            mb_strlen(
                $documentTitle
            ) > 100
        ) {
            throw new InvalidArgumentException(
                'Document title cannot exceed 100 characters.'
            );
        }

        if (
            mb_strlen(
                $documentDescription
            ) > 5000
        ) {
            throw new InvalidArgumentException(
                'Document description cannot exceed 5,000 characters.'
            );
        }

        if ($documentFileName === '') {
            throw new InvalidArgumentException(
                'Document file name is required.'
            );
        }

        if ($documentFilePath === '') {
            throw new InvalidArgumentException(
                'Document file path is required.'
            );
        }

        if ($documentFileType === '') {
            throw new InvalidArgumentException(
                'Document file type is required.'
            );
        }

        if ($documentFileSize <= 0) {
            throw new InvalidArgumentException(
                'Invalid document file size.'
            );
        }

        if ($documentUserId <= 0) {
            throw new InvalidArgumentException(
                'A valid document uploader is required.'
            );
        }

        if (
            mb_strlen(
                $documentFileName
            ) > 255
        ) {
            throw new InvalidArgumentException(
                'Document file name cannot exceed 255 characters.'
            );
        }

        if (
            mb_strlen(
                $documentFilePath
            ) > 500
        ) {
            throw new InvalidArgumentException(
                'Document file path cannot exceed 500 characters.'
            );
        }

        if (
            $documentCoverPath !== null &&
            mb_strlen(
                $documentCoverPath
            ) > 500
        ) {
            throw new InvalidArgumentException(
                'Document cover path cannot exceed 500 characters.'
            );
        }

        if (
            mb_strlen(
                $documentFileType
            ) > 100
        ) {
            throw new InvalidArgumentException(
                'Document file type is invalid.'
            );
        }

        $allowedFileTypes = [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'txt'
        ];

        if (
            !in_array(
                $documentFileType,
                $allowedFileTypes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported document file type.'
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
                'Invalid document workflow status.'
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
                'Invalid document release mode.'
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

        /*
         * Only published documents should be visible
         * in the Information Hub.
         */
        $status =
            $workflowStatus === 'published'
            ? 'active'
            : 'inactive';

        $stmt =
            $this->conn->prepare("
        INSERT INTO documents
        (
            title,
            description,
            file_name,
            file_path,
            cover_image_path,
            file_type,
            file_size,
            created_at,
            status,
            workflow_status,
            release_mode,
            scheduled_publish_at,
            calendar_event_id,
            send_notification,
            allow_reactions,
            allow_comments,
            require_acknowledgment,
            user_id
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
            NOW(),
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");
        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document insert: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssssssissssiiiiii',
            $documentTitle,
            $documentDescription,
            $documentFileName,
            $documentFilePath,
            $documentCoverPath,
            $documentFileType,
            $documentFileSize,
            $status,
            $workflowStatus,
            $releaseMode,
            $scheduledPublishAt,
            $calendarEventId,
            $sendNotification,
            $allowReactions,
            $allowComments,
            $requireAcknowledgment,
            $documentUserId
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to save document: '
                    . $error
            );
        }

        $documentId =
            (int) $this->conn->insert_id;

        $stmt->close();

        return $documentId;
    }

    /* ==========================================
       SAVE DOCUMENT TARGETS

       Accepted fields:
       - role_id
       - department_id
       - education_level_id
       - academic_program_id
       - grade_level_id
       - section_id
    ========================================== */

    public function saveTargets(
        int $documentId,
        array $targets
    ): void {
        if ($documentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid document ID.'
            );
        }

        $deleteStmt =
            $this->conn->prepare("
                DELETE FROM document_target
                WHERE document_id = ?
            ");

        if (!$deleteStmt) {
            throw new RuntimeException(
                'Unable to prepare document target cleanup: '
                    . $this->conn->error
            );
        }

        $deleteStmt->bind_param(
            'i',
            $documentId
        );

        if (!$deleteStmt->execute()) {
            $error =
                $deleteStmt->error;

            $deleteStmt->close();

            throw new RuntimeException(
                'Unable to clear document targets: '
                    . $error
            );
        }

        $deleteStmt->close();

        if (empty($targets)) {
            return;
        }

        $insertStmt =
            $this->conn->prepare("
                INSERT INTO document_target
                (
                    document_id,
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
                'Unable to prepare document target insert: '
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

            /*
             * Ignore completely empty target rows.
             */
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

            $signature = implode(
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
                    $insertedTargets[$signature]
                )
            ) {
                continue;
            }

            $insertStmt->bind_param(
                'iiiiiii',
                $documentId,
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
                    'Unable to save document target: '
                        . $error
                );
            }

            $insertedTargets[$signature] = true;
        }

        $insertStmt->close();
    }

    /* ==========================================
   GET DOCUMENT TARGETS
========================================== */

    public function getTargets(
        int $documentId
    ): array {
        if ($documentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid document ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            SELECT
                dt.role_id,
                dt.department_id,
                dt.education_level_id,
                dt.academic_program_id,
                dt.grade_level_id,
                dt.section_id,
                r.role_prefix

            FROM document_target dt

            LEFT JOIN role r
                ON r.role_id =
                   dt.role_id

            WHERE dt.document_id = ?

            ORDER BY dt.target_id ASC
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document targets: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $documentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load document targets: '
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
   UPDATE DOCUMENT
========================================== */

    public function update(
        int $documentId,
        array $data,
        int $userId
    ): bool {
        if (
            $documentId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid document or user ID.'
            );
        }


        $documentTitle = trim(
            (string) (
                $data['title']
                ?? ''
            )
        );

        $documentDescription = trim(
            (string) (
                $data['description']
                ?? ''
            )
        );


        $documentFileName = trim(
            (string) (
                $data['file_name']
                ?? ''
            )
        );

        $documentFilePath = trim(
            (string) (
                $data['file_path']
                ?? ''
            )
        );

        $documentFileType = strtolower(
            trim(
                (string) (
                    $data['file_type']
                    ?? ''
                )
            )
        );

        $documentFileSize = (int) (
            $data['file_size']
            ?? 0
        );

        $documentCoverPath =
            !empty($data['cover_image_path'])
            ? trim(
                (string) $data['cover_image_path']
            )
            : null;

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

        $sendNotification =
            !empty($data['send_notification'])
            ? 1
            : 0;

        $allowReactions =
            !empty($data['allow_reactions'])
            ? 1
            : 0;

        $allowComments =
            !empty($data['allow_comments'])
            ? 1
            : 0;

        $requireAcknowledgment =
            !empty($data['require_acknowledgment'])
            ? 1
            : 0;

        /* ======================================
       VALIDATION
    ======================================= */

        if ($documentFileName === '') {
            throw new InvalidArgumentException(
                'Document file name is required.'
            );
        }

        if ($documentFilePath === '') {
            throw new InvalidArgumentException(
                'Document file path is required.'
            );
        }

        if ($documentFileType === '') {
            throw new InvalidArgumentException(
                'Document file type is required.'
            );
        }

        if ($documentFileSize <= 0) {
            throw new InvalidArgumentException(
                'Invalid document file size.'
            );
        }

        if (
            mb_strlen(
                $documentFileName
            ) > 255
        ) {
            throw new InvalidArgumentException(
                'Document file name cannot exceed 255 characters.'
            );
        }

        if (
            mb_strlen(
                $documentFilePath
            ) > 500
        ) {
            throw new InvalidArgumentException(
                'Document file path cannot exceed 500 characters.'
            );
        }

        if (
            $documentCoverPath !== null &&
            mb_strlen(
                $documentCoverPath
            ) > 500
        ) {
            throw new InvalidArgumentException(
                'Document cover path cannot exceed 500 characters.'
            );
        }

        if (
            mb_strlen(
                $documentFileType
            ) > 100
        ) {
            throw new InvalidArgumentException(
                'Document file type is invalid.'
            );
        }

        $allowedFileTypes = [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'txt'
        ];

        if (
            !in_array(
                $documentFileType,
                $allowedFileTypes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported document file type.'
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
                'Invalid document workflow status.'
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
                'Invalid document release mode.'
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

        if ($releaseMode !== 'scheduled') {
            $scheduledPublishAt = null;
        }

        if ($releaseMode !== 'calendar') {
            $calendarEventId = null;
        }

        $status =
            $workflowStatus === 'published'
            ? 'active'
            : 'inactive';

        $stmt =
            $this->conn->prepare("
            UPDATE documents

SET
    title = ?,
    description = ?,
    file_name = ?,
    file_path = ?,
                cover_image_path = ?,
                file_type = ?,
                file_size = ?,
                status = ?,
                workflow_status = ?,
                release_mode = ?,
                scheduled_publish_at = ?,
                calendar_event_id = ?,
                send_notification = ?,
                allow_reactions = ?,
                allow_comments = ?,
                require_acknowledgment = ?,
                reviewed_by = NULL,
                reviewed_at = NULL,
                review_notes = NULL

            WHERE document_id = ?
              AND user_id = ?
              AND workflow_status IN (
                    'draft',
                    'rejected'
              )

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document update: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssssssissssiiiiiii',
            $documentTitle,
            $documentDescription,
            $documentFileName,
            $documentFilePath,
            $documentCoverPath,
            $documentFileType,
            $documentFileSize,
            $status,
            $workflowStatus,
            $releaseMode,
            $scheduledPublishAt,
            $calendarEventId,
            $sendNotification,
            $allowReactions,
            $allowComments,
            $requireAcknowledgment,
            $documentId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to update document: '
                    . $error
            );
        }

        /*
 * MySQL reports zero affected rows when the
 * submitted values match the existing row.
 * The update is still considered successful
 * because execute() already completed.
 */
        $updated =
            $stmt->affected_rows >= 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
       RECENT PUBLISHED DOCUMENTS
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
                d.document_id,
                d.title,
                d.description,
                d.file_name,
                d.file_path,
                d.cover_image_path,
                d.file_type,
                d.file_size,
                d.created_at,
                d.status,
                d.workflow_status,
                d.send_notification,
                d.user_id,

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

            FROM documents d

            LEFT JOIN user u
                ON u.user_id =
                    d.user_id

            WHERE d.status =
                    'active'

              AND d.workflow_status =
                    'published'

            ORDER BY
                d.created_at DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare recent documents: '
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
                'Unable to load recent documents: '
                    . $error
            );
        }

        $documents =
            $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $documents;
    }

    /* ==========================================
   COUNT PUBLISHED DOCUMENTS
========================================== */

    public function countPublished(): int
    {
        $stmt =
            $this->conn->prepare("
            SELECT COUNT(*) AS total
            FROM documents
            WHERE status = 'active'
              AND workflow_status = 'published'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare published document count: '
                    . $this->conn->error
            );
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to count published documents: '
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
       FIND PUBLISHED DOCUMENT
    ========================================== */

    public function findById(
        int $documentId
    ): ?array {
        if ($documentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid document ID.'
            );
        }

        $stmt = $this->conn->prepare("
        SELECT
            d.document_id,
            d.file_name,
            d.file_path,
            d.cover_image_path,
            d.file_type,
            d.file_size,

            d.allow_reactions,
            d.allow_comments,
            d.require_acknowledgment,

            d.created_at,
            d.status,
            d.workflow_status,
            d.send_notification,
            d.user_id,

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

        FROM documents d

        LEFT JOIN user u
            ON u.user_id = d.user_id

        WHERE d.document_id = ?
          AND d.status = 'active'
          AND d.workflow_status = 'published'

        LIMIT 1
    ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document details: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $documentId
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load document details: '
                    . $error
            );
        }

        $document = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $document ?: null;
    }

    /* ==========================================
       WORKFLOW QUERIES
    ========================================== */

    public function getByWorkflowStatus(
        string $workflowStatus,
        int $limit = 100,
        ?int $ownerId = null
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
                'Invalid document workflow status.'
            );
        }

        $limit = max(
            1,
            min(
                $limit,
                250
            )
        );

        if ($ownerId !== null && $ownerId <= 0) {
            throw new InvalidArgumentException('Invalid workspace owner.');
        }
        $ownerFilter = $ownerId === null ? '' : ' AND d.user_id = ?';
        $stmt = $this->conn->prepare("
            SELECT
                d.document_id,
                d.file_name,
                d.file_path,
                d.cover_image_path,
                d.file_type,
                d.file_size,
                d.created_at,
                d.status,
              d.workflow_status,

d.release_mode,
d.scheduled_publish_at,
d.calendar_event_id,

linked_event.title
    AS linked_event_title,

linked_event.event_date
    AS linked_event_date,

d.reviewed_by,
                d.reviewed_at,
                d.review_notes,
                d.send_notification,
                d.user_id,

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

            FROM documents d

           LEFT JOIN user u
    ON u.user_id = d.user_id

LEFT JOIN events linked_event
    ON linked_event.event_id =
       d.calendar_event_id

WHERE d.workflow_status = ? {$ownerFilter}

            ORDER BY d.created_at DESC

            LIMIT ?
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document workflow list: '
                    . $this->conn->error
            );
        }

        if ($ownerId === null) {
            $stmt->bind_param('si', $workflowStatus, $limit);
        } else {
            $stmt->bind_param('sii', $workflowStatus, $ownerId, $limit);
        }

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load document workflow list: '
                    . $error
            );
        }

        $documents = $stmt
            ->get_result()
            ->fetch_all(
                MYSQLI_ASSOC
            );

        $stmt->close();

        return $documents;
    }


    /* ==========================================
   FIND DOCUMENT FOR WORKSPACE
========================================== */

    public function findWorkflowItemById(
        int $documentId
    ): ?array {
        if ($documentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid document ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            SELECT
    d.document_id,
    d.title,
    d.description,
    d.file_name,
    d.file_path,
                d.cover_image_path,
                d.file_type,
                d.file_size,

                d.allow_reactions,
                d.allow_comments,
                d.require_acknowledgment,
                d.send_notification,

                d.created_at,
                d.status,
                d.workflow_status,

                d.release_mode,
                d.scheduled_publish_at,
                d.calendar_event_id,

                d.reviewed_by,
                d.reviewed_at,
                d.review_notes,

                d.user_id,

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
                ) AS reviewer_name

            FROM documents d

            LEFT JOIN user creator
                ON creator.user_id =
                   d.user_id

            LEFT JOIN user reviewer
                ON reviewer.user_id =
                   d.reviewed_by

            WHERE d.document_id = ?

            LIMIT 1
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document workspace details: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $documentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to load document workspace details: '
                    . $error
            );
        }

        $document =
            $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        return $document ?: null;
    }
    /* ==========================================
   SUBMIT DOCUMENT FOR REVIEW
========================================== */

    public function submitForReview(
        int $documentId,
        int $userId
    ): bool {
        if (
            $documentId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid document or user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE documents

            SET
                workflow_status =
                    'pending_review',

                status =
                    'inactive',

                reviewed_by =
                    NULL,

                reviewed_at =
                    NULL,

                review_notes =
                    NULL

            WHERE document_id = ?
              AND user_id = ?
              AND workflow_status IN (
                    'draft',
                    'rejected'
              )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document review submission: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $documentId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to submit document for review: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   APPROVE AND PUBLISH DOCUMENT
========================================== */

    public function approve(
        int $documentId,
        int $reviewerId
    ): bool {
        if (
            $documentId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid document or reviewer ID.'
            );
        }

        $lookup =
            $this->conn->prepare("
            SELECT
                release_mode

            FROM documents

            WHERE document_id = ?
              AND workflow_status =
                  'pending_review'

            LIMIT 1
        ");

        if (!$lookup) {
            throw new RuntimeException(
                'Unable to prepare Document approval lookup: '
                    . $this->conn->error
            );
        }

        $lookup->bind_param(
            'i',
            $documentId
        );

        if (!$lookup->execute()) {
            $error =
                $lookup->error;

            $lookup->close();

            throw new RuntimeException(
                'Unable to load the Document for approval: '
                    . $error
            );
        }

        $document =
            $lookup
            ->get_result()
            ->fetch_assoc();

        $lookup->close();

        if (!$document) {
            return false;
        }

        $releaseMode =
            strtolower(
                trim(
                    (string) (
                        $document['release_mode']
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
            $workflowStatus === 'published'
            ? 'active'
            : 'inactive';

        $stmt =
            $this->conn->prepare("
            UPDATE documents

            SET
                workflow_status = ?,
                status = ?,
                reviewed_by = ?,
                reviewed_at = NOW(),
                review_notes = NULL

            WHERE document_id = ?
              AND workflow_status =
                  'pending_review'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Document approval: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ssii',
            $workflowStatus,
            $status,
            $reviewerId,
            $documentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to approve Document: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   REJECT DOCUMENT
========================================== */

    public function reject(
        int $documentId,
        int $reviewerId,
        string $reviewNotes
    ): bool {
        if (
            $documentId <= 0 ||
            $reviewerId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid document or reviewer ID.'
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
            UPDATE documents

            SET
                workflow_status =
                    'rejected',

                status =
                    'inactive',

                reviewed_by =
                    ?,

                reviewed_at =
                    NOW(),

                review_notes =
                    ?

            WHERE document_id = ?
              AND workflow_status =
                  'pending_review'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document rejection: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'isi',
            $reviewerId,
            $reviewNotes,
            $documentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to reject document: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   ARCHIVE DOCUMENT
========================================== */

    public function archive(
        int $documentId
    ): bool {
        if ($documentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid document ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE documents

            SET
                workflow_status =
                    'archived',

                status =
                    'inactive'

            WHERE document_id = ?
              AND workflow_status =
                  'published'
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document archive: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'i',
            $documentId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to archive document: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }

    /* ==========================================
   RESTORE DOCUMENT TO DRAFT
========================================== */

    public function restoreToDraft(
        int $documentId,
        int $userId
    ): bool {
        if (
            $documentId <= 0 ||
            $userId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid document or user ID.'
            );
        }

        $stmt =
            $this->conn->prepare("
            UPDATE documents

            SET
                workflow_status =
                    'draft',

                status =
                    'inactive',

                reviewed_by =
                    NULL,

                reviewed_at =
                    NULL,

                review_notes =
                    NULL

            WHERE document_id = ?
              AND user_id = ?
              AND workflow_status IN (
                    'rejected',
                    'archived'
              )
        ");

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare document draft restoration: '
                    . $this->conn->error
            );
        }

        $stmt->bind_param(
            'ii',
            $documentId,
            $userId
        );

        if (!$stmt->execute()) {
            $error =
                $stmt->error;

            $stmt->close();

            throw new RuntimeException(
                'Unable to restore document to draft: '
                    . $error
            );
        }

        $updated =
            $stmt->affected_rows > 0;

        $stmt->close();

        return $updated;
    }
}
