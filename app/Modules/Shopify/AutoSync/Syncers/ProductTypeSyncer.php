<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

class ProductTypeSyncer extends ProductAttributeSyncer
{
    protected function productField(): string
    {
        return 'product_type_id';
    }
}
