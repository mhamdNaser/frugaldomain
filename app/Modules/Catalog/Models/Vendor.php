<?php

namespace App\Modules\Catalog\Models;

use App\Modules\CMS\Models\Metafield;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table = 'vendors';

    protected $fillable = [
        'store_id',
        'shopify_vendor_id',
        'name',
        'slug',
        'description',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
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
