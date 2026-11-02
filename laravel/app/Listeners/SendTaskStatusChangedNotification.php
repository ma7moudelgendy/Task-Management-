<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Models\User;
use App\Notifications\TaskStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskStatusChangedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TaskStatusChanged $event): void
    {
        $task = $event->task->loadMissing(['assignees', 'creator']);
        $changerId = (int) $event->changer->id;

        $recipients = collect();

        // Include creator if not changer
        if ($task->creator && (int) $task->creator->id !== $changerId) {
            $recipients->push($task->creator);
        }

        // Include assignees if not changer
        foreach ($task->assignees as $assignee) {
            if ((int) $assignee->id !== $changerId) {
                $recipients->push($assignee);
            }
        }

        /** @var \Illuminate\Support\Collection<int, User> $uniqueRecipients */
        $uniqueRecipients = $recipients->unique('id');

        foreach ($uniqueRecipients as $recipient) {
            $recipient->notify(
                new TaskStatusChangedNotification(
                    $event->task,
                    $event->oldStatus,
                    $event->newStatus,
                    $event->changer
                )
            );
        }
    }
}
