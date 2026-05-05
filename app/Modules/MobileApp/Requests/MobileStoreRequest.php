<?php

namespace App\Modules\MobileApp\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MobileStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'uuid'],
            'warehouse_name' => ['nullable', 'string', 'max:255'],
            'warehouse_id' => ['nullable', 'string', 'max:255'],
            'warehouse_location' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'collection_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'menu_handle' => ['nullable', 'string', 'max:255'],
        ];
    }
}
