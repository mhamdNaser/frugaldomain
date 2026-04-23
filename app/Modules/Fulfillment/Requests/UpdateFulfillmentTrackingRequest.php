<?php

namespace App\Modules\Fulfillment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFulfillmentTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fulfillment_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'url' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
