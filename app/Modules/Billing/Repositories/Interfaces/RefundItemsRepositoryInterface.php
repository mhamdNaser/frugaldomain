<?php

namespace App\Modules\Billing\Repositories\Interfaces;

interface RefundItemsRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1, $refundId = null);
    public function find(int $id);
    public function findForFrontend(int $id);
    public function update(int $id, array $data);
}
