<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'max:100'],
            'started_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
        ];
    }
}
