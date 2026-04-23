<?php

namespace App\Modules\Shopify\DTOs;

class CustomerAddressData
{
    public function __construct(
        public readonly string $shopifyAddressId,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $name,
        public readonly ?string $company,
        public readonly ?string $address1,
        public readonly ?string $address2,
        public readonly ?string $city,
        public readonly ?string $province,
        public readonly ?string $provinceCode,
        public readonly ?string $country,
        public readonly ?string $countryCode,
        public readonly ?string $zip,
        public readonly ?string $phone,
        public readonly bool $isDefault,
        public readonly array $rawPayload,
    ) {}
}
