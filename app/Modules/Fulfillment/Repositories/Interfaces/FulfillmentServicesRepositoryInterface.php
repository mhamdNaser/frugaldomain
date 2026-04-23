<?php

namespace App\Modules\Fulfillment\Repositories\Interfaces;

interface FulfillmentServicesRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1);
    public function find(int $id);
    public function findForFrontend(int $id);
    public function update(int $id, array $data);
}
