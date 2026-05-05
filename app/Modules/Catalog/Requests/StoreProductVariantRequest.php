<?php

namespace App\Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'uuid'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'shopify_variant_id' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'compare_at_price' => ['nullable', 'numeric'],
            'inventory_quantity' => ['nullable', 'integer'],
            'position' => ['nullable', 'integer'],
            'is_default' => ['sometimes', 'boolean'],
            'availableForSale' => ['sometimes', 'boolean'],
            'taxable' => ['sometimes', 'boolean'],
            'option_value_ids' => ['nullable', 'array'],
            'option_value_ids.*' => ['integer', 'exists:option_values,id'],
            'shopify_sync' => ['sometimes', 'array'],
            'shopify_sync.mutation' => ['sometimes', 'required_without:shopify_sync.query', 'string'],
            'shopify_sync.query' => ['sometimes', 'required_without:shopify_sync.mutation', 'string'],
            'shopify_sync.variables' => ['nullable', 'array'],
            'shopify_sync.resource_path' => ['nullable', 'string', 'max:255'],
            'shopify_sync.user_errors_path' => ['nullable', 'string', 'max:255'],
            'shopify_sync.idempotency_key' => ['nullable', 'string', 'max:255'],
            'shopify_sync.correlation_id' => ['nullable', 'string', 'max:255'],
            'shopify_sync.priority' => ['nullable', 'integer', 'min:0', 'max:9'],
            'shopify_sync.max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}

