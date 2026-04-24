<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_theme_id',
        'name',
        'role',
        'processing',
        'previewable',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'processing' => 'boolean',
        'previewable' => 'boolean',
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(ThemeAsset::class, 'theme_id');
    }
}

