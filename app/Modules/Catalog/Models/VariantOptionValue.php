<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class VariantOptionValue extends Model
{
    protected $table = 'variant_option_values';

    protected $fillable = [
        'product_variant_id',
        'option_value_id',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function optionValue()
    {
        return $this->belongsTo(OptionValue::class, 'option_value_id');
    }
}
