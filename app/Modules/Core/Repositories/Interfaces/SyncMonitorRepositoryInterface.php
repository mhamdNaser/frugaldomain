<?php

namespace App\Modules\Core\Repositories\Interfaces;

interface SyncMonitorRepositoryInterface
{
    public function all(string $type, ?string $storeId = null, ?string $search = null, int $rowsPerPage = 10, int $page = 1);
    public function allowedTypes(): array;
}