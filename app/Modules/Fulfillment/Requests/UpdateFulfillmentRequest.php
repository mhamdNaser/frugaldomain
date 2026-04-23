<?php

namespace App\Modules\Fulfillment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipment_status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_url' => ['sometimes', 'nullable', 'string'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
