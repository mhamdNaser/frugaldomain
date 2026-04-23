<?php

namespace App\Modules\Shipping\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shopify_zone_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shopify_profile_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'countries' => ['sometimes', 'nullable'],
        ];
    }
}
