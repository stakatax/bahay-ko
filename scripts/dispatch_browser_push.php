<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$lockPath =
    sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'olshco_browser_push_dispatch.lock';

$lockHandle =
    fopen(
        $lockPath,
        'c'
    );

if ($lockHandle === false) {
    fwrite(
        STDERR,
        "Unable to create dispatcher lock."
            . PHP_EOL
    );

    exit(1);
}

if (
    !flock(
        $lockHandle,
        LOCK_EX |
            LOCK_NB
    )
) {
    echo "Browser push dispatcher is already running.";
    echo PHP_EOL;

    fclose(
        $lockHandle
    );

    exit(75);
}


$logDirectory =
    dirname(__DIR__)
    . DIRECTORY_SEPARATOR
    . 'logs';

$logPath =
    $logDirectory
    . DIRECTORY_SEPARATOR
    . 'browser-push-dispatch.log';

$writeLog =
    static function (
        string $message
    ) use (
        $logDirectory,
        $logPath
    ): bool {
        if (
            !is_dir($logDirectory) &&
            !mkdir(
                $logDirectory,
                0775,
                true
            ) &&
            !is_dir($logDirectory)
        ) {
            return false;
        }


        $maximumLogSize =
            5 * 1024 * 1024;

        if (
            is_file($logPath) &&
            filesize($logPath) >=
            $maximumLogSize
        ) {
            $archivePath =
                $logPath
                . '.1';

            if (is_file($archivePath)) {
                if (!unlink($archivePath)) { return false; }
            }

            if (!rename($logPath, $archivePath)) { return false; }
        }

        return file_put_contents(
            $logPath,
            $message
                . PHP_EOL,
            FILE_APPEND |
                LOCK_EX
        ) !== false;
    };

try {
    require_once __DIR__
        . '/../app/services/ContentReleaseService.php';

    require_once __DIR__
        . '/../app/services/NotificationService.php';

    require_once __DIR__
        . '/../app/services/EmailDeliveryService.php';

    require_once __DIR__
        . '/../app/services/BrowserPushDeliveryService.php';

    /*
     * Publish due scheduled/calendar content first.
     * This also creates the corresponding targeted
     * in-system notification records.
     */
    $releaseResult =
        (
            new ContentReleaseService()
        )->processPendingReleases();


    /*
     * Create deduplicated reminders for published
     * events beginning within the next 24 hours.
     */
    $reminderResult =
        (
            new NotificationService()
        )->processUpcomingEventReminders();


    /*
     * Queue and deliver eligible email notifications.
     * The smaller batch protects the SMTP account
     * from large bursts.
     */
    $profileResult = (new NotificationService())->recoverStudentProfileNotifications(100);

    $emailResult =
        (
            new EmailDeliveryService()
        )->dispatch(
            500,
            25
        );

    /*
     * Queue and deliver browser notifications created
     * by the release process above.
     */
    $result =
        (
            new BrowserPushDeliveryService()
        )->dispatch(
            500,
            100
        );

    $summaryLine =
        '['
        . date('Y-m-d H:i:s')
        . '] Released: '
        . (int) (
            $releaseResult['total_released']
            ?? 0
        )
        . ' | Notifications: '
        . (int) (
            $releaseResult['notifications']['created']
            ?? 0
        )
        . ' | Notification failures: '
        . (int) ($releaseResult['notifications']['failed'] ?? 0)
        . ' | Profile reminders: ' . (int) ($profileResult['created'] ?? 0)
        . ' | Profile reminder failures: ' . (int) ($profileResult['failed'] ?? 0)
        . ' | Reminder failures: '
        . (int) ($reminderResult['failed'] ?? 0)
        . ' | Reminders: '
        . (int) (
            $reminderResult['created']
            ?? 0
        )
        . ' | Email queued: '
        . (int) (
            $emailResult['queued']
            ?? 0
        )
        . ' | Email sent: '
        . (int) (
            $emailResult['sent']
            ?? 0
        )
        . ' | Email failed: '
        . (int) (
            $emailResult['failed']
            ?? 0
        )
        . ' | Push queued: '
        . (int) (
            $result['queued']
            ?? 0
        )
        . ' | Push sent: '
        . (int) (
            $result['sent']
            ?? 0
        )
        . ' | Push failed: '
        . (int) (
            $result['failed']
            ?? 0
        )
        . ' | Push expired: '
        . (int) (
            $result['expired']
            ?? 0
        );

    echo $summaryLine;
    echo PHP_EOL;

    $logWritten = $writeLog($summaryLine);
    if (!$logWritten) {
        fwrite(STDERR, "Worker summary log could not be written.\n");
    }

    $hasDeliveryFailure =
        !$logWritten
        || (int) ($releaseResult['notifications']['failed'] ?? 0) > 0
        || (int) ($reminderResult['failed'] ?? 0) > 0
        || (int) ($profileResult['failed'] ?? 0) > 0
        ||
        (int) (
            $emailResult['failed']
            ?? 0
        ) > 0

        ||

        (int) (
            $result['failed']
            ?? 0
        ) > 0;

    $exitCode =
        $hasDeliveryFailure
        ? 1
        : 0;
} catch (Throwable $exception) {
    $writeLog(
        '['
            . date('Y-m-d H:i:s')
            . '] FAILED: '
            . get_class($exception) . ' (code ' . (int) $exception->getCode() . ')'
    );
    fwrite(
        STDERR,
        '['
            . date('Y-m-d H:i:s')
            . '] Browser push dispatcher failed: '
            . get_class($exception) . ' (code ' . (int) $exception->getCode() . ')'
            . PHP_EOL
    );

    $exitCode =
        1;
} finally {
    flock(
        $lockHandle,
        LOCK_UN
    );

    fclose(
        $lockHandle
    );
}

exit($exitCode);
