<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'shopify_return_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'requested_at' => ['sometimes', 'nullable', 'date'],
            'opened_at' => ['sometimes', 'nullable', 'date'],
            'closed_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
