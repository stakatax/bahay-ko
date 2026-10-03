<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ParentChildRecord.php';
require_once __DIR__
    . '/NotificationService.php';

require_once __DIR__ . '/RequestRateLimitService.php';

class AuthService
{
    private User $user;
    private ?RequestRateLimitService $rateLimits = null;
    private string $clientIp = 'unknown';
    private NotificationService $notifications;

    public function __construct(?RequestRateLimitService $rateLimits = null, string $clientIp = 'unknown')
    {
        $this->rateLimits = $rateLimits;
        $this->clientIp = $clientIp;
        $this->user =
            new User();

        $this->notifications =
            new NotificationService();
    }

    /* ==========================================
       LOGIN
    ========================================== */

    public function login(
        string $identifier,
        string $password
    ): array {
        $identifier = trim($identifier);

        if ($identifier === '') {
            throw new Exception(
                'Enter your account ID or email address.'
            );
        }

        if ($password === '') {
            throw new Exception(
                'Enter your password.'
            );
        }

        $this->rateLimiter()->login($identifier, $this->clientIp);

        $user = $this->user->findByIdentifier(
            $identifier
        );

        // A fixed, non-account hash keeps unknown identifiers on the password-verification path.
        $validPassword = password_verify($password, $user['password']
            ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi');
        if (!$user) {
            throw new InvalidArgumentException('Invalid login credentials. Please try again.');
        }
        $this->validateAccountLock($user);
        if (!$validPassword) {
            if (($user['status'] ?? '') === 'Active') {
                $this->handleFailedLogin($user);
            }
            throw new InvalidArgumentException('Invalid login credentials. Please try again.');
        }
        // Account-status guidance is disclosed only after a correct password.
        $this->validateAccountStatus($user['status'] ?? '');
        $userId = (int) $user['user_id'];

        $this->user->resetFailedAttempts(
            $userId
        );

        $this->user->updateLastLogin(
            $userId
        );

        $freshUser = $this->user->findById(
            $userId
        );

        if (!$freshUser) {
            throw new Exception(
                'Unable to load the authenticated account.'
            );
        }

        return $freshUser;
    }


    /* ==========================================
       LEGAL RE-CONSENT
    ========================================== */

    public function getPendingLegalDocuments(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid legal-consent user ID.'
            );
        }

        return $this->user
            ->getPendingLegalDocumentVersions(
                $userId
            );
    }

    public function acceptPendingLegalDocuments(
        int $userId,
        bool $confirmed
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid legal-consent user ID.'
            );
        }

        if (!$confirmed) {
            throw new InvalidArgumentException(
                'You must review and accept the current Terms and Privacy Notice.'
            );
        }

        $user =
            $this->user
            ->findById(
                $userId
            );

        if (!$user) {
            throw new RuntimeException(
                'The account could not be found.'
            );
        }

        if (
            (
                $user['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new RuntimeException(
                'Only an Active account may provide legal consent.'
            );
        }

        $pendingDocuments =
            $this->user
            ->getPendingLegalDocumentVersions(
                $userId
            );

        if (empty($pendingDocuments)) {
            return [
                'accepted' => 0,
                'documents' => []
            ];
        }

        $userAgent =
            isset($_SERVER['HTTP_USER_AGENT'])
            ? substr(
                (string) $_SERVER['HTTP_USER_AGENT'],
                0,
                500
            )
            : null;

        $this->user->beginTransaction();

        try {
            $acceptedDocuments = [];

            foreach (
                $pendingDocuments
                as $documentType =>
                $document
            ) {
                $legalDocumentVersionId =
                    (int) (
                        $document['legal_document_version_id']
                        ?? 0
                    );

                if ($legalDocumentVersionId <= 0) {
                    throw new RuntimeException(
                        'An active legal-document version is invalid.'
                    );
                }

                $this->user
                    ->createLegalAcceptance(
                        $userId,
                        $legalDocumentVersionId,
                        'Reconsent',
                        $userAgent
                    );

                $acceptedDocuments[] = [
                    'document_type' =>
                    $documentType,

                    'legal_document_version_id' =>
                    $legalDocumentVersionId,

                    'version' =>
                    (string) (
                        $document['version']
                        ?? ''
                    )
                ];
            }

            $this->user->commit();

            return [
                'accepted' =>
                count(
                    $acceptedDocuments
                ),

                'documents' =>
                $acceptedDocuments
            ];
        } catch (Throwable $exception) {
            $this->user->rollback();

            throw $exception;
        }
    }

    /* ==========================================
   REQUIRED PASSWORD CHANGE
========================================== */

    public function changeRequiredPassword(
        int $userId,
        string $newPassword,
        string $newPasswordConfirmation,
        string $credentialVersion
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid password-change user ID.'
            );
        }

        $user =
            $this->user
            ->findById(
                $userId
            );

        if (!$user) {
            throw new RuntimeException(
                'The account could not be found.'
            );
        }

        if (
            (
                $user['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new RuntimeException(
                'Only an Active account may change its password.'
            );
        }

        if (
            empty($user['must_change_password'])
        ) {
            throw new RuntimeException(
                'This account no longer requires a temporary-password change.'
            );
        }

        if (
            $credentialVersion === '' ||
            !hash_equals(
                hash('sha256', (string) ($user['password'] ?? '')),
                $credentialVersion
            )
        ) {
            throw new RuntimeException(
                'Your sign-in has expired. Please sign in again.'
            );
        }

        /*
     * Reuse the same password policy enforced
     * during public account registration.
     */
        $this->validatePassword(
            $newPassword,
            $newPasswordConfirmation
        );

        if (
            password_verify(
                $newPassword,
                (string) (
                    $user['password']
                    ?? ''
                )
            )
        ) {
            throw new InvalidArgumentException(
                'Your new password must be different from the temporary password.'
            );
        }

        $passwordHash =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

        if (!is_string($passwordHash)) {
            throw new RuntimeException(
                'Unable to secure the new password.'
            );
        }

        $changed =
            $this->user
            ->completeRequiredPasswordChange(
                $userId,
                $passwordHash
            );

        if (!$changed) {
            throw new RuntimeException(
                'The required password change could not be completed.'
            );
        }

        $updatedUser =
            $this->user
            ->findById(
                $userId
            );

        if (!$updatedUser) {
            throw new RuntimeException(
                'The updated account could not be reloaded.'
            );
        }

        /*
     * Never return a password hash outside
     * the authentication service.
     */
        unset(
            $updatedUser['password']
        );

        return $updatedUser;
    }

    private function validateAccountStatus(
        string $status
    ): void {
        switch ($status) {
            case 'Active':
                return;

            case 'Pending':
                throw new Exception(
                    'Your account is awaiting Administrator approval.'
                );

            case 'Rejected':
                throw new Exception(
                    'Your registration was rejected. Please contact the school administrator.'
                );

            case 'Inactive':
                throw new Exception(
                    'Your account is inactive. Please contact the school administrator.'
                );

            default:
                throw new Exception(
                    'Your account is currently unavailable.'
                );
        }
    }

    private function validateAccountLock(
        array $user
    ): void {
        $lockUntil =
            $user['lock_until'] ?? null;

        if (!$lockUntil) {
            return;
        }

        $lockTimestamp = strtotime(
            $lockUntil
        );

        if (
            $lockTimestamp === false ||
            $lockTimestamp <= time()
        ) {
            return;
        }

        throw new InvalidArgumentException('Invalid login credentials. Please try again.');
    }

    private function rateLimiter(): RequestRateLimitService
    {
        return $this->rateLimits ??= new RequestRateLimitService(
            new RequestRateLimit($this->user->getDatabaseConnection())
        );
    }

    private function handleFailedLogin(
        array $user
    ): void {
        $userId =
            (int) $user['user_id'];

        $this->user
            ->incrementFailedAttempts(
                $userId
            );

        $updatedUser =
            $this->user->findById(
                $userId
            );

        $failedAttempts = (int) (
            $updatedUser['failed_attempts'] ?? 0
        );

        if ($failedAttempts >= 5) {
            $this->user->lockAccount(
                $userId,
                15
            );

        }
        throw new InvalidArgumentException('Invalid login credentials. Please try again.');
    }

    /* ==========================================
       PUBLIC REGISTRATION
    ========================================== */

    public function register(
        array $data
    ): int {
        $this->rateLimiter()->register($this->clientIp);

        $roleType =
            $this->normalizeRoleType(
                $data['role_type']
                    ?? 'Student'
            );

        $commonData =
            $this->validateCommonFields(
                $data
            );

        $role =
            $this->user
            ->findRoleByPrefix(
                $roleType
            );

        if (!$role) {
            throw new Exception(
                "{$roleType} role is not configured."
            );
        }

        $commonData['role_id'] =
            (int) $role['role_id'];

        $commonData['password'] =
            password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            );

        $registeredUserId =
            $roleType === 'Student'
            ? $this->registerStudent(
                $data,
                $commonData
            )
            : $this->registerParent(
                $data,
                $commonData
            );

        /*
     * Registration remains successful even when
     * notification delivery encounters a problem.
     */
        try {
            $applicantName =
                implode(
                    ' ',
                    array_filter(
                        [
                            trim(
                                (string) (
                                    $commonData['first_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $commonData['middle_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $commonData['last_name']
                                    ?? ''
                                )
                            ),

                            trim(
                                (string) (
                                    $commonData['name_suffix']
                                    ?? ''
                                )
                            )
                        ],
                        static fn(
                            string $part
                        ): bool =>
                        $part !== ''
                    )
                );

            $result =
                $this->notifications
                ->notifyRegistrationSubmitted(
                    $registeredUserId,
                    $applicantName,
                    $roleType
                );

            error_log(
                'Registration notification result: '
                    . json_encode(
                        $result,
                        JSON_UNESCAPED_SLASHES
                    )
            );
        } catch (Throwable $exception) {
            error_log(
                'Registration notification error: user #'
                    . $registeredUserId
                    . ' | '
                    . $exception->getMessage()
            );
        }

        return $registeredUserId;
    }

    private function normalizeRoleType(
        string $roleType
    ): string {
        $roleType = ucfirst(
            strtolower(
                trim($roleType)
            )
        );

        if (
            !in_array(
                $roleType,
                [
                    'Student',
                    'Parent'
                ],
                true
            )
        ) {
            throw new Exception(
                'Only Student and Parent accounts may register publicly.'
            );
        }

        return $roleType;
    }

    /* ==========================================
       COMMON REGISTRATION VALIDATION
    ========================================== */

    private function validateCommonFields(
        array $data
    ): array {
        $firstName =
            $this->cleanName(
                $data['first_name']
                    ?? ''
            );

        $middleName =
            $this->cleanOptionalName(
                $data['middle_name']
                    ?? ''
            );

        $lastName =
            $this->cleanName(
                $data['last_name']
                    ?? ''
            );


        $nameSuffix =
            $this->normalizeNameSuffix(
                $data['name_suffix']
                    ?? ''
            );

        $email = strtolower(
            trim(
                $data['email']
                    ?? ''
            )
        );

        $birthdate = trim(
            $data['birthdate']
                ?? ''
        );

        $gender = ucfirst(
            strtolower(
                trim(
                    $data['gender']
                        ?? ''
                )
            )
        );

        $password = (string) (
            $data['password']
            ?? ''
        );

        $passwordConfirmation =
            (string) (
                $data['password_confirmation'] ?? ''
            );

        if ($firstName === '') {
            throw new Exception(
                'First name is required.'
            );
        }

        if ($lastName === '') {
            throw new Exception(
                'Last name is required.'
            );
        }

        if (
            mb_strlen($firstName) > 100 ||
            mb_strlen($lastName) > 100
        ) {
            throw new Exception(
                'First name and last name must not exceed 100 characters.'
            );
        }

        if (
            $middleName !== null &&
            mb_strlen($middleName) > 50
        ) {
            throw new Exception(
                'Middle name must not exceed 50 characters.'
            );
        }

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new Exception(
                'Enter a valid email address.'
            );
        }

        if (
            mb_strlen($email) > 100
        ) {
            throw new Exception(
                'Email address must not exceed 100 characters.'
            );
        }

        if (
            $this->user->findByEmail(
                $email
            )
        ) {
            throw new Exception(
                'That email address is already registered.'
            );
        }

        if (
            !in_array(
                $gender,
                [
                    'Male',
                    'Female',
                    'Other'
                ],
                true
            )
        ) {
            throw new Exception(
                'Select a valid gender.'
            );
        }

        $age =
            $this->calculateAge(
                $birthdate
            );

        $this->validatePassword(
            $password,
            $passwordConfirmation
        );

        if (
            ($data['accept_terms'] ?? null) !== '1' ||
            ($data['accept_privacy'] ?? null) !== '1'
        ) {
            throw new Exception(
                'You must accept the Terms and Conditions and Privacy Notice before registering.'
            );
        }

        return [
            'studID' => null,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'name_suffix' => $nameSuffix,
            'email' => $email,
            'gender' => $gender,
            'birthdate' => $birthdate,
            'age' => $age,
            'department_id' => null,
            'education_level_id' => null,
            'academic_program_id' => null,
            'grade_level_id' => null,
            'section_id' => null
        ];
    }

    private function cleanName(
        string $value
    ): string {
        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                $value
            )
        );
    }

    private function cleanOptionalName(
        string $value
    ): ?string {
        $value =
            $this->cleanName(
                $value
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function normalizeNameSuffix(
        string $value
    ): ?string {
        $value =
            strtoupper(
                trim(
                    preg_replace(
                        '/\s+/',
                        '',
                        $value
                    )
                )
            );

        if ($value === '') {
            return null;
        }

        $suffixes = [
            'JR' =>
            'Jr.',

            'JR.' =>
            'Jr.',

            'SR' =>
            'Sr.',

            'SR.' =>
            'Sr.',

            'II' =>
            'II',

            'III' =>
            'III',

            'IV' =>
            'IV',

            'V' =>
            'V'
        ];

        if (
            !array_key_exists(
                $value,
                $suffixes
            )
        ) {
            throw new Exception(
                'Select a valid name suffix.'
            );
        }

        return $suffixes[$value];
    }

    private function calculateAge(
        string $birthdate
    ): int {
        $date =
            DateTime::createFromFormat(
                'Y-m-d',
                $birthdate
            );

        $errors =
            DateTime::getLastErrors();

        if (
            !$date ||
            (
                is_array($errors) &&
                (
                    $errors['warning_count'] > 0 ||
                    $errors['error_count'] > 0
                )
            )
        ) {
            throw new Exception(
                'Enter a valid birthdate.'
            );
        }

        $today =
            new DateTime('today');

        if ($date > $today) {
            throw new Exception(
                'Birthdate cannot be in the future.'
            );
        }

        $age =
            $date->diff(
                $today
            )->y;

        if (
            $age < 1 ||
            $age > 120
        ) {
            throw new Exception(
                'The calculated age is invalid.'
            );
        }

        return $age;
    }

    public function validatePassword(
        string $password,
        string $passwordConfirmation
    ): void {
        if (
            strlen($password) < 8
        ) {
            throw new Exception(
                'Password must contain at least 8 characters.'
            );
        }

        if (
            strlen($password) > 72
        ) {
            throw new Exception(
                'Password must not exceed 72 characters.'
            );
        }

        if (
            !preg_match(
                '/[A-Z]/',
                $password
            )
        ) {
            throw new Exception(
                'Password must contain at least one uppercase letter.'
            );
        }

        if (
            !preg_match(
                '/[a-z]/',
                $password
            )
        ) {
            throw new Exception(
                'Password must contain at least one lowercase letter.'
            );
        }

        if (
            !preg_match(
                '/\d/',
                $password
            )
        ) {
            throw new Exception(
                'Password must contain at least one number.'
            );
        }

        if (
            $password !==
            $passwordConfirmation
        ) {
            throw new Exception(
                'Password confirmation does not match.'
            );
        }
    }

    /* ==========================================
       STUDENT REGISTRATION
    ========================================== */

    private function registerStudent(
        array $data,
        array $cleanData
    ): int {
        $studentId = strtoupper(
            trim(
                $data['student_id']
                    ?? $data['studID']
                    ?? ''
            )
        );

        $departmentId = (int) (
            $data['department_id']
            ?? 0
        );

        $educationLevelId =
            (int) (
                $data['education_level_id']
                ?? 0
            );

        $academicProgramId =
            !empty($data['academic_program_id'])
            ? (int) $data['academic_program_id']
            : null;

        $gradeLevelId = (int) (
            $data['grade_level_id']
            ?? 0
        );

        $sectionId = (int) (
            $data['section_id']
            ?? 0
        );

        if ($studentId === '') {
            throw new Exception(
                'Student ID is required.'
            );
        }

        if (
            mb_strlen($studentId) > 50
        ) {
            throw new Exception(
                'Student ID must not exceed 50 characters.'
            );
        }

        if (
            $this->user
            ->findByStudentID(
                $studentId
            )
        ) {
            throw new Exception(
                'That Student ID is already registered.'
            );
        }

        $department =
            $this->user
            ->findDepartmentById(
                $departmentId
            );

        if (!$department) {
            throw new Exception(
                'Select a valid School Division.'
            );
        }

        $educationLevel =
            $this->user
            ->findEducationLevelById(
                $educationLevelId
            );

        if (!$educationLevel) {
            throw new Exception(
                'Select a valid Education Level.'
            );
        }

        if (
            (int) (
                $educationLevel['department_id']
                ?? 0
            ) !== $departmentId
        ) {
            throw new Exception(
                'The selected Education Level does not belong to the selected School Division.'
            );
        }

        $requiresProgram =
            $this->user
            ->educationLevelHasPrograms(
                $educationLevelId
            );

        if (
            $requiresProgram &&
            $academicProgramId === null
        ) {
            throw new Exception(
                'Select a valid Program or Strand.'
            );
        }

        if (!$requiresProgram) {
            $academicProgramId = null;
        }

        if (
            $academicProgramId !== null
        ) {
            $program =
                $this->user
                ->findAcademicProgramById(
                    $academicProgramId
                );

            if (!$program) {
                throw new Exception(
                    'Select a valid Program or Strand.'
                );
            }

            if (
                (int) $program['education_level_id'] !==
                $educationLevelId
            ) {
                throw new Exception(
                    'The selected Program or Strand does not belong to the selected School Division.'
                );
            }
        }

        $gradeLevel =
            $this->user
            ->findGradeLevelById(
                $gradeLevelId
            );

        if (!$gradeLevel) {
            throw new Exception(
                'Select a valid Grade or Year Level.'
            );
        }

        if (
            (int) $gradeLevel['education_level_id'] !==
            $educationLevelId
        ) {
            throw new Exception(
                'The selected Grade or Year Level does not belong to the selected Education Level.'
            );
        }

        if ($sectionId <= 0) {
            throw new Exception(
                'Select a valid Section.'
            );
        }

        $section =
            $this->user
            ->findSectionById(
                $sectionId
            );

        if (!$section) {
            throw new Exception(
                'Select a valid Section.'
            );
        }

        if (
            (int) $section['grade_level_id'] !==
            $gradeLevelId
        ) {
            throw new Exception(
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
            throw new Exception(
                'The selected Section does not belong to the selected Program or Strand.'
            );
        }

        $cleanData['studID'] =
            $studentId;

        $cleanData['department_id'] =
            $departmentId;

        $cleanData['education_level_id'] =
            $educationLevelId;

        $cleanData['academic_program_id'] =
            $academicProgramId;

        $cleanData['grade_level_id'] =
            $gradeLevelId;

        $cleanData['section_id'] =
            $sectionId;

        $legalDocuments =
            $this->user
            ->getActiveLegalDocumentVersions();

        if (
            empty($legalDocuments['Terms']) ||
            empty($legalDocuments['Privacy'])
        ) {
            throw new RuntimeException(
                'Active Terms and Privacy versions are not configured.'
            );
        }

        $this->user->beginTransaction();

        try {
            $studentUserId =
                $this->user->create(
                    $cleanData
                );

            $userAgent =
                isset($_SERVER['HTTP_USER_AGENT'])
                ? substr(
                    (string) $_SERVER['HTTP_USER_AGENT'],
                    0,
                    500
                )
                : null;

            $this->user->createLegalAcceptance(
                $studentUserId,
                (int) $legalDocuments['Terms']['legal_document_version_id'],
                'Registration',
                $userAgent
            );

            $this->user->createLegalAcceptance(
                $studentUserId,
                (int) $legalDocuments['Privacy']['legal_document_version_id'],
                'Registration',
                $userAgent
            );

            $this->user->commit();

            return $studentUserId;
        } catch (Throwable $exception) {
            $this->user->rollback();

            throw $exception;
        }
    }

    /* ==========================================
       PARENT REGISTRATION
    ========================================== */

    private function registerParent(
        array $data,
        array $cleanData
    ): int {
        $childStudentId = strtoupper(
            trim(
                $data['child_student_id'] ?? ''
            )
        );

        $relationship = ucfirst(
            strtolower(
                trim(
                    $data['relationship'] ?? ''
                )
            )
        );

        $allowedRelationships = [
            'Mother',
            'Father',
            'Guardian',
            'Grandparent',
            'Relative',
            'Other'
        ];

        $withoutAccount = ($data['child_no_account'] ?? '') === '1';
        $childRecords = new ParentChildRecord($this->user->getDatabaseConnection());
        $childRecord = $withoutAccount ? $childRecords->validate($data) : null;

        if (!$withoutAccount && $childStudentId === '') {
            throw new Exception(
                'Child Student ID is required.'
            );
        }

        if (
            !in_array(
                $relationship,
                $allowedRelationships,
                true
            )
        ) {
            throw new Exception(
                'Select a valid relationship to the student.'
            );
        }

        $student = $withoutAccount ? null : $this->user->findActiveStudent($childStudentId);

        if (!$withoutAccount && !$student) {
            throw new Exception(
                'No active Student account was found using that Student ID.'
            );
        }

        /*
         * Parent classification is inherited
         * from the linked Student.
         *
         * Existing links use parent_student. Independent child claims
         * stay in parent_child_record and confer no access until verified.
         */
        $cleanData['department_id'] =
            $student['department_id'] ?? null;

        $cleanData['education_level_id'] =
            $student['education_level_id'] ?? null;

        $cleanData['academic_program_id'] =
            $student['academic_program_id'] ?? null;

        $cleanData['grade_level_id'] =
            $student['grade_level_id'] ?? null;

        $cleanData['section_id'] =
            $student['section_id'] ?? null;

        $legalDocuments =
            $this->user
            ->getActiveLegalDocumentVersions();

        if (
            empty($legalDocuments['Terms']) ||
            empty($legalDocuments['Privacy'])
        ) {
            throw new RuntimeException(
                'Active Terms and Privacy versions are not configured.'
            );
        }

        $this->user->beginTransaction();

        try {
            $parentUserId =
                $this->user->create(
                    $cleanData
                );

            if ($withoutAccount) {
                $childRecords->create($parentUserId, $childRecord);
            } else {
                $this->user->createParentStudentLink(
                    $parentUserId,
                    (int) $student['user_id'],
                    $relationship
                );
            }

            $userAgent =
                isset($_SERVER['HTTP_USER_AGENT'])
                ? substr(
                    (string) $_SERVER['HTTP_USER_AGENT'],
                    0,
                    500
                )
                : null;

            $this->user->createLegalAcceptance(
                $parentUserId,
                (int) $legalDocuments['Terms']['legal_document_version_id'],
                'Registration',
                $userAgent
            );

            $this->user->createLegalAcceptance(
                $parentUserId,
                (int) $legalDocuments['Privacy']['legal_document_version_id'],
                'Registration',
                $userAgent
            );

            $this->user->commit();

            return $parentUserId;
        } catch (Throwable $exception) {
            $this->user->rollback();

            throw $exception;
        }
    }

    /* ==========================================
       REGISTRATION FORM DATA
    ========================================== */

    public function getRegistrationOptions(): array
    {
        return [
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

            'legal_documents' =>
            $this->user
                ->getActiveLegalDocumentVersions(),

            'child_registration_available' => (new ParentChildRecord($this->user->getDatabaseConnection()))->available(),
            'child_sections' => (new ParentChildRecord($this->user->getDatabaseConnection()))->sections(),
            'child_reasons' => ParentChildRecord::REASONS,
            'relationships' => [
                'Mother',
                'Father',
                'Guardian',
                'Grandparent',
                'Relative',
                'Other'
            ]
        ];
    }
}
