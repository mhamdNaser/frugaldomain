<?php

namespace App\Modules\User\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'nullable|in:admin,user', // يسمح بإرسالها أو تجاهلها
        ];
    }

    /**
     * تخصيص رسالة الخطأ
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'The name field is required.',
            'email.required'    => 'The email field is required.',
            'email.email'       => 'The email format is invalid.',
            'email.unique'      => 'This email has already been taken.',
            'password.required' => 'The password field is required.',
            'password.min'      => 'The password must be at least 6 characters.',
            'role.in'           => 'The selected role is invalid.',
        ];
    }

    /**
     * تخصيص رد الخطأ عند الفشل في الفاليديشن
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
