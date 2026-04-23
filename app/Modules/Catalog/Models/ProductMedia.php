<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMedia extends Model
{
    protected $table = 'product_media';

    protected $fillable = [
        'store_id',
        'product_id',
        'shopify_product_id',
        'shopify_media_id',
        'media_content_type',
        'status',
        'position',
        'alt',
        'url',
        'preview_url',
        'mime_type',
        'width',
        'height',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];
}

