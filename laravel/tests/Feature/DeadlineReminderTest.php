<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Events\TaskDueSoon;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TaskDueSoonNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DeadlineReminderTest extends TestCase
{
    use DatabaseTransactions;

    protected User $owner;
    protected User $member;
    protected Team $team;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Reminder Owner', 'email' => 'rem_owner@example.com']);
        $this->member = User::factory()->create(['name' => 'Reminder Member', 'email' => 'rem_member@example.com']);

        $this->team = Team::factory()->create(['created_by' => $this->owner->id]);
        $this->team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $this->team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);

        $this->project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->owner->id,
        ]);

        $this->project->members()->attach($this->owner->id, ['role' => ProjectRole::OWNER->value]);
        $this->project->members()->attach($this->member->id, ['role' => ProjectRole::MEMBER->value]);
    }

    public function test_tasks_due_within_24_hours_dispatch_deadline_reminders(): void
    {
        Notification::fake();
        Event::fake([TaskDueSoon::class]);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_PROGRESS->value,
            'priority' => TaskPriority::HIGH->value,
            'due_at' => now()->addHours(12),
        ]);

        $task->assignees()->attach($this->member->id, [
            'assigned_by' => $this->owner->id,
            'assigned_at' => now(),
        ]);

        $this->artisan('tasks:send-due-reminders')
            ->expectsOutputToContain('Dispatched deadline reminders for 1 task(s)')
            ->assertSuccessful();

        Notification::assertSentTo($this->member, TaskDueSoonNotification::class, function ($notification) use ($task) {
            return (int) $notification->task->id === (int) $task->id;
        });

        Event::assertDispatched(TaskDueSoon::class, function ($event) use ($task) {
            return (int) $event->task->id === (int) $task->id;
        });
    }

    public function test_tasks_past_or_far_future_or_done_do_not_dispatch_reminders(): void
    {
        Notification::fake();

        // Far future (> 24h)
        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::TODO->value,
            'due_at' => now()->addHours(48),
        ]);

        // Already DONE
        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::DONE->value,
            'due_at' => now()->addHours(6),
        ]);

        $this->artisan('tasks:send-due-reminders')
            ->expectsOutputToContain('Dispatched deadline reminders for 0 task(s)')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_deadline_reminder_is_not_sent_twice_idempotency(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::TODO->value,
            'due_at' => now()->addHours(8),
        ]);

        $task->assignees()->attach($this->member->id, [
            'assigned_by' => $this->owner->id,
            'assigned_at' => now(),
        ]);

        // First execution dispatches reminder
        $this->artisan('tasks:send-due-reminders')
            ->expectsOutputToContain('Dispatched deadline reminders for 1 task(s)')
            ->assertSuccessful();

        // Second immediate execution does not redispatch
        $this->artisan('tasks:send-due-reminders')
            ->expectsOutputToContain('Dispatched deadline reminders for 0 task(s)')
            ->assertSuccessful();
    }
}

