<?php

namespace App\Modules\Locale\Repositories\Interfaces;

interface StateRepositoryInterface
{
    public function getByCountryId($countryId);
    public function find($id);
    public function delete($id);
    public function deleteMany(array $ids);
}
