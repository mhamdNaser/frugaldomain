<?php

namespace App\Modules\Fulfillment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFulfillmentOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'request_status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'assigned_location_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fulfill_at' => ['sometimes', 'nullable', 'date'],
            'fulfill_by' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
