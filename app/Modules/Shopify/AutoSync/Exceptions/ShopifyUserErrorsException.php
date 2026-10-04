<?php

namespace App\Modules\Shopify\AutoSync\Exceptions;

use RuntimeException;

/**
 * Shopify rejected the data (userErrors). Not retryable: the local change must be rolled back.
 */
class ShopifyUserErrorsException extends RuntimeException
{
    /**
     * @param array<int, array<string, mixed>> $errors
     */
    public function __construct(
        string $message,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function isNotFound(): bool
    {
        $message = strtolower($this->getMessage());

        foreach (['not exist', 'not found', 'could not find', 'does not exist'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
