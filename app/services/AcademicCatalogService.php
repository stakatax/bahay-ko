<?php

require_once __DIR__
    . '/../models/AcademicStructure.php';

class AcademicCatalogService
{
    private AcademicStructure $academicStructure;

    public function __construct()
    {
        $this->academicStructure =
            new AcademicStructure();
    }

    public function getCatalog(): array
    {
        return $this->academicStructure
            ->getPublicCatalog();
    }
}
