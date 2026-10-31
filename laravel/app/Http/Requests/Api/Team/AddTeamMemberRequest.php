<?php

namespace App\Http\Requests\Api\Team;

use App\Enums\TeamRole;
use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class AddTeamMemberRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
            'role' => ['required', Rule::enum(TeamRole::class)],
        ];
    }
}

