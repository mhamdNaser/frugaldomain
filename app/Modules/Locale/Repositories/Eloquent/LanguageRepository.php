<?php

namespace App\Modules\Locale\Repositories\Eloquent;

use App\Modules\Locale\Models\Language;
use App\Modules\Locale\Repositories\Interfaces\LanguageRepositoryInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LanguageRepository implements LanguageRepositoryInterface
{
    protected $model;
    protected $cacheKey;

    public function __construct(Language $language)
    {
        $this->model = $language;
        $this->cacheKey = "all_languages";
    }

    public function getAllLanguages()
    {
        return Cache::remember($this->cacheKey, 86400 * 30, function () {
            return $this->model
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        });
    }

    public function getActiveLanguages()
    {
        return Cache::remember($this->cacheKey, 86400 * 30, function () {
            return $this->model::where("status", 1)->get();
        });
    }

    public function createLanguage(array $data)
    {
        Cache::forget($this->cacheKey);
        $language = $this->model::create($data);
        $this->createLanguageFiles($language->slug);
        return $language;
    }

    public function addWordToAdminFile($slug, Request $request)
    {
        try {
            $key = $request->input('key');
            $translation = $request->input('value');

            $adminFilePath = resource_path("lang/{$slug}/admin.php");

            if (!File::exists(dirname($adminFilePath))) {
                File::makeDirectory(dirname($adminFilePath), 0755, true);
            }

            $adminData = [];

            if (File::exists($adminFilePath)) {
                $adminData = include $adminFilePath;
                if (!is_array($adminData)) {
                    $adminData = [];
                }
            }

            $adminData[$key] = $translation;
            $phpCode = "<?php\n\nreturn " . var_export($adminData, true) . ";\n";
            File::put($adminFilePath, $phpCode);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLanguageBySlug($slug)
    {
        $languageDir = resource_path("lang/{$slug}");

        if (!File::exists($languageDir)) {
            return null;
        }

        $combinedData = [];
        $adminFilePath = "{$languageDir}/admin.php";
        if (File::exists($adminFilePath)) {
            $adminData = include $adminFilePath;
            if (is_array($adminData)) {
                foreach ($adminData as $key => $value) {
                    $combinedData[] = ['key' => $key, 'value' => $value];
                }
            }
        }

        return $combinedData;
    }

    public function updateLanguageStatus($id)
    {
        Cache::forget($this->cacheKey);
        $language = $this->model::findOrFail($id);
        $language->update([
            'status' => $language->status == 1 ? 0 : 1,
        ]);

        return $language;
    }

    public function deleteLanguage($id)
    {
        Cache::forget($this->cacheKey);
        $language = $this->model::findOrFail($id);
        $language->delete();

        $languageDir = resource_path("lang/{$language->slug}");
        if (File::exists($languageDir)) {
            File::deleteDirectory($languageDir);
        }

        return true;
    }

    public function deleteLanguages(array $ids)
    {
        Cache::forget($this->cacheKey);
        $languages = $this->model::whereIn('id', $ids)->get();
        foreach ($languages as $language) {
            $slug = $language->slug;

            $language->delete();

            $languageDir = resource_path("lang/{$slug}");
            if (File::exists($languageDir)) {
                File::deleteDirectory($languageDir);
            }
        }

        return true;
    }

    protected function createLanguageFiles($slug)
    {
        $languageDir = resource_path("lang/{$slug}");

        if (!File::exists($languageDir)) {
            File::makeDirectory($languageDir, 0755, true);
        }

        $adminContent = "<?php\n\nreturn [\n    // مصفوفة للإدارة\n];\n";
        File::put("{$languageDir}/admin.php", $adminContent);
    }
}
