<?php

namespace App\Modules\Stores\Models;

use App\Modules\CMS\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreBranding extends Model
{
    protected $fillable = [
        'store_id',
        'logo_url',
        'splash_image_url',
        'favicon_url',
        'primary_color',
        'secondary_color',
        'dark_primary_color',
        'dark_secondary_color',
        'font_family',
        'extra_styles',
    ];

    protected $casts = [
        'extra_styles' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    // Files relation (use the media table)
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
