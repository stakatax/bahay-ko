<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/StudentPlacement.php';

class StudentPlacementController extends BaseController
{
    public function review(): void
    {
        $this->requireRole('Admin');
        if (!$this->requirePlacementPost()) return;
        if (!$this->requirePlacementCsrf()) return;
        try {
            $id = filter_var($_POST['change_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            $action = $_POST['review_action'] ?? '';
            if (!$id || !is_string($action)) throw new InvalidArgumentException('Select a valid placement change.');
            (new StudentPlacement())->review((int) $_SESSION['user_id'], $id, $action, $_POST);
            try { $this->log('REVIEW_STUDENT_PLACEMENT', 'Reviewed Student placement change #' . $id . ' (' . $action . ').'); }
            catch (Throwable $error) { error_log('Placement review activity log failed.'); }
            $_SESSION['placement_review_message'] = 'Placement review saved.';
        } catch (Throwable $error) {
            $_SESSION['placement_review_message'] = $error instanceof InvalidArgumentException || $error instanceof DomainException
                ? $error->getMessage() : 'Placement review could not be saved.';
        }
        $this->redirect('index.php?page=student_profile_management#placement-changes');
    }
    public function index(): array
    {
        $this->requireRole('Student');
        $data = (new StudentPlacement())->pageData((int) $_SESSION['user_id']);
        $data['error'] = $_SESSION['placement_error'] ?? '';
        unset($_SESSION['placement_error']);
        if ($data['confirmed_at'] && $data['error'] === '' && ($_GET['review_details'] ?? '') !== '1') {
            $this->redirect('index.php?page=student_profile_survey');
        }
        return $data;
    }

    public function save(): void
    {
        $this->requireRole('Student');
        if (!$this->requirePlacementPost()) return;
        if (!$this->requirePlacementCsrf()) return;
        try {
            $id = filter_var($_POST['assignment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) throw new InvalidArgumentException('Reload the assigned update.');
            (new StudentPlacement())->stage((int) $_SESSION['user_id'], $id, $_POST);
            $this->redirect('index.php?page=student_profile_survey');
        } catch (Throwable $error) {
            $_SESSION['placement_error'] = $error instanceof InvalidArgumentException || $error instanceof DomainException
                ? $error->getMessage() : 'Your details could not be saved. Please try again.';
            error_log('Profile account confirmation failed (' . get_class($error) . ').');
            $this->redirect('index.php?page=student_profile_account');
        }
    }

    private function requirePlacementPost(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') return true;
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: application/json');
        echo json_encode(['success'=>false,'message'=>'Use POST for this action.']);
        return false;
    }

    private function requirePlacementCsrf(): bool
    {
        try { $this->requireCsrfToken(); return true; }
        catch (RuntimeException $error) {
            http_response_code(419);
            header('Content-Type: application/json');
            header('Cache-Control: no-store');
            echo json_encode(['success'=>false,'message'=>'Your form session expired. Refresh the page and try again.']);
            return false;
        }
    }
}
