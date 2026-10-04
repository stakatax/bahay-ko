<?php

/** Learns only from the current user's already-authorized feed candidates. */
final class FeedPreference
{
    public static function topics(array $sets): array
    {
        $totals = [];
        $now = time();
        foreach ($sets as $items) {
            foreach ($items as $item) {
                $vote = $item['user_reaction'] ?? null;
                $signal = $vote === 'Upvote' ? 4 : ($vote === 'Downvote' ? -5 : (!empty($item['user_viewed']) ? 1 : 0));
                if ($signal === 0) {
                    continue;
                }
                // Publication age provides a conservative decay without storing new tracking data.
                $published = strtotime((string) ($item['published_at'] ?? $item['created_at'] ?? ''));
                $ageDays = $published === false ? 30 : max(0, ($now - $published) / 86400);
                $signal *= max(0.1, pow(0.5, $ageDays / 30));
                $ids = array_unique(array_filter(array_map('intval', $item['interest_ids'] ?? []), static fn(int $id): bool => $id > 0));
                foreach ($ids as $id) {
                    // Multiple tags must not multiply a single interaction's influence.
                    $totals[$id] = ($totals[$id] ?? 0) + $signal / count($ids);
                }
            }
        }
        return array_map(static fn(float $value): int => (int) round(max(-10, min(10, $value)) * 30), $totals);
    }
}
