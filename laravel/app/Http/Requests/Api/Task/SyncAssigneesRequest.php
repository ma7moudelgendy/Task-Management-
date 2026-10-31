<?php

namespace App\Http\Requests\Api\Task;

use App\Http\Requests\Api\BaseApiRequest;

class SyncAssigneesRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignee_ids' => ['present', 'array'],
            'assignee_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}

