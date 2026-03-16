<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;

trait ManageFiles
{
    /**
     * Upload file content to public directory with safe and unique name.
     *
     * @param  string  $fileContent  محتوى الملف (ناتج encode)
     * @param  string  $directory    مجلد التخزين داخل public (مثل: images/converted)
     * @param  string  $fileName     الاسم الأساسي للملف بدون امتداد
     * @param  string  $extension    الامتداد المطلوب (jpg, png, webp...)
     * @return string  المسار الكامل داخل public
     */
    public function uploadFile($fileContent, $directory, $fileName, $extension)
    {
        $destination = public_path($directory);
        if (!\Illuminate\Support\Facades\File::exists($destination)) {
            \Illuminate\Support\Facades\File::makeDirectory($destination, 0755, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
        $uniqueName = $safeName . '_' . uniqid() . '.' . $extension;

        $fullPath = $destination . '/' . $uniqueName;
        \Illuminate\Support\Facades\File::put($fullPath, $fileContent);

        return $directory . '/' . $uniqueName;
    }

    public function uploadImage($file, $directory, $fileName, $extension = null)
    {
        $destination = public_path($directory);
        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));

        // إذا كان الملف UploadedFile
        if ($file instanceof UploadedFile) {
            $ext = $extension ?: $file->getClientOriginalExtension();
            $uniqueName = $safeName . '_' . uniqid() . '.' . $ext;

            // الطريقة الصحيحة
            $file->move($destination, $uniqueName);

            return $directory . '/' . $uniqueName;
        }

        // إذا كان محتوى خام (string/bytes) لسبب ما
        $ext = $extension ?: 'bin';
        $uniqueName = $safeName . '_' . uniqid() . '.' . $ext;
        File::put($destination . '/' . $uniqueName, $file);

        return $directory . '/' . $uniqueName;
    }

    /**
     * Delete a file from public folder
     */
    public function deleteFile($filePath)
    {
        $path = public_path($filePath);
        if (File::exists($path)) {
            return File::delete($path);
        }
        return false;
    }
}
