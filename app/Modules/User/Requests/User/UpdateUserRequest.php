<?php

namespace App\Modules\User\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
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
            'name'          => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable',
            'password'      => 'nullable|string|min:8',
            'first_name'    => 'nullable|string|max:255',
            'medium_name'   => 'nullable|string|max:255',
            'last_name'     => 'nullable|string|max:255',
            'country_id'    => 'nullable|integer',
            'state_id'      => 'nullable|integer',
            'city_id'       => 'nullable|integer',
            'address_1'     => 'nullable|string|max:255',
            'address_2'     => 'nullable|string|max:255',
            'address_3'     => 'nullable|string|max:255',
            'status'        => 'nullable|boolean',
            'role_id'       => 'nullable',
            'image'         => 'nullable',
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
