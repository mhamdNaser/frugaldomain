<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'max:100'],
            'total_amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:20'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
