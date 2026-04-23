<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_title' => ['sometimes', 'string', 'max:255'],
            'variant_title' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric'],
            'total_price' => ['nullable', 'numeric'],
        ];
    }
}
