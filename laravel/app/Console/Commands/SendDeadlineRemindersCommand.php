<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Events\TaskDueSoon;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDueSoonNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendDeadlineRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:send-due-reminders {--hours=24 : Lookahead window in hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan tasks approaching their due date and dispatch deadline reminders';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $now = now();
        $threshold = $now->copy()->addHours($hours);

        $this->info("Scanning tasks due between now and {$threshold->toIso8601String()} ({$hours} hours)...");

        $tasks = Task::query()
            ->with(['assignees', 'creator', 'project'])
            ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::CANCELLED->value])
            ->whereNotNull('due_at')
            ->where('due_at', '>=', $now)
            ->where('due_at', '<=', $threshold)
            ->get();

        $remindersSent = 0;

        foreach ($tasks as $task) {
            $dueTimestamp = $task->due_at->timestamp;
            $cacheKey = "task_due_reminder:{$task->id}:{$dueTimestamp}";

            // Idempotency: skip if already reminded for this exact due date
            if (Cache::has($cacheKey)) {
                continue;
            }

            $hoursRemaining = max(1, (int) round($now->diffInHours($task->due_at)));

            // Identify recipients: assignees or creator if unassigned
            $recipients = $task->assignees->isNotEmpty()
                ? $task->assignees
                : collect([$task->creator])->filter();

            /** @var User $recipient */
            foreach ($recipients as $recipient) {
                $recipient->notify(new TaskDueSoonNotification($task, $hoursRemaining));
            }

            TaskDueSoon::dispatch($task, $hoursRemaining);

            Cache::put($cacheKey, true, now()->addDays(7));
            $remindersSent++;
        }

        $this->info("✓ Dispatched deadline reminders for {$remindersSent} task(s).");

        return Command::SUCCESS;
    }
}
