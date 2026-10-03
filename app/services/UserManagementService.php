<?php

require_once __DIR__ . '/FacultyScopeService.php';

require_once __DIR__
    . '/../models/User.php';

class UserManagementService
{
    private User $user;

    private const ACCOUNT_STATUSES = [
        'Pending',
        'Active',
        'Inactive',
        'Rejected'
    ];

    public function __construct()
    {
        $this->user =
            new User();
    }

    /* ==========================================
       MANAGED USER DIRECTORY
    ========================================== */

    public function getDirectory(
        array $filters,
        int $selectedUserId = 0
    ): array {
        $normalizedFilters =
            $this->normalizeFilters(
                $filters
            );

        $allUsers =
            $this->user
            ->getManagedUsers(
                [],
                500
            );

        $filteredUsers =
            $this->user
            ->getManagedUsers(
                $normalizedFilters,
                500
            );

        $selectedUser = null;

        if ($selectedUserId > 0) {
            foreach (
                $allUsers
                as $managedUser
            ) {
                if (
                    (int) (
                        $managedUser['user_id']
                        ?? 0
                    ) === $selectedUserId
                ) {
                    $selectedUser =
                        $managedUser;

                    break;
                }
            }

            if (!$selectedUser) {
                throw new RuntimeException(
                    'The selected user account could not be found.'
                );
            }
        } elseif (
            !empty($filteredUsers)
        ) {
            $selectedUser =
                $filteredUsers[0];
        }

        $selectedUserHistory =
            $selectedUser
            ? $this->user
            ->getManagedUserActivityHistory(
                (int) (
                    $selectedUser['user_id']
                    ?? 0
                ),
                100
            )
            : [];

        return [
            'users' =>
            $filteredUsers,

            'selected_user' =>
            $selectedUser,

            'selected_user_history' =>
            $selectedUserHistory,

            'selected_user_id' =>
            (int) (
                $selectedUser['user_id']
                ?? 0
            ),

            'filters' =>
            $normalizedFilters,

            'counts' =>
            $this->buildCounts(
                $allUsers
            ),

            'roles' =>
            $this->user
                ->getRoles(),

            'departments' =>
            $this->user
                ->getActiveDepartments(),

            'education_levels' =>
            $this->user
                ->getActiveEducationLevels(),

            'academic_programs' =>
            $this->user
                ->getActiveAcademicPrograms(),

            'grade_levels' =>
            $this->user
                ->getActiveGradeLevels(),

            'sections' =>
            $this->user
                ->getActiveSections(),

            'statuses' =>
            self::ACCOUNT_STATUSES
        ];
    }

    /* ==========================================
   PROVISION FACULTY ACCOUNT
========================================== */

    public function provisionFaculty(
        int $adminId,
        array $data
    ): array {
        if ($adminId <= 0) {
            throw new InvalidArgumentException(
                'Invalid Administrator ID.'
            );
        }

        $administrator =
            $this->user
            ->findById(
                $adminId
            );

        if (
            !$administrator ||
            (
                $administrator['role_prefix']
                ?? ''
            ) !== 'Admin' ||
            (
                $administrator['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new RuntimeException(
                'Only an active Administrator may provision Faculty accounts.'
            );
        }

        $firstName =
            $this->normalizeManagedName(
                $data['first_name']
                    ?? ''
            );

        $lastName =
            $this->normalizeManagedName(
                $data['last_name']
                    ?? ''
            );

        if ($firstName === '') {
            throw new InvalidArgumentException(
                'Faculty first name is required.'
            );
        }

        if ($lastName === '') {
            throw new InvalidArgumentException(
                'Faculty last name is required.'
            );
        }

        if (
            mb_strlen($firstName) > 100 ||
            mb_strlen($lastName) > 100
        ) {
            throw new InvalidArgumentException(
                'Faculty first and last names must not exceed 100 characters.'
            );
        }

        $email =
            strtolower(
                trim(
                    (string) (
                        $data['email']
                        ?? ''
                    )
                )
            );

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new InvalidArgumentException(
                'Enter a valid Faculty email address.'
            );
        }

        if (
            mb_strlen($email) > 100
        ) {
            throw new InvalidArgumentException(
                'Faculty email address must not exceed 100 characters.'
            );
        }

        if (
            $this->user->findByEmail(
                $email
            )
        ) {
            throw new RuntimeException(
                'That email address is already registered.'
            );
        }

        $gender = null;
        $age = null;
        $birthdate = null;

        $departmentId =
            (int) (
                $data['department_id']
                ?? 0
            );

        $department =
            $this->user
            ->findDepartmentById(
                $departmentId
            );

        if (!$department) {
            throw new InvalidArgumentException(
                'Select an active Faculty School Division.'
            );
        }

        $assignment = (new FacultyScopeService($this->user))->validateAssignment($data);
        $educationLevelId = $assignment['education_level_id'];

        $facultyRole =
            $this->user
            ->findRoleByPrefix(
                'Faculty'
            );

        if (
            !$facultyRole ||
            (int) (
                $facultyRole['role_id']
                ?? 0
            ) <= 0
        ) {
            throw new RuntimeException(
                'The Faculty system role could not be found.'
            );
        }

        $temporaryPassword =
            $this->generateTemporaryPassword();

        $passwordHash =
            password_hash(
                $temporaryPassword,
                PASSWORD_DEFAULT
            );

        if (!is_string($passwordHash)) {
            throw new RuntimeException(
                'Unable to secure the temporary Faculty password.'
            );
        }

        $facultyUserId =
            $this->user
            ->createProvisionedFaculty([
                'first_name' =>
                $firstName,

                'middle_name' => null,

                'last_name' =>
                $lastName,

                'name_suffix' => null,

                'email' =>
                $email,

                'password' =>
                $passwordHash,

                'gender' =>
                $gender,

                'age' =>
                $age,

                'birthdate' =>
                $birthdate,

                'role_id' =>
                (int) $facultyRole['role_id'],

                'department_id' =>
                $departmentId,

                'academic_program_id' => $assignment['academic_program_id'],

                'education_level_id' =>
                $educationLevelId,

                'provisioned_by' =>
                $adminId
            ]);

        $faculty =
            $this->getManagedUserById(
                $facultyUserId
            );

        /*
     * Plain temporary password exists only in this
     * returned request result and is never persisted.
     */
        $faculty['temporary_password'] =
            $temporaryPassword;

        return $faculty;
    }

    /* ==========================================
   CHANGE MANAGED ACCOUNT STATUS
========================================== */

    public function changeAccountStatus(
        int $userId,
        int $adminId,
        string $newStatus,
        string $reason
    ): array {
        if (
            $userId <= 0 ||
            $adminId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid user or Administrator ID.'
            );
        }

        $newStatus =
            ucfirst(
                strtolower(
                    trim(
                        $newStatus
                    )
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
                'Select a valid account status.'
            );
        }

        $reason =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $reason
                )
            );

        if ($reason === '') {
            throw new InvalidArgumentException(
                'Enter a reason for changing the account status.'
            );
        }

        if (
            mb_strlen(
                $reason
            ) > 1000
        ) {
            throw new InvalidArgumentException(
                'The account-status reason must not exceed 1000 characters.'
            );
        }

        $administrator =
            $this->user
            ->findById(
                $adminId
            );

        if (
            !$administrator ||
            (
                $administrator['role_prefix']
                ?? ''
            ) !== 'Admin' ||
            (
                $administrator['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new RuntimeException(
                'Only an active Administrator may change account access.'
            );
        }

        $managedUser =
            $this->getManagedUserById(
                $userId
            );

        $currentStatus =
            trim(
                (string) (
                    $managedUser['status']
                    ?? ''
                )
            );

        $managedRole =
            trim(
                (string) (
                    $managedUser['role_prefix']
                    ?? ''
                )
            );

        if (
            !in_array(
                $currentStatus,
                [
                    'Active',
                    'Inactive'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Pending and Rejected accounts must be handled through the registration-review workflow.'
            );
        }

        if ($currentStatus === $newStatus) {
            throw new RuntimeException(
                'The account already has the selected status.'
            );
        }

        /*
     * Administrators cannot deactivate their own
     * account from the current session.
     */
        if (
            $userId === $adminId &&
            $newStatus === 'Inactive'
        ) {
            throw new RuntimeException(
                'You cannot deactivate your own Administrator account.'
            );
        }

        /*
     * At least one active Administrator must always
     * remain capable of managing the system.
     */
        if (
            $managedRole === 'Admin' &&
            $currentStatus === 'Active' &&
            $newStatus === 'Inactive' &&
            $this->user
            ->countActiveAdministrators() <= 1
        ) {
            throw new RuntimeException(
                'The last active Administrator account cannot be deactivated.'
            );
        }

        $changed =
            $this->user
            ->changeManagedAccountStatus(
                $userId,
                $newStatus,
                $reason,
                $adminId
            );

        if (!$changed) {
            throw new RuntimeException(
                'The account status could not be changed.'
            );
        }

        return $this->getManagedUserById(
            $userId
        );
    }

    /* ==========================================
   UNLOCK MANAGED ACCOUNT
========================================== */

    public function unlockAccount(
        int $userId,
        int $adminId
    ): array {
        if (
            $userId <= 0 ||
            $adminId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid user or Administrator ID.'
            );
        }

        $administrator =
            $this->user
            ->findById(
                $adminId
            );

        if (
            !$administrator ||
            (
                $administrator['role_prefix']
                ?? ''
            ) !== 'Admin' ||
            (
                $administrator['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new RuntimeException(
                'Only an active Administrator may unlock accounts.'
            );
        }

        $managedUser =
            $this->getManagedUserById(
                $userId
            );

        if (
            (
                $managedUser['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new RuntimeException(
                'Only an Active account may be unlocked.'
            );
        }

        $failedAttempts =
            max(
                0,
                (int) (
                    $managedUser['failed_attempts']
                    ?? 0
                )
            );

        $lockUntil =
            trim(
                (string) (
                    $managedUser['lock_until']
                    ?? ''
                )
            );

        $hasActiveLock =
            $lockUntil !== '' &&
            strtotime(
                $lockUntil
            ) !== false &&
            strtotime(
                $lockUntil
            ) > time();

        if (
            $failedAttempts <= 0 &&
            !$hasActiveLock
        ) {
            throw new RuntimeException(
                'This account is not currently locked and has no failed login attempts to reset.'
            );
        }

        $unlocked =
            $this->user
            ->resetFailedAttempts(
                $userId
            );

        if (!$unlocked) {
            throw new RuntimeException(
                'The account could not be unlocked.'
            );
        }

        $updatedUser =
            $this->getManagedUserById(
                $userId
            );

        if (
            (int) (
                $updatedUser['failed_attempts']
                ?? 0
            ) !== 0 ||
            !empty($updatedUser['lock_until'])
        ) {
            throw new RuntimeException(
                'The account lock could not be cleared completely.'
            );
        }

        return $updatedUser;
    }

    /* ==========================================
   CHANGE STAFF SYSTEM ROLE
========================================== */

    public function changeStaffRole(
        int $userId,
        int $adminId,
        int $newRoleId,
        string $reason
    ): array {
        if (
            $userId <= 0 ||
            $adminId <= 0 ||
            $newRoleId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid user, Administrator, or role ID.'
            );
        }

        $reason =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $reason
                )
            );

        if ($reason === '') {
            throw new InvalidArgumentException(
                'Enter a reason for changing the staff role.'
            );
        }

        if (
            mb_strlen(
                $reason
            ) > 1000
        ) {
            throw new InvalidArgumentException(
                'The role-change reason must not exceed 1000 characters.'
            );
        }

        $administrator =
            $this->user
            ->findById(
                $adminId
            );

        if (
            !$administrator ||
            (
                $administrator['role_prefix']
                ?? ''
            ) !== 'Admin' ||
            (
                $administrator['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new RuntimeException(
                'Only an active Administrator may change staff roles.'
            );
        }

        $managedUser =
            $this->getManagedUserById(
                $userId
            );

        $currentRole =
            trim(
                (string) (
                    $managedUser['role_prefix']
                    ?? ''
                )
            );

        $currentStatus =
            trim(
                (string) (
                    $managedUser['status']
                    ?? ''
                )
            );

        if (
            !in_array(
                $currentStatus,
                [
                    'Active',
                    'Inactive'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Pending and Rejected accounts cannot receive staff-role changes.'
            );
        }

        if (
            !in_array(
                $currentRole,
                [
                    'Admin',
                    'Faculty'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Student and Parent roles cannot be changed through Staff Role Management.'
            );
        }

        $selectedRole = null;

        foreach (
            $this->user->getRoles()
            as $role
        ) {
            if (
                (int) (
                    $role['role_id']
                    ?? 0
                ) === $newRoleId
            ) {
                $selectedRole =
                    $role;

                break;
            }
        }

        if (!$selectedRole) {
            throw new InvalidArgumentException(
                'Select a valid system role.'
            );
        }

        $newRole =
            trim(
                (string) (
                    $selectedRole['role_prefix']
                    ?? ''
                )
            );

        if (
            !in_array(
                $newRole,
                [
                    'Admin',
                    'Faculty'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only the Admin and Faculty staff roles may be assigned here.'
            );
        }

        if ($currentRole === $newRole) {
            throw new RuntimeException(
                'The account already has the selected role.'
            );
        }

        /*
     * The model transaction repeats the critical
     * self-removal and last-Administrator safeguards.
     */
        $changed =
            $this->user
            ->changeManagedStaffRole(
                $userId,
                $newRoleId,
                $reason,
                $adminId
            );

        if (!$changed) {
            throw new RuntimeException(
                'The staff role could not be changed.'
            );
        }

        $updatedUser =
            $this->getManagedUserById(
                $userId
            );

        if (
            (
                $updatedUser['role_prefix']
                ?? ''
            ) !== $newRole
        ) {
            throw new RuntimeException(
                'The updated staff role could not be verified.'
            );
        }

        return $updatedUser;
    }

    /* ==========================================
   UPDATE ACADEMIC ASSIGNMENT
========================================== */

    public function updateFacultyAssignment(int $userId, int $adminId, array $data): array
    {
        $actor = $this->user->findById($adminId);
        if (!$actor || $actor['role_prefix'] !== 'Admin' || $actor['status'] !== 'Active') {
            throw new DomainException('Only an active Administrator may update Faculty assignments.');
        }
        $faculty = $this->user->findById($userId);
        if (!$faculty || $faculty['role_prefix'] !== 'Faculty') {
            throw new InvalidArgumentException('Select a Faculty account.');
        }
        return $this->updateAcademicAssignment($userId, $adminId, $data);
    }

    public function updateAcademicAssignment(
        int $userId,
        int $adminId,
        array $data
    ): array {
        if (
            $userId <= 0 ||
            $adminId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid user or Administrator ID.'
            );
        }

        $administrator =
            $this->user->findById(
                $adminId
            );

        if (
            !$administrator ||
            (
                $administrator['role_prefix']
                ?? ''
            ) !== 'Admin' ||
            (
                $administrator['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new RuntimeException(
                'Only an active Administrator may update academic assignments.'
            );
        }

        $managedUser =
            $this->getManagedUserById(
                $userId
            );

        $role =
            trim(
                (string) (
                    $managedUser['role_prefix']
                    ?? ''
                )
            );

        $status =
            trim(
                (string) (
                    $managedUser['status']
                    ?? ''
                )
            );

        if (
            !in_array(
                $status,
                [
                    'Active',
                    'Inactive'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only Active or Inactive accounts may have their academic assignment corrected here.'
            );
        }

        if (
            !in_array(
                $role,
                [
                    'Student',
                    'Faculty'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Academic assignments may only be changed for Student and Faculty accounts.'
            );
        }

        $departmentId =
            (int) (
                $data['department_id']
                ?? 0
            );

        if ($departmentId <= 0) {
            throw new InvalidArgumentException(
                'Select a valid School Division.'
            );
        }

        $department =
            $this->user
            ->findDepartmentById(
                $departmentId
            );

        if (!$department) {
            throw new InvalidArgumentException(
                'Select an active School Division.'
            );
        }

        if ($role === 'Faculty') {
            $assignment = (new FacultyScopeService($this->user))->validateAssignment($data);
            if (!$this->user->updateManagedAcademicAssignment(
                $userId, $assignment['department_id'], $assignment['education_level_id'],
                $assignment['academic_program_id'], null, null
            )) {
                throw new RuntimeException('The Faculty assignment could not be updated.');
            }
            return $this->getManagedUserById($userId);
        }

        $educationLevelId =
            (int) (
                $department['education_level_id']
                ?? 0
            );

        if ($educationLevelId <= 0) {
            throw new RuntimeException(
                'The selected School Division is not matched to an active Education Level.'
            );
        }

        /*
     * Student accounts require a complete,
     * internally consistent hierarchy.
     */
        $academicProgramId =
            !empty($data['academic_program_id'])
            ? (int) $data['academic_program_id']
            : null;

        $gradeLevelId =
            (int) (
                $data['grade_level_id']
                ?? 0
            );

        $sectionId =
            (int) (
                $data['section_id']
                ?? 0
            );

        $requiresProgram =
            $this->user
            ->educationLevelHasPrograms(
                $educationLevelId
            );

        if (
            $requiresProgram &&
            $academicProgramId === null
        ) {
            throw new InvalidArgumentException(
                'Select a valid Program or Strand.'
            );
        }

        if (!$requiresProgram) {
            $academicProgramId = null;
        }

        if ($academicProgramId !== null) {
            $program =
                $this->user
                ->findAcademicProgramById(
                    $academicProgramId
                );

            if (
                !$program ||
                (int) (
                    $program['education_level_id']
                    ?? 0
                ) !== $educationLevelId
            ) {
                throw new InvalidArgumentException(
                    'The selected Program or Strand does not belong to the selected School Division.'
                );
            }
        }

        $gradeLevel =
            $this->user
            ->findGradeLevelById(
                $gradeLevelId
            );

        if (
            !$gradeLevel ||
            (int) (
                $gradeLevel['education_level_id']
                ?? 0
            ) !== $educationLevelId
        ) {
            throw new InvalidArgumentException(
                'The selected Grade or Year Level does not belong to the selected School Division.'
            );
        }

        $section =
            $this->user
            ->findSectionById(
                $sectionId
            );

        if (
            !$section ||
            (int) (
                $section['grade_level_id']
                ?? 0
            ) !== $gradeLevelId
        ) {
            throw new InvalidArgumentException(
                'The selected Section does not belong to the selected Grade or Year Level.'
            );
        }

        $sectionProgramId =
            !empty($section['academic_program_id'])
            ? (int) $section['academic_program_id']
            : null;

        if (
            $sectionProgramId !==
            $academicProgramId
        ) {
            throw new InvalidArgumentException(
                'The selected Section does not belong to the selected Program or Strand.'
            );
        }

        $updated =
            $this->user
            ->updateManagedAcademicAssignment(
                $userId,
                $departmentId,
                $educationLevelId,
                $academicProgramId,
                $gradeLevelId,
                $sectionId
            );

        if (!$updated) {
            throw new RuntimeException(
                'The Student academic assignment could not be updated.'
            );
        }

        return $this->getManagedUserById(
            $userId
        );
    }

    /* ==========================================
   SAFE MANAGED USER LOOKUP
========================================== */

    private function getManagedUserById(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid managed user ID.'
            );
        }

        $managedUsers =
            $this->user
            ->getManagedUsers(
                [],
                500
            );

        foreach (
            $managedUsers
            as $managedUser
        ) {
            if (
                (int) (
                    $managedUser['user_id']
                    ?? 0
                ) === $userId
            ) {
                return $managedUser;
            }
        }

        throw new RuntimeException(
            'The managed user account could not be found.'
        );
    }

    /* ==========================================
   FACULTY PROVISIONING HELPERS
========================================== */

    private function normalizeManagedName(
        mixed $value
    ): string {
        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                (string) $value
            )
        );
    }

    private function generateTemporaryPassword(
        int $length = 16
    ): string {
        $length =
            max(
                12,
                $length
            );

        $uppercase =
            'ABCDEFGHJKLMNPQRSTUVWXYZ';

        $lowercase =
            'abcdefghijkmnopqrstuvwxyz';

        $numbers =
            '23456789';

        $symbols =
            '!@#$%*-_';

        $allCharacters =
            $uppercase
            . $lowercase
            . $numbers
            . $symbols;

        $characters = [
            $uppercase[random_int(
                0,
                strlen($uppercase) - 1
            )],

            $lowercase[random_int(
                0,
                strlen($lowercase) - 1
            )],

            $numbers[random_int(
                0,
                strlen($numbers) - 1
            )],

            $symbols[random_int(
                0,
                strlen($symbols) - 1
            )]
        ];

        while (
            count($characters) < $length
        ) {
            $characters[] =
                $allCharacters[random_int(
                    0,
                    strlen($allCharacters) - 1
                )];
        }

        for (
            $index =
                count($characters) - 1;
            $index > 0;
            $index--
        ) {
            $swapIndex =
                random_int(
                    0,
                    $index
                );

            [
                $characters[$index],
                $characters[$swapIndex]
            ] = [
                $characters[$swapIndex],
                $characters[$index]
            ];
        }

        return implode(
            '',
            $characters
        );
    }

    /* ==========================================
       FILTER NORMALIZATION
    ========================================== */

    private function normalizeFilters(
        array $filters
    ): array {
        $search =
            trim(
                (string) (
                    $filters['search']
                    ?? ''
                )
            );

        if (
            mb_strlen(
                $search
            ) > 150
        ) {
            $search =
                mb_substr(
                    $search,
                    0,
                    150
                );
        }

        $status =
            trim(
                (string) (
                    $filters['status']
                    ?? ''
                )
            );

        if (
            !in_array(
                $status,
                array_merge(
                    [
                        ''
                    ],
                    self::ACCOUNT_STATUSES
                ),
                true
            )
        ) {
            $status = '';
        }

        return [
            'search' =>
            $search,

            'role_id' =>
            max(
                0,
                (int) (
                    $filters['role_id']
                    ?? 0
                )
            ),

            'status' =>
            $status,

            'department_id' =>
            max(
                0,
                (int) (
                    $filters['department_id']
                    ?? 0
                )
            ),

            'education_level_id' =>
            max(
                0,
                (int) (
                    $filters['education_level_id']
                    ?? 0
                )
            )
        ];
    }

    /* ==========================================
       DIRECTORY COUNTS
    ========================================== */

    private function buildCounts(
        array $users
    ): array {
        $counts = [
            'total' =>
            count($users),

            'Pending' =>
            0,

            'Active' =>
            0,

            'Inactive' =>
            0,

            'Rejected' =>
            0,

            'Admin' =>
            0,

            'Faculty' =>
            0,

            'Student' =>
            0,

            'Parent' =>
            0
        ];

        foreach (
            $users
            as $managedUser
        ) {
            $status =
                trim(
                    (string) (
                        $managedUser['status']
                        ?? ''
                    )
                );

            $role =
                trim(
                    (string) (
                        $managedUser['role_prefix']
                        ?? ''
                    )
                );

            if (
                array_key_exists(
                    $status,
                    $counts
                )
            ) {
                $counts[$status]++;
            }

            if (
                array_key_exists(
                    $role,
                    $counts
                )
            ) {
                $counts[$role]++;
            }
        }

        return $counts;
    }
}
