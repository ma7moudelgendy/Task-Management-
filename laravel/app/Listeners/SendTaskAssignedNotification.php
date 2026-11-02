<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskAssignedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(TaskAssigned $event): void
    {
        // Avoid sending notification if assigning to oneself
        if ((int) $event->assignee->id === (int) $event->assigner->id) {
            return;
        }

        $event->assignee->notify(new TaskAssignedNotification($event->task, $event->assigner));
    }
}
