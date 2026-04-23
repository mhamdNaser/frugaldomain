<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'shopify_line_item_id',
        'variant_id',
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
