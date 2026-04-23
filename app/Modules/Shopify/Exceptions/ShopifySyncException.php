<?php

namespace App\Modules\Shopify\Exceptions;

use Exception;
use Throwable;

class ShopifySyncException extends Exception
{
    public function __construct(
        string $message = 'Shopify sync error.',
        int $code = 0,
        ?Throwable $previous = null,
        public array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * الحصول على سياق الخطأ (البيانات الإضافية)
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * إضافة سياق إضافي للخطأ
     *
     * @param array<string, mixed> $extraContext
     * @return self
     */
    public function withContext(array $extraContext): self
    {
        $this->context = array_merge($this->context, $extraContext);
        return $this;
    }
}
