<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{

    protected $table = 'options';

    protected $fillable = [
        'store_id',
        'name',
    ];

    public function values()
    {
        return $this->hasMany(OptionValue::class);
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_options',
            'option_id',
            'product_id'
        )->withTimestamps()->withPivot('position');
    }
}
