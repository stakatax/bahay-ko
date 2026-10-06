<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../app/services/ContactInquiryService.php';
class InquirySenderFixture extends EmailSender {
    public array $sent=[];
    public bool $confirmed=true;
    public function __construct() {}
    public function send(string $recipientEmail, string $recipientName, string $subject, string $message, array $context=[]): bool {
        $this->sent[]=[$recipientEmail,$subject,$message]; return $this->confirmed;
    }
}
class InquiryLimiterFixture extends RequestRateLimitService {
    public int $calls=0;
    public bool $blocked=false;
    public function contact(string $email, string $ip): void {
        $this->calls++; if ($this->blocked) { throw new RequestRateLimitException(60); }
    }
}
$sender=new InquirySenderFixture(); $limiter=new InquiryLimiterFixture();
$service=new ContactInquiryService($sender,$limiter);
$valid=['name'=>'Evaluator','email'=>'evaluator@example.invalid','subject'=>'Admissions','message'=>'This is an isolated test inquiry.'];
$checks=0;
foreach ([['name'=>''],['name'=>str_repeat('x',151)],['name'=>"Visitor\r\nBcc: other@example.invalid"],['email'=>'invalid'],
    ['email'=>"a@example.invalid\r\nBcc: x@example.invalid"],['subject'=>'arbitrary'],['message'=>'short'],['message'=>str_repeat('x',1501)],
    ['message'=>"invalid\0message"],['name'=>[]],['email'=>[]],['subject'=>[]],['message'=>[]]] as $bad) {
    try { $service->send(array_replace($valid,$bad),'127.0.0.1'); }
    catch (InvalidArgumentException $e) { $checks++; continue; }
    throw new RuntimeException('Invalid inquiry accepted.');
}
if ($sender->sent || $limiter->calls) { throw new RuntimeException('Invalid input reached SMTP/limiter.'); }
$checks++;
foreach (ContactInquiryService::TYPES as $type) { $service->send(array_replace($valid,['subject'=>$type,'recipient'=>'attacker@example.invalid']),'127.0.0.1'); $checks++; }
foreach ($sender->sent as [$recipient,$subject,$message]) {
    if ($recipient!=='sapinjanfortun1@gmail.com' || !str_starts_with($subject,'Website inquiry: ')
        || !str_contains($message,'evaluator@example.invalid')) { throw new RuntimeException('Fixed recipient or sender details changed.'); }
}
$checks++;
$limiter->blocked=true;
$before=count($sender->sent);
try { $service->send($valid,'127.0.0.1'); throw new RuntimeException('Limiter bypassed.'); }
catch (RequestRateLimitException $e) { if (count($sender->sent)!==$before) { throw new RuntimeException('Blocked inquiry sent.'); } $checks++; }
$limiter->blocked=false; $sender->confirmed=false;
try { $service->send($valid,'127.0.0.1'); throw new LogicException('Unconfirmed mail accepted.'); }
catch (RuntimeException $e) { $checks++; }
echo "PASS: $checks inquiry validation/destination/rate-limit/failure checks; fake mailer, no DB writes or emails sent.\n";
