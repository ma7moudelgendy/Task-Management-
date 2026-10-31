<?php

namespace App\Http\Requests\Api\Task;

use App\Http\Requests\Api\BaseApiRequest;

class AddDependencyRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'depends_on_task_id' => ['required', 'integer', 'exists:tasks,id'],
        ];
    }
}

