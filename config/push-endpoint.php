<?php

/** Only browser push services may receive worker requests. */
function validateBrowserPushEndpoint(mixed $value): string
{
    if (!is_string($value)) {
        throw new InvalidArgumentException('A valid secure push endpoint is required.');
    }
    $endpoint = trim($value);
    $parts = parse_url($endpoint);
    if ($endpoint === '' || strlen($endpoint) > 4096
        || preg_match('/[\x00-\x20\x7f]/', $endpoint)
        || !filter_var($endpoint, FILTER_VALIDATE_URL) || !is_array($parts)
        || strtolower($parts['scheme'] ?? '') !== 'https'
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
        || (isset($parts['port']) && $parts['port'] !== 443)) {
        throw new InvalidArgumentException('A valid secure push endpoint is required.');
    }
    $host = strtolower($parts['host'] ?? '');
    $allowed = $host === 'fcm.googleapis.com';
    foreach (['push.services.mozilla.com', 'push.apple.com', 'notify.windows.com'] as $domain) {
        if ($host === $domain || str_ends_with($host, '.' . $domain)) $allowed = true;
    }
    if (!$allowed) {
        throw new InvalidArgumentException('This browser push provider is not supported. Please use a supported browser.');
    }
    return $endpoint;
}

function validateBrowserPushKeys(array $keys): void
{
    $decoded = [];
    foreach (['p256dh', 'auth'] as $key) {
        $value = $keys[$key] ?? null;
        if (!is_string($value) || !preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $value) || strlen($value) > 255) {
            throw new InvalidArgumentException('The browser push encryption keys are invalid.');
        }
        $decoded[$key] = base64_decode(strtr($value, '-_', '+/'), true);
    }
    if (!is_string($decoded['p256dh']) || strlen($decoded['p256dh']) !== 65
        || $decoded['p256dh'][0] !== "\x04"
        || !is_string($decoded['auth']) || strlen($decoded['auth']) !== 16) {
        throw new InvalidArgumentException('The browser push encryption keys are invalid.');
    }
    // P-256 SubjectPublicKeyInfo prefix followed by the uncompressed browser key.
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $decoded['p256dh'];
    $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    if (openssl_pkey_get_public($pem) === false) {
        throw new InvalidArgumentException('The browser push encryption keys are invalid.');
    }
}
