<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PasswordRecoveryController.php';
class ScalarFormProbe extends BaseController {
    public function __construct() {}
    public function validate(): void { $this->requireScalarFormValues(); }
}
$checks = 0;
$probe = new ScalarFormProbe();
foreach (['identifier','password','role_type','first_name','email','token','new_password','new_password_confirmation'] as $field) {
    $_POST = [$field => ['crafted']];
    try { $probe->validate(); throw new RuntimeException('Malformed input accepted: ' . $field); }
    catch (InvalidArgumentException $exception) {
        if ($exception->getMessage() !== 'Invalid form values. Please review your entries and try again.') throw $exception;
        $checks++;
    }
}
$_POST = ['password' => '  Keep password spaces!  ', 'identifier' => 'example@example.com', 'section_id' => '12', 'csrf_token' => 'fixture'];
$original = $_POST;
$probe->validate();
if ($_POST !== $original) throw new RuntimeException('Validation altered scalar inputs or password.');
$checks++;
$_GET = ['token' => ['crafted']];
$controller = (new ReflectionClass(PasswordRecoveryController::class))->newInstanceWithoutConstructor();
$result = $controller->resetPage();
if ($result['valid_token'] !== false || $result['token'] !== '') throw new RuntimeException('Malformed reset token not rejected.');
$checks++;
echo "PASS: $checks scalar-form validation checks; no writes or deliveries.\n";
