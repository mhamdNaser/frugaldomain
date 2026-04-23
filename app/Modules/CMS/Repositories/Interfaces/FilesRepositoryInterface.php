<?php

namespace App\Modules\CMS\Repositories\Interfaces;

interface FilesRepositoryInterface
{
    public function all(?string $search = null, int $rowsPerPage = 15, int $page = 1, array $filters = []);

    public function facets(): array;
}
