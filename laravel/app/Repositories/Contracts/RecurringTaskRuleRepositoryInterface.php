<?php

namespace App\Repositories\Contracts;

use App\Models\RecurringTaskRule;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

interface RecurringTaskRuleRepositoryInterface
{
    /**
     * Get active rules that are due to run.
     *
     * @return Collection<int, RecurringTaskRule>
     */
    public function getDueRules(CarbonInterface $now): Collection;

    /**
     * Create a new recurring task rule.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): RecurringTaskRule;

    /**
     * Update a recurring task rule.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(RecurringTaskRule $rule, array $attributes): bool;

    /**
     * Find rule for a specific task.
     */
    public function findForTask(Task $task): ?RecurringTaskRule;
}
