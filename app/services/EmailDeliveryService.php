<?php

require_once __DIR__
    . '/../models/EmailDelivery.php';

require_once __DIR__
    . '/EmailSender.php';

class EmailDeliveryService
{
    private EmailDelivery $delivery;

    private EmailSender $sender;

    public function __construct()
    {
        $this->delivery =
            new EmailDelivery();

        $this->sender =
            new EmailSender();
    }

    public function dispatch(
        int $queueLimit = 500,
        int $sendLimit = 50
    ): array {
        $summary = [
            'queued' => 0,
            'processed' => 0,
            'sent' => 0,
            'failed' => 0
        ];

        if (!$this->sender->isEnabled()) {
            return $summary;
        }

        $summary['queued'] =
            $this->delivery
            ->queueEligible(
                $queueLimit
            );

        $deliveries =
            $this->delivery
            ->getPending(
                $sendLimit
            );

        foreach (
            $deliveries
            as $delivery
        ) {
            $deliveryId =
                (int) (
                    $delivery['delivery_id']
                    ?? 0
                );

            if ($deliveryId <= 0) {
                continue;
            }

            $summary['processed']++;

            try {
                $recipientName =
                    $this->buildRecipientName(
                        $delivery
                    );

                $sent =
                    $this->sender
                    ->send(
                        (string) (
                            $delivery['recipient_email']
                            ?? ''
                        ),
                        $recipientName,
                        (string) (
                            $delivery['title']
                            ?? 'OLSHCO Notification'
                        ),
                        (string) (
                            $delivery['message']
                            ?? 'You have a new notification.'
                        ),
                        $delivery
                    );

                if (!$sent) {
                    throw new RuntimeException(
                        'The SMTP sender did not confirm delivery.'
                    );
                }

                $this->delivery
                    ->markSent(
                        $deliveryId
                    );

                $summary['sent']++;
            } catch (Throwable $exception) {
                $this->delivery
                    ->markFailed(
                        $deliveryId,
                        $exception->getMessage()
                    );

                $summary['failed']++;

                error_log(
                    'Email notification delivery failed: '
                        . 'Delivery #'
                        . $deliveryId
                        . ' | '
                        . $exception->getMessage()
                );
            }
        }

        return $summary;
    }

    private function buildRecipientName(
        array $delivery
    ): string {
        $parts = [
            trim(
                (string) (
                    $delivery['first_name']
                    ?? ''
                )
            ),

            trim(
                (string) (
                    $delivery['middle_name']
                    ?? ''
                )
            ),

            trim(
                (string) (
                    $delivery['last_name']
                    ?? ''
                )
            ),

            trim(
                (string) (
                    $delivery['name_suffix']
                    ?? ''
                )
            )
        ];

        $parts =
            array_values(
                array_filter(
                    $parts,
                    static function (
                        string $part
                    ): bool {
                        return $part !== '';
                    }
                )
            );

        return $parts !== []
            ? implode(
                ' ',
                $parts
            )
            : 'OLSHCO User';
    }
}
