<?php

namespace App\Modules\MobileApp\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MobileCheckoutQuoteRequest extends FormRequest
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
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.variant_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
