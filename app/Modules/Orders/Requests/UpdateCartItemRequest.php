<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric'],
            'total_price' => ['nullable', 'numeric'],
        ];
    }
}
