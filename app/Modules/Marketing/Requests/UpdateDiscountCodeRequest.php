<?php

namespace App\Modules\Marketing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discount_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'shopify_discount_code_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'usage_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
