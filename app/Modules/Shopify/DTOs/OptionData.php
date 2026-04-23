<?php

namespace App\Modules\Shopify\DTOs;

class OptionData
{
    public function __construct(
        public string $name,
        public ?array $optionValues
    ) {}
}
