<?php

namespace App\Modules\Icon\Repositories\Interfaces;

interface IconCategoryRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1);
    public function allWithoutPagination();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function deleteArray(array $ids);
    public function changeStatus($id);
}
