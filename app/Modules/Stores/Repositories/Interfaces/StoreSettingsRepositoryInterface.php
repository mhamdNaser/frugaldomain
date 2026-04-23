<?php

namespace App\Modules\Stores\Repositories\Interfaces;

interface StoreSettingsRepositoryInterface
{
    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, ?string $storeId = null);
}

