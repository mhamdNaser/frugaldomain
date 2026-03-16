<?php

namespace App\Modules\User\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق من صحة بيانات تسجيل الدخول.
     */
    public function rules(): array
    {
        return [
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string|min:6',
        ];
    }

    /**
     * الرسائل المخصصة للأخطاء.
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'Email is required.',
            'email.email'       => 'Invalid email format.',
            'email.exists'      => 'Email is not registered.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 6 characters.',
        ];
    }

    /**
     * تخصيص رد الفعل عند فشل التحقق.
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
