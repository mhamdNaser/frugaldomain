<?php

namespace App\Modules\CMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'handle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
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


