<?php

namespace App\Modules\Catalog\Repositories\Interfaces;

interface ProductsRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1);
    public function find(int $id);
    public function toggleStatus(int $id);

}
