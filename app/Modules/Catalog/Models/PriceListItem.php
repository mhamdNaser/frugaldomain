<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListItem extends Model
{
    protected $table = 'price_list_items';

    protected $fillable = [
        'store_id',
        'price_list_id',
        'product_variant_id',
        'shopify_variant_id',
        'amount',
        'compare_at_amount',
        'currency',
        'origin_type',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'compare_at_amount' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function priceList()
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
