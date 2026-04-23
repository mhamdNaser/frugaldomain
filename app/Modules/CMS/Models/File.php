<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class File extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'disk',
        'path',
        'url',
        'mime_type',
        'width',
        'height',
        'altText',
        'type',
        'role',
        'position',
        'fileable_type',
        'fileable_id',
        'meta',
        'shopify_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }


    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }


    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'video/');
    }
}
