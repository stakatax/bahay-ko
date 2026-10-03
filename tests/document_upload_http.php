<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/DocumentUploadValidator.php';
$root = dirname(__DIR__);
$temp = sys_get_temp_dir() . '/olshco-upload-' . bin2hex(random_bytes(8));
mkdir($temp, 0700);
$checks = 0;
function uploadCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
}
try {
    $entry = $temp . '/request.php';
    $bootstrap = '<?php require_once ' . var_export($root . '/app/services/PostService.php', true) . ';';
    $bootstrap .= <<<'CGI'
header('Content-Type: application/json');
$file = $_FILES['document'] ?? null;
if (isset($_GET['size']) && $file) { $file['size'] = 1; }
try {
    if (isset($_GET['replace'])) {
        define('UPLOAD_TEST_ROOT', ROOT_PATH);
        require ROOT_PATH . '/tests/support/document_upload_fixture.php';
        $result = replacementFixture($file, isset($_GET['fail']));
    } elseif (isset($_GET['store'])) {
        $service = (new ReflectionClass(PostService::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(PostService::class, 'storeDocumentUpload');
        $result = $method->invoke($service, $file);
        $path = ROOT_PATH . '/' . $result['file_path'];
        try {
            $result['stored'] = is_file($path);
            $result['hash'] = hash_file('sha256', $path);
        } finally { if (is_file($path)) { unlink($path); } }
    } else { $result = DocumentUploadValidator::validate($file); }
    echo json_encode(['ok' => true, 'result' => $result]);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()]);
}
CGI;
    file_put_contents($entry, str_replace('ROOT_PATH', var_export($root, true), $bootstrap));
    $request = static function (string $name, string $bytes, string $query = '') use ($entry, $temp): array {
        $boundary = 'olshco' . bin2hex(random_bytes(12));
        $body = "--$boundary\r\nContent-Disposition: form-data; name=\"document\"; filename=\"$name\"\r\nContent-Type: application/octet-stream\r\n\r\n" . $bytes . "\r\n--$boundary--\r\n";
        $environment = array_merge(getenv(), [
            'REDIRECT_STATUS' => '1', 'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_PROTOCOL' => 'HTTP/1.1', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80',
            'REQUEST_METHOD' => 'POST', 'SCRIPT_FILENAME' => $entry, 'SCRIPT_NAME' => '/request.php',
            'QUERY_STRING' => $query, 'CONTENT_TYPE' => 'multipart/form-data; boundary=' . $boundary,
            'CONTENT_LENGTH' => (string) strlen($body)
        ]);
        $cgi = dirname(PHP_BINARY) . '/php-cgi' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
        $process = proc_open([$cgi, '-d', 'cgi.force_redirect=0', '-d', 'upload_max_filesize=24M',
            '-d', 'post_max_size=32M', '-d', 'upload_tmp_dir=' . $temp],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $temp, $environment);
        if (!is_resource($process)) { throw new RuntimeException('CGI unavailable'); }
        $offset = 0;
        while ($offset < strlen($body)) {
            $written = fwrite($pipes[0], substr($body, $offset, 65536));
            if (!$written) { throw new RuntimeException('CGI upload failed'); }
            $offset += $written;
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0 || trim($errors) !== '') { throw new RuntimeException($errors . $output); }
        $parts = preg_split('/\r?\n\r?\n/', $output, 2);
        $result = json_decode($parts[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
        uploadCheck(str_contains($parts[0], 'Content-Type: application/json'), 'JSON response');
        uploadCheck($result['ok'] || str_contains($parts[0], '422'), 'Validation HTTP status');
        return $result;
    };
    foreach ([null, [], ['error' => []], ['error' => UPLOAD_ERR_NO_FILE], ['error' => UPLOAD_ERR_PARTIAL],
        ['error' => UPLOAD_ERR_INI_SIZE], ['error' => UPLOAD_ERR_FORM_SIZE],
        ['error' => UPLOAD_ERR_OK, 'name' => [], 'tmp_name' => $entry],
        ['error' => UPLOAD_ERR_OK, 'name' => 'fake.txt', 'tmp_name' => $entry]] as $invalid) {
        try { DocumentUploadValidator::validate($invalid); uploadCheck(false, 'Invalid upload rejected'); }
        catch (InvalidArgumentException $exception) { uploadCheck(true, 'Invalid upload rejected'); }
    }
    $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
    foreach (['notice.PDF' => $pdf, 'notice.txt' => "School notice\r\nClasses resume Monday.\r\n"] as $name => $bytes) {
        $result = $request($name, $bytes, 'store=1&size=1');
        uploadCheck($result['ok'], 'Valid ' . $name);
        uploadCheck($result['result']['file_size'] === strlen($bytes), 'Measured size overrides reported size');
        uploadCheck($result['result']['stored'] && $result['result']['hash'] === hash('sha256', $bytes), 'Real upload moved intact');
        uploadCheck((bool) preg_match('/document_[a-f0-9]{24}\.(pdf|txt)$/', $result['result']['file_path']), 'Randomized storage name');
    }
    foreach (['docx' => ['word/document.xml', 'wordprocessingml.document'],
        'xlsx' => ['xl/workbook.xml', 'spreadsheetml.sheet'],
        'pptx' => ['ppt/presentation.xml', 'presentationml.presentation']] as $extension => $parts) {
        $archivePath = $temp . '/' . $extension . '.zip';
        $archive = new PharData($archivePath, 0, null, Phar::ZIP);
        $archive[$parts[0]] = '<?xml version="1.0"?><fixture/>';
        $archive['[Content_Types].xml'] = '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/' . $parts[0] . '" ContentType="application/vnd.openxmlformats-officedocument.' . $parts[1] . '.main+xml"/></Types>';
        unset($archive);
        $bytes = file_get_contents($archivePath);
        uploadCheck($request('fixture.' . $extension, $bytes, 'store=1')['ok'], 'Office package accepted: ' . $extension);
        uploadCheck(!$request('renamed.' . ($extension === 'docx' ? 'xlsx' : 'docx'), $bytes)['ok'], 'Office extension mismatch');
        uploadCheck(!$request('renamed.pdf', $bytes)['ok'], 'ZIP renamed PDF');
    }
    // Compound-file fixtures exercise MIME/header/stream classification, not Office rendering.
    foreach (['doc' => 'WordDocument', 'xls' => 'Workbook', 'ppt' => 'PowerPoint Document'] as $extension => $stream) {
        $ole = "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1" . str_repeat("\0", 504)
            . mb_convert_encoding($stream . "\0", 'UTF-16LE', 'UTF-8') . str_repeat("\0", 512);
        uploadCheck($request('legacy.' . $extension, $ole)['ok'], 'Legacy compound classification: ' . $extension);
        uploadCheck(!$request('mismatch.' . ($extension === 'doc' ? 'xls' : 'doc'), $ole)['ok'], 'Legacy stream mismatch');
    }
    foreach ([
        '<broken>',
        '<!DOCTYPE Types [<!ENTITY x SYSTEM "file:///unread-fixture">]><Types/>',
        str_repeat('x', 65537),
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.ms-word.document.macroEnabled.main+xml"/></Types>'
    ] as $i => $manifest) {
        $archive = new PharData($temp . '/invalid-' . $i . '.zip', 0, null, Phar::ZIP);
        $archive['word/document.xml'] = '<fixture/>';
        $archive['[Content_Types].xml'] = $manifest;
        unset($archive);
        uploadCheck(!$request('invalid.docx', file_get_contents($temp . '/invalid-' . $i . '.zip'))['ok'], 'Invalid Office manifest rejected');
    }
    $zipPath = $temp . '/unrelated.zip';
    $zip = new PharData($zipPath, 0, null, Phar::ZIP);
    $zip['unrelated.txt'] = 'not an Office document'; unset($zip);
    uploadCheck(!$request('arbitrary.docx', file_get_contents($zipPath))['ok'], 'Arbitrary ZIP rejected');
    foreach ([['empty.txt', ''], ['renamed.pdf', '<?php echo 1;'], ['renamed.doc', 'not a Word file'],
        ['renamed.xls', $pdf], ['renamed.ppt', $pdf], ['renamed.txt', "\0\1\2\3"],
        ['web.txt', '<html><body>HTML payload</body></html>'], ['bad.php', $pdf],
        ['huge.txt', str_repeat('x', 20 * 1024 * 1024 + 1)]] as [$name, $bytes]) {
        uploadCheck(!$request($name, $bytes, 'size=1')['ok'], 'Rejected: ' . $name);
    }
    $replacement = $request('replacement.txt', 'Updated school notice', 'replace=1')['result'];
    uploadCheck($replacement['error'] === null && !$replacement['same_path'], 'Replacement saved');
    uploadCheck(!$replacement['old_exists'] && $replacement['saved_exists'], 'Old file removed after successful replacement');
    uploadCheck($replacement['saved']['file_name'] === 'replacement.txt' && (int) $replacement['saved']['file_size'] === 21, 'Replacement metadata reloads');
    foreach ([['fake.pdf', 'Not a PDF', 'replace=1'], ['valid.txt', 'Valid replacement', 'replace=1&fail=1']] as [$name, $bytes, $query]) {
        $replacement = $request($name, $bytes, $query)['result'];
        uploadCheck($replacement['error'] !== null && $replacement['same_path'], 'Failed replacement preserves database path');
        uploadCheck($replacement['old_exists'] && $replacement['saved_exists'], 'Failed replacement preserves original file');
        uploadCheck($replacement['new_files'] === 0, 'Failed replacement leaves no uploaded orphan');
        uploadCheck($replacement['saved']['title'] === 'Original fixture', 'Failed replacement preserves metadata');
    }
    echo 'PASS: ' . $checks . " document upload checks.\n";
} finally {
    foreach (glob($temp . '/*') as $file) { if (is_file($file)) { unlink($file); } }
    rmdir($temp);
}
