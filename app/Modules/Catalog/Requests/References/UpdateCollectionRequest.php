<?php

namespace App\Modules\Catalog\Requests\References;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', Rule::in(['manual', 'automated', 'smart'])],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
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

