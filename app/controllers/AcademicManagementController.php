<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/AcademicManagementService.php';

class AcademicManagementController extends BaseController
{
    private AcademicManagementService $service;

    public function __construct()
    {
        $this->service =
            new AcademicManagementService();
    }

    /* ==========================================
       PAGE
    ========================================== */

    public function index(): array
    {
        $this->requireRole(
            'Admin'
        );

        $flash =
            $_SESSION['academic_management_flash'] ?? null;

        unset(
            $_SESSION['academic_management_flash']
        );

        try {
            return array_merge(
                $this->service
                    ->getDirectory(),
                [
                    'flash' =>
                    $flash,

                    'selected_type' =>
                    trim(
                        (string) (
                            $_GET['type']
                            ?? ''
                        )
                    ),

                    'selected_id' =>
                    max(
                        0,
                        (int) (
                            $_GET['entity_id']
                            ?? 0
                        )
                    )
                ]
            );
        } catch (Throwable $exception) {
            error_log(
                'Academic Management page error: '
                    . publicErrorMessage($exception)
            );

            return [
                'departments' => [],
                'education_levels' => [],
                'academic_programs' => [],
                'grade_levels' => [],
                'sections' => [],
                'history' => [],

                'counts' => [
                    'departments' => 0,
                    'education_levels' => 0,
                    'academic_programs' => 0,
                    'grade_levels' => 0,
                    'sections' => 0
                ],

                'flash' => [
                    'type' => 'error',
                    'message' =>
                    'The academic structure could not be loaded.'
                ],

                'selected_type' => '',
                'selected_id' => 0
            ];
        }
    }

    /* ==========================================
       CREATE
    ========================================== */

    public function create(): void
    {
        $this->requireRole(
            'Admin'
        );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $this->requireScalarFormValues();

            $entityType =
                trim(
                    (string) (
                        $_POST['entity_type']
                        ?? ''
                    )
                );

            $reason =
                trim(
                    (string) (
                        $_POST['reason']
                        ?? ''
                    )
                );

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $createdEntity =
                $this->service
                ->create(
                    $entityType,
                    $_POST,
                    $adminId,
                    $reason
                );

            $entityId =
                $this->extractEntityId(
                    $entityType,
                    $createdEntity
                );

            $entityName =
                $this->extractEntityName(
                    $entityType,
                    $createdEntity
                );

            try {
                $this->log(
                    'CREATE_ACADEMIC_STRUCTURE',
                    'Created '
                        . $this->entityLabel(
                            $entityType
                        )
                        . ': '
                        . $entityName
                        . '.'
                );
            } catch (Throwable $loggingException) {
                /*
                 * The academic transaction remains
                 * successful if general logging fails.
                 */
            }

            $this->setFlash(
                'success',
                $this->entityLabel(
                    $entityType
                )
                    . ' created successfully.'
            );

            $this->redirect(
                $this->buildRedirect(
                    $entityType,
                    $entityId
                )
            );
        } catch (Throwable $exception) {
            $entityType =
                trim(
                    (string) (
                        $_POST['entity_type']
                        ?? ''
                    )
                );

            $this->setFlash(
                'error',
                publicErrorMessage($exception),
                $this->preserveInput(
                    $_POST
                ),
                'create',
                $entityType
            );

            $this->redirect(
                $this->buildRedirect(
                    $entityType,
                    0
                )
            );
        }
    }

    /* ==========================================
       UPDATE
    ========================================== */

    public function update(): void
    {
        $this->requireRole(
            'Admin'
        );

        $entityType =
            trim(
                (string) (
                    $_POST['entity_type']
                    ?? ''
                )
            );

        $entityId =
            max(
                0,
                (int) filter_var(
                    $_POST['entity_id'] ?? null,
                    FILTER_VALIDATE_INT
                )
            );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $this->requireScalarFormValues();

            $reason =
                trim(
                    (string) (
                        $_POST['reason']
                        ?? ''
                    )
                );

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $updatedEntity =
                $this->service
                ->update(
                    $entityType,
                    $entityId,
                    $_POST,
                    $adminId,
                    $reason
                );

            $entityName =
                $this->extractEntityName(
                    $entityType,
                    $updatedEntity
                );

            try {
                $this->log(
                    'UPDATE_ACADEMIC_STRUCTURE',
                    'Updated '
                        . $this->entityLabel(
                            $entityType
                        )
                        . ': '
                        . $entityName
                        . '.'
                );
            } catch (Throwable $loggingException) {
                /*
                 * The academic transaction remains
                 * successful if general logging fails.
                 */
            }

            $this->setFlash(
                'success',
                $this->entityLabel(
                    $entityType
                )
                    . ' updated successfully.'
            );

            $this->redirect(
                $this->buildRedirect(
                    $entityType,
                    $entityId
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception),
                $this->preserveInput(
                    $_POST
                ),
                'edit',
                $entityType,
                $entityId
            );

            $this->redirect(
                $this->buildRedirect(
                    $entityType,
                    $entityId
                )
            );
        }
    }

    /* ==========================================
       CHANGE STATUS
    ========================================== */

    public function changeStatus(): void
    {
        $this->requireRole(
            'Admin'
        );

        $entityType =
            trim(
                (string) (
                    $_POST['entity_type']
                    ?? ''
                )
            );

        $entityId =
            max(
                0,
                (int) filter_var(
                    $_POST['entity_id'] ?? null,
                    FILTER_VALIDATE_INT
                )
            );

        try {
            $this->requirePostMethod();
            $this->requireCsrfToken();
            $this->requireScalarFormValues();

            if (
                (
                    $_POST['confirm_status_change'] ?? ''
                ) !== '1'
            ) {
                throw new RuntimeException(
                    'Confirm the academic status change before continuing.'
                );
            }

            $newStatus =
                trim(
                    (string) (
                        $_POST['new_status']
                        ?? ''
                    )
                );

            $reason =
                trim(
                    (string) (
                        $_POST['reason']
                        ?? ''
                    )
                );

            $adminId =
                (int) (
                    $_SESSION['user_id']
                    ?? 0
                );

            $updatedEntity =
                $this->service
                ->changeStatus(
                    $entityType,
                    $entityId,
                    $newStatus,
                    $adminId,
                    $reason
                );

            $entityName =
                $this->extractEntityName(
                    $entityType,
                    $updatedEntity
                );

            try {
                $this->log(
                    'CHANGE_ACADEMIC_STRUCTURE_STATUS',
                    'Changed '
                        . $this->entityLabel(
                            $entityType
                        )
                        . ' status to '
                        . $newStatus
                        . ': '
                        . $entityName
                        . '.'
                );
            } catch (Throwable $loggingException) {
                /*
                 * The academic transaction remains
                 * successful if general logging fails.
                 */
            }

            $this->setFlash(
                'success',
                $this->entityLabel(
                    $entityType
                )
                    . ' marked as '
                    . $newStatus
                    . '.'
            );

            $this->redirect(
                $this->buildRedirect(
                    $entityType,
                    $entityId
                )
            );
        } catch (Throwable $exception) {
            $this->setFlash(
                'error',
                publicErrorMessage($exception),
                $this->preserveInput(
                    $_POST
                ),
                'status',
                $entityType,
                $entityId
            );

            $this->redirect(
                $this->buildRedirect(
                    $entityType,
                    $entityId
                )
            );
        }
    }

    /* ==========================================
       FLASH
    ========================================== */

    private function setFlash(
        string $type,
        string $message,
        array $oldInput = [],
        ?string $openForm = null,
        string $entityType = '',
        int $entityId = 0
    ): void {
        $_SESSION['academic_management_flash'] = [
            'type' =>
            $type,

            'message' =>
            $message,

            'old_input' =>
            $oldInput,

            'open_form' =>
            $openForm,

            'entity_type' =>
            $entityType,

            'entity_id' =>
            $entityId
        ];
    }

    private function preserveInput(
        array $input
    ): array {
        $allowedFields = [
            'entity_type',
            'entity_id',
            'department_name',
            'department_code',
            'description',
            'department_id',
            'education_level_name',
            'education_level_id',
            'program_name',
            'program_code',
            'program_type',
            'grade_level_name',
            'grade_level_id',
            'academic_program_id',
            'section_name',
            'new_status',
            'reason'
        ];

        return array_intersect_key(
            $input,
            array_flip(
                $allowedFields
            )
        );
    }

    /* ==========================================
       ENTITY HELPERS
    ========================================== */

    private function extractEntityId(
        string $entityType,
        array $entity
    ): int {
        $primaryKeys = [
            'department' =>
            'department_id',

            'education_level' =>
            'education_level_id',

            'academic_program' =>
            'academic_program_id',

            'grade_level' =>
            'grade_level_id',

            'section' =>
            'section_id'
        ];

        $primaryKey =
            $primaryKeys[$entityType]
            ?? '';

        return (int) (
            $entity[$primaryKey]
            ?? 0
        );
    }

    private function extractEntityName(
        string $entityType,
        array $entity
    ): string {
        $nameFields = [
            'department' =>
            'department_name',

            'education_level' =>
            'education_level_name',

            'academic_program' =>
            'program_name',

            'grade_level' =>
            'grade_level_name',

            'section' =>
            'section_name'
        ];

        $field =
            $nameFields[$entityType]
            ?? '';

        $name =
            trim(
                (string) (
                    $entity[$field]
                    ?? ''
                )
            );

        return $name !== ''
            ? $name
            : 'Unknown record';
    }

    private function entityLabel(
        string $entityType
    ): string {
        $labels = [
            'department' =>
            'School Division',

            'education_level' =>
            'Education Level',

            'academic_program' =>
            'Program or Strand',

            'grade_level' =>
            'Grade or Year Level',

            'section' =>
            'Section'
        ];

        return $labels[$entityType]
            ?? 'Academic record';
    }

    /* ==========================================
       REDIRECT
    ========================================== */

    private function buildRedirect(
        string $entityType,
        int $entityId
    ): string {
        $query = [
            'page' =>
            'academic_management'
        ];

        if ($entityType !== '') {
            $query['type'] =
                $entityType;
        }

        if ($entityId > 0) {
            $query['entity_id'] =
                $entityId;
        }

        return 'index.php?'
            . http_build_query(
                $query
            );
    }
}
