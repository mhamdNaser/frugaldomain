<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundItemsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'rowsPerPage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'refund_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
