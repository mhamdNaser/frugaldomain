<?php

namespace App\Modules\Stores\Repositories\Interfaces;

interface StoreRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1);
    public function find($id);
    public function create($data);
    public function update($id, array $data);
    public function toggleStatus(int $id);
    public function delete($id);
}
