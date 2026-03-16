<?php

namespace App\Modules\Stores\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateStoreRequest extends FormRequest
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
        return [
            'owner_id'              => 'nullable|integer',
            'shopify_store_id'      => 'nullable|string|max:255',
            'shopify_domain'        => 'nullable|string|max:255',
            'shopify_access_token'  => 'nullable|string',
            'name'                  => 'nullable',
            'email'                 => 'nullable|string|min:8',
            'currency'              => 'nullable|string|max:255',
            'timezone'              => 'nullable|string|max:255',
            'status'                => 'nullable|string|max:255',
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
