<?php

namespace App\Modules\Locale\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'direction' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:languages',
            'status' => 'required|boolean',
            'default' => 'required|boolean',
        ];

        return $rules;
    }
}
