<?php

namespace App\Modules\Fulfillment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReverseFulfillmentsIndexRequest extends FormRequest
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
            'order_return_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
