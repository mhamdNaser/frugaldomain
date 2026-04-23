<?php

namespace App\Modules\Billing\Repositories\Interfaces;

interface PlansRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1);
    public function find(int $id);
    public function findForFrontend(int $id);
    public function update(int $id, array $data);
    public function toggleStatus(int $id);
}
