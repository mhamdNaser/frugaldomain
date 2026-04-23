<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    protected $table = 'price_lists';

    protected $fillable = [
        'store_id',
        'market_id',
        'shopify_price_list_id',
        'shopify_catalog_id',
        'name',
        'currency',
        'fixed_prices_count',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(PriceListItem::class, 'price_list_id');
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'price_list_items',
            'price_list_id',
            'product_variant_id'
        )->withPivot([
            'id',
            'store_id',
            'shopify_variant_id',
            'amount',
            'compare_at_amount',
            'currency',
            'origin_type',
            'raw_payload',
        ])->withTimestamps();
    }
}
