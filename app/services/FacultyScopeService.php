<?php

require_once __DIR__ . '/../models/User.php';

class FacultyScopeService
{
    private User $user;

    public function __construct(?User $user = null)
    {
        $this->user = $user ?? new User();
    }

    public function validateAssignment(array $data): array
    {
        $departmentId = $this->id($data['department_id'] ?? null);
        $educationId = $this->id($data['education_level_id'] ?? null);
        $programId = $this->id($data['academic_program_id'] ?? null);
        $department = $this->user->findDepartmentById($departmentId ?? 0);
        $education = $this->user->findEducationLevelById($educationId ?? 0);
        if (!$department || !$education || (int) $education['department_id'] !== $departmentId) {
            throw new InvalidArgumentException('Select an active Faculty division and its education level.');
        }

        $division = strtoupper((string) $department['department_code']);
        if ($division === 'COLLEGE') {
            $program = $this->user->findAcademicProgramById($programId ?? 0);
            if (!$program || (int) $program['education_level_id'] !== $educationId || $program['program_type'] !== 'Program') {
                throw new InvalidArgumentException('Assign an active College program in the selected education level.');
            }
        } elseif ($division === 'IBED') {
            if ($programId !== null) {
                throw new InvalidArgumentException('IBED Faculty are assigned an education level, not a program or strand.');
            }
        } else {
            throw new InvalidArgumentException('Select a supported Faculty division.');
        }

        return [
            'department_id' => $departmentId,
            'education_level_id' => $educationId,
            'academic_program_id' => $programId
        ];
    }

    public function forUser(int $userId): ?array
    {
        $actor = $userId > 0 ? $this->user->findById($userId) : null;
        if (!$actor || ($actor['status'] ?? '') !== 'Active' || !in_array($actor['role_prefix'] ?? '', ['Admin', 'Faculty'], true)) {
            throw new DomainException('An active Administrator or Faculty account is required.');
        }
        if ($actor['role_prefix'] === 'Admin') {
            return null;
        }
        try {
            return $this->validateAssignment($actor);
        } catch (InvalidArgumentException $exception) {
            throw new DomainException('Ask an Administrator to complete your Faculty division, education level, and College program assignment.');
        }
    }

    public function prepareSubmission(array $data, int $userId): array
    {
        $scope = $this->forUser($userId);
        // A changed staff role must not reuse the old session's publishing powers.
        $expectedRole = $scope === null ? 'Admin' : 'Faculty';
        if (($_SESSION['role'] ?? '') !== $expectedRole) {
            throw new DomainException('Your account role changed. Sign in again before posting.');
        }
        if ($scope === null) {
            return $data;
        }
        if (($data['audience_scope'] ?? 'schoolwide') !== 'custom') {
            throw new DomainException('Faculty content must target your assigned academic scope.');
        }
        if (!in_array($data['workflow_action'] ?? 'draft', ['draft', 'submit_review'], true)) {
            throw new DomainException('Faculty content must be submitted for Administrator review.');
        }
        $rawScopes = $data['audience_scopes'] ?? [];
        if (!is_array($rawScopes)) {
            throw new InvalidArgumentException('Select valid academic targets.');
        }
        if ($rawScopes === []) {
            $rawScopes = [[
                'department_id' => $data['target_department'] ?? null,
                'education_level_id' => $data['target_education_level'] ?? null,
                'academic_program_id' => $data['target_program'] ?? null,
                'grade_level_id' => $data['target_grade_level'] ?? null,
                'section_id' => $data['target_section'] ?? null
            ]];
        }
        foreach ($rawScopes as &$target) {
            if (!is_array($target)) {
                throw new InvalidArgumentException('Select valid academic targets.');
            }
            foreach (['department_id', 'education_level_id', 'academic_program_id', 'grade_level_id', 'section_id'] as $field) {
                $target[$field] = $this->id($target[$field] ?? null);
            }
            foreach ($scope as $field => $assignedId) {
                if ($assignedId === null) {
                    continue;
                }
                if ($target[$field] !== null && $target[$field] !== $assignedId) {
                    throw new DomainException('Faculty recipients must stay within your assigned academic scope.');
                }
                $target[$field] = $assignedId;
            }
            $this->validateNarrowerTarget($target, $scope);
        }
        unset($target);
        $data['audience_scopes'] = $rawScopes;
        return $data;
    }

    public function assertSavedTargets(array $targets, int $userId): void
    {
        $scope = $this->forUser($userId);
        if ($scope === null) {
            return;
        }
        if ($targets === []) {
            throw new DomainException('Edit this draft and select recipients within your current Faculty assignment before submitting.');
        }
        foreach ($targets as $target) {
            foreach ($scope as $field => $assignedId) {
                if ($assignedId !== null && (int) ($target[$field] ?? 0) !== $assignedId) {
                    throw new DomainException('Edit this draft and select recipients within your current Faculty assignment before submitting.');
                }
            }
            $this->validateNarrowerTarget($target, $scope);
        }
    }

    private function validateNarrowerTarget(array $target, array $scope): void
    {
        $programId = $this->id($target['academic_program_id'] ?? null);
        $gradeId = $this->id($target['grade_level_id'] ?? null);
        $sectionId = $this->id($target['section_id'] ?? null);
        if ($programId !== null) {
            $program = $this->user->findAcademicProgramById($programId);
            if (!$program || (int) $program['education_level_id'] !== $scope['education_level_id']) {
                throw new DomainException('Select a program or strand within your assigned education level.');
            }
        }
        if ($sectionId !== null) {
            $section = $this->user->findSectionById($sectionId);
            if (!$section || ($gradeId !== null && (int) $section['grade_level_id'] !== $gradeId)
                || ($programId !== null && (int) $section['academic_program_id'] !== $programId)) {
                throw new DomainException('Select a section within the selected academic scope.');
            }
            if (!empty($section['academic_program_id'])) {
                $sectionProgram = $this->user->findAcademicProgramById((int) $section['academic_program_id']);
                if (!$sectionProgram || (int) $sectionProgram['education_level_id'] !== $scope['education_level_id']) {
                    throw new DomainException('Select a section within your assigned education level.');
                }
            }
            $gradeId = (int) $section['grade_level_id'];
        }
        if ($gradeId !== null) {
            $grade = $this->user->findGradeLevelById($gradeId);
            if (!$grade || (int) $grade['education_level_id'] !== $scope['education_level_id']) {
                throw new DomainException('Select a grade or year within your assigned education level.');
            }
        }
    }

    private function id(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }
        if ((!is_int($value) && !is_string($value)) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value <= 0) {
            throw new InvalidArgumentException('Select a valid academic assignment.');
        }
        return (int) $value;
    }
}
