<?php

namespace App\Http\Requests\Api\Recurrence;

use App\Enums\RecurrenceFrequency;
use App\Http\Requests\Api\BaseApiRequest;
use Illuminate\Validation\Rule;

class StoreRecurrenceRuleRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'frequency' => ['required', Rule::in(RecurrenceFrequency::values())],
            'interval' => ['required', 'integer', 'min:1', 'max:365'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'timezone' => ['sometimes', 'string', 'timezone'],
        ];
    }
}
