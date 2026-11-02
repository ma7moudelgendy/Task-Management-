<?php

namespace App\Repositories\Eloquent;

use App\Models\RecurringTaskRule;
use App\Models\Task;
use App\Repositories\Contracts\RecurringTaskRuleRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class RecurringTaskRuleRepository implements RecurringTaskRuleRepositoryInterface
{
    /**
     * Get active rules that are due to run.
     *
     * @return Collection<int, RecurringTaskRule>
     */
    public function getDueRules(CarbonInterface $now): Collection
    {
        return RecurringTaskRule::query()
            ->with(['task.assignees', 'task.project'])
            ->where('is_active', true)
            ->where('next_run_at', '<=', $now)
            ->get();
    }

    /**
     * Create a new recurring task rule.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): RecurringTaskRule
    {
        return RecurringTaskRule::create($attributes);
    }

    /**
     * Update a recurring task rule.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(RecurringTaskRule $rule, array $attributes): bool
    {
        return $rule->update($attributes);
    }

    /**
     * Find rule for a specific task.
     */
    public function findForTask(Task $task): ?RecurringTaskRule
    {
        return RecurringTaskRule::where('task_id', $task->id)->first();
    }
}
