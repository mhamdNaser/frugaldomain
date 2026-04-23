<?php

namespace App\Modules\Core\Services;

use App\Modules\Icon\Models\Icon;
use App\Modules\Icon\Models\IconDownloads;
use App\Modules\Icon\Models\IconFiles;
use Illuminate\Support\Facades\DB;

class IconStatisticsService
{
    public function getStatistics(): array
    {
        $totalIcons = Icon::count();
        $totalFiles = IconFiles::count();
        $totalDownloads = IconDownloads::count();

        $svgFiles = IconFiles::where('file_type', 'svg')->count();
        $pngFiles = IconFiles::where('file_type', 'png')->count();

        $svgDownloads = IconDownloads::where('download_type', 'svg')->count();
        $pngDownloads = IconDownloads::where('download_type', 'png')->count();

        $iconsWithDownloads = Icon::whereHas('downloads')->count();
        $iconsWithoutDownloads = $totalIcons - $iconsWithDownloads;

        $mostDownloadedIcons = Icon::withCount('downloads')
            ->orderByDesc('downloads_count')
            ->take(3)
            ->get();

        $mostDownloadedFiles = IconFiles::withCount('downloads')
            ->with('icon')
            ->orderByDesc('downloads_count')
            ->take(10)
            ->get();

        $downloadsLast7Days = IconDownloads::select(
                DB::raw('DATE(downloaded_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->whereDate('downloaded_at', '>=', now()->subDays(6)->toDateString())
            ->groupBy(DB::raw('DATE(downloaded_at)'))
            ->orderBy('date')
            ->get();

        $downloadsByType = [
            'svg' => $svgDownloads,
            'png' => $pngDownloads,
        ];

        $fileDistribution = [
            'svg' => $svgFiles,
            'png' => $pngFiles,
        ];

        return [
            'summary' => [
                'total_icons' => $totalIcons,
                'total_files' => $totalFiles,
                'total_downloads' => $totalDownloads,
                'icons_with_downloads' => $iconsWithDownloads,
                'icons_without_downloads' => $iconsWithoutDownloads,
            ],

            'files' => [
                'svg_files' => $svgFiles,
                'png_files' => $pngFiles,
            ],

            'downloads' => [
                'svg_downloads' => $svgDownloads,
                'png_downloads' => $pngDownloads,
            ],

            'percentages' => [
                'icons_with_downloads_percentage' => $totalIcons > 0
                    ? round(($iconsWithDownloads / $totalIcons) * 100, 2)
                    : 0,

                'svg_files_percentage' => $totalFiles > 0
                    ? round(($svgFiles / $totalFiles) * 100, 2)
                    : 0,

                'png_files_percentage' => $totalFiles > 0
                    ? round(($pngFiles / $totalFiles) * 100, 2)
                    : 0,

                'svg_downloads_percentage' => $totalDownloads > 0
                    ? round(($svgDownloads / $totalDownloads) * 100, 2)
                    : 0,

                'png_downloads_percentage' => $totalDownloads > 0
                    ? round(($pngDownloads / $totalDownloads) * 100, 2)
                    : 0,
            ],

            'charts' => [
                'downloads_last_7_days' => $downloadsLast7Days,
                'downloads_by_type' => $downloadsByType,
                'file_distribution' => $fileDistribution,
            ],

            'top' => [
                'most_downloaded_icons' => $mostDownloadedIcons,
                'most_downloaded_files' => $mostDownloadedFiles,
            ],
        ];
    }
}
