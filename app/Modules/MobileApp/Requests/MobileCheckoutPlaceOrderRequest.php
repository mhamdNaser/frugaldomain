<?php

namespace App\Modules\MobileApp\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MobileCheckoutPlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'uuid'],
            'warehouse_name' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer'],
            'currency' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shipping_address' => ['nullable', 'array'],
            'billing_address' => ['nullable', 'array'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.variant_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
