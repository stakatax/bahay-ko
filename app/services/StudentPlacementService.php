<?php
require_once __DIR__ . '/../models/User.php';

/** Validation shared by staging and final submission; never trusts browser IDs. */
class StudentPlacementService
{
    public function __construct(private User $users) {}

    public function validate(array $data): array
    {
        $ids = [];
        foreach (['department_id', 'education_level_id', 'academic_program_id', 'grade_level_id', 'section_id'] as $field) {
            $value = $data[$field] ?? '';
            if (!is_scalar($value) || is_bool($value)) throw new InvalidArgumentException('Select valid academic details.');
            $ids[$field] = $value === '' || $value === null ? null : filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($ids[$field] === false) throw new InvalidArgumentException('Select valid academic details.');
        }
        $department = $this->users->findDepartmentById((int) $ids['department_id']);
        if (!$department) throw new InvalidArgumentException('Select an active school division.');
        $level = (int) $ids['education_level_id'];
        $education = $this->users->findEducationLevelById($level);
        if (!$education || (int) $education['department_id'] !== $ids['department_id']) throw new InvalidArgumentException('Select an education level in your division.');
        $program = $ids['academic_program_id'];
        if ($this->users->educationLevelHasPrograms($level)) {
            $record = $this->users->findAcademicProgramById((int) $program);
            if (!$record || (int) $record['education_level_id'] !== $level) throw new InvalidArgumentException('Select a program or strand in your division.');
        } elseif ($program !== null) {
            throw new InvalidArgumentException('This division does not use a program or strand.');
        }
        $grade = $this->users->findGradeLevelById((int) $ids['grade_level_id']);
        if (!$grade || (int) $grade['education_level_id'] !== $level) throw new InvalidArgumentException('Select a grade or year in your division.');
        $section = $this->users->findSectionById((int) $ids['section_id']);
        if (!$section || (int) $section['grade_level_id'] !== $ids['grade_level_id']
            || ((int) ($section['academic_program_id'] ?? 0)) !== (int) $program) {
            throw new InvalidArgumentException('Select a section belonging to your grade and program or strand.');
        }
        return ['department_id' => $ids['department_id'], 'education_level_id' => $level,
            'academic_program_id' => $program, 'grade_level_id' => $ids['grade_level_id'], 'section_id' => $ids['section_id']];
    }
}
