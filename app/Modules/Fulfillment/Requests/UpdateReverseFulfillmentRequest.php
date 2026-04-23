<?php

namespace App\Modules\Fulfillment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReverseFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_return_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'shopify_reverse_fulfillment_order_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shopify_created_at' => ['sometimes', 'nullable', 'date'],
            'shopify_updated_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
