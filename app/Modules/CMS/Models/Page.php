<?php

namespace App\Modules\CMS\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_page_id',
        'handle',
        'title',
        'seo_title',
        'seo_description',
        'template_suffix',
        'is_published',
        'published_at',
        'author',
        'body',
        'raw_payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];
}
