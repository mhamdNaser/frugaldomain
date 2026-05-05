<?php

namespace App\Modules\User\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordWithCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'new_password' => ['required', 'string', 'min:8'],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ];
    }
}

