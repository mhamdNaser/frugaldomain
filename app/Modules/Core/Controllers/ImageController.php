<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Encoders\GifEncoder;

class ImageController extends Controller
{
    public function convert(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
            'format' => 'required|in:jpg,jpeg,png,webp,gif'
        ]);

        $imageFile = $request->file('image');
        $format = strtolower($request->input('format'));

        // اختيار الـ Driver تلقائيًا
        if (extension_loaded('imagick')) {
            $driver = new ImagickDriver();
        } elseif (extension_loaded('gd')) {
            $driver = new GdDriver();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No suitable image driver available on the server.'
            ], 500);
        }

        $manager = new ImageManager($driver);

        try {
            // قراءة الصورة
            $image = $manager->read($imageFile->getPathname());

            // تنظيف الاسم من الرموز والمسافات
            $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName);

            // إنشاء اسم الملف
            $fileName = $safeName . '_' . uniqid() . '.' . $format;

            // تحديد مسار الحفظ في public مباشرة
            $directory = public_path('images/converted');
            if (!file_exists($directory)) {
                mkdir($directory, 0775, true);
            }

            $savePath = $directory . '/' . $fileName;

            // تحويل الصورة للصيغة المطلوبة
            $encodedImage = match ($format) {
                'jpg', 'jpeg' => $image->encode(new JpegEncoder(quality: 90)),
                'png' => $image->encode(new PngEncoder(interlaced: false)), // لا يوجد quality
                'webp' => $image->encode(new WebpEncoder(quality: 90)),
                'gif' => $image->encode(new GifEncoder()),
                default => throw new \Exception('Unsupported format'),
            };

            // حفظ الصورة
            $encodedImage->save($savePath);

            // إنشاء URL عام
            $publicUrl = 'images/converted/' . rawurlencode($fileName);

            return response()->json([
                'success' => true,
                'url' => $publicUrl,
                'fileName' => $fileName
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing image: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function download($fileName)
    {
        $path = 'images/converted/' . $fileName;

        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.'
            ], 404);
        }

        return response()->download($path, $fileName, [
            'Content-Type' => mime_content_type($path),
        ]);
    }
}
