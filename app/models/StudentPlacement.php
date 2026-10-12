<?php
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/StudentProfile.php';
require_once __DIR__ . '/../services/StudentPlacementService.php';

class StudentPlacement extends BaseModel
{
    public const FIELDS = ['department_id', 'education_level_id', 'academic_program_id', 'grade_level_id', 'section_id'];

    public function currentDetailsConfirmed(int $userId): bool
    {
        $statement = $this->conn->prepare("SELECT a.account_details_confirmed_at FROM student_profile_cycle_assignment a
            INNER JOIN student_profile p ON p.student_profile_id=a.student_profile_id
            INNER JOIN student_profile_cycle c ON c.student_profile_cycle_id=a.student_profile_cycle_id
            WHERE p.user_id=? AND c.status='Active' AND (c.opens_at IS NULL OR c.opens_at<=NOW())
            ORDER BY c.is_default ASC,c.activated_at DESC,c.student_profile_cycle_id DESC LIMIT 1");
        $statement->bind_param('i', $userId); $statement->execute(); $row = $statement->get_result()->fetch_assoc(); $statement->close();
        return !empty($row['account_details_confirmed_at']);
    }

    public function reviewDirectory(int $adminId): array
    {
        $this->requireAdministrator($adminId);
        return $this->conn->query("SELECT h.*,u.first_name,u.last_name,u.studID,c.cycle_name FROM student_profile_placement_change h
            INNER JOIN user u ON u.user_id=h.student_user_id
            INNER JOIN student_profile_cycle_assignment a ON a.student_profile_cycle_assignment_id=h.student_profile_cycle_assignment_id
            INNER JOIN student_profile_cycle c ON c.student_profile_cycle_id=a.student_profile_cycle_id
            ORDER BY (h.review_status='Unreviewed') DESC,h.created_at DESC,h.student_profile_placement_change_id DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
    }

    private function requireAdministrator(int $adminId): void
    {
        $actor = (new User($this->conn))->findById($adminId);
        if (!$actor || $actor['status'] !== 'Active' || $actor['role_prefix'] !== 'Admin') throw new DomainException('Administrator access is required.');
    }

    public function review(int $adminId, int $changeId, string $action, array $input): void
    {
        $this->requireAdministrator($adminId);
        if (!in_array($action, ['verify','correct'], true)) throw new InvalidArgumentException('Choose a valid review action.');
        $notes = $input['review_notes'] ?? '';
        if (!is_string($notes) || trim($notes) === '' || mb_strlen($notes) > 600) throw new InvalidArgumentException('Enter verification notes, up to 600 characters.');
        $this->conn->begin_transaction();
        try {
            $statement = $this->conn->prepare('SELECT * FROM student_profile_placement_change WHERE student_profile_placement_change_id=? FOR UPDATE');
            $statement->bind_param('i', $changeId); $statement->execute(); $history = $statement->get_result()->fetch_assoc(); $statement->close();
            if (!$history || $history['review_status'] !== 'Unreviewed') throw new DomainException('This change is unavailable or already reviewed.');
            $userId = (int) $history['student_user_id'];
            $statement = $this->conn->prepare('SELECT department_id,education_level_id,academic_program_id,grade_level_id,section_id FROM user WHERE user_id=? FOR UPDATE');
            $statement->bind_param('i', $userId); $statement->execute(); $current = $statement->get_result()->fetch_assoc(); $statement->close();
            foreach (self::FIELDS as $field) $current[$field] = $current[$field] === null ? null : (int) $current[$field];
            if ($action === 'correct' && $current !== json_decode($history['reported_placement_json'], true)) throw new DomainException('The student placement has changed again. Review the latest record instead.');
            $status = $action === 'verify' ? 'Verified' : 'Corrected';
            if ($action === 'correct') {
                $placement = (new StudentPlacementService(new User($this->conn)))->validate($input);
                (new User($this->conn))->updateManagedAcademicAssignment($userId, ...array_values($placement));
                $notes .= '\nCorrected placement: ' . json_encode($placement, JSON_THROW_ON_ERROR);
            }
            $statement = $this->conn->prepare('UPDATE student_profile_placement_change SET review_status=?,reviewed_by=?,reviewed_at=NOW(),review_notes=? WHERE student_profile_placement_change_id=?');
            $statement->bind_param('sisi', $status, $adminId, $notes, $changeId); $statement->execute(); $statement->close();
            $this->conn->commit();
        } catch (Throwable $error) { $this->conn->rollback(); throw $error; }
    }

    public function pageData(int $userId): array
    {
        $users = new User($this->conn);
        $user = $users->findById($userId);
        if (!$user || $user['role_prefix'] !== 'Student' || $user['status'] !== 'Active') throw new DomainException('Student access is required.');
        unset($user['password']);
        $assignment = (new StudentProfile($this->conn))->getCurrentSurveyAssignment($userId);
        if (!$assignment) throw new DomainException('No profile update is available.');
        $statement = $this->conn->prepare('SELECT account_details_confirmed_at, account_details_draft_json FROM student_profile_cycle_assignment WHERE student_profile_cycle_assignment_id=?');
        $id = (int) $assignment['student_profile_cycle_assignment_id'];
        $statement->bind_param('i', $id); $statement->execute();
        $state = $statement->get_result()->fetch_assoc(); $statement->close();
        $draft = json_decode($state['account_details_draft_json'] ?? 'null', true);
        return ['user' => $user, 'assignment' => $assignment, 'confirmed_at' => $state['account_details_confirmed_at'],
            'draft' => is_array($draft) ? $draft : null,
            'departments' => $users->getActiveDepartments(), 'levels' => $users->getActiveEducationLevels(), 'programs' => $users->getActiveAcademicPrograms(),
            'grades' => $users->getActiveGradeLevels(), 'sections' => $users->getActiveSections()];
    }

    public function stage(int $userId, int $assignmentId, array $input): void
    {
        $data = $this->pageData($userId);
        if ((int) $data['assignment']['student_profile_cycle_assignment_id'] !== $assignmentId) throw new DomainException('The update assignment changed. Please reload.');
        $this->conn->begin_transaction();
        try {
            $assignment = $this->lockAssignment($userId, $assignmentId);
            if (!in_array($assignment['assignment_status'], ['Assigned', 'InProgress'], true)) throw new DomainException('This update is already complete or exempt.');
            $draft = (new StudentPlacementService(new User($this->conn)))->validate($input);
            $statement = $this->conn->prepare('SELECT department_id,education_level_id,academic_program_id,grade_level_id,section_id FROM user WHERE user_id=? FOR UPDATE');
            $statement->bind_param('i', $userId); $statement->execute();
            $baseline = $statement->get_result()->fetch_assoc(); $statement->close();
            foreach (self::FIELDS as $field) $baseline[$field] = $baseline[$field] === null ? null : (int) $baseline[$field];
            $draft['_baseline'] = $baseline;
            $json = json_encode($draft, JSON_THROW_ON_ERROR);
            $statement = $this->conn->prepare("UPDATE student_profile_cycle_assignment SET account_details_draft_json=?, account_details_confirmed_at=NOW(), assignment_status='InProgress', started_at=COALESCE(started_at,NOW()), last_saved_at=NOW() WHERE student_profile_cycle_assignment_id=?");
            $statement->bind_param('si', $json, $assignmentId); $statement->execute(); $statement->close();
            $this->conn->commit();
        } catch (Throwable $error) { $this->conn->rollback(); throw $error; }
    }

    private function lockAssignment(int $userId, int $assignmentId): array
    {
        $statement = $this->conn->prepare("SELECT a.* FROM student_profile_cycle_assignment a
            INNER JOIN student_profile p ON p.student_profile_id=a.student_profile_id
            INNER JOIN student_profile_cycle c ON c.student_profile_cycle_id=a.student_profile_cycle_id
            INNER JOIN user u ON u.user_id=p.user_id INNER JOIN role r ON r.role_id=u.role_id
            WHERE a.student_profile_cycle_assignment_id=? AND p.user_id=? AND c.status='Active'
              AND (c.opens_at IS NULL OR c.opens_at<=NOW()) AND u.status='Active' AND r.role_prefix='Student' FOR UPDATE");
        $statement->bind_param('ii', $assignmentId, $userId); $statement->execute();
        $row = $statement->get_result()->fetch_assoc(); $statement->close();
        if (!$row) throw new DomainException('The assigned update is unavailable.');
        return $row;
    }

    /** Called inside the questionnaire save transaction, before completion commit. */
    public function applyOnCompletion(int $userId, int $assignmentId): void
    {
        $assignment = $this->lockAssignment($userId, $assignmentId);
        if ($assignment['assignment_status'] === 'Completed') return;
        if (!$assignment['account_details_confirmed_at']) throw new DomainException('Confirm your account details before submitting the questionnaire.');
        $draft = json_decode($assignment['account_details_draft_json'] ?? '', true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($draft)) throw new DomainException('Confirm your account details again.');
        $placement = (new StudentPlacementService(new User($this->conn)))->validate($draft);
        $statement = $this->conn->prepare('SELECT department_id,education_level_id,academic_program_id,grade_level_id,section_id FROM user WHERE user_id=? FOR UPDATE');
        $statement->bind_param('i', $userId); $statement->execute();
        $old = $statement->get_result()->fetch_assoc(); $statement->close();
        foreach (self::FIELDS as $field) $old[$field] = $old[$field] === null ? null : (int) $old[$field];
        if ($old === $placement) return;
        if (isset($draft['_baseline']) && $draft['_baseline'] !== $old) {
            throw new DomainException('The school changed your placement while this update was in progress. Review and confirm your account details again.');
        }
        $oldJson = json_encode($old, JSON_THROW_ON_ERROR); $newJson = json_encode($placement, JSON_THROW_ON_ERROR);
        $statement = $this->conn->prepare('INSERT INTO student_profile_placement_change (student_profile_cycle_assignment_id,student_user_id,actor_user_id,previous_placement_json,reported_placement_json) VALUES (?,?,?,?,?)');
        $statement->bind_param('iiiss', $assignmentId, $userId, $userId, $oldJson, $newJson); $statement->execute(); $statement->close();
        (new User($this->conn))->updateManagedAcademicAssignment($userId, ...array_values($placement));
    }
}
