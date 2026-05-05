<?php

namespace App\Modules\Shopify\OutboundSync\DTOs;

use Throwable;

class OutboundSyncResult
{
    /**
     * @param array<string, mixed> $responsePayload
     */
    private function __construct(
        public readonly bool $success,
        public readonly bool $retryable,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
        public readonly ?int $httpStatus,
        public readonly ?string $shopifyResourceId,
        public readonly array $responsePayload,
    ) {}

    /**
     * @param array<string, mixed> $responsePayload
     */
    public static function success(array $responsePayload = [], ?string $shopifyResourceId = null, ?int $httpStatus = 200): self
    {
        return new self(
            success: true,
            retryable: false,
            errorCode: null,
            errorMessage: null,
            httpStatus: $httpStatus,
            shopifyResourceId: $shopifyResourceId,
            responsePayload: $responsePayload,
        );
    }

    /**
     * @param array<string, mixed> $responsePayload
     */
    public static function failure(
        string $errorCode,
        string $errorMessage,
        bool $retryable = true,
        ?int $httpStatus = null,
        array $responsePayload = [],
    ): self {
        return new self(
            success: false,
            retryable: $retryable,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            httpStatus: $httpStatus,
            shopifyResourceId: null,
            responsePayload: $responsePayload,
        );
    }

    public static function fromThrowable(Throwable $exception, bool $retryable = true): self
    {
        return self::failure(
            errorCode: class_basename($exception),
            errorMessage: $exception->getMessage(),
            retryable: $retryable,
            httpStatus: null,
        );
    }
}

