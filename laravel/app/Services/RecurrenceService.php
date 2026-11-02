<?php

namespace App\Services;

use App\DTOs\Recurrence\StoreRecurrenceRuleDTO;
use App\Enums\RecurrenceFrequency;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\RecurringTaskRule;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\RecurringTaskRuleRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RecurrenceService
{
    public function __construct(
        protected RecurringTaskRuleRepositoryInterface $ruleRepository,
        protected TaskRepositoryInterface $taskRepository,
    ) {}

    /**
     * Calculate the next occurrence timestamp.
     */
    public function calculateNextRun(CarbonInterface $current, RecurrenceFrequency|string $frequency, int $interval): Carbon
    {
        $freq = is_string($frequency) ? RecurrenceFrequency::from($frequency) : $frequency;
        $date = Carbon::instance($current);

        return match ($freq) {
            RecurrenceFrequency::DAILY => $date->addDays($interval),
            RecurrenceFrequency::WEEKLY => $date->addWeeks($interval),
            RecurrenceFrequency::MONTHLY => $date->addMonths($interval),
        };
    }

    /**
     * Create or update recurring rule for a task.
     */
    public function createOrUpdateRule(Task $task, StoreRecurrenceRuleDTO $dto, User $actor): RecurringTaskRule
    {
        return DB::transaction(function () use ($task, $dto, $actor) {
            $nextRunAt = $dto->startsAt;

            // Ensure initial next_run_at is not in the past if task is scheduled for future
            $existingRule = $this->ruleRepository->findForTask($task);

            $attributes = [
                'task_id' => $task->id,
                'frequency' => $dto->frequency->value,
                'interval' => $dto->interval,
                'starts_at' => $dto->startsAt,
                'ends_at' => $dto->endsAt,
                'next_run_at' => $nextRunAt,
                'timezone' => $dto->timezone,
                'is_active' => true,
            ];

            if ($existingRule) {
                $this->ruleRepository->update($existingRule, $attributes);
                $rule = $existingRule->fresh();
            } else {
                $rule = $this->ruleRepository->create($attributes);
            }

            ActivityLog::create([
                'actor_id' => $actor->id,
                'team_id' => $task->project?->team_id,
                'project_id' => $task->project_id,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action' => 'set_recurrence_rule',
                'metadata' => [
                    'frequency' => $dto->frequency->value,
                    'interval' => $dto->interval,
                    'next_run_at' => $nextRunAt->toIso8601String(),
                ],
                'created_at' => now(),
            ]);

            return $rule;
        });
    }

    /**
     * Process due recurring rules and generate cloned task instances.
     *
     * @return int Number of task instances generated
     */
    public function processDueRules(?CarbonInterface $now = null): int
    {
        $currentTime = $now ? Carbon::instance($now) : Carbon::now();
        $dueRules = $this->ruleRepository->getDueRules($currentTime);
        $processedCount = 0;

        foreach ($dueRules as $rule) {
            DB::transaction(function () use ($rule, $currentTime, &$processedCount) {
                // Re-fetch lock/fresh check
                /** @var RecurringTaskRule $freshRule */
                $freshRule = RecurringTaskRule::where('id', $rule->id)->lockForUpdate()->first();
                if (! $freshRule || ! $freshRule->is_active || $freshRule->next_run_at->gt($currentTime)) {
                    return;
                }

                // If ends_at is set and already passed, deactivate rule
                if ($freshRule->ends_at && $currentTime->gte($freshRule->ends_at)) {
                    $this->ruleRepository->update($freshRule, ['is_active' => false]);
                    return;
                }

                $templateTask = $freshRule->task;
                if (! $templateTask) {
                    $this->ruleRepository->update($freshRule, ['is_active' => false]);
                    return;
                }

                // Calculate duration if template task had both start_at and due_at
                $startAt = $freshRule->next_run_at->copy();
                $dueAt = null;
                if ($templateTask->due_at && $templateTask->start_at) {
                    $durationSeconds = $templateTask->due_at->diffInSeconds($templateTask->start_at);
                    $dueAt = $startAt->copy()->addSeconds($durationSeconds);
                } elseif ($templateTask->due_at) {
                    $dueAt = $startAt->copy()->addDay();
                }

                // Clone task
                $clonedTask = $this->taskRepository->create([
                    'project_id' => $templateTask->project_id,
                    'created_by' => $templateTask->created_by,
                    'parent_id' => null,
                    'title' => $templateTask->title,
                    'description' => $templateTask->description,
                    'priority' => $templateTask->priority->value,
                    'status' => TaskStatus::TODO->value,
                    'start_at' => $startAt,
                    'due_at' => $dueAt,
                ]);

                // Clone assignees
                $assigneeIds = $templateTask->assignees->pluck('id')->all();
                if (! empty($assigneeIds)) {
                    $this->taskRepository->syncAssignees($clonedTask, $assigneeIds, $templateTask->created_by);
                }

                // Advance next_run_at
                $nextRun = $this->calculateNextRun($freshRule->next_run_at, $freshRule->frequency, $freshRule->interval);
                $isActive = true;

                if ($freshRule->ends_at && $nextRun->gt($freshRule->ends_at)) {
                    $isActive = false;
                }

                $this->ruleRepository->update($freshRule, [
                    'next_run_at' => $nextRun,
                    'is_active' => $isActive,
                ]);

                ActivityLog::create([
                    'actor_id' => $templateTask->created_by,
                    'team_id' => $templateTask->project?->team_id,
                    'project_id' => $templateTask->project_id,
                    'subject_type' => Task::class,
                    'subject_id' => $clonedTask->id,
                    'action' => 'generated_recurring_task',
                    'metadata' => [
                        'template_task_id' => $templateTask->id,
                        'rule_id' => $freshRule->id,
                        'next_run_at' => $nextRun->toIso8601String(),
                    ],
                    'created_at' => now(),
                ]);

                $processedCount++;
            });
        }

        return $processedCount;
    }
}
