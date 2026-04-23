<?php

namespace App\Modules\Marketing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discount_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'method' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'short_summary' => ['sometimes', 'nullable', 'string'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'usage_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'total_sales' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'max:20'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
