<?php
require_once __DIR__.'/EmailSender.php';
require_once __DIR__.'/RequestRateLimitService.php';

class ContactInquiryService
{
    public const TYPES = ['Admissions', 'Enrollment', 'Academic Records', 'Basic Education', 'College', 'Technical Support', 'General Inquiry'];
    private const RECIPIENT = 'sapinjanfortun1@gmail.com';

    public function __construct(private ?EmailSender $sender = null, private ?RequestRateLimitService $limiter = null) {}

    public function send(array $input, string $ip): void
    {
        $data = [];
        foreach (['name', 'email', 'subject', 'message'] as $field) {
            if (!is_string($input[$field] ?? null)) { throw new InvalidArgumentException('Complete all inquiry fields.'); }
            $data[$field] = trim($input[$field]);
        }
        if ($data['name'] === '' || mb_strlen($data['name']) > 150 || preg_match('/[\x00-\x1f\x7f]/', $data['name'])) {
            throw new InvalidArgumentException('Enter a valid full name of up to 150 characters.');
        }
        if (strlen($data['email']) > 254 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if (!in_array($data['subject'], self::TYPES, true)) { throw new InvalidArgumentException('Select an inquiry type from the list.'); }
        if (mb_strlen($data['message']) < 10 || mb_strlen($data['message']) > 1500
            || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $data['message'])) {
            throw new InvalidArgumentException('Enter a message between 10 and 1,500 characters.');
        }
        $this->limiter ??= new RequestRateLimitService();
        $this->limiter->contact($data['email'], $ip);
        $this->sender ??= new EmailSender();
        $message = "Website inquiry\nName: {$data['name']}\nEmail: {$data['email']}\nType: {$data['subject']}\n\n{$data['message']}";
        // Fixed recipient and configured SMTP sender; visitors cannot choose a destination or spoof From.
        if (!$this->sender->send(self::RECIPIENT, 'Jan Fortun Sapin', 'Website inquiry: '.$data['subject'], $message)) {
            throw new RuntimeException('Inquiry SMTP delivery was not confirmed.');
        }
    }
}
