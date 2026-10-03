<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Local Apache only. Creates uniquely named inert files and removes precisely those files.
$root = dirname(__DIR__) . '/Assets/uploads/';
$prefix = 'h5-check-' . bin2hex(random_bytes(10));
$created = [];
$checks = 0;
try {
    foreach (['jpg' => 200, 'jpeg' => 200, 'png' => 200, 'webp' => 200,
        'mp3' => 200, 'm4a' => 200, 'wav' => 200, 'webm' => 200,
        'pdf' => 403, 'PDF' => 403, 'docx' => 403, 'txt' => 403, 'html' => 403,
        'svg' => 403, 'php' => 403, 'PHP' => 403, 'phtml' => 403,
        'php.jpg' => 403, 'PHP8.png' => 403, 'cgi.webp' => 403, 'unknown' => 403] as $suffix => $expected) {
        $name = $prefix . '-' . count($created) . '.' . $suffix;
        $path = $root . $name;
        $body = 'Inert upload protection fixture ' . $prefix;
        if (file_exists($path)) { throw new RuntimeException('Fixture collision'); }
        file_put_contents($path, $body); $created[] = $path;
        $curl = curl_init('http://127.0.0.1/bahay-ko/Assets/uploads/' . rawurlencode($name));
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 10, CURLOPT_PROXY => '']);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl); curl_close($curl);
        if ($status !== $expected || $response === false) { throw new RuntimeException("$suffix expected $expected, got $status: $error"); }
        $checks++;
        if (!str_contains(strtolower($response), 'x-content-type-options: nosniff')) { throw new RuntimeException('Missing nosniff: ' . $suffix); }
        $checks++;
        if (($expected === 200) !== str_contains($response, $body)) { throw new RuntimeException('Unexpected body: ' . $suffix); }
        $checks++;
    }
    foreach (['documents', 'profile-photos'] as $directory) {
        foreach (['png', 'php.jpg'] as $extension) {
            $name = $prefix . '.' . $extension;
            $path = $root . $directory . '/' . $name;
            if (file_exists($path)) { throw new RuntimeException('Fixture collision'); }
            file_put_contents($path, 'Inert fixture'); $created[] = $path;
            $curl = curl_init('http://127.0.0.1/bahay-ko/Assets/uploads/' . $directory . '/' . $name);
            curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_PROXY => '']);
            curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
            $expected = $directory === 'profile-photos' && $extension === 'png' ? 200 : 403;
            if ($status !== $expected) { throw new RuntimeException("$directory/$extension expected $expected, got $status"); }
            $checks++;
        }
    }
    $curl = curl_init('http://127.0.0.1/bahay-ko/Assets/uploads/');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_PROXY => '']);
    curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
    if ($status !== 403) { throw new RuntimeException('Directory listing not denied'); }
    $checks++;
    echo "PASS: $checks Apache upload protection checks.\n";
} finally {
    foreach ($created as $path) { if (is_file($path)) { unlink($path); } }
}
