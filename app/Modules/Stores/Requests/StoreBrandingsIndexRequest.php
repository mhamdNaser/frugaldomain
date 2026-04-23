<?php

namespace App\Modules\Stores\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandingsIndexRequest extends FormRequest
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
            'store_id' => ['nullable', 'string', 'max:36'],
        ];
    }
}

