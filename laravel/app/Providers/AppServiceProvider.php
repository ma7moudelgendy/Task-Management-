<?php

namespace App\Providers;

use App\Events\TaskAssigned;
use App\Events\TaskStatusChanged;
use App\Listeners\SendTaskAssignedNotification;
use App\Listeners\SendTaskStatusChangedNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\Team;
use App\Policies\AttachmentPolicy;
use App\Policies\CommentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskComment::class, CommentPolicy::class);
        Gate::policy(TaskAttachment::class, AttachmentPolicy::class);

        Event::listen(TaskAssigned::class, SendTaskAssignedNotification::class);
        Event::listen(TaskStatusChanged::class, SendTaskStatusChangedNotification::class);
    }
}
