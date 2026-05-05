<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_variant_id' => ['sometimes', 'integer'],
            'inventory_item_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shopify_location_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'available' => ['sometimes', 'integer'],
            'committed' => ['sometimes', 'integer'],
            'incoming' => ['sometimes', 'integer'],
            'reserved' => ['sometimes', 'integer'],
            'on_hand' => ['sometimes', 'integer'],
            'shopify_updated_at' => ['sometimes', 'nullable', 'date'],
            'raw_payload' => ['sometimes', 'nullable', 'array'],
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
