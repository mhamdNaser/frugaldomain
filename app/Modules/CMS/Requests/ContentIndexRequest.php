<?php

namespace App\Modules\CMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContentIndexRequest extends FormRequest
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
            'parent_field' => ['nullable', 'string', 'max:80'],
            'parent_id' => ['nullable', 'string', 'max:120'],
        ];
    }
}
