<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'store_id',
        'shopify_category_id',
        'name',
        'slug'
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
