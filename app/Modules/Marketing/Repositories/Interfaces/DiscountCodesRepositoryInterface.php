<?php

namespace App\Modules\Marketing\Repositories\Interfaces;

interface DiscountCodesRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1, $discountId = null);
    public function find(int $id);
    public function findForFrontend(int $id);
    public function update(int $id, array $data);
}
