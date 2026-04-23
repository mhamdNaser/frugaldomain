<?php

namespace App\Modules\Orders\Repositories\Interfaces;

interface OrdersRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1, $customerId = null);
    public function find(int $id);
    public function findForFrontend(int $id);
    public function update(int $id, array $data);
}
