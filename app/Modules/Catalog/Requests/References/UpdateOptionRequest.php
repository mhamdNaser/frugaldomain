<?php

namespace App\Modules\Catalog\Requests\References;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'values' => ['nullable', 'array'],
            'values.*.label' => ['required_with:values', 'string', 'max:255'],
            'values.*.value' => ['required_with:values', 'string', 'max:255'],
        ];
    }
}
