<?php

namespace App\Modules\Catalog\Models;

use App\Modules\CMS\Models\File;
use App\Modules\CMS\Models\Metafield;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'shopify_product_id',
        'title',
        'slug',
        'description',
        'handle',
        'vendor_id',
        'product_type_id',
        'category_id',
        'status',
        'tags',
        'image_url',
        'price_min',
        'price_max',
        'seo_title',
        'seo_description',
        'isGiftCard',
        'hasOnlyDefaultVariant',
        'featured_image',
        'raw_payload',
        'published_at',
        'shopify_created_at',
        'shopify_updated_at'
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'images' => 'array',
        'media' => 'array',
        'tags' => 'array',
        'featured_image' => 'array',
        'published_at' => 'datetime',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
        'price_min' => 'decimal:2',
        'price_max' => 'decimal:2',
    ];

    // العلاقات
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable')->orderBy('position');
    }

    public function productMedia()
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position')->orderBy('id');
    }

    public function options()
    {
        return $this->belongsToMany(
            Option::class,
            'product_options',
            'product_id',
            'option_id'
        )->withPivot('store_id', 'position')->withTimestamps();
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function metafields()
    {
        return $this->morphMany(Metafield::class, 'metafieldable');
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'collection_products');
    }
}
