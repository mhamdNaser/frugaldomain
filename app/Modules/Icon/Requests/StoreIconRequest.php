<?php

namespace App\Modules\Icon\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIconRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:icon_categories,id',
            'is_premium' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'is_active' => 'boolean',
            'icon_text' => 'required|string', // هنا تخزين الكود مباشرة
        ];
    }
}
