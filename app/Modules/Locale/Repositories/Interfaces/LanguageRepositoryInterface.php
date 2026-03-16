<?php

namespace App\Modules\Locale\Repositories\Interfaces;

use Illuminate\Http\Request;

interface LanguageRepositoryInterface
{
    public function getAllLanguages();
    public function getActiveLanguages();
    public function createLanguage(array $data);
    public function addWordToAdminFile($slug, Request $request);
    public function getLanguageBySlug($slug);
    public function updateLanguageStatus($id);
    public function deleteLanguage($id);
    public function deleteLanguages(array $ids);

}
