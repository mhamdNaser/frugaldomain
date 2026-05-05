<?php

namespace App\Modules\CMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_id' => ['required', 'integer', 'exists:files,id'],
            'owner_type' => ['required', 'in:product,variant,collection'],
            'owner_id' => ['required', 'integer', 'min:1'],
            'role' => ['nullable', 'string', 'max:120'],
        ];
    }
}

