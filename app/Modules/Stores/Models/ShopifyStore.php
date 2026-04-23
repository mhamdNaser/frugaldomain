<?php

namespace App\Modules\Stores\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopifyStore extends Model
{
    // اسم الجدول إذا كان غير الافتراضي
    protected $table = 'shopify_stores';

    // الحقول القابلة للتعبئة
    protected $fillable = [
        'store_id',
        'shopify_store_id',
        'name',
        'email',
        'domain',
        'myshopify_domain',
        'shop_owner',
        'phone',
        'country',
        'country_code',
        'currency',
        'timezone',
        'iana_timezone',
        'plan_name',
        'plan_display_name',
        'taxes_included',
        'county_taxes',
        'has_discounts',
        'has_gift_cards',
        'multi_location_enabled',
        'primary_location_id',
        'raw_data',
    ];

    // تحويل الحقول boolean تلقائيًا
    protected $casts = [
        'taxes_included' => 'boolean',
        'county_taxes' => 'boolean',
        'has_discounts' => 'boolean',
        'has_gift_cards' => 'boolean',
        'multi_location_enabled' => 'boolean',
        'raw_data' => 'array', // JSON يتحول لمصفوفة تلقائيًا
    ];

    // علاقة مع الجدول الرئيسي Store
    public function store(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Stores\Models\Store::class);
    }
}
