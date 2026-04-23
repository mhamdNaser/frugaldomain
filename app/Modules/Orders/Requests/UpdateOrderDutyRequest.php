<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderDutyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'shopify_order_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shopify_duty_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'harmonized_system_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
