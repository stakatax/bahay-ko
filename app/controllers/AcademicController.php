<?php

require_once __DIR__
    . '/BaseController.php';

require_once __DIR__
    . '/../services/AcademicCatalogService.php';

class AcademicController extends BaseController
{
    private AcademicCatalogService $service;

    public function __construct()
    {
        $this->service =
            new AcademicCatalogService();
    }

    public function index(): array
    {
        try {
            return [
                'academic_catalog' =>
                $this->service
                    ->getCatalog(),

                'academic_catalog_error' =>
                false
            ];
        } catch (Throwable $exception) {
            /*
             * Public institutional pages must remain
             * reachable if the catalog lookup fails.
             */
            error_log(
                'Public academic catalog error: '
                    . publicErrorMessage($exception)
            );

            return [
                'academic_catalog' => [
                    'education_levels' =>
                    [],

                    'academic_programs' =>
                    [],

                    'grade_levels' =>
                    []
                ],

                'academic_catalog_error' =>
                true
            ];
        }
    }
}
