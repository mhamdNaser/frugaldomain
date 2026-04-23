<?php

namespace App\Modules\Icon\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Icon\Models\IconDownloads;
use App\Modules\Icon\Models\IconFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class IconDownloadCopyController extends Controller
{
    public function download($fileName)
    {

        $path = public_path('icons/' . $fileName);

        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        // إرسال الملف للتحميل
        return response()->download($path, $fileName, [
            'Content-Type' => mime_content_type($path),
        ]);
    }

    public function downloadCount(Request $request, $fileName)
    {
        $iconFile = IconFiles::with('icon')
            ->where('file_name', $fileName)
            ->first();

        if (!$iconFile) {
            return response()->json([
                'success' => false,
                'message' => 'Icon file not found.',
                'file_name' => $fileName,
            ], 404);
        }

        if (!$iconFile->icon) {
            return response()->json([
                'success' => false,
                'message' => 'Related icon not found.',
                'file_name' => $fileName,
            ], 404);
        }

        IconDownloads::create([
            'user_id'       => auth()->id(),
            'icon_id'       => $iconFile->icon->id,
            'icon_file_id'  => $iconFile->id,
            'download_type' => $iconFile->file_type,
            'ip_address'    => $request->ip(),
            'downloaded_at' => now(),
        ]);

        if (Schema::hasColumn('icons', 'download_count')) {
            $iconFile->icon->increment('download_count');
            $iconFile->icon->refresh();
        }

        return response()->json([
            'success' => true,
            'message' => 'Download counted successfully.',
            'download_count' => $iconFile->icon->download_count ?? null,
            'icon_id' => $iconFile->icon->id,
            'icon_file_id' => $iconFile->id,
            'file_name' => $iconFile->file_name,
            'file_type' => $iconFile->file_type,
        ]);
    }

    public function getIconCode($fileName)
    {
        $path = public_path('icons/' . $fileName);

        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.'
            ], 404);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === 'svg') {
            $content = file_get_contents($path);
            return response()->json([
                'success' => true,
                'type' => 'svg',
                'code' => $content,
            ]);
        } else {
            $data = base64_encode(file_get_contents($path));
            $mime = mime_content_type($path);
            $base64String = "data:$mime;base64,$data";

            return response()->json([
                'success' => true,
                'type' => $extension,
                'code' => $base64String,
            ]);
        }
    }

    public function getIconCodeJsx($fileName)
    {
        $path = public_path('icons/' . $fileName);

        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.'
            ], 404);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === 'svg') {
            $content = file_get_contents($path);

            // تحويل SVG إلى JSX
            $jsxContent = $this->convertSvgToJsx($content);

            return response()->json([
                'success' => true,
                'type' => 'jsx',
                'code' => $jsxContent,
            ]);
        } else {
            // للصور الأخرى (PNG، JPG...) نرسل Base64
            $data = base64_encode(file_get_contents($path));
            $mime = mime_content_type($path);
            $base64String = "data:$mime;base64,$data";

            return response()->json([
                'success' => true,
                'type' => $extension,
                'code' => $base64String,
            ]);
        }
    }


    private function convertSvgToJsx(string $svgContent, array $customMap = []): string
    {
        $defaultMap = [
            'stroke-width' => 'strokeWidth',
            'stroke-linecap' => 'strokeLinecap',
            'stroke-linejoin' => 'strokeLinejoin',
            'class' => 'className',
            'fill-rule' => 'fillRule',
            'clip-rule' => 'clipRule',
        ];

        $map = array_merge($defaultMap, $customMap);

        foreach ($map as $svgAttr => $jsxAttr) {
            $svgContent = preg_replace_callback(
                "/\b$svgAttr\s*=\s*(['\"])(.*?)\\1/",
                function ($matches) use ($jsxAttr) {
                    $quote = $matches[1];
                    $value = $matches[2];

                    if (is_numeric($value)) {
                        return $jsxAttr . "={" . $value . "}";
                    }

                    return $jsxAttr . "=" . $quote . $value . $quote;
                },
                $svgContent
            );
        }

        return $svgContent;
    }
}
