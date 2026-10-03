<?php
require_once __DIR__ . '/../models/SessionAccount.php';

class SessionSecurityService
{
    public function __construct(private SessionAccount $accounts) {}

    public function refresh(array &$session): string
    {
        if (empty($session['user_id'])) return 'guest';
        $id = filter_var($session['user_id'], FILTER_VALIDATE_INT);
        $actor = $id !== false && $id > 0 ? $this->accounts->findForSession($id) : null;
        $version = $session['auth_credential_version'] ?? null;
        if (!$actor || $actor['status'] !== 'Active'
            || !in_array($actor['role_prefix'],['Admin','Faculty','Student','Parent'],true)
            || ($session['role'] ?? '') !== $actor['role_prefix']
            || (int)($session['role_id'] ?? 0) !== (int)$actor['role_id']
            || !is_string($version) || $version === ''
            || !is_string($actor['credential_version'])
            || !hash_equals($actor['credential_version'],$version)) {
            $session = [];
            return 'revoked';
        }
        foreach (['department_id','education_level_id','academic_program_id','grade_level_id','section_id'] as $field) {
            $session[$field] = $actor[$field] !== null ? (int)$actor[$field] : null;
        }
        foreach (['department_name','education_level_name','academic_program_name','academic_program_type','grade_level_name','section_name'] as $field) {
            $session[$field] = $actor[$field];
        }
        $session['account_status'] = $actor['status'];
        $session['must_change_password'] = !empty($actor['must_change_password']);
        $session['faculty_profile_required'] = $actor['role_prefix'] === 'Faculty'
            && (empty($actor['gender']) || empty($actor['birthdate']));
        return 'valid';
    }
}
