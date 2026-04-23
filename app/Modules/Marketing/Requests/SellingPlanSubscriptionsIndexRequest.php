<?php

namespace App\Modules\Marketing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SellingPlanSubscriptionsIndexRequest extends FormRequest
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
            'customer_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

