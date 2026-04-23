<?php

namespace App\Modules\CMS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FileTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'disk' => $this->disk,
            'path' => $this->path,
            'url' => $this->url,
            'download_url' => $this->url,
            'type' => $this->type,
            'role' => $this->role,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'dimensions' => $this->width && $this->height ? "{$this->width} x {$this->height}" : null,
            'altText' => $this->altText,
            'position' => $this->position,
            'fileable_type' => $this->fileable_type,
            'fileable_label' => $this->fileableLabel(),
            'fileable_id' => $this->fileable_id,
            'shopify_id' => $this->shopify_id,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function fileableLabel(): ?string
    {
        if (!$this->fileable_type) {
            return null;
        }

        return class_basename($this->fileable_type);
    }
}
