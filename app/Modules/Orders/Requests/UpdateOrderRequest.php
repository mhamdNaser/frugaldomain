<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'max:100'],
            'payment_status' => ['sometimes', 'string', 'max:100'],
            'fulfillment_status' => ['sometimes', 'string', 'max:100'],
            'placed_at' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:20'],
            'subtotal' => ['nullable', 'numeric'],
            'tax' => ['nullable', 'numeric'],
            'shipping' => ['nullable', 'numeric'],
            'discount' => ['nullable', 'numeric'],
            'total' => ['nullable', 'numeric'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
