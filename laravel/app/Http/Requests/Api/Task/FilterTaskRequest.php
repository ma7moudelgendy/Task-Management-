<?php

namespace App\Http\Requests\Api\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class FilterTaskRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'assignee_id' => ['sometimes', 'integer', 'exists:users,id'],
            'due_before' => ['sometimes', 'date'],
            'due_after' => ['sometimes', 'date'],
            'sort_by' => ['sometimes', Rule::in(['due_at', 'priority', 'created_at', 'position', 'title'])],
            'sort_dir' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}

