<?php

namespace App\Modules\CMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:articles,id'],
        ];
    }
}
