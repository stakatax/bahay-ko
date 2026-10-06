<?php
require_once __DIR__.'/../services/ContactInquiryService.php';
require_once __DIR__.'/../../config/security.php';

class ContactController
{
    public function submit(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405); header('Allow: POST'); echo 'Method not allowed.'; exit;
        }
        $input = [];
        foreach (['name', 'email', 'subject', 'message'] as $field) {
            $input[$field] = is_string($_POST[$field] ?? null) ? mb_substr($_POST[$field], 0, 1600) : '';
        }
        try {
            requireValidCsrfToken();
            $token = $_POST['contact_token'] ?? null;
            if (!is_string($token) || !is_string($_SESSION['contact_form_token'] ?? null)
                || !hash_equals($_SESSION['contact_form_token'], $token)) {
                throw new InvalidArgumentException('This inquiry form expired or was already submitted. Refresh the page and try again.');
            }
            (new ContactInquiryService())->send($_POST, (string)($_SERVER['REMOTE_ADDR'] ?? ''));
            unset($_SESSION['contact_form_token']);
            $_SESSION['contact_flash'] = ['success'=>'Your inquiry was accepted by the email service. We will reply using the email address you supplied.'];
        } catch (InvalidArgumentException|RequestRateLimitException $error) {
            $_SESSION['contact_flash'] = ['error'=>$error->getMessage(), 'input'=>$input];
        } catch (Throwable $error) {
            error_log('Contact inquiry failed: '.get_class($error).' code='.(int)$error->getCode());
            $_SESSION['contact_flash'] = ['error'=>'We could not send your inquiry. Please try again later or use Send an Email above.', 'input'=>$input];
        }
        header('Cache-Control: no-store');
        header('Location: index.php?page=contact#contactForm', true, 303); exit;
    }
}
