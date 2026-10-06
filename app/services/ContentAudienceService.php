<?php

require_once __DIR__ . '/../models/ContentAudience.php';

class ContentAudienceService
{
    private ContentAudience $model;

    private const DIMENSIONS = [
        'department_id' => [50, 'Matches your department'],
        'education_level_id' => [60, 'Matches your education level'],
        'academic_program_id' => [80, 'Matches your program or strand'],
        'grade_level_id' => [90, 'Matches your grade or year level'],
        'section_id' => [110, 'Matches your section']
    ];

    public function __construct(?ContentAudience $model = null)
    {
        $this->model = $model ?? new ContentAudience();
    }

    public function actor(int $userId): ?array
    {
        $actor = $this->model->findActor($userId);
        if (!$actor || ($actor['status'] ?? '') !== 'Active'
            || !in_array($actor['role_prefix'] ?? '', ['Admin', 'Faculty', 'Student', 'Parent'], true)) {
            return null;
        }
        return $actor;
    }

    public function filterForUser(
        string $contentType,
        string $idColumn,
        array $items,
        int $userId,
        bool $allowGuest = false
    ): array {
        if (!in_array($contentType, ['announcement', 'event', 'document', 'survey'], true)
            || $idColumn !== $contentType . '_id') {
            throw new InvalidArgumentException('Invalid content type.');
        }
        if ($items === []) {
            return [];
        }

        return $this->filterSetsForUser([$contentType => $items], $userId, $allowGuest)[$contentType];
    }

    /** Resolve the viewer once for this batch only; never retain authorization state. */
    public function filterSetsForUser(array $sets, int $userId, bool $allowGuest = false): array
    {
        foreach ($sets as $type => $items) {
            if (!in_array($type, ['announcement', 'event', 'document', 'survey'], true)) {
                throw new InvalidArgumentException('Invalid content type.');
            }
        }
        $empty = array_fill_keys(array_keys($sets), []);
        if (!array_filter($sets)) {
            return $empty;
        }

        $actor = $this->actor($userId);
        if ($actor === null) {
            if (!$allowGuest || $userId !== 0) {
                return $empty;
            }
            $actor = ['role_id' => 0, 'role_prefix' => 'Guest'];
        }

        $profiles = [$actor];
        if ($actor['role_prefix'] === 'Parent') {
            $profiles = $this->model->getVerifiedStudentProfiles($userId);
            // Never fall back to a Parent's cached or self-assigned academic profile.
            if ($profiles === []) {
                return $empty;
            }
        }

        $targetSets = [];
        if ($actor['role_prefix'] !== 'Admin') {
            $ids = [];
            foreach ($sets as $type => $items) { $ids[$type] = array_column($items, $type . '_id'); }
            $targetSets = $this->model->getTargetSets($ids);
        }
        $visible = [];
        foreach ($sets as $type => $items) {
            $visible[$type] = $this->filterResolved($type, $type . '_id', $items, $actor, $profiles, $targetSets[$type] ?? []);
        }
        return $visible;
    }

    private function filterResolved(
        string $contentType, string $idColumn, array $items, array $actor, array $profiles, array $targetMap
    ): array {
        if ($items === []) {
            return [];
        }

        $visible = [];
        foreach ($items as $item) {
            $id = (int) ($item[$idColumn] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $match = $actor['role_prefix'] === 'Admin'
                ? [0, 'Administrator access']
                : $this->matchTargets($targetMap[$id] ?? [], (int) $actor['role_id'], $profiles);
            if ($match === null) {
                continue;
            }

            [$item['target_specificity_score'], $item['target_specificity_reason']] = $match;
            $visible[] = $item;
        }
        return $visible;
    }

    public function requirePublishedAccess(string $contentType, array $content, int $userId): void
    {
        if (($content['workflow_status'] ?? '') !== 'published'
            || strtolower((string) ($content['status'] ?? '')) !== 'active'
            || $this->filterForUser($contentType, $contentType . '_id', [$content], $userId) === []) {
            throw new DomainException('Content not found.');
        }
    }

    private function matchTargets(array $targets, int $roleId, array $profiles): ?array
    {
        if ($targets === []) {
            return [10, 'School-wide audience'];
        }

        $best = null;
        foreach ($targets as $target) {
            $targetRole = (int) ($target['role_id'] ?? 0);
            if ($targetRole !== 0 && $targetRole !== $roleId) {
                continue;
            }

            foreach ($profiles as $profile) {
                $score = $targetRole > 0 ? 40 : 0;
                $reason = $targetRole > 0 ? 'Matches your role' : 'Matches your profile';
                foreach (self::DIMENSIONS as $key => [$weight, $label]) {
                    $value = (int) ($target[$key] ?? 0);
                    if ($value !== 0 && $value !== (int) ($profile[$key] ?? 0)) {
                        continue 2;
                    }
                    if ($value > 0) {
                        $score += $weight;
                        $reason = $label;
                    }
                }
                if ($best === null || $score > $best[0]) {
                    $best = [$score, $reason];
                }
            }
        }
        return $best;
    }
}
