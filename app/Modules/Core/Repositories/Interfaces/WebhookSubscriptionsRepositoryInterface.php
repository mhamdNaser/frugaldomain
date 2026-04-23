<?php

namespace App\Modules\Core\Repositories\Interfaces;

interface WebhookSubscriptionsRepositoryInterface
{
    public function all(?string $search = null, int $rowsPerPage = 10, int $page = 1);
}

