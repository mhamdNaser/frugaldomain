<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IconStatisticsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'status' => 'success',
            'message' => 'Icon statistics retrieved successfully',
            'data' => [
                'summary' => [
                    'total_icons' => $this['summary']['total_icons'],
                    'total_files' => $this['summary']['total_files'],
                    'total_downloads' => $this['summary']['total_downloads'],
                    'icons_with_downloads' => $this['summary']['icons_with_downloads'],
                    'icons_without_downloads' => $this['summary']['icons_without_downloads'],
                ],

                'files' => [
                    'svg_files' => $this['files']['svg_files'],
                    'png_files' => $this['files']['png_files'],
                ],

                'downloads' => [
                    'svg_downloads' => $this['downloads']['svg_downloads'],
                    'png_downloads' => $this['downloads']['png_downloads'],
                ],

                'percentages' => [
                    'icons_with_downloads_percentage' => $this['percentages']['icons_with_downloads_percentage'],
                    'svg_files_percentage' => $this['percentages']['svg_files_percentage'],
                    'png_files_percentage' => $this['percentages']['png_files_percentage'],
                    'svg_downloads_percentage' => $this['percentages']['svg_downloads_percentage'],
                    'png_downloads_percentage' => $this['percentages']['png_downloads_percentage'],
                ],

                'charts' => [
                    'downloads_last_7_days' => $this['charts']['downloads_last_7_days'],
                    'downloads_by_type' => $this['charts']['downloads_by_type'],
                    'file_distribution' => $this['charts']['file_distribution'],
                ],

                'top' => [
                    'most_downloaded_icons' => $this['top']['most_downloaded_icons'],
                    'most_downloaded_files' => $this['top']['most_downloaded_files'],
                ],
            ],
        ];
    }
}
