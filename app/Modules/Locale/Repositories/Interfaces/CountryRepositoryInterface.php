<?php

namespace App\Modules\Locale\Repositories\Interfaces;

use App\Modules\Locale\Requests\StoreLocationRequest;

interface CountryRepositoryInterface
{
    public function all($search = null, $rowsPerPage = 10, $page = 1);
    public function storeCountry(StoreLocationRequest $request);
    public function deleteCountry(string $id);
    public function deleteCountries(array $ids);
}
