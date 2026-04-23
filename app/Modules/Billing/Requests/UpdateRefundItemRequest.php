<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefundItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'restock_type' => ['nullable', 'string', 'max:100'],
            'restocked' => ['sometimes', 'boolean'],
            'subtotal' => ['nullable', 'numeric'],
            'tax' => ['nullable', 'numeric'],
            'total' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:20'],
        ];
    }
}
