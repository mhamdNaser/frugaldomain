<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionProduct extends Model
{
    protected $table = 'collection_products';

    protected $fillable = [
        'store_id',
        'collection_id',
        'product_id',
        'position',
        'added_via',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
