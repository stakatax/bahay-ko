<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/DepartmentAnalyticsService.php';

require_once __DIR__
    . '/../services/DepartmentAnalyticsExcelExporter.php';

class DepartmentAnalyticsController extends BaseController
{
    private DepartmentAnalyticsService $service;

    public function __construct()
    {
        $this->service =
            new DepartmentAnalyticsService();
    }

    public function index(): array
    {
        $this->requireRole(
            'Faculty'
        );

        $departmentId =
            (int) (
                $_SESSION['department_id']
                ?? 0
            );

        $departmentName =
            trim(
                (string) (
                    $_SESSION['department_name']
                    ?? ''
                )
            );



        return $this->service
            ->index(
                $departmentId,
                $departmentName,
                $_GET['range']
                    ?? '30'
            );
    }

    /* ==========================================
       EXPORT DEPARTMENT ANALYTICS
    ========================================== */

    public function export(): void
    {
        $this->requireRole(
            'Faculty'
        );

        try {
            $departmentId =
                (int) (
                    $_SESSION['department_id']
                    ?? 0
                );

            $departmentName =
                trim(
                    (string) (
                        $_SESSION['department_name']
                        ?? ''
                    )
                );

            $reportData =
                $this->service
                ->index(
                    $departmentId,
                    $departmentName,
                    $_GET['range']
                        ?? '30'
                );

            $exporter =
                new DepartmentAnalyticsExcelExporter();

            $exporter->download(
                $reportData
            );
        } catch (Throwable $exception) {
            error_log(
                'Department analytics export error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=department_analytics'
                    . '&error='
                    . urlencode(
                        'Unable to generate the department report.'
                    )
            );
        }
    }

    /* ==========================================
       READ-ONLY DEPARTMENT CONTENT PREVIEW
    ========================================== */

    public function preview(): array
    {
        $this->requireRole(
            'Faculty'
        );

        try {
            $departmentId =
                (int) (
                    $_SESSION['department_id']
                    ?? 0
                );

            $departmentName =
                trim(
                    (string) (
                        $_SESSION['department_name']
                        ?? ''
                    )
                );

            $viewData =
                $this->service
                ->preview(
                    $departmentId,
                    $departmentName,
                    $_GET['content_type']
                        ?? '',
                    $_GET['content_id']
                        ?? 0
                );

            $returnRange =
                strtolower(
                    trim(
                        (string) (
                            $_GET['range']
                            ?? '30'
                        )
                    )
                );

            if (
                !in_array(
                    $returnRange,
                    [
                        '7',
                        '30',
                        '90',
                        'all'
                    ],
                    true
                )
            ) {
                $returnRange =
                    '30';
            }

            $viewData['return_range'] =
                $returnRange;

            return $viewData;
        } catch (Throwable $exception) {
            error_log(
                'Department content preview error: '
                    . publicErrorMessage($exception)
            );

            $this->redirect(
                'index.php?page=department_analytics'
                    . '&error='
                    . urlencode(
                        publicErrorMessage($exception)
                    )
            );

            /*
             * Unreachable at runtime because
             * redirect() exits. This satisfies
             * static return-path analysis.
             */
            return [];
        }
    }
}
