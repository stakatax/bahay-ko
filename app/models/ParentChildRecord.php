<?php
require_once __DIR__ . '/BaseModel.php';

/** Child enrollment claims are independent of login accounts; only Admin verification grants access. */
class ParentChildRecord extends BaseModel
{
    public const REASONS = [
        'assistance' => 'Child needs adult assistance',
        'device' => 'No personal device or email',
        'not_completed' => 'Registration has not been completed',
        'other' => 'Other'
    ];

    public function available(): bool
    {
        try { $this->rows('SELECT parent_user_id FROM parent_child_record LIMIT 0'); return true; }
        catch (mysqli_sql_exception $e) { if ($e->getCode() === 1146) { return false; } throw $e; }
    }

    private function rows(string $sql, array $params = []): array
    {
        $result = $this->conn->execute_query($sql, $params);
        if ($result === false) { throw new RuntimeException('Unable to process child verification.'); }
        return $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function sections(?int $sectionId = null): array
    {
        return $this->rows("SELECT s.section_id, s.academic_program_id, g.grade_level_id,
            e.education_level_id, d.department_id,
            CONCAT_WS(' / ', d.department_name,e.education_level_name,p.program_code,g.grade_level_name,s.section_name) AS label
            FROM section s JOIN grade_level g ON g.grade_level_id=s.grade_level_id AND g.status='Active'
            JOIN education_level e ON e.education_level_id=g.education_level_id AND e.status='Active'
            JOIN department d ON d.department_id=e.department_id AND d.status='Active'
            LEFT JOIN academic_program p ON p.academic_program_id=s.academic_program_id
            WHERE s.status='Active' AND ((s.academic_program_id IS NULL AND NOT EXISTS
                (SELECT 1 FROM academic_program ap WHERE ap.education_level_id=e.education_level_id AND ap.status='Active'))
                OR (p.status='Active' AND p.education_level_id=e.education_level_id))"
            . ($sectionId === null ? '' : ' AND s.section_id=?') . ' ORDER BY label',
            $sectionId === null ? [] : [$sectionId]);
    }

    public function section(int $id): array
    {
        foreach ($this->sections($id) as $section) { if ((int)$section['section_id'] === $id) { return $section; } }
        throw new InvalidArgumentException('Select an active child grade and section.');
    }

    public function find(int $parentId, bool $lock = false): ?array
    {
        if (!$this->available()) { return null; }
        return $this->rows('SELECT * FROM parent_child_record WHERE parent_user_id=?' . ($lock ? ' FOR UPDATE' : ''), [$parentId])[0] ?? null;
    }

    public function validate(array $data): array
    {
        $this->requireScalarFields($data);
        $name = trim((string)($data['child_name'] ?? ''));
        $id = strtoupper(trim((string)($data['child_student_id'] ?? '')));
        $reason = (string)($data['child_reason'] ?? '');
        $details = trim((string)($data['child_reason_details'] ?? ''));
        $relationship = ucfirst(strtolower(trim((string)($data['relationship'] ?? ''))));
        if ($name === '' || mb_strlen($name)>200) { throw new InvalidArgumentException('Child full name is required (maximum 200 characters).'); }
        if (mb_strlen($id)>50) { throw new InvalidArgumentException('Child Student ID must be at most 50 characters.'); }
        if (mb_strlen($details)>500) { throw new InvalidArgumentException('Child reason details must be at most 500 characters.'); }
        if (!isset(self::REASONS[$reason])) { throw new InvalidArgumentException('Select a valid child registration reason.'); }
        if (!in_array($relationship,['Mother','Father','Guardian','Grandparent','Relative','Other'],true)) {
            throw new InvalidArgumentException('Select a valid relationship to the student.');
        }
        $sectionId = filter_var($data['child_section_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$sectionId || $sectionId < 1) { throw new InvalidArgumentException('Select an active child grade and section.'); }
        $section = $this->section($sectionId);
        return ['child_name'=>$name,'child_student_id'=>$id ?: null,'section_id'=>(int)$section['section_id'],
            'relationship'=>$relationship,'reason'=>$reason,'reason_details'=>$details];
    }

    private function requireScalarFields(array $data): void
    {
        foreach (['child_name','child_student_id','child_section_id','relationship','child_reason','child_reason_details',
            'review_notes','child_action','confirm_child_review','link_student_id'] as $field) {
            if (isset($data[$field]) && !is_scalar($data[$field])) { throw new InvalidArgumentException('Invalid child verification input.'); }
        }
    }

    public function create(int $parentId, array $data): void
    {
        if (!$this->available()) { throw new RuntimeException('Child verification registration is not configured yet.'); }
        $this->rows("INSERT INTO parent_child_record (parent_user_id,child_name,child_student_id,section_id,relationship,reason,reason_details)
            VALUES (?,?,?,?,?,?,?)", [$parentId,$data['child_name'],$data['child_student_id'],$data['section_id'],$data['relationship'],$data['reason'],$data['reason_details']]);
    }

    /** Called inside User's existing approval transaction after locking the Parent. */
    public function approve(int $parentId, int $adminId, ?string $notes): bool
    {
        $record = $this->find($parentId, true);
        if (!$record) { return false; }
        if ($record['status'] !== 'Pending') { throw new DomainException('Child verification is no longer pending.'); }
        $this->section((int)$record['section_id']);
        if (trim((string)$notes)==='' || mb_strlen((string)$notes)>1000) { throw new InvalidArgumentException('Record how enrollment and the Parent relationship were verified (maximum 1000 characters).'); }
        $this->rows("UPDATE parent_child_record SET status='Verified',verified_by=?,verified_at=NOW(),review_notes=? WHERE parent_user_id=? AND status='Pending'",[$adminId,$notes,$parentId]);
        $this->audit($parentId, $adminId, 'approve', (string)$notes, $record);
        return true;
    }

    public function reject(int $parentId, int $adminId, string $notes): bool
    {
        $record = $this->find($parentId,true);
        if (!$record) { return false; }
        $this->rows("UPDATE parent_child_record SET status='Rejected',verified_by=?,verified_at=NOW(),review_notes=? WHERE parent_user_id=? AND status='Pending'",[$adminId,$notes,$parentId]);
        if ($this->conn->affected_rows !== 1) { throw new DomainException('Child verification is no longer pending.'); }
        $this->audit($parentId, $adminId, 'reject', $notes, $record);
        return true;
    }

    public function profiles(int $parentId): array
    {
        // Do not fetch names, reasons or review history during content eligibility checks.
        try {
            $record=$this->rows("SELECT section_id FROM parent_child_record WHERE parent_user_id=? AND status='Verified'",[$parentId])[0] ?? null;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode()===1146) { return []; }
            throw $e;
        }
        return $record ? $this->sections((int)$record['section_id']) : [];
    }

    private function audit(int $parentId, int $adminId, string $action, string $notes, array $before): void
    {
        $after = $this->find($parentId, true);
        $history = $before['review_history'] ? json_decode($before['review_history'], true, 512, JSON_THROW_ON_ERROR) : [];
        $fields = array_flip(['child_name','child_student_id','section_id','status','linked_student_user_id']);
        $history[] = ['admin_id'=>$adminId,'action'=>$action,'at'=>gmdate('c'),'note'=>$notes,
            'before'=>array_intersect_key($before,$fields),'after'=>array_intersect_key($after,$fields)];
        $this->rows('UPDATE parent_child_record SET review_history=? WHERE parent_user_id=?',
            [json_encode($history, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),$parentId]);
    }

    public function update(int $parentId, int $adminId, array $data): void
    {
        $this->requireScalarFields($data);
        $notes=trim((string)($data['review_notes'] ?? ''));
        $action=(string)($data['child_action'] ?? '');
        if (($data['confirm_child_review'] ?? '')!=='1' || $notes==='' || mb_strlen($notes)>1000
            || !in_array($action,['update','revoke','link'],true)) { throw new InvalidArgumentException('Confirm verification and provide a review note (maximum 1000 characters).'); }
        $this->conn->begin_transaction();
        try {
            $admin=$this->rows("SELECT u.user_id FROM user u JOIN role r ON r.role_id=u.role_id WHERE u.user_id=? AND u.status='Active' AND r.role_prefix='Admin' FOR UPDATE",[$adminId]);
            if (!$admin) { throw new DomainException('Only an active Administrator may verify a child.'); }
            $parent=$this->rows("SELECT u.status FROM user u JOIN role r ON r.role_id=u.role_id WHERE u.user_id=? AND r.role_prefix='Parent' FOR UPDATE",[$parentId])[0] ?? null;
            if (!$parent || $parent['status'] !== 'Active') { throw new DomainException('Pending applications are read-only. Use Approve or Reject Registration after verification.'); }
            $record=$this->find($parentId,true);
            if (!$record || in_array($record['status'],['Linked','Rejected'],true)) { throw new DomainException('This child record cannot be changed here.'); }
            if ($action==='revoke') {
                if ($parent['status']!=='Active') { throw new DomainException('Reject a pending application using Reject Registration.'); }
                $this->rows("UPDATE parent_child_record SET status='Revoked',verified_by=?,verified_at=NOW(),review_notes=? WHERE parent_user_id=?",[$adminId,$notes,$parentId]);
            } elseif ($action==='link') {
                if ($parent['status']!=='Active' || $record['status']!=='Verified') { throw new DomainException('Verify and approve the Parent before linking a Student account.'); }
                $id=strtoupper(trim((string)($data['link_student_id'] ?? '')));
                if ($id==='') { throw new InvalidArgumentException('Student ID is required for linking.'); }
                $student=$this->rows("SELECT u.user_id FROM user u JOIN role r ON r.role_id=u.role_id WHERE u.studID=? AND u.status='Active' AND r.role_prefix='Student' FOR UPDATE",[$id])[0] ?? null;
                if (!$student) { throw new DomainException('No active Student account was found using that Student ID.'); }
                if ($record['child_student_id'] && strcasecmp($record['child_student_id'],$id)!==0) { throw new DomainException('Student ID differs from the verified child record. Correct the record after checking school records first.'); }
                $this->rows("INSERT INTO parent_student (parent_user_id,student_user_id,relationship,status,verified_by,verified_at)
                    VALUES (?,?,?,'Verified',?,NOW()) ON DUPLICATE KEY UPDATE status='Verified',relationship=VALUES(relationship),verified_by=VALUES(verified_by),verified_at=NOW()",[$parentId,$student['user_id'],$record['relationship'],$adminId]);
                $this->rows("UPDATE parent_child_record SET status='Linked',linked_student_user_id=?,verified_by=?,verified_at=NOW(),review_notes=? WHERE parent_user_id=?",[$student['user_id'],$adminId,$notes,$parentId]);
            } else {
                $clean=$this->validate(array_merge($data,['relationship'=>$record['relationship'],'child_reason'=>$record['reason'],'child_reason_details'=>$record['reason_details']]));
                $status=$parent['status']==='Pending' ? 'Pending' : 'Verified';
                $this->rows('UPDATE parent_child_record SET child_name=?,child_student_id=?,section_id=?,status=?,verified_by=?,verified_at=NOW(),review_notes=? WHERE parent_user_id=?',[$clean['child_name'],$clean['child_student_id'],$clean['section_id'],$status,$adminId,$notes,$parentId]);
            }
            $this->audit($parentId, $adminId, $action, $notes, $record);
            $this->conn->commit();
        } catch (Throwable $e) { $this->conn->rollback(); throw $e; }
    }
}
