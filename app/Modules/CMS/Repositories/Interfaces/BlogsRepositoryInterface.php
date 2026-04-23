<?php

namespace App\Modules\CMS\Repositories\Interfaces;

interface BlogsRepositoryInterface
{
    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = []);
}
