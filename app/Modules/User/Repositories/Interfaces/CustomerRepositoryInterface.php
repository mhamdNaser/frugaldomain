<?php

namespace App\Modules\User\Repositories\Interfaces;

interface CustomerRepositoryInterface
{
    public function getAllByStore(string $storeId, ?string $search = null, int $rowsPerPage = 10);

    public function findForStoreWithDetails(string $storeId, int $id);
    public function createForStore(string $storeId, array $data);

    public function updateForStore(string $storeId, int $id, array $data);
    public function deleteForStore(string $storeId, int $id): void;
}
