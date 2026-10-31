<?php

namespace App\Http\Requests\Api\Project;

use App\Enums\ProjectStatus;
use App\Http\Requests\Api\BaseApiRequest;
use App\Models\Project;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ];

        $project = $this->route('project');
        if ($this->filled('start_date')) {
            $rules['due_date'][] = 'after_or_equal:start_date';
        } elseif ($project instanceof Project && $project->start_date) {
            $rules['due_date'][] = 'after_or_equal:' . $project->start_date->toDateString();
        }

        return $rules;
    }
}

