<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }
}
