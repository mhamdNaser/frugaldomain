<?php

namespace App\Modules\Locale\Repositories\Eloquent;

use App\Modules\Locale\Repositories\Interfaces\CityRepositoryInterface;
use App\Modules\Locale\Models\City;
use Illuminate\Support\Facades\DB;

class CityRepository implements CityRepositoryInterface
{
    protected $model;

    public function __construct(City $state)
    {
        $this->model = $state;
    }

    public function getAllCitiesByStateId($id)
    {
        return City::query()
            ->select('id', 'name', 'state_id')
            ->where('state_id', $id)
            ->orderBy('name')
            ->get();
    }

    public function deleteCity($id)
    {
        $city = $this->model::findOrFail($id);

        DB::beginTransaction();
        try {
            $city->delete();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function deleteCities(array $ids)
    {
        DB::beginTransaction();
        try {
            $citiesToDelete = $this->model::whereIn('id', $ids)->get();

            foreach ($citiesToDelete as $city) {
                $city->delete();
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
