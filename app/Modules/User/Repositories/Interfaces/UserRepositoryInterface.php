<?php

namespace App\Modules\User\Repositories\Interfaces;

interface UserRepositoryInterface
{
    public function getAllUsers($search = null, $rowsPerPage = 10, $page = 1);
    public function all();
    public function find($id);
    public function create($data);
    public function update($id, array $data);
    public function toggleStatus(int $id);
    public function delete($id);
    public function deleteArray(array $ids);
}
