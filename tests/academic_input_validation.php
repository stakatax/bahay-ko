<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/services/AcademicManagementService.php';
$service = (new ReflectionClass(AcademicManagementService::class))->newInstanceWithoutConstructor();
$id = new ReflectionMethod(AcademicManagementService::class, 'positiveId');
$text = new ReflectionMethod(AcademicManagementService::class, 'cleanText');
$checks = 0;
foreach (['12abc','1.5','1e2', ['12'], true, false, 0, -1, '', null, '99999999999999999999999'] as $value) {
    try { $id->invoke($service, $value, 'Grade'); throw new RuntimeException('Malformed academic ID accepted.'); }
    catch (InvalidArgumentException $exception) { $checks++; }
}
foreach ([1, '12', ' 12 '] as $value) {
    if ($id->invoke($service, $value, 'Grade') !== (int) $value) throw new RuntimeException('Valid ID rejected.');
    $checks++;
}
foreach ([[], ['name'], true] as $value) {
    try { $text->invoke($service, $value); throw new RuntimeException('Malformed text accepted.'); }
    catch (InvalidArgumentException $exception) { $checks++; }
}
if ($text->invoke($service, '  Grade   12 ') !== 'Grade 12') throw new RuntimeException('Text normalization changed.');
$checks++;
echo "PASS: $checks academic input checks; no writes.\n";
