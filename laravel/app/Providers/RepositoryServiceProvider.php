<?php

namespace App\Providers;

use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\RecurringTaskRuleRepositoryInterface;
use App\Repositories\Contracts\TaskDependencyRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\TaskStatusHistoryRepositoryInterface;
use App\Repositories\Contracts\TeamRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\AnalyticsRepository;
use App\Repositories\Eloquent\AttachmentRepository;
use App\Repositories\Eloquent\CommentRepository;
use App\Repositories\Eloquent\NotificationRepository;
use App\Repositories\Eloquent\ProjectRepository;
use App\Repositories\Eloquent\RecurringTaskRuleRepository;
use App\Repositories\Eloquent\TaskDependencyRepository;
use App\Repositories\Eloquent\TaskRepository;
use App\Repositories\Eloquent\TaskStatusHistoryRepository;
use App\Repositories\Eloquent\TeamRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TeamRepositoryInterface::class, TeamRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(TaskDependencyRepositoryInterface::class, TaskDependencyRepository::class);
        $this->app->bind(TaskStatusHistoryRepositoryInterface::class, TaskStatusHistoryRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
        $this->app->bind(AttachmentRepositoryInterface::class, AttachmentRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(RecurringTaskRuleRepositoryInterface::class, RecurringTaskRuleRepository::class);
        $this->app->bind(AnalyticsRepositoryInterface::class, AnalyticsRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
