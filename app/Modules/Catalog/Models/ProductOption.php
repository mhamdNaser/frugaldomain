<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    protected $table = 'product_options'; // pivot table

    protected $fillable = [
        'store_id',
        'product_id',
        'option_id',
        'position',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id');
    }
}
