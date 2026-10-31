<?php

namespace App\Http\Requests\Api\Team;

use App\Http\Requests\Api\BaseApiRequest;
use App\Models\Team;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('team');
        $teamId = $team instanceof Team ? $team->id : $team;

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('teams', 'slug')->ignore($teamId),
            ],
        ];
    }
}

