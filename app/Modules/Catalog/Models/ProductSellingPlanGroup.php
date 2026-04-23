<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSellingPlanGroup extends Model
{
    protected $table = 'product_selling_plan_groups';

    protected $fillable = [
        'store_id',
        'product_id',
        'selling_plan_group_id',
        'shopify_product_id',
    ];
}

