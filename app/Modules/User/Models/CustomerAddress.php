<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'store_id',
        'customer_id',
        'shopify_customer_address_id',
        'first_name',
        'last_name',
        'name',
        'company',
        'address1',
        'address2',
        'city',
        'province',
        'province_code',
        'country',
        'country_code',
        'zip',
        'phone',
        'is_default',
        'raw_payload',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'raw_payload' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
