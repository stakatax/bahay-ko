<?php

function pushConfiguration(): array
{
    static $configuration =
    null;

    if (is_array($configuration)) {
        return $configuration;
    }

    /*
     * XAMPP on Windows may not automatically
     * locate its OpenSSL configuration.
     */
    if (
        PHP_OS_FAMILY === 'Windows' &&
        trim(
            (string) getenv(
                'OPENSSL_CONF'
            )
        ) === ''
    ) {
        $xamppRoot =
            dirname(
                __DIR__,
                3
            );

        $opensslConfigurationPath =
            $xamppRoot
            . DIRECTORY_SEPARATOR
            . 'php'
            . DIRECTORY_SEPARATOR
            . 'extras'
            . DIRECTORY_SEPARATOR
            . 'openssl'
            . DIRECTORY_SEPARATOR
            . 'openssl.cnf';

        if (
            is_file(
                $opensslConfigurationPath
            )
        ) {
            putenv(
                'OPENSSL_CONF='
                    . $opensslConfigurationPath
            );
        }
    }

    $localConfigurationPath =
        __DIR__
        . DIRECTORY_SEPARATOR
        . 'push.local.php';

    if (
        !is_file(
            $localConfigurationPath
        )
    ) {
        throw new RuntimeException(
            'The local Web Push configuration is missing.'
        );
    }

    $loadedConfiguration =
        require $localConfigurationPath;

    if (
        !is_array(
            $loadedConfiguration
        ) ||
        empty($loadedConfiguration['subject']) ||
        empty($loadedConfiguration['public_key']) ||
        empty($loadedConfiguration['private_key'])
    ) {
        throw new RuntimeException(
            'The Web Push configuration is incomplete.'
        );
    }

    $configuration =
        $loadedConfiguration;

    return $configuration;
}
