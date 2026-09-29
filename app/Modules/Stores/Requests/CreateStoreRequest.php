<?php

namespace App\Modules\Stores\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // The store's name, email, currency, timezone and Shopify id come from
        // Shopify itself once the credentials are verified (StoreConnector).
        return [
            'owner_id'               => 'required|integer|exists:users,id',
            'shopify_domain'         => 'required|string|max:255',
            'shopify_access_token'   => 'required|string|max:255',
            'shopify_webhook_secret' => 'nullable|string|max:255',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data' => $validator->errors(),
        ], 422));
    }
}
