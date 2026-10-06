<?php

require_once __DIR__
    . '/../models/EmailDelivery.php';

require_once __DIR__
    . '/EmailSender.php';

require_once __DIR__ . '/../models/Announcement.php';
require_once __DIR__ . '/ContentAudienceService.php';

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

                $message = (string) ($delivery['message'] ?? 'You have a new notification.');
                // Review decisions may reference pending/rejected content, not a published post.
                if (self::includesAnnouncementText($delivery)) {
                    $announcement = (new Announcement())->findById((int) ($delivery['content_id'] ?? 0));
                    if ($announcement === null) {
                        throw new DomainException('Announcement is no longer available for email delivery.');
                    }
                    (new ContentAudienceService())->requirePublishedAccess(
                        'announcement', $announcement, (int) ($delivery['user_id'] ?? 0)
                    );
                    $message .= "\n\n" . trim((string) ($announcement['title'] ?? 'Announcement'))
                        . "\n\n" . self::announcementText((string) ($announcement['content'] ?? ''));
                }

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
                        $message,
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

    private static function includesAnnouncementText(array $delivery): bool
    {
        return ($delivery['content_type'] ?? '') === 'announcement'
            && ($delivery['notification_type'] ?? '') !== 'workflow';
    }

    /** Convert editor markup to readable text; EmailSender escapes it before rendering. */
    private static function announcementText(string $content): string
    {
        $content = preg_replace('~<(script|style)\b[^>]*>.*?</\1\s*>~is', '', $content) ?? '';
        $content = preg_replace('~<br\s*/?>|</(?:p|div|h[1-6]|li|ul|ol|blockquote)>~i', "\n", $content) ?? '';
        $content = preg_replace('~<li\b[^>]*>~i', '- ', $content) ?? '';
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\n[\t \r]*\n(?:[\t \r]*\n)+/', "\n\n", $content) ?? '');
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
