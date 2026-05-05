<?php

namespace App\Modules\CMS\Repositories\Interfaces;

interface BlogsRepositoryInterface
{
    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1, array $filters = []);
    public function findForFrontend(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): void;
}
