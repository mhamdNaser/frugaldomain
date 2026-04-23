<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class OptionValue extends Model
{
    protected $table = 'option_values';

    protected $fillable = [
        'option_id',
        'label',
        'value',
    ];

    public function option()
    {
        return $this->belongsTo(Option::class);
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'variant_option_values',
            'option_value_id',
            'product_variant_id'
        )->withTimestamps();
    }
}
