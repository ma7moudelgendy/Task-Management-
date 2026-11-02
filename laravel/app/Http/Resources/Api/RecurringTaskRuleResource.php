<?php

namespace App\Http\Resources\Api;

use App\Models\RecurringTaskRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecurringTaskRule
 */
class RecurringTaskRuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'frequency' => $this->frequency->value,
            'interval' => $this->interval,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'next_run_at' => $this->next_run_at?->toIso8601String(),
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
        ];
    }
}
