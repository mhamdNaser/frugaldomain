<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway' => ['sometimes', 'string', 'max:255'],
            'transaction_reference' => ['sometimes', 'string', 'max:255'],
            'kind' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'string', 'max:100'],
            'processed_at' => ['nullable', 'date'],
            'test' => ['sometimes', 'boolean'],
            'manual_payment_gateway' => ['sometimes', 'boolean'],
        ];
    }
}
