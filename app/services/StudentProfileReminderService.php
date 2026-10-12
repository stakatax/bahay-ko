<?php
require_once __DIR__ . '/../models/StudentProfileCycle.php';
require_once __DIR__ . '/../models/Notification.php';

class StudentProfileReminderService
{
    public function __construct(private ?mysqli $connection = null) {}

    public function dispatch(int $limit = 100): array
    {
        $cycles = new StudentProfileCycle($this->connection);
        $notifications = new Notification($cycles->getDatabaseConnection());
        $summary = ['eligible' => 0, 'created' => 0, 'duplicates' => 0, 'failed' => 0];
        foreach ($cycles->getDuePeriodicReminders($limit) as $row) {
            $summary['eligible']++;
            try {
                $message = 'Please confirm your account details and complete ' . $row['cycle_name'] . '.';
                if ($row['due_at']) $message .= ' Due ' . date('F j, Y, g:i A', strtotime($row['due_at'])) . '.';
                if ($row['due_at'] && strtotime($row['due_at']) < time()) $message .= ' This update is overdue; you can still submit it while it remains open.';
                $created = $notifications->createForUser((int) $row['user_id'], 'reminder', 'Please update your Student Profile', $message,
                    null, null, 'student-profile-cycle-reminder:cycle:' . $row['student_profile_cycle_id'] . ':day:' . $row['reminder_day']);
                $summary[$created ? 'created' : 'duplicates']++;
            } catch (Throwable $error) {
                $summary['failed']++;
                error_log('Periodic profile reminder failed (' . get_class($error) . ').');
            }
        }
        return $summary;
    }

    /** Recheck at dispatch time so queued reminders stop after completion/closure. */
    public function isDeliveryCurrent(array $delivery): bool
    {
        $key = $delivery['deduplication_key'] ?? '';
        if (!preg_match('/^student-profile-cycle-(?:assigned|reminder):cycle:([1-9][0-9]*)(?::day:\d{4}-\d{2}-\d{2})?$/', $key, $match)) return true;
        return (new StudentProfileCycle($this->connection))->hasIncompleteAssignment((int) ($delivery['user_id'] ?? 0), (int) $match[1]);
    }
}
