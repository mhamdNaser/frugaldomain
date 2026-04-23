<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'slug',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    // علاقة polymorphic مع المنتجات والكولكشن والفيندور
    public function products()
    {
        return $this->morphedByMany(Product::class, 'taggable');
    }

    public function collections()
    {
        return $this->morphedByMany(Collection::class, 'taggable');
    }

    public function vendors()
    {
        return $this->morphedByMany(Vendor::class, 'taggable');
    }
}
