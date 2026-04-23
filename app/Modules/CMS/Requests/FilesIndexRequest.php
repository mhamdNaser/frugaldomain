<?php

namespace App\Modules\CMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilesIndexRequest extends FormRequest
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
            'file_type' => ['nullable', 'string', 'max:80'],
            'role' => ['nullable', 'string', 'max:120'],
            'owner_type' => ['nullable', 'string', 'max:160'],
            'sort_by' => ['nullable', 'string', 'max:80'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
