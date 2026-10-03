<?php

function emailConfiguration(): array
{
    static $configuration = null;

    if (is_array($configuration)) {
        return $configuration;
    }

    $localPath =
        __DIR__
        . '/email.local.php';

    if (!is_file($localPath)) {
        throw new RuntimeException(
            'Email configuration is unavailable.'
        );
    }

    $localConfiguration =
        require $localPath;

    if (!is_array($localConfiguration)) {
        throw new RuntimeException(
            'Email configuration is invalid.'
        );
    }

    $configuration =
        array_merge(
            [
                'enabled' => false,
                'host' => '',
                'port' => 587,
                'encryption' => 'tls',
                'username' => '',
                'password' => '',
                'from_email' => '',
                'from_name' =>
                'OLSHCO Digital Hub',
                'reply_to_email' => '',
                'timeout' => 20
            ],
            $localConfiguration
        );

    $configuration['enabled'] =
        !empty($configuration['enabled']);

    $configuration['host'] =
        trim(
            (string) (
                $configuration['host']
                ?? ''
            )
        );

    $configuration['port'] =
        (int) (
            $configuration['port']
            ?? 587
        );

    $configuration['encryption'] =
        strtolower(
            trim(
                (string) (
                    $configuration['encryption']
                    ?? 'tls'
                )
            )
        );

    $configuration['username'] =
        trim(
            (string) (
                $configuration['username']
                ?? ''
            )
        );

    $configuration['password'] =
        preg_replace(
            '/\s+/',
            '',
            (string) (
                $configuration['password']
                ?? ''
            )
        );

    $configuration['from_email'] =
        trim(
            (string) (
                $configuration['from_email']
                ?? ''
            )
        );

    $configuration['from_name'] =
        trim(
            (string) (
                $configuration['from_name']
                ?? 'OLSHCO Digital Hub'
            )
        );

    $configuration['reply_to_email'] =
        trim(
            (string) (
                $configuration['reply_to_email']
                ?? ''
            )
        );

    $configuration['timeout'] =
        max(
            5,
            min(
                60,
                (int) (
                    $configuration['timeout']
                    ?? 20
                )
            )
        );

    if (!$configuration['enabled']) {
        return $configuration;
    }

    if ($configuration['host'] === '') {
        throw new RuntimeException(
            'SMTP host is required.'
        );
    }

    if (
        $configuration['port'] <= 0 ||
        $configuration['port'] > 65535
    ) {
        throw new RuntimeException(
            'SMTP port is invalid.'
        );
    }

    if (
        !in_array(
            $configuration['encryption'],
            [
                'tls',
                'ssl'
            ],
            true
        )
    ) {
        throw new RuntimeException(
            'SMTP encryption must be tls or ssl.'
        );
    }

    if (
        !filter_var(
            $configuration['username'],
            FILTER_VALIDATE_EMAIL
        )
    ) {
        throw new RuntimeException(
            'SMTP username must be a valid email address.'
        );
    }

    if ($configuration['password'] === '') {
        throw new RuntimeException(
            'SMTP App Password is required.'
        );
    }

    if (
        !filter_var(
            $configuration['from_email'],
            FILTER_VALIDATE_EMAIL
        )
    ) {
        throw new RuntimeException(
            'Sender email address is invalid.'
        );
    }

    if ($configuration['from_name'] === '') {
        throw new RuntimeException(
            'Sender name is required.'
        );
    }

    if (
        $configuration['reply_to_email'] !== '' &&
        !filter_var(
            $configuration['reply_to_email'],
            FILTER_VALIDATE_EMAIL
        )
    ) {
        throw new RuntimeException(
            'Reply-to email address is invalid.'
        );
    }

    return $configuration;
}
