<?php
require_once __DIR__ . '/../app/services/FeedPreference.php';
function checkRanking(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
$today = date('Y-m-d H:i:s');
$topic = static fn(string $vote, array $ids): array => ['user_reaction' => $vote, 'interest_ids' => $ids, 'published_at' => $today];
$scores = FeedPreference::topics([[$topic('Upvote', [1]), $topic('Downvote', [2])]]);
checkRanking($scores[1] > 0 && $scores[2] < 0, 'Votes learn opposite topic preferences');
checkRanking(FeedPreference::topics([]) === [], 'Cold start');
checkRanking(FeedPreference::topics([array_fill(0, 100, $topic('Upvote', [1]))])[1] === 300, 'Repeated interactions are bounded');
checkRanking(FeedPreference::topics([[$topic('Upvote', [1, 1])]]) === FeedPreference::topics([[$topic('Upvote', [1])]]), 'Duplicate tags do not amplify');
$currentUserInterestWeights = [1 => 5];
$learnedTopicScores = [1 => 300];
$source = file_get_contents(__DIR__ . '/../pages/news.php');
$start = strpos($source, '$calculateHubRanking =');
$end = strpos($source, "/*", strpos($source, "implode(", $start));
// Extract exactly the ranking closure, excluding page rendering and database access.
$closureEnd = strpos($source, "    };", $start) + strlen("    };");
eval(substr($source, $start, $closureEnd - $start));
$item = static fn(string $priority, array $data): array => ['type' => 'announcement', 'priority' => $priority, 'date' => date('Y-m-d'), 'data' => $data];
$personal = $calculateHubRanking($item('normal', ['interest_ids' => [1]]));
$plain = $calculateHubRanking($item('normal', ['interest_ids' => []]));
checkRanking($personal['score'] > $plain['score'], 'Interest match ranks higher');
checkRanking($calculateHubRanking($item('emergency', []))['tier'] > $calculateHubRanking($item('important', []))['tier'], 'Emergency first');
checkRanking($calculateHubRanking($item('important', []))['tier'] > $personal['tier'], 'Important cannot be displaced by personalization');
checkRanking($calculateHubRanking($item('normal', ['require_acknowledgment' => 1]))['tier'] > $personal['tier'], 'Required actions preserved');
checkRanking($calculateHubRanking($item('normal', ['user_reaction' => 'Downvote']))['score'] < $plain['score'], 'Downvoted post demoted');
echo "PASS: feed ranking and learned preferences\n";
