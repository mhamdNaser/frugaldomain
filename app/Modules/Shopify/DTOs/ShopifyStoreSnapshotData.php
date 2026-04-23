<?php

namespace App\Modules\Shopify\DTOs;

class ShopifyStoreSnapshotData
{
    public function __construct(
        public readonly string $shopifyStoreId,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $domain,
        public readonly ?string $myshopifyDomain,
        public readonly ?string $shopOwner,
        public readonly ?string $phone,
        public readonly ?string $country,
        public readonly ?string $countryCode,
        public readonly ?string $currency,
        public readonly ?string $timezone,
        public readonly ?string $ianaTimezone,
        public readonly ?string $planName,
        public readonly ?string $planDisplayName,
        public readonly bool $taxesIncluded,
        public readonly bool $countyTaxes,
        public readonly bool $hasDiscounts,
        public readonly bool $hasGiftCards,
        public readonly bool $multiLocationEnabled,
        public readonly ?int $primaryLocationId,
        public readonly array $rawData,
    ) {}
}

