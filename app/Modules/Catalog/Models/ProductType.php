<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_product_type_id',
        'name',
        'slug',
        'description',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
