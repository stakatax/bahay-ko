<?php
require_once __DIR__.'/../models/PublicationNotificationOutbox.php';
require_once __DIR__.'/NotificationService.php';

class PublicationNotificationDispatcher
{
    public function __construct(
        private ?PublicationNotificationOutbox $outbox = null,
        private ?NotificationService $notifications = null
    ) {
        $this->outbox ??= new PublicationNotificationOutbox();
        $this->notifications ??= new NotificationService();
    }

    public function dispatch(int $limit = 25, ?string $type = null, ?int $id = null): array
    {
        $summary = ['eligible'=>0, 'created'=>0, 'duplicates'=>0, 'completed'=>0, 'cancelled'=>0, 'failed'=>0];
        for ($i = 0; $i < max(1, min(100, $limit)); $i++) {
            $job = $this->outbox->claim($type, $id);
            if (!$job) break;
            try {
                if (!$this->outbox->isDeliverable($job['content_type'], (int)$job['content_id'])) {
                    if ($this->outbox->finish($job, 'Cancelled')) $summary['cancelled']++;
                    continue;
                }
                $result = $this->notifications->notifyPublishedContent($job['content_type'], (int)$job['content_id']);
                foreach (['eligible', 'created', 'duplicates'] as $field) $summary[$field] += (int)($result[$field] ?? 0);
                if ($this->outbox->finish($job, 'Completed')) $summary['completed']++;
            } catch (Throwable $exception) {
                $summary['failed']++;
                $this->outbox->retry($job, $exception);
                error_log('Publication notification deferred: '.$job['content_type'].' #'.(int)$job['content_id']
                    .' ('.get_class($exception).', code '.(int)$exception->getCode().').');
            }
        }
        return $summary;
    }
}
