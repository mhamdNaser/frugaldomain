<?php

namespace App\Modules\Marketing\Repositories\Interfaces;

interface MarketsRepositoryInterface
{
    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1);
}

