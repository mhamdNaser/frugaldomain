<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class DraftOrderItem extends Model
{
    protected $fillable = [
        'store_id',
        'draft_order_id',
        'variant_id',
        'shopify_line_item_id',
        'shopify_product_id',
        'shopify_variant_id',
        'product_title',
        'variant_title',
        'sku',
        'quantity',
        'unit_price',
        'total_price',
        'raw_payload',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function draftOrder()
    {
        return $this->belongsTo(DraftOrder::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
