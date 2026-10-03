<?php

require_once __DIR__
    . '/../models/User.php';

require_once __DIR__
    . '/NotificationService.php';

class AccountApprovalService
{
    private User $user;
    private NotificationService $notifications;

    public function __construct(?mysqli $connection = null)
    {
        $this->user =
            new User($connection);

        $this->notifications =
            new NotificationService($connection);
    }

    public function childSections(): array
    {
        return (new ParentChildRecord($this->user->getDatabaseConnection()))->sections();
    }

    public function updateChildRecord(int $parentId, int $adminId, array $data): void
    {
        (new ParentChildRecord($this->user->getDatabaseConnection()))->update($parentId, $adminId, $data);
    }

    /* ==========================================
       PENDING REGISTRATION QUEUE
    ========================================== */

    public function countPendingRegistrations(int $limit = 100): int
    {
        return $this->user->countPendingRegistrations($limit);
    }

    public function getPendingRegistrations(
        int $limit = 100
    ): array {
        return $this->user
            ->getPendingRegistrations(
                $limit
            );
    }

    /* ==========================================
       REGISTRATION REVIEW DETAILS
    ========================================== */

    public function getRegistrationForReview(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'Invalid registration user ID.'
            );
        }

        $registration =
            $this->user
            ->findRegistrationForReview(
                $userId
            );

        if (!$registration) {
            throw new RuntimeException(
                'The registration could not be found.'
            );
        }

        $registration['child_record'] = ($registration['role_prefix'] ?? '') === 'Parent'
            ? (new ParentChildRecord($this->user->getDatabaseConnection()))->find($userId) : null;
        return $registration;
    }



    /* ==========================================
       APPROVE REGISTRATION
    ========================================== */

    public function approveRegistration(
        int $userId,
        int $adminId,
        ?string $reviewNotes = null
    ): array {
        if (
            $userId <= 0 ||
            $adminId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid applicant or Administrator ID.'
            );
        }

        $registration =
            $this->getRegistrationForReview(
                $userId
            );

        if (
            (
                $registration['status']
                ?? ''
            ) !== 'Pending'
        ) {
            throw new RuntimeException(
                'The registration is no longer pending.'
            );
        }

        $role =
            trim(
                (string) (
                    $registration['role_prefix']
                    ?? ''
                )
            );

        if (
            !in_array(
                $role,
                [
                    'Student',
                    'Parent'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only Student and Parent registrations may be approved here.'
            );
        }

        $email =
            strtolower(
                trim(
                    (string) (
                        $registration['email']
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
            throw new RuntimeException(
                'The applicant does not have a valid email address.'
            );
        }

        if ($role === 'Student') {
            $studentId =
                trim(
                    (string) (
                        $registration['studID']
                        ?? ''
                    )
                );

            $departmentId =
                (int) (
                    $registration['department_id']
                    ?? 0
                );

            $educationLevelId =
                (int) (
                    $registration['education_level_id']
                    ?? 0
                );

            $academicProgramId =
                !empty($registration['academic_program_id'])
                ? (int) $registration['academic_program_id']
                : null;

            $gradeLevelId =
                (int) (
                    $registration['grade_level_id']
                    ?? 0
                );

            $sectionId =
                (int) (
                    $registration['section_id']
                    ?? 0
                );

            if (
                $studentId === '' ||
                $departmentId <= 0 ||
                $educationLevelId <= 0 ||
                $gradeLevelId <= 0 ||
                $sectionId <= 0
            ) {
                throw new RuntimeException(
                    'The Student registration has incomplete academic information.'
                );
            }

            $this->validateActiveStudentAcademicAssignment(
                $departmentId,
                $educationLevelId,
                $academicProgramId,
                $gradeLevelId,
                $sectionId
            );
        }

        if ($role === 'Parent' && empty($registration['child_record'])) {
            $relationshipId =
                (int) (
                    $registration['parent_student_id']
                    ?? 0
                );

            $childUserId =
                (int) (
                    $registration['child_user_id']
                    ?? 0
                );

            $relationshipStatus =
                trim(
                    (string) (
                        $registration['relationship_status']
                        ?? ''
                    )
                );

            $childStatus =
                trim(
                    (string) (
                        $registration['child_account_status']
                        ?? ''
                    )
                );

            if (
                $relationshipId <= 0 ||
                $childUserId <= 0 ||
                $relationshipStatus !== 'Pending' ||
                $childStatus !== 'Active'
            ) {
                throw new RuntimeException(
                    'The Parent registration does not have a valid pending relationship with an active Student.'
                );
            }
        }

        $approved =
            $this->user
            ->approveRegistration(
                $userId,
                $adminId,
                $reviewNotes
            );

        if (!$approved) {
            throw new RuntimeException(
                'The registration could not be approved.'
            );
        }

        $updatedRegistration =
            $this->user
            ->findRegistrationForReview(
                $userId
            );

        if (!$updatedRegistration) {
            throw new RuntimeException(
                'The approved account could not be reloaded.'
            );
        }

        $this->notifyApplicantDecisionSafely(
            $updatedRegistration,
            'approved',
            $reviewNotes
        );

        return $updatedRegistration;
    }


    /* ==========================================
   ACTIVE STUDENT ACADEMIC ASSIGNMENT
========================================== */

    private function validateActiveStudentAcademicAssignment(
        int $departmentId,
        int $educationLevelId,
        ?int $academicProgramId,
        int $gradeLevelId,
        int $sectionId
    ): void {
        $department =
            $this->user
            ->findDepartmentById(
                $departmentId
            );

        if (!$department) {
            throw new RuntimeException(
                'The Student registration references an inactive or unavailable School Division.'
            );
        }

        $educationLevel =
            $this->user
            ->findEducationLevelById(
                $educationLevelId
            );

        if (!$educationLevel) {
            throw new RuntimeException(
                'The Student registration references an inactive or unavailable Education Level.'
            );
        }

        if (
            (int) (
                $educationLevel['department_id']
                ?? 0
            ) !== $departmentId
        ) {
            throw new RuntimeException(
                'The Student registration has an invalid School Division and Education Level assignment.'
            );
        }

        $requiresProgram =
            $this->user
            ->educationLevelHasPrograms(
                $educationLevelId
            );

        if ($requiresProgram) {
            if ($academicProgramId === null) {
                throw new RuntimeException(
                    'The Student registration requires an active Program or Strand.'
                );
            }

            $program =
                $this->user
                ->findAcademicProgramById(
                    $academicProgramId
                );

            if (!$program) {
                throw new RuntimeException(
                    'The Student registration references an inactive or unavailable Program or Strand.'
                );
            }

            if (
                (int) (
                    $program['education_level_id']
                    ?? 0
                ) !== $educationLevelId
            ) {
                throw new RuntimeException(
                    'The Student registration has an invalid Program or Strand assignment.'
                );
            }
        } elseif ($academicProgramId !== null) {
            throw new RuntimeException(
                'The Student registration references a Program or Strand that is no longer available for its Education Level.'
            );
        }

        $gradeLevel =
            $this->user
            ->findGradeLevelById(
                $gradeLevelId
            );

        if (!$gradeLevel) {
            throw new RuntimeException(
                'The Student registration references an inactive or unavailable Grade or Year Level.'
            );
        }

        if (
            (int) (
                $gradeLevel['education_level_id']
                ?? 0
            ) !== $educationLevelId
        ) {
            throw new RuntimeException(
                'The Student registration has an invalid Grade or Year Level assignment.'
            );
        }

        $section =
            $this->user
            ->findSectionById(
                $sectionId
            );

        if (!$section) {
            throw new RuntimeException(
                'The Student registration references an inactive or unavailable Section.'
            );
        }

        if (
            (int) (
                $section['grade_level_id']
                ?? 0
            ) !== $gradeLevelId
        ) {
            throw new RuntimeException(
                'The Student registration has an invalid Section and Grade or Year Level assignment.'
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
            throw new RuntimeException(
                'The Student registration has an invalid Section and Program or Strand assignment.'
            );
        }
    }


    /* ==========================================
   REJECT REGISTRATION
========================================== */

    public function rejectRegistration(
        int $userId,
        int $adminId,
        string $reviewNotes
    ): array {
        if (
            $userId <= 0 ||
            $adminId <= 0
        ) {
            throw new InvalidArgumentException(
                'Invalid applicant or Administrator ID.'
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
                'The rejection reason must not exceed 1000 characters.'
            );
        }

        $registration =
            $this->getRegistrationForReview(
                $userId
            );

        if (
            (
                $registration['status']
                ?? ''
            ) !== 'Pending'
        ) {
            throw new RuntimeException(
                'The registration is no longer pending.'
            );
        }

        $role =
            trim(
                (string) (
                    $registration['role_prefix']
                    ?? ''
                )
            );

        if (
            !in_array(
                $role,
                [
                    'Student',
                    'Parent'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only Student and Parent registrations may be rejected here.'
            );
        }

        $rejected =
            $this->user
            ->rejectRegistration(
                $userId,
                $adminId,
                $reviewNotes
            );

        if (!$rejected) {
            throw new RuntimeException(
                'The registration could not be rejected.'
            );
        }

        $updatedRegistration =
            $this->user
            ->findRegistrationForReview(
                $userId
            );

        if (!$updatedRegistration) {
            throw new RuntimeException(
                'The rejected account could not be reloaded.'
            );
        }

        $this->notifyApplicantDecisionSafely(
            $updatedRegistration,
            'rejected',
            $reviewNotes
        );

        return $updatedRegistration;
    }


    /* ==========================================
   SAFE APPLICANT DECISION NOTIFICATION
========================================== */

    private function notifyApplicantDecisionSafely(
        array $registration,
        string $decision,
        ?string $reviewNotes
    ): void {
        $applicantId =
            (int) (
                $registration['user_id']
                ?? 0
            );
        $applicantName =
            implode(
                ' ',
                array_filter(
                    [
                        trim(
                            (string) (
                                $registration['first_name']
                                ?? ''
                            )
                        ),

                        trim(
                            (string) (
                                $registration['middle_name']
                                ?? ''
                            )
                        ),

                        trim(
                            (string) (
                                $registration['last_name']
                                ?? ''
                            )
                        ),

                        trim(
                            (string) (
                                $registration['name_suffix']
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

        if ($applicantId <= 0) {
            error_log(
                'Registration decision notification skipped: invalid applicant ID.'
            );

            return;
        }

        try {
            $created =
                $this->notifications
                ->notifyRegistrationDecision(
                    $applicantId,
                    $applicantName,
                    $decision,
                    $reviewNotes
                );

            error_log(
                'Registration decision notification result: user #'
                    . $applicantId
                    . ' | decision='
                    . $decision
                    . ' | created='
                    . (
                        $created
                        ? '1'
                        : '0'
                    )
            );
        } catch (Throwable $exception) {
            /*
         * Notification failure must not undo a
         * successful account review decision.
         */
            error_log(
                'Registration decision notification error: user #'
                    . $applicantId
                    . ' | decision='
                    . $decision
                    . ' | '
                    . $exception->getMessage()
            );
        }
    }
}
