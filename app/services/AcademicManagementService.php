<?php

require_once __DIR__
    . '/../models/AcademicStructure.php';

class AcademicManagementService
{
    private AcademicStructure $structure;

    private const ENTITY_TYPES = [
        'department',
        'education_level',
        'academic_program',
        'grade_level',
        'section'
    ];

    public function __construct()
    {
        $this->structure =
            new AcademicStructure();
    }

    /* ==========================================
       DIRECTORY
    ========================================== */

    public function getDirectory(): array
    {
        $directory =
            $this->structure
            ->getDirectory();

        return array_merge(
            $directory,
            [
                'counts' => [
                    'departments' =>
                    count(
                        $directory['departments']
                    ),

                    'education_levels' =>
                    count(
                        $directory['education_levels']
                    ),

                    'academic_programs' =>
                    count(
                        $directory['academic_programs']
                    ),

                    'grade_levels' =>
                    count(
                        $directory['grade_levels']
                    ),

                    'sections' =>
                    count(
                        $directory['sections']
                    )
                ],

                'history' =>
                $this->structure
                    ->getHistory(
                        50
                    )
            ]
        );
    }

    /* ==========================================
       CREATE
    ========================================== */

    public function create(
        string $entityType,
        array $data,
        int $adminId,
        string $reason
    ): array {
        $this->validateEntityType(
            $entityType
        );

        $this->validateAdministrator(
            $adminId
        );

        $reason =
            $this->cleanReason(
                $reason
            );

        $cleanData =
            $this->normalizeEntityData(
                $entityType,
                $data
            );

        $this->structure
            ->beginTransaction();

        try {
            $entityId =
                $this->structure
                ->createEntity(
                    $entityType,
                    $cleanData
                );

            if ($entityId <= 0) {
                throw new RuntimeException(
                    'The created academic record ID was unavailable.'
                );
            }

            $createdEntity =
                $this->structure
                ->findEntity(
                    $entityType,
                    $entityId
                );

            if (!$createdEntity) {
                throw new RuntimeException(
                    'The created academic record could not be reloaded.'
                );
            }

            $this->structure
                ->recordHistory(
                    $entityType,
                    $entityId,
                    'create',
                    null,
                    $createdEntity,
                    $reason,
                    $adminId
                );

            $this->structure
                ->commit();

            return $createdEntity;
        } catch (
            mysqli_sql_exception $exception
        ) {
            $this->structure
                ->rollback();

            if (
                $exception->getCode() ===
                1062
            ) {
                throw new InvalidArgumentException(
                    'That academic record already exists in the selected hierarchy.'
                );
            }

            throw new RuntimeException(
                'Unable to create the academic record: '
                    . $exception->getMessage()
            );
        } catch (Throwable $exception) {
            $this->structure
                ->rollback();

            throw $exception;
        }
    }

    /* ==========================================
   UPDATE
========================================== */

    public function update(
        string $entityType,
        int $entityId,
        array $data,
        int $adminId,
        string $reason
    ): array {
        $this->validateEntityType(
            $entityType
        );

        $this->validateAdministrator(
            $adminId
        );

        if ($entityId <= 0) {
            throw new InvalidArgumentException(
                'Select a valid academic record.'
            );
        }

        $reason =
            $this->cleanReason(
                $reason
            );

        $currentEntity =
            $this->structure
            ->findEntity(
                $entityType,
                $entityId
            );

        if (!$currentEntity) {
            throw new RuntimeException(
                'The academic record could not be found.'
            );
        }

        $cleanData =
            $this->normalizeEntityData(
                $entityType,
                $data
            );

        $immutableFields = [
            'education_level' => [
                'department_id'
            ],

            'academic_program' => [
                'education_level_id',
                'program_type'
            ],

            'grade_level' => [
                'education_level_id'
            ],

            'section' => [
                'grade_level_id',
                'academic_program_id'
            ]
        ];

        foreach (
            $immutableFields[$entityType]
                ?? []
            as $immutableField
        ) {
            $currentValue =
                $currentEntity[$immutableField]
                ?? null;

            $newValue =
                $cleanData[$immutableField]
                ?? null;

            if (
                (string) (
                    $currentValue
                    ?? ''
                ) !==
                (string) (
                    $newValue
                    ?? ''
                )
            ) {
                throw new RuntimeException(
                    'The academic hierarchy of an existing record cannot be changed through ordinary editing.'
                );
            }
        }

        $hasChanges = false;

        foreach (
            $cleanData
            as $field => $newValue
        ) {
            $currentValue =
                $currentEntity[$field]
                ?? null;

            if (
                (string) ($currentValue ?? '') !==
                (string) ($newValue ?? '')
            ) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            throw new InvalidArgumentException(
                'No changes were detected in the academic record.'
            );
        }

        $this->structure
            ->beginTransaction();

        try {
            $this->structure
                ->updateEntity(
                    $entityType,
                    $entityId,
                    $cleanData
                );

            $updatedEntity =
                $this->structure
                ->findEntity(
                    $entityType,
                    $entityId
                );

            if (!$updatedEntity) {
                throw new RuntimeException(
                    'The updated academic record could not be reloaded.'
                );
            }

            $this->structure
                ->recordHistory(
                    $entityType,
                    $entityId,
                    'update',
                    $currentEntity,
                    $updatedEntity,
                    $reason,
                    $adminId
                );

            $this->structure
                ->commit();

            return $updatedEntity;
        } catch (
            mysqli_sql_exception $exception
        ) {
            $this->structure
                ->rollback();

            if (
                $exception->getCode() ===
                1062
            ) {
                throw new InvalidArgumentException(
                    'That academic record already exists in the selected hierarchy.'
                );
            }

            throw new RuntimeException(
                'Unable to update the academic record: '
                    . $exception->getMessage()
            );
        } catch (Throwable $exception) {
            $this->structure
                ->rollback();

            throw $exception;
        }
    }


    /* ==========================================
   STATUS MANAGEMENT
========================================== */

    public function changeStatus(
        string $entityType,
        int $entityId,
        string $newStatus,
        int $adminId,
        string $reason
    ): array {
        $this->validateEntityType(
            $entityType
        );

        $this->validateAdministrator(
            $adminId
        );

        if ($entityId <= 0) {
            throw new InvalidArgumentException(
                'Select a valid academic record.'
            );
        }

        $newStatus =
            ucfirst(
                strtolower(
                    trim($newStatus)
                )
            );

        if (
            !in_array(
                $newStatus,
                [
                    'Active',
                    'Inactive'
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Select a valid academic record status.'
            );
        }

        $reason =
            $this->cleanReason(
                $reason
            );

        $currentEntity =
            $this->structure
            ->findEntity(
                $entityType,
                $entityId
            );

        if (!$currentEntity) {
            throw new RuntimeException(
                'The academic record could not be found.'
            );
        }

        $currentStatus =
            $currentEntity['status']
            ?? '';

        if ($currentStatus === $newStatus) {
            throw new InvalidArgumentException(
                "The academic record is already {$newStatus}."
            );
        }

        if ($newStatus === 'Inactive') {
            $dependencies =
                $this->structure
                ->getDependencyCounts(
                    $entityType,
                    $entityId
                );

            if (
                (int) (
                    $dependencies['active_children']
                    ?? 0
                ) > 0
            ) {
                throw new RuntimeException(
                    'Deactivate the active child records first.'
                );
            }

            if (
                (int) (
                    $dependencies['assigned_users']
                    ?? 0
                ) > 0
            ) {
                throw new RuntimeException(
                    'This academic record cannot be deactivated while users are still assigned to it.'
                );
            }
        }

        if ($newStatus === 'Active') {
            switch ($entityType) {
                case 'education_level':
                    $this->requireActiveParent(
                        'department',
                        (int) (
                            $currentEntity['department_id']
                            ?? 0
                        ),
                        'School Division'
                    );
                    break;

                case 'academic_program':
                case 'grade_level':
                    $this->requireActiveParent(
                        'education_level',
                        (int) (
                            $currentEntity['education_level_id']
                            ?? 0
                        ),
                        'Education Level'
                    );
                    break;

                case 'section':
                    $gradeLevel =
                        $this->requireActiveParent(
                            'grade_level',
                            (int) (
                                $currentEntity['grade_level_id'] ?? 0
                            ),
                            'Grade or Year Level'
                        );

                    $academicProgramId =
                        !empty($currentEntity['academic_program_id'])
                        ? (int) $currentEntity['academic_program_id']
                        : null;

                    if ($academicProgramId !== null) {
                        $program =
                            $this->requireActiveParent(
                                'academic_program',
                                $academicProgramId,
                                'Program or Strand'
                            );

                        if (
                            (int) (
                                $program['education_level_id'] ?? 0
                            ) !==
                            (int) (
                                $gradeLevel['education_level_id'] ?? 0
                            )
                        ) {
                            throw new RuntimeException(
                                'The Section Grade and Program no longer belong to the same Education Level.'
                            );
                        }
                    }
                    break;
            }
        }

        $this->structure
            ->beginTransaction();

        try {
            $this->structure
                ->changeStatus(
                    $entityType,
                    $entityId,
                    $newStatus
                );

            $updatedEntity =
                $this->structure
                ->findEntity(
                    $entityType,
                    $entityId
                );

            if (!$updatedEntity) {
                throw new RuntimeException(
                    'The updated academic record could not be reloaded.'
                );
            }

            $changeType =
                $newStatus === 'Active'
                ? 'activate'
                : 'deactivate';

            $this->structure
                ->recordHistory(
                    $entityType,
                    $entityId,
                    $changeType,
                    $currentEntity,
                    $updatedEntity,
                    $reason,
                    $adminId
                );

            $this->structure
                ->commit();

            return $updatedEntity;
        } catch (
            mysqli_sql_exception $exception
        ) {
            $this->structure
                ->rollback();

            throw new RuntimeException(
                'Unable to change the academic record status: '
                    . $exception->getMessage()
            );
        } catch (Throwable $exception) {
            $this->structure
                ->rollback();

            throw $exception;
        }
    }




    /* ==========================================
       NORMALIZATION
    ========================================== */

    private function normalizeEntityData(
        string $entityType,
        array $data
    ): array {
        switch ($entityType) {
            case 'department':
                return [
                    'department_name' =>
                    $this->cleanRequiredText(
                        $data['department_name']
                            ?? '',
                        'School Division name',
                        100
                    ),

                    'department_code' =>
                    strtoupper(
                        $this->cleanRequiredText(
                            $data['department_code']
                                ?? '',
                            'School Division code',
                            20
                        )
                    ),

                    'description' =>
                    $this->cleanOptionalText(
                        $data['description']
                            ?? '',
                        2000
                    )
                ];

            case 'education_level':
                $departmentId =
                    $this->positiveId(
                        $data['department_id']
                            ?? 0,
                        'School Division'
                    );

                $this->requireActiveParent(
                    'department',
                    $departmentId,
                    'School Division'
                );

                return [
                    'department_id' =>
                    $departmentId,

                    'education_level_name' =>
                    $this->cleanRequiredText(
                        $data['education_level_name'] ?? '',
                        'Education Level name',
                        100
                    )
                ];

            case 'academic_program':
                $educationLevelId =
                    $this->positiveId(
                        $data['education_level_id'] ?? 0,
                        'Education Level'
                    );

                $this->requireActiveParent(
                    'education_level',
                    $educationLevelId,
                    'Education Level'
                );

                $programType =
                    ucfirst(
                        strtolower(
                            trim(
                                (string) (
                                    $data['program_type']
                                    ?? ''
                                )
                            )
                        )
                    );

                if (
                    !in_array(
                        $programType,
                        [
                            'Program',
                            'Strand'
                        ],
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Select a valid Program type.'
                    );
                }

                return [
                    'education_level_id' =>
                    $educationLevelId,

                    'program_name' =>
                    $this->cleanRequiredText(
                        $data['program_name']
                            ?? '',
                        'Program or Strand name',
                        150
                    ),

                    'program_code' =>
                    strtoupper(
                        $this->cleanRequiredText(
                            $data['program_code']
                                ?? '',
                            'Program or Strand code',
                            30
                        )
                    ),

                    'program_type' =>
                    $programType,

                    'description' =>
                    $this->cleanOptionalText(
                        $data['description']
                            ?? '',
                        2000
                    )
                ];

            case 'grade_level':
                $educationLevelId =
                    $this->positiveId(
                        $data['education_level_id'] ?? 0,
                        'Education Level'
                    );

                $this->requireActiveParent(
                    'education_level',
                    $educationLevelId,
                    'Education Level'
                );

                return [
                    'education_level_id' =>
                    $educationLevelId,

                    'grade_level_name' =>
                    $this->cleanRequiredText(
                        $data['grade_level_name'] ?? '',
                        'Grade or Year Level name',
                        100
                    )
                ];

            case 'section':
                $gradeLevelId =
                    $this->positiveId(
                        $data['grade_level_id']
                            ?? 0,
                        'Grade or Year Level'
                    );

                $gradeLevel =
                    $this->requireActiveParent(
                        'grade_level',
                        $gradeLevelId,
                        'Grade or Year Level'
                    );

                $academicProgramId =
                    !empty($data['academic_program_id'])
                    ? $this->positiveId($data['academic_program_id'], 'Program or Strand')
                    : null;

                if (
                    $academicProgramId !==
                    null
                ) {
                    $program =
                        $this->requireActiveParent(
                            'academic_program',
                            $academicProgramId,
                            'Program or Strand'
                        );

                    if (
                        (int) (
                            $program['education_level_id'] ?? 0
                        ) !==
                        (int) (
                            $gradeLevel['education_level_id'] ?? 0
                        )
                    ) {
                        throw new InvalidArgumentException(
                            'The selected Program or Strand and Grade or Year Level must belong to the same Education Level.'
                        );
                    }
                }

                return [
                    'grade_level_id' =>
                    $gradeLevelId,

                    'academic_program_id' =>
                    $academicProgramId,

                    'section_name' =>
                    $this->cleanRequiredText(
                        $data['section_name']
                            ?? '',
                        'Section name',
                        100
                    )
                ];
        }

        throw new InvalidArgumentException(
            'Unsupported academic entity type.'
        );
    }

    /* ==========================================
       PARENT VALIDATION
    ========================================== */

    private function requireActiveParent(
        string $entityType,
        int $entityId,
        string $label
    ): array {
        $entity =
            $this->structure
            ->findEntity(
                $entityType,
                $entityId
            );

        if (
            !$entity ||
            (
                $entity['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new InvalidArgumentException(
                "Select an active {$label}."
            );
        }

        return $entity;
    }

    /* ==========================================
       INPUT HELPERS
    ========================================== */

    private function validateEntityType(
        string $entityType
    ): void {
        if (
            !in_array(
                $entityType,
                self::ENTITY_TYPES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported academic entity type.'
            );
        }
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

    private function positiveId(
        mixed $value,
        string $label
    ): int {
        $id = (is_int($value) || is_string($value))
            ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;

        if ($id === false) {
            throw new InvalidArgumentException(
                "Select a valid {$label}."
            );
        }

        return $id;
    }

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
            mb_strlen(
                $cleanValue
            ) > $maximumLength
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
            mb_strlen(
                $cleanValue
            ) > $maximumLength
        ) {
            throw new InvalidArgumentException(
                "Description must not exceed {$maximumLength} characters."
            );
        }

        return $cleanValue;
    }

    private function cleanReason(
        string $reason
    ): string {
        $reason =
            $this->cleanText(
                $reason
            );

        if (
            mb_strlen($reason) < 5
        ) {
            throw new InvalidArgumentException(
                'Provide a clear reason containing at least 5 characters.'
            );
        }

        if (
            mb_strlen($reason) > 1000
        ) {
            throw new InvalidArgumentException(
                'The reason must not exceed 1000 characters.'
            );
        }

        return $reason;
    }

    private function cleanText(
        mixed $value
    ): string {
        if (!is_string($value) && !is_int($value) && $value !== null) {
            throw new InvalidArgumentException('Enter a valid text value.');
        }

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string) $value
            )
        );
    }
}
