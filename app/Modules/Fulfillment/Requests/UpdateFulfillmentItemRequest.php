<?php

namespace App\Modules\Fulfillment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFulfillmentItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fulfillment_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'order_item_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'shopify_line_item_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
