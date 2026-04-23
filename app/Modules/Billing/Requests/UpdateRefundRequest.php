<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string'],
            'total' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:20'],
            'processed_at' => ['nullable', 'date'],
        ];
    }
}
