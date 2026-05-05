<?php

namespace App\Modules\Inventory\Repositories\Interfaces;

interface InventoriesRepositoryInterface
{
    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1);
    public function find(int $id);
    public function findForFrontend(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): void;
}

