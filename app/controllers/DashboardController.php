<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/DashboardService.php';

require_once __DIR__
    . '/../services/DashboardExcelExporter.php';

require_once __DIR__
    . '/../services/NotificationService.php';

class DashboardController extends BaseController
{
    private DashboardService $service;

    private DashboardExcelExporter $excelExporter;

    private NotificationService $notificationService;

    public function __construct()
    {
        $this->service =
            new DashboardService();

        $this->excelExporter =
            new DashboardExcelExporter();

        $this->notificationService =
            new NotificationService();
    }

    /* ==========================================
       ADMIN DASHBOARD
    ========================================== */

    public function index(): array
    {
        $this->requireRole(
            'Admin'
        );

        $dashboardData =
            $this->service
            ->index();

        $currentUserId =
            (int) (
                $_SESSION['user_id']
                ?? 0
            );

        $dashboardData['notifications'] =
            $this->notificationService
            ->getForUser(
                $currentUserId,
                5
            );

        return $dashboardData;
    }
    /* ==========================================
       EXPORT DASHBOARD REPORT
    ========================================== */

    public function export(): void
    {
        $this->requireRole(
            'Admin'
        );

        $data =
            $this->service
            ->export();

        $this->excelExporter
            ->download(
                $data
            );
    }
}
