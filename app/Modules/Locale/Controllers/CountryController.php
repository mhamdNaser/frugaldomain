<?php

namespace App\Modules\Locale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Locale\Repositories\Interfaces\CountryRepositoryInterface;
use App\Modules\Locale\Requests\StoreLocationRequest;
use App\Modules\Locale\Resources\CountriesResource;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CountryController extends Controller
{
    protected $repos;

    public function __construct(CountryRepositoryInterface $countryRepository)
    {
        $this->repos = $countryRepository;
    }

    public function index()
    {
        return $this->repos->all();
    }

    public function allCountry(Request $request)
    {
        $search = $request->get('search');
        $rowsPerPage = $request->get('rowsPerPage', 10);
        $page = $request->get('page', 1);

        $result = $this->repos->all($search, $rowsPerPage, $page);

        return response()->json([
            'data' => CountriesResource::collection($result['data']),
            'meta' => $result['meta'],
            'links' => $result['links'],
        ]);
    }

    public function store(StoreLocationRequest $request)
    {
        return $this->repos->storeCountry($request);
    }

    public function destroy($id)
    {
        return $this->repos->deleteCountry($id);
    }

    public function destroyarray(Request $request)
    {
        $validatedData = $request->validate([
            'array' => 'required|array',
        ]);

        return $this->repos->deleteCountries($validatedData['array']);
    }
}
