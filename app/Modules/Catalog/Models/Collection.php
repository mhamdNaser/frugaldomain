<?php

namespace App\Modules\Catalog\Models;

use App\Modules\CMS\Models\Metafield;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'shopify_collection_id',
        'title',
        'handle',
        'description',
        'image_url',
        'image_alt',
        'type',
        'is_active',
        'sort_order',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_products');
    }

    public function metafields()
    {
        return $this->morphMany(Metafield::class, 'metafieldable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
