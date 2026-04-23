<?php

namespace App\Modules\Billing\Repositories\Interfaces;

interface PaymentTransactionsRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1, $orderId = null);
    public function find(int $id);
    public function findForFrontend(int $id);
    public function update(int $id, array $data);
}
