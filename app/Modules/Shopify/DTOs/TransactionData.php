<?php

namespace App\Modules\Shopify\DTOs;

class TransactionData
{
    public function __construct(
        public readonly string $shopifyTransactionId,
        public readonly ?string $parentShopifyTransactionId,
        public readonly string $gateway,
        public readonly ?string $accountNumber,
        public readonly string $transactionReference,
        public readonly string $kind,
        public readonly float $amount,
        public readonly ?string $currency,
        public readonly string $status,
        public readonly bool $test,
        public readonly bool $manualPaymentGateway,
        public readonly ?string $processedAt,
        public readonly array $rawPayload,
    ) {}
}
