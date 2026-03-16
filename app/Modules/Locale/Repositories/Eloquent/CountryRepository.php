<?php

namespace App\Modules\Locale\Repositories\Eloquent;

use App\Modules\Locale\Repositories\Interfaces\CountryRepositoryInterface;
use App\Modules\Locale\Requests\StoreLocationRequest;
use App\Modules\Locale\Models\Country;
use App\Traits\PaginatesCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CountryRepository implements CountryRepositoryInterface
{
    use PaginatesCollection;
    protected $model;
    protected $cacheKey;

    public function __construct(Country $country)
    {
        $this->model = $country;
        $this->cacheKey = "all_countries";
    }

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $items = Cache::remember($this->cacheKey, 60, function () {
            return $this->model::orderBy('id', 'desc')
                ->get();
        });

        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item->name, $search) !== false;
            });
        }

        return $this->paginate($items, $rowsPerPage, $page);
        return $this->model
        ->select('id', 'name')
        ->orderBy('name')
        ->get();
    }

    public function storeCountry(StoreLocationRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();

        try {
            $country = new Country();
            $country->name = $validatedData['name'];
            $country->status = 1;
            $country->save();

            DB::commit();

            return response()->json([
                'message' => 'Resource created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create resource',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCountry(string $id)
    {
        DB::beginTransaction();

        try {
            $country = Country::findOrFail($id);
            $country->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Country deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete country.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCountries(array $ids)
    {
        DB::beginTransaction();

        try {
            $countries = $this->model::whereIn('id', $ids)->get();

            foreach ($countries as $country) {
                $country->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Countries deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete countries.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
