<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerMarketingConsent extends Model
{
    protected $fillable = [
        'store_id',
        'customer_id',
        'shopify_customer_id',
        'email_marketing_state',
        'email_marketing_opt_in_level',
        'email_consent_updated_at',
        'sms_marketing_state',
        'sms_marketing_opt_in_level',
        'sms_consent_updated_at',
        'source_location_id',
        'raw_payload',
    ];

    protected $casts = [
        'email_consent_updated_at' => 'datetime',
        'sms_consent_updated_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}

