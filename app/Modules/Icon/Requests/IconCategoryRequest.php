<?php

namespace App\Modules\Icon\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IconCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // عدل حسب الصلاحيات
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:icon_categories,slug,' . $this->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:icon_categories,id',
            'is_active' => 'boolean',
        ];

        return $rules;
    }
}

