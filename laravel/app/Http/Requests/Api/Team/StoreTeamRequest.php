<?php

namespace App\Http\Requests\Api\Team;

use App\Http\Requests\Api\BaseApiRequest;

class StoreTeamRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:teams,slug'],
        ];
    }
}

