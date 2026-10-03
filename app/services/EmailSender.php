<?php

require_once __DIR__
    . '/../../vendor/autoload.php';

require_once __DIR__
    . '/../../config/email.php';

require_once __DIR__
    . '/../../config/security.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailSender
{
    private array $configuration;

    public function __construct()
    {
        $this->configuration =
            emailConfiguration();
    }

    public function isEnabled(): bool
    {
        return !empty($this->configuration['enabled']);
    }

    /* ==========================================
       NOTIFICATION PRESENTATION
    ========================================== */

    private function buildNotificationPresentation(
        string $subject,
        array $context
    ): array {
        $deduplicationKey =
            strtolower(
                trim(
                    (string) (
                        $context['deduplication_key']
                        ?? ''
                    )
                )
            );

        $normalizedSubject =
            strtolower(
                trim(
                    $subject
                )
            );

        $contentType =
            strtolower(
                trim(
                    (string) (
                        $context['content_type']
                        ?? ''
                    )
                )
            );

        $contentLabel =
            match ($contentType) {
                'announcement' =>
                'Announcement',

                'event' =>
                'Event',

                'document' =>
                'Document',

                'survey' =>
                'Survey',

                default =>
                'Digital Hub'
            };

        $presentation = [
            'eyebrow' =>
            $contentLabel . ' notification',

            'action_label' =>
            'Open notification',

            'accent_color' =>
            '#8b0000',

            'accent_background' =>
            '#fff1f1'
        ];

        if (
            str_contains(
                $deduplicationKey,
                'engagement:reply:'
            ) ||
            str_contains(
                $normalizedSubject,
                'reply'
            )
        ) {
            return array_merge(
                $presentation,
                [
                    'eyebrow' =>
                    'Discussion update',

                    'action_label' =>
                    'View reply',

                    'accent_color' =>
                    '#8b0000',

                    'accent_background' =>
                    '#fff1f1'
                ]
            );
        }

        if (
            str_contains(
                $deduplicationKey,
                'engagement:comment:'
            ) ||
            str_contains(
                $normalizedSubject,
                'comment'
            )
        ) {
            return array_merge(
                $presentation,
                [
                    'eyebrow' =>
                    'New discussion activity',

                    'action_label' =>
                    'Open discussion'
                ]
            );
        }

        if (
            str_contains(
                $deduplicationKey,
                'engagement:reaction:'
            ) ||
            str_contains(
                $normalizedSubject,
                'reaction'
            )
        ) {
            return array_merge(
                $presentation,
                [
                    'eyebrow' =>
                    'Engagement update',

                    'action_label' =>
                    'View reaction',

                    'accent_color' =>
                    '#b45309',

                    'accent_background' =>
                    '#fff7e8'
                ]
            );
        }

        if (
            str_contains(
                $deduplicationKey,
                'engagement:acknowledgment:'
            ) ||
            str_contains(
                $normalizedSubject,
                'acknowledged'
            )
        ) {
            return array_merge(
                $presentation,
                [
                    'eyebrow' =>
                    'Acknowledgment update',

                    'action_label' =>
                    'View acknowledgment',

                    'accent_color' =>
                    '#15803d',

                    'accent_background' =>
                    '#effcf4'
                ]
            );
        }

        if (
            str_contains(
                $deduplicationKey,
                'engagement:survey_response:'
            ) ||
            str_contains(
                $normalizedSubject,
                'survey response'
            )
        ) {
            return array_merge(
                $presentation,
                [
                    'eyebrow' =>
                    'Survey activity',

                    'action_label' =>
                    'View survey results',

                    'accent_color' =>
                    '#2563eb',

                    'accent_background' =>
                    '#eff6ff'
                ]
            );
        }

        return $presentation;
    }

    private function buildNotificationActionUrl(
        array $context
    ): string {
        $notificationId =
            (int) (
                $context['notification_id']
                ?? 0
            );

        if ($notificationId <= 0) {
            return '';
        }

        $baseUrl =
            rtrim(
                applicationBaseUrl(),
                '/'
            );

        if ($baseUrl === '') {
            return '';
        }

        return $baseUrl
            . '/index.php?page=notification_open'
            . '&notification_id='
            . $notificationId;
    }

    private function escape(
        string $value
    ): string {
        return htmlspecialchars(
            $value,
            ENT_QUOTES |
                ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    public function send(
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $message,
        array $context = []
    ): bool {
        if (!$this->isEnabled()) {
            throw new RuntimeException(
                'Email delivery is disabled.'
            );
        }

        $recipientEmail =
            trim($recipientEmail);

        $recipientName =
            trim($recipientName);

        $subject =
            trim($subject);

        $message =
            trim($message);

        if (
            !filter_var(
                $recipientEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new InvalidArgumentException(
                'Recipient email address is invalid.'
            );
        }

        if ($subject === '') {
            throw new InvalidArgumentException(
                'Email subject is required.'
            );
        }

        if ($message === '') {
            throw new InvalidArgumentException(
                'Email message is required.'
            );
        }

        $safeSubject =
            mb_substr(
                $subject,
                0,
                255
            );

        $safeRecipientName =
            $recipientName !== ''
            ? mb_substr(
                $recipientName,
                0,
                200
            )
            : $recipientEmail;

        /*
         * Remove accidental Markdown heading
         * markers from notification titles.
         */
        $safeSubject =
            ltrim(
                $safeSubject,
                "# \t\n\r\0\x0B"
            );

        if ($safeSubject === '') {
            $safeSubject =
                'OLSHCO Notification';
        }

        $presentation =
            $this->buildNotificationPresentation(
                $safeSubject,
                $context
            );

        $actionUrl =
            $this->buildNotificationActionUrl(
                $context
            );

        $contentType =
            strtolower(
                trim(
                    (string) (
                        $context['content_type']
                        ?? ''
                    )
                )
            );

        $contentLabel =
            match ($contentType) {
                'announcement' =>
                'Announcement',

                'event' =>
                'Event',

                'document' =>
                'Document',

                'survey' =>
                'Survey',

                default =>
                'Notification'
            };

        $createdAt =
            trim(
                (string) (
                    $context['created_at']
                    ?? ''
                )
            );

        $createdTimestamp =
            $createdAt !== ''
            ? strtotime(
                $createdAt
            )
            : false;

        $formattedCreatedAt =
            $createdTimestamp !== false
            ? date(
                'F j, Y \a\t g:i A',
                $createdTimestamp
            )
            : '';

        $escapedSubject =
            $this->escape(
                $safeSubject
            );

        $escapedRecipientName =
            $this->escape(
                $safeRecipientName
            );

        $escapedMessage =
            $this->escape(
                $message
            );

        $safeMessage =
            nl2br(
                $escapedMessage
            );

        $escapedEyebrow =
            $this->escape(
                (string) (
                    $presentation['eyebrow']
                    ?? 'Digital Hub notification'
                )
            );

        $escapedActionLabel =
            $this->escape(
                (string) (
                    $presentation['action_label']
                    ?? 'Open notification'
                )
            );

        $escapedContentLabel =
            $this->escape(
                $contentLabel
            );

        $escapedCreatedAt =
            $this->escape(
                $formattedCreatedAt
            );

        $escapedActionUrl =
            $this->escape(
                $actionUrl
            );

        $accentColor =
            (string) (
                $presentation['accent_color']
                ?? '#8b0000'
            );

        $accentBackground =
            (string) (
                $presentation['accent_background']
                ?? '#fff1f1'
            );

        $plainTextBody =
            "OLSHCO Digital Hub\n\n"
            . 'Hello '
            . $safeRecipientName
            . ",\n\n"
            . $safeSubject
            . "\n\n"
            . $message;

        if ($formattedCreatedAt !== '') {
            $plainTextBody .=
                "\n\nReceived: "
                . $formattedCreatedAt;
        }

        if ($actionUrl !== '') {
            $plainTextBody .=
                "\n\n"
                . (
                    $presentation['action_label']
                    ?? 'Open notification'
                )
                . ': '
                . $actionUrl;
        }

        $plainTextBody .=
            "\n\nThis automated message was sent by "
            . 'the OLSHCO Digital Hub.';

        $htmlBody =
            '<!DOCTYPE html>'
            . '<html lang="en">'
            . '<head>'
            . '<meta charset="UTF-8">'
            . '<meta name="viewport" '
            . 'content="width=device-width, initial-scale=1.0">'
            . '<meta name="color-scheme" content="light">'
            . '<meta name="supported-color-schemes" content="light">'
            . '<title>'
            . $escapedSubject
            . '</title>'
            . '</head>'

            . '<body style="'
            . 'margin:0;'
            . 'padding:0;'
            . 'background-color:#f3f4f6;'
            . 'font-family:Arial,Helvetica,sans-serif;'
            . 'color:#20242a;'
            . '-webkit-text-size-adjust:100%;'
            . '">'

            /*
             * Hidden inbox preview text.
             */
            . '<div style="'
            . 'display:none;'
            . 'max-height:0;'
            . 'overflow:hidden;'
            . 'opacity:0;'
            . 'color:transparent;'
            . 'mso-hide:all;'
            . '">'
            . $escapedMessage
            . '</div>'

            . '<table role="presentation" '
            . 'width="100%" cellspacing="0" cellpadding="0" border="0" '
            . 'style="'
            . 'width:100%;'
            . 'border-collapse:collapse;'
            . 'background-color:#f3f4f6;'
            . '">'
            . '<tr>'
            . '<td align="center" style="padding:32px 16px;">'

            . '<table role="presentation" '
            . 'width="100%" cellspacing="0" cellpadding="0" border="0" '
            . 'style="'
            . 'width:100%;'
            . 'max-width:640px;'
            . 'border-collapse:separate;'
            . 'background-color:#ffffff;'
            . 'border:1px solid #e5e7eb;'
            . 'border-radius:18px;'
            . 'overflow:hidden;'
            . 'box-shadow:0 10px 30px rgba(15,23,42,0.08);'
            . '">'

            /*
             * Brand header.
             */
            . '<tr>'
            . '<td style="'
            . 'padding:22px 26px;'
            . 'background-color:#8b0000;'
            . 'color:#ffffff;'
            . '">'
            . '<table role="presentation" '
            . 'width="100%" cellspacing="0" cellpadding="0" border="0" '
            . 'style="border-collapse:collapse;">'
            . '<tr>'

            . '<td width="46" valign="middle">'
            . '<div style="'
            . 'width:40px;'
            . 'height:40px;'
            . 'border-radius:12px;'
            . 'background-color:#ffffff;'
            . 'color:#8b0000;'
            . 'font-size:13px;'
            . 'font-weight:800;'
            . 'line-height:40px;'
            . 'text-align:center;'
            . '">'
            . 'ODH'
            . '</div>'
            . '</td>'

            . '<td valign="middle" style="padding-left:12px;">'
            . '<div style="'
            . 'font-size:18px;'
            . 'font-weight:800;'
            . 'line-height:1.3;'
            . '">'
            . 'OLSHCO Digital Hub'
            . '</div>'
            . '<div style="'
            . 'margin-top:3px;'
            . 'font-size:12px;'
            . 'line-height:1.4;'
            . 'color:#f5dada;'
            . '">'
            . 'Official school information and engagement'
            . '</div>'
            . '</td>'

            . '</tr>'
            . '</table>'
            . '</td>'
            . '</tr>'

            /*
             * Main email content.
             */
            . '<tr>'
            . '<td style="padding:30px 28px 28px;">'

            . '<p style="'
            . 'margin:0 0 20px;'
            . 'font-size:14px;'
            . 'line-height:1.6;'
            . 'color:#69717d;'
            . '">'
            . 'Hello '
            . $escapedRecipientName
            . ','
            . '</p>'

            . '<span style="'
            . 'display:inline-block;'
            . 'margin:0 0 12px;'
            . 'padding:7px 10px;'
            . 'border-radius:999px;'
            . 'background-color:'
            . $accentBackground
            . ';'
            . 'color:'
            . $accentColor
            . ';'
            . 'font-size:11px;'
            . 'font-weight:800;'
            . 'letter-spacing:0.05em;'
            . 'line-height:1;'
            . 'text-transform:uppercase;'
            . '">'
            . $escapedEyebrow
            . '</span>'

            . '<h1 style="'
            . 'margin:0;'
            . 'font-size:26px;'
            . 'font-weight:800;'
            . 'line-height:1.3;'
            . 'color:#20242a;'
            . '">'
            . $escapedSubject
            . '</h1>'

            . '<div style="'
            . 'margin-top:20px;'
            . 'padding:18px;'
            . 'border-left:4px solid '
            . $accentColor
            . ';'
            . 'border-radius:0 12px 12px 0;'
            . 'background-color:#fafafa;'
            . 'font-size:15px;'
            . 'line-height:1.75;'
            . 'color:#4b5563;'
            . '">'
            . $safeMessage
            . '</div>'

            /*
             * Notification metadata.
             */
            . '<table role="presentation" '
            . 'width="100%" cellspacing="0" cellpadding="0" border="0" '
            . 'style="'
            . 'width:100%;'
            . 'margin-top:20px;'
            . 'border-collapse:collapse;'
            . '">'
            . '<tr>'
            . '<td style="'
            . 'padding:11px 12px;'
            . 'border:1px solid #e8eaed;'
            . 'border-radius:10px;'
            . 'background-color:#ffffff;'
            . 'font-size:12px;'
            . 'line-height:1.5;'
            . 'color:#7d858f;'
            . '">'
            . '<strong style="color:#4b5563;">'
            . 'Content type:'
            . '</strong> '
            . $escapedContentLabel

            . (
                $escapedCreatedAt !== ''
                ? (
                    '<span style="'
                    . 'display:inline-block;'
                    . 'margin:0 8px;'
                    . 'color:#c5c9ce;'
                    . '">'
                    . '&bull;'
                    . '</span>'
                    . '<strong style="color:#4b5563;">'
                    . 'Received:'
                    . '</strong> '
                    . $escapedCreatedAt
                )
                : ''
            )

            . '</td>'
            . '</tr>'
            . '</table>'

            /*
             * Context-aware action button.
             */
            . (
                $escapedActionUrl !== ''
                ? (
                    '<table role="presentation" '
                    . 'cellspacing="0" cellpadding="0" border="0" '
                    . 'style="margin-top:24px;">'
                    . '<tr>'
                    . '<td style="'
                    . 'border-radius:10px;'
                    . 'background-color:#8b0000;'
                    . '">'
                    . '<a href="'
                    . $escapedActionUrl
                    . '" target="_blank" style="'
                    . 'display:inline-block;'
                    . 'padding:13px 20px;'
                    . 'color:#ffffff;'
                    . 'font-size:14px;'
                    . 'font-weight:800;'
                    . 'line-height:1.2;'
                    . 'text-decoration:none;'
                    . '">'
                    . $escapedActionLabel
                    . ' &rarr;'
                    . '</a>'
                    . '</td>'
                    . '</tr>'
                    . '</table>'
                )
                : ''
            )

            . '<p style="'
            . 'margin:24px 0 0;'
            . 'font-size:12px;'
            . 'line-height:1.6;'
            . 'color:#858c96;'
            . '">'
            . 'For your security, this button opens the Digital Hub, '
            . 'where you may be asked to sign in before viewing the notification.'
            . '</p>'

            . '</td>'
            . '</tr>'

            /*
             * Footer.
             */
            . '<tr>'
            . '<td style="'
            . 'padding:18px 28px;'
            . 'border-top:1px solid #e5e7eb;'
            . 'background-color:#fafafa;'
            . 'font-size:12px;'
            . 'line-height:1.6;'
            . 'color:#858c96;'
            . '">'
            . '<strong style="color:#626a75;">'
            . 'OLSHCO Digital Hub'
            . '</strong>'
            . '<br>'
            . 'This automated email reflects your notification preferences. '
            . 'Please do not reply unless a reply-to address is provided.'
            . '</td>'
            . '</tr>'

            . '</table>'
            . '</td>'
            . '</tr>'
            . '</table>'

            . '</body>'
            . '</html>';

        $mailer =
            new PHPMailer(
                true
            );

        try {
            $mailer->isSMTP();

            $mailer->Host =
                $this->configuration['host'];

            $mailer->SMTPAuth =
                true;

            $mailer->Username =
                $this->configuration['username'];

            $mailer->Password =
                $this->configuration['password'];

            $mailer->Port =
                $this->configuration['port'];

            $mailer->Timeout =
                $this->configuration['timeout'];

            $mailer->CharSet =
                PHPMailer::CHARSET_UTF8;

            $mailer->Encoding =
                PHPMailer::ENCODING_BASE64;

            $mailer->SMTPSecure =
                $this->configuration['encryption']
                === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;

            $mailer->setFrom(
                $this->configuration['from_email'],
                $this->configuration['from_name']
            );

            if (
                $this->configuration['reply_to_email']
                !== ''
            ) {
                $mailer->addReplyTo(
                    $this->configuration['reply_to_email'],
                    $this->configuration['from_name']
                );
            }

            $mailer->addAddress(
                $recipientEmail,
                $safeRecipientName
            );

            $mailer->isHTML(
                true
            );

            $mailer->Subject =
                $safeSubject;

            $mailer->Body =
                $htmlBody;

            $mailer->AltBody =
                $plainTextBody;

            $mailer->send();

            return true;
        } catch (Exception $exception) {
            throw new RuntimeException(
                'SMTP delivery failed: '
                    . $mailer->ErrorInfo,
                0,
                $exception
            );
        }
    }
}
