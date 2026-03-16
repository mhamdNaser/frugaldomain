<?php

namespace App\Modules\Locale\Repositories\Interfaces;


interface CityRepositoryInterface
{
    public function getAllCitiesByStateId($id);
    public function deleteCity($id);
    public function deleteCities(array $ids);
}
