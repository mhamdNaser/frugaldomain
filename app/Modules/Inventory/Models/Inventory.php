<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'store_id',
        'product_variant_id',
        'shopify_inventory_item_id',
        'location_id',
        'available_quantity',
        'tracked',
        'requires_shipping',
        'weight',
        'weight_unit'
    ];

    protected $casts = [
        'available_quantity' => 'integer',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
