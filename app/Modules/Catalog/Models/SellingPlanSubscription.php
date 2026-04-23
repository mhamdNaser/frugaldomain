<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class SellingPlanSubscription extends Model
{
    protected $fillable = [
        'store_id',
        'customer_id',
        'shopify_subscription_contract_id',
        'shopify_customer_id',
        'status',
        'currency',
        'next_billing_amount',
        'next_billing_date',
        'raw_payload',
    ];

    protected $casts = [
        'next_billing_amount' => 'decimal:2',
        'next_billing_date' => 'datetime',
        'raw_payload' => 'array',
    ];
}

