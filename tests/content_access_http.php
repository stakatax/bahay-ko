<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/services/DocumentDownloadService.php';
require __DIR__ . '/../config/dbconnect.php';

$cgi = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'php-cgi'
    . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
if (!is_file($cgi)) {
    throw new RuntimeException('php-cgi is required for isolated HTTP verification.');
}

$temporary = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'olshco-http-' . bin2hex(random_bytes(12));
if (!mkdir($temporary, 0700)) {
    throw new RuntimeException('Unable to create the isolated HTTP test directory.');
}
$checks = 0;
function httpCheck(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $checks++;
}

try {
    $root = dirname(__DIR__);
    $entry = $temporary . DIRECTORY_SEPARATOR . 'controller.php';
    $bootstrap = "<?php\nrequire_once " . var_export($root . '/app/controllers/DocumentDownloadController.php', true)
        . ";\nrequire_once " . var_export($root . '/app/controllers/ContentEngagementController.php', true)
        . ";\n"
        . '$_SESSION = [\'user_id\' => (int) getenv(\'ACCESS_TEST_ACTOR\'), \'csrf_token\' => \'fixture-csrf\'];'
        . "\n"
        . '$action = getenv(\'ACCESS_TEST_ACTION\');'
        . "\n"
        . 'if ($action === \'download\') { (new DocumentDownloadController())->download(); }'
        . "\n"
        . 'if (!in_array($action, [\'open\', \'react\', \'comment\', \'acknowledge\'], true)) { exit(1); }'
        . "\n"
        . '(new ContentEngagementController())->{$action}();';
    file_put_contents($entry, $bootstrap);

    $request = static function (
        string $action, string $method, int $actor, array $query = [], array $post = []
    ) use ($cgi, $temporary, $entry): array {
        $body = http_build_query($post);
        $environment = getenv();
        unset($environment['HTTP_COOKIE'], $environment['HTTP_X_CSRF_TOKEN']);
        $environment = array_merge($environment, [
            'REDIRECT_STATUS' => '1',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'REQUEST_METHOD' => $method,
            'SCRIPT_FILENAME' => $entry,
            'SCRIPT_NAME' => '/controller.php',
            'QUERY_STRING' => http_build_query($query),
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'CONTENT_LENGTH' => (string) strlen($body),
            'ACCESS_TEST_ACTOR' => (string) $actor,
            'ACCESS_TEST_ACTION' => $action
        ]);
        $process = proc_open([
            $cgi, '-d', 'cgi.force_redirect=0',
            '-d', 'session.save_path=' . $temporary,
            '-d', 'display_errors=0'
        ], [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, dirname($entry), $environment);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start isolated CGI verification.');
        }
        fwrite($pipes[0], $body);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0 || trim($errors) !== '') {
            throw new RuntimeException('CGI verification failed: ' . $errors);
        }
        $parts = preg_split("/\r?\n\r?\n/", $output, 2);
        if (count($parts) !== 2) {
            throw new RuntimeException('Invalid CGI response.');
        }
        [$headers, $responseBody] = $parts;
        $status = preg_match('/^Status:\s*(\d+)/mi', $headers, $match) ? (int) $match[1] : 200;
        return [$status, $headers, $responseBody];
    };

    // Only metadata is read from existing records. No login, publication or engagement write is invoked.
    $admin = $conn->query("SELECT u.user_id FROM user u INNER JOIN role r ON r.role_id=u.role_id
        WHERE u.status='Active' AND r.role_prefix='Admin' ORDER BY u.user_id LIMIT 1")->fetch_assoc();
    if (!$admin) {
        throw new RuntimeException('An active Administrator is required for read-only delivery verification.');
    }
    $adminId = (int) $admin['user_id'];
    $rows = $conn->query('SELECT document_id FROM documents ORDER BY document_id')->fetch_all(MYSQLI_ASSOC);
    $service = new DocumentDownloadService();
    $file = null;
    foreach ($rows as $row) {
        try {
            $file = $service->resolve((int) $row['document_id'], $adminId, 'workspace');
            $documentId = (int) $row['document_id'];
            break;
        } catch (DomainException $exception) {
            continue;
        }
    }
    if ($file === null) {
        throw new RuntimeException('An existing readable document is required for delivery verification.');
    }

    $query = ['document_id' => (string) $documentId, 'context' => 'workspace'];
    [$status,$headers,$body] = $request('download', 'GET', $adminId, $query);
    httpCheck($status === 200, 'Authorized GET succeeds');
    httpCheck(hash('sha256', $body) === hash_file('sha256', $file['path']), 'Delivered bytes match the authorized file');
    httpCheck(stripos($headers, 'Content-Disposition: attachment;') !== false, 'Attachment header present');
    httpCheck(stripos($headers, 'Cache-Control: private, no-store') !== false, 'Private noncacheable delivery');
    httpCheck(stripos($headers, 'X-Content-Type-Options: nosniff') !== false, 'No MIME sniffing');
    [$status,$headers,$body] = $request('download', 'HEAD', $adminId, $query);
    httpCheck($status === 200 && $body === '', 'HEAD succeeds without a body');
    httpCheck(stripos($headers, 'Content-Length: ' . filesize($file['path'])) !== false, 'HEAD reports correct file size');
    [$status] = $request('download', 'GET', 0, $query);
    httpCheck($status === 401, 'Anonymous download rejected');
    [$status,$headers] = $request('download', 'POST', $adminId, $query);
    httpCheck($status === 405 && stripos($headers, 'Allow: GET, HEAD') !== false, 'POST rejected with Allow header');
    foreach (['', '0', '-1', 'abc', ['1']] as $invalidId) {
        [$status] = $request('download', 'GET', $adminId, ['document_id'=>$invalidId]);
        httpCheck($status === 404, 'Invalid document IDs return safe 404');
    }
    [$status] = $request('download', 'GET', $adminId, $query + ['inline'=>'1']);
    httpCheck($status === 200, 'Preview parameter keeps authorized access');

    $student = $conn->query("SELECT u.user_id FROM user u INNER JOIN role r ON r.role_id=u.role_id
        WHERE u.status='Active' AND r.role_prefix='Student' ORDER BY u.user_id LIMIT 1")->fetch_assoc();
    if (!$student) {
        throw new RuntimeException('An active Student is required for denied-role HTTP verification.');
    }
    foreach (['workspace','department'] as $context) {
        [$status] = $request('download', 'GET', (int) $student['user_id'],
            ['document_id'=>(string) $documentId,'context'=>$context]);
        httpCheck($status === 404, 'Student cannot forge a staff download context');
    }

    $verifiedPreviewTypes = [];
    foreach ($rows as $row) {
        try {
            $candidate = $service->resolve((int) $row['document_id'], $adminId, 'workspace');
        } catch (DomainException $exception) {
            continue;
        }
        $extension = strtolower(pathinfo($candidate['path'], PATHINFO_EXTENSION));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($candidate['path']);
        if (isset($verifiedPreviewTypes[$extension])
            || !(($extension === 'pdf' && $mime === 'application/pdf')
                || ($extension === 'txt' && $mime === 'text/plain'))) {
            continue;
        }
        [$status,$headers,$body] = $request('download', 'GET', $adminId,
            ['document_id'=>(string) $row['document_id'],'context'=>'workspace','inline'=>'1']);
        httpCheck($status === 200 && stripos($headers, 'Content-Disposition: inline;') !== false,
            'Supported preview has inline disposition');
        httpCheck(stripos($headers, 'Content-Type: ' . $mime) !== false, 'Preview has verified MIME type');
        httpCheck(hash('sha256', $body) === hash_file('sha256', $candidate['path']), 'Preview bytes match');
        $verifiedPreviewTypes[$extension] = true;
    }

    // Every engagement request stops before writes: wrong method, no login, bad CSRF or invalid ID.
    foreach (['open','react','comment','acknowledge'] as $action) {
        foreach ([
            ['GET', $adminId, [], 405],
            ['POST', 0, [], 401],
            ['POST', $adminId, ['content_type'=>'document'], 419],
            ['POST', $adminId, ['content_type'=>'document','csrf_token'=>'fixture-csrf','content_id'=>'0'], 422],
            ['POST', $adminId, ['content_type'=>'document','csrf_token'=>'fixture-csrf','content_id'=>['1']], 422],
            ['POST', $adminId, ['content_type'=>['document'],'csrf_token'=>'fixture-csrf','content_id'=>'0'], 422]
        ] as [$method,$actor,$post,$expected]) {
            [$status,$headers,$body] = $request($action, $method, $actor, [], $post);
            httpCheck($status === $expected, 'Engagement request security status');
            httpCheck(stripos($headers, 'Content-Type: application/json') !== false, 'AJAX errors stay JSON');
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            httpCheck($json['success'] === false, 'AJAX error payload is valid');
        }
    }

    echo "PASS: {$checks} CGI HTTP checks; metadata/file reads only, no existing record changes.\n";
} finally {
    $conn->close();
    // The directory was uniquely created above. Remove only files created by these CGI tests.
    foreach (glob($temporary . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
    rmdir($temporary);
}
