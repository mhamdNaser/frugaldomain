<?php

namespace App\Modules\Locale\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Locale\Resources\LanguageResource;
use App\Modules\Locale\Repositories\Interfaces\LanguageRepositoryInterface;
use App\Modules\Locale\Requests\StoreLanguageRequest;
use App\Traits\PaginatesCollection;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    use PaginatesCollection;
    protected $languageRepository;

    public function __construct(LanguageRepositoryInterface $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    public function index()
    {
        $languages = $this->languageRepository->getAllLanguages();
        return LanguageResource::collection($languages);
    }

    public function active()
    {
        $languages = $this->languageRepository->getActiveLanguages();

        return $languages;
    }

    public function store(StoreLanguageRequest $request)
    {
        $validated = $request->validated();
        $language = $this->languageRepository->createLanguage($validated);

        return response()->json(['message' => 'Language created successfully'], 201);
    }


    public function addWordToAdminFile($slug, Request $request)
    {
        $result = $this->languageRepository->addWordToAdminFile($slug, $request);

        if ($result) {
            return response()->json(['message' => 'Word added successfully'], 200);
        }

        return response()->json(['message' => 'Failed to add word'], 500);
    }

    public function show(Request $request, $slug)
    {
        $languageData = $this->languageRepository->getLanguageBySlug($slug);

        if ($languageData === null) {
            return response()->json(['message' => 'Language not found'], 404);
        }

        $search = $request->input('search', null);
        $rowsPerPage = $request->input('rowsPerPage', 10);

        $items = collect($languageData);

        if (!empty($search)) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['key'], $search) !== false;
            });
        }

        $paginator = $this->paginate($items, $rowsPerPage, $request->input('page', 1));

        return response()->json([
            'data' => $paginator['data'],
            'meta' => $paginator['meta'],
            'links' => $paginator['links'],
        ], 200);
    }

    public function changestatus($id)
    {
        $language = $this->languageRepository->updateLanguageStatus($id);

        return response()->json([
            'success' => true,
            'message' => 'Language status updated successfully.',
            "data" => $language
        ], 200);
    }

    public function destroy($id)
    {
        $result = $this->languageRepository->deleteLanguage($id);

        if ($result) {
            return response()->json(['message' => 'Language deleted successfully'], 200);
        }

        return response()->json(['message' => 'Failed to delete language'], 500);
    }

    public function destroyarray(Request $request)
    {
        $ids = $request->input('ids', []);
        $result = $this->languageRepository->deleteLanguages($ids);

        if ($result) {
            return response()->json(['message' => 'Languages deleted successfully'], 200);
        }

        return response()->json(['message' => 'Failed to delete languages'], 500);
    }
}
