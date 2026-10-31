<?php

namespace App\Http\Requests\Api\Project;

use App\Enums\ProjectRole;
use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class AddProjectMemberRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', Rule::enum(ProjectRole::class)],
        ];
    }
}

