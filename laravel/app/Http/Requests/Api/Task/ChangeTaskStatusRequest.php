<?php

namespace App\Http\Requests\Api\Task;

use App\Enums\TaskStatus;
use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class ChangeTaskStatusRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}

