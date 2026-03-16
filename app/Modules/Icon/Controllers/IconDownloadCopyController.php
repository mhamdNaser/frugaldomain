<?php

namespace App\Modules\Icon\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Icon\Models\Icon;

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

    public function downloadCount($fileName)
    {
        $path = 'icons/' . $fileName;
        $icon = Icon::where('file_svg', $path)
            ->orWhere('file_png', $path)
            ->first();

        if (!$icon) {
            return response()->json([
                'success' => false,
                'message' => 'Icon not found.'
            ], 404);
        }

        $icon->download_count += 1;
        $icon->save();

        return response()->json([
            'success' => true,
            'download_count' => $icon->download_count,
            'file_path' => $path,
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
