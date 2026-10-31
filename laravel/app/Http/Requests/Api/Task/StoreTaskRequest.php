<?php

namespace App\Http\Requests\Api\Task;

use App\Enums\TaskPriority;
use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'parent_task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'start_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}

