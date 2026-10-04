<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

class VendorSyncer extends ProductAttributeSyncer
{
    protected function productField(): string
    {
        return 'vendor_id';
    }
}
