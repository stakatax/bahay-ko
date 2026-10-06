<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/EmailDeliveryService.php';
$method = new ReflectionMethod(EmailDeliveryService::class, 'announcementText');
$cases = [
    ['<p>School update</p><p>Classes resume tomorrow.<br>Please arrive early.</p>', "School update\nClasses resume tomorrow.\nPlease arrive early."],
    ['<ul><li>Bring ID</li><li>Bring books</li></ul>', "- Bring ID\n- Bring books"],
    ['<script>alert(1)</script><style>body{display:none}</style><p>Safe &amp; readable</p>', 'Safe & readable'],
    ['<p>&lt;img src=x onerror=alert(1)&gt;</p>', '<img src=x onerror=alert(1)>'],
    ["Plain text\nSecond line", "Plain text\nSecond line"],
];
foreach ($cases as [$input, $expected]) {
    if ($method->invoke(null, $input) !== $expected) { throw new RuntimeException('Announcement text conversion failed.'); }
}
// Sender escapes even entity-encoded tags after conversion.
$escape = new ReflectionMethod(EmailSender::class, 'escape');
$sender = (new ReflectionClass(EmailSender::class))->newInstanceWithoutConstructor();
$escaped = $escape->invoke($sender, $method->invoke(null, $cases[3][0]));
if (str_contains($escaped, '<img')) { throw new RuntimeException('Unsafe email markup.'); }
$include = new ReflectionMethod(EmailDeliveryService::class, 'includesAnnouncementText');
foreach ([['announcement', 'announcement', true], ['announcement', 'workflow', false], ['event', 'event', false]] as [$content, $type, $expected]) {
    if ($include->invoke(null, ['content_type'=>$content, 'notification_type'=>$type]) !== $expected) {
        throw new RuntimeException('Workflow/content email separation failed.');
    }
}
echo "PASS: 9 announcement email text and workflow checks; no database changes or deliveries.\n";
