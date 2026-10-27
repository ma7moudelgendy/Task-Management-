<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['sometimes', 'string', Password::min(8)->letters()->numbers()],
        ];
    }
}
