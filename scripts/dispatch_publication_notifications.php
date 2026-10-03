<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../app/services/PublicationNotificationDispatcher.php';
$limit = filter_var($argv[1] ?? 25, FILTER_VALIDATE_INT);
if (count($argv) > 2 || $limit === false || $limit < 1 || $limit > 100) {
    fwrite(STDERR, "Usage: php dispatch_publication_notifications.php [1-100]\n");
    exit(2);
}
try {
    $result = (new PublicationNotificationDispatcher())->dispatch($limit);
    $result['profile_reminders'] = (new NotificationService())->recoverStudentProfileNotifications($limit);
    echo json_encode($result, JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit($result['failed'] > 0 || $result['profile_reminders']['failed'] > 0 ? 1 : 0);
} catch (Throwable $exception) {
    error_log('Publication worker failed: '.get_class($exception).' (code '.(int)$exception->getCode().').');
    fwrite(STDERR, "Publication worker could not finish. Pending work is retained; check server logs.\n");
    exit(1);
}
