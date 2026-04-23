<?php

namespace App\Modules\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDraftOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:100'],
            'invoice_url' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'numeric'],
            'tax' => ['nullable', 'numeric'],
            'total' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:20'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}
