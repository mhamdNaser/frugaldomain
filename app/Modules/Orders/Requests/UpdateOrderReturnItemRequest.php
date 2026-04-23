<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderReturnItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_return_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'order_item_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'shopify_return_line_item_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shopify_line_item_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
