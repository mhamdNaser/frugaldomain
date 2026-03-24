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
        return [
            'owner_id'              => 'required|integer',
            'shopify_store_id'      => 'required|string|max:255',
            'shopify_domain'        => 'required|string|max:255',
            'shopify_access_token'  => 'required|string',
            'name'                  => 'required',
            'email'                 => 'required|string|min:8',
            'currency'              => 'required|string|max:255',
            'timezone'              => 'required|string|max:255',
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
