<?php

namespace App\Modules\CMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadShopifyFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['nullable', 'uuid'],
            'file' => ['required', 'file', 'max:20480'],
            'title' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:120'],
            'owner_type' => ['nullable', 'in:product,variant,collection'],
            'owner_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

