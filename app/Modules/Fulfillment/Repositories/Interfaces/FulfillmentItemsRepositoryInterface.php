<?php

namespace App\Modules\Fulfillment\Repositories\Interfaces;

interface FulfillmentItemsRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1, $fulfillmentId = null, $orderItemId = null);
    public function find(int $id);
    public function findForFrontend(int $id);
    public function update(int $id, array $data);
}
