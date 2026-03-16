<?php

namespace App\Modules\User\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
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
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'required',
            'password'      => 'required|string|min:8',
            'first_name'    => 'required|string|max:255',
            'medium_name'   => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'country_id'    => 'nullable|integer',
            'state_id'      => 'nullable|integer',
            'city_id'       => 'nullable|integer',
            'status'        => 'required|boolean',
            'role_id'       => 'required|integer',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
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
