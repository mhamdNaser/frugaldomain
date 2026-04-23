<?php

namespace App\Modules\Marketing\Repositories\Interfaces;

interface DiscountUsagesRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1, $discountId = null, $orderId = null);
    public function find(int $id);
    public function findForFrontend(int $id);
    public function update(int $id, array $data);
}
