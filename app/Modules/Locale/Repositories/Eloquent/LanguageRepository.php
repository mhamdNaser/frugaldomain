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
        return Cache::remember($this->cacheKey . '_active', 86400 * 30, function () {
            return $this->model::where("status", 1)->get();
        });
    }

    public function createLanguage(array $data)
    {
        $this->forgetLanguageLists();
        $language = $this->model::create($data);
        $this->createLanguageFiles($language->slug);
        return $language;
    }

    /**
     * Adds or updates one word. A key that already lives in site.php (public
     * website texts) is updated there; everything else goes to admin.php.
     */
    public function addWordToAdminFile($slug, Request $request)
    {
        try {
            $key = trim((string) $request->input('key'));
            $translation = (string) $request->input('value');

            if ($key === '' || ! $this->isValidSlug($slug)) {
                return false;
            }

            $languageDir = resource_path("lang/{$slug}");
            if (!File::exists($languageDir)) {
                File::makeDirectory($languageDir, 0755, true);
            }

            $siteData = $this->readLanguageFile("{$languageDir}/site.php");
            $file = array_key_exists($key, $siteData) ? 'site' : 'admin';
            $filePath = "{$languageDir}/{$file}.php";

            $data = $file === 'site' ? $siteData : $this->readLanguageFile($filePath);
            $data[$key] = $translation;

            $phpCode = "<?php\n\nreturn " . var_export($data, true) . ";\n";
            File::put($filePath, $phpCode);

            // Opcache would otherwise keep serving the old array from `include`.
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($filePath, true);
            }

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

        // Both files are editable from the dashboard: site.php holds the public
        // website texts, admin.php the dashboard texts.
        $combinedData = [];
        foreach (['site', 'admin'] as $file) {
            foreach ($this->readLanguageFile("{$languageDir}/{$file}.php") as $key => $value) {
                if (is_string($value)) {
                    $combinedData[] = ['key' => $key, 'value' => $value, 'file' => $file];
                }
            }
        }

        return $combinedData;
    }

    public function updateLanguageStatus($id)
    {
        $this->forgetLanguageLists();
        $language = $this->model::findOrFail($id);
        $language->update([
            'status' => $language->status == 1 ? 0 : 1,
        ]);

        return $language;
    }

    public function deleteLanguage($id)
    {
        $this->forgetLanguageLists();
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
        $this->forgetLanguageLists();
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

    protected function forgetLanguageLists(): void
    {
        Cache::forget($this->cacheKey);
        Cache::forget($this->cacheKey . '_active');
    }

    protected function isValidSlug($slug): bool
    {
        return (bool) preg_match('/^[A-Za-z]{2,3}([_-][A-Za-z]{2,4})?$/', (string) $slug);
    }

    protected function readLanguageFile(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        $data = include $path;

        return is_array($data) ? $data : [];
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
