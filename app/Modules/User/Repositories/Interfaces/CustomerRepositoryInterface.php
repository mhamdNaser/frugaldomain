<?php

namespace App\Modules\User\Repositories\Interfaces;

interface CustomerRepositoryInterface
{
    public function getAllByStore(string $storeId, ?string $search = null, int $rowsPerPage = 10);

    public function findForStoreWithDetails(string $storeId, int $id);
}

