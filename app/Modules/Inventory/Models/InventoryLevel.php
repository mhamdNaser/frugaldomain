<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryLevel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'product_variant_id',
        'inventory_item_id',
        'shopify_location_id',
        'available',
        'shopify_updated_at',
        'raw_payload',
    ];

    protected $casts = [
        'available' => 'integer',
        'shopify_updated_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'shopify_location_id', 'shopify_location_id');
    }
}
