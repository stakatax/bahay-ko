<?php
require_once __DIR__.'/../models/PublicationNotificationOutbox.php';

class PublicationTransaction
{
    public static function run(mysqli $connection, string $type, callable $write, ?int $contentId = null): mixed
    {
        // Never implicitly commit an unrelated transaction with BEGIN.
        $active = $connection->query('SELECT @@in_transaction AS active')->fetch_assoc();
        if (!empty($active['active'])) throw new LogicException('Publication requires its own transaction boundary.');
        if (!$connection->begin_transaction()) throw new RuntimeException('Unable to start publication transaction.');
        try {
            $result = $write();
            if ($result !== false) {
                $id = $contentId ?? (int)$result;
                (new PublicationNotificationOutbox($connection))->enqueue($type, $id);
            }
            if (!$connection->commit()) throw new RuntimeException('Unable to commit publication.');
            return $result;
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }
}
