<?php

namespace App\Modules\Fulfillment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFulfillmentOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fulfillment_order_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'order_item_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'shopify_fulfillment_order_line_item_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shopify_line_item_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'total_quantity' => ['sometimes', 'integer', 'min:0'],
            'remaining_quantity' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
