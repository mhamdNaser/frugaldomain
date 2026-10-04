<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

class TagSyncer extends ProductAttributeSyncer
{
    protected function productField(): string
    {
        return 'tag_ids';
    }
}
