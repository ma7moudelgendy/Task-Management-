<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\RecurrenceFrequency;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\RecurringTaskRule;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecurrenceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $owner;
    protected User $manager;
    protected User $member;
    protected User $viewer;
    protected Team $team;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Recurrence Owner', 'email' => 'r_owner@example.com']);
        $this->manager = User::factory()->create(['name' => 'Recurrence Manager', 'email' => 'r_manager@example.com']);
        $this->member = User::factory()->create(['name' => 'Recurrence Member', 'email' => 'r_member@example.com']);
        $this->viewer = User::factory()->create(['name' => 'Recurrence Viewer', 'email' => 'r_viewer@example.com']);

        $this->team = Team::factory()->create(['created_by' => $this->owner->id]);
        $this->team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $this->team->members()->attach($this->manager->id, ['role' => TeamRole::MEMBER->value]);
        $this->team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);

        $this->project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->owner->id,
        ]);

        $this->project->members()->attach($this->owner->id, ['role' => ProjectRole::OWNER->value]);
        $this->project->members()->attach($this->manager->id, ['role' => ProjectRole::MANAGER->value]);
        $this->project->members()->attach($this->member->id, ['role' => ProjectRole::MEMBER->value]);
        $this->project->members()->attach($this->viewer->id, ['role' => ProjectRole::VIEWER->value]);

        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'title' => 'Weekly Team Sync',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::HIGH->value,
            'start_at' => now(),
            'due_at' => now()->addHours(2),
        ]);

        $this->task->assignees()->attach($this->member->id, [
            'assigned_by' => $this->owner->id,
            'assigned_at' => now(),
        ]);
    }

    public function test_user_can_set_recurrence_rule_on_task(): void
    {
        Sanctum::actingAs($this->owner);

        $startsAt = now()->addDay()->startOfHour();

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/recurrence", [
            'frequency' => RecurrenceFrequency::WEEKLY->value,
            'interval' => 1,
            'starts_at' => $startsAt->toIso8601String(),
            'timezone' => 'UTC',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'task_id' => $this->task->id,
                    'frequency' => RecurrenceFrequency::WEEKLY->value,
                    'interval' => 1,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('recurring_task_rules', [
            'task_id' => $this->task->id,
            'frequency' => RecurrenceFrequency::WEEKLY->value,
            'interval' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_viewer_cannot_set_recurrence_rule(): void
    {
        Sanctum::actingAs($this->viewer);

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/recurrence", [
            'frequency' => RecurrenceFrequency::DAILY->value,
            'interval' => 1,
            'starts_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
            ]);
    }

    public function test_process_recurring_command_creates_cloned_task_instance(): void
    {
        $dueTime = now()->subMinutes(15);

        RecurringTaskRule::create([
            'task_id' => $this->task->id,
            'frequency' => RecurrenceFrequency::WEEKLY->value,
            'interval' => 1,
            'starts_at' => $dueTime,
            'next_run_at' => $dueTime,
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $initialTaskCount = Task::where('project_id', $this->project->id)->count();

        $this->artisan('tasks:process-recurring')
            ->expectsOutputToContain('Generated 1 recurring task instance(s)')
            ->assertSuccessful();

        $this->assertSame($initialTaskCount + 1, Task::where('project_id', $this->project->id)->count());

        $clonedTask = Task::where('project_id', $this->project->id)->latest('id')->first();
        $this->assertSame('Weekly Team Sync', $clonedTask->title);
        $this->assertSame(TaskStatus::TODO, $clonedTask->status);
        $this->assertSame(TaskPriority::HIGH, $clonedTask->priority);
        $this->assertTrue($clonedTask->assignees()->where('users.id', $this->member->id)->exists());

        // Rule next_run_at should advance by 1 week
        $rule = RecurringTaskRule::where('task_id', $this->task->id)->first();
        $this->assertTrue($rule->next_run_at->gt(now()));
    }

    public function test_process_recurring_command_deactivates_expired_rule(): void
    {
        RecurringTaskRule::create([
            'task_id' => $this->task->id,
            'frequency' => RecurrenceFrequency::DAILY->value,
            'interval' => 1,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
            'next_run_at' => now()->subDay(),
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->artisan('tasks:process-recurring')
            ->assertSuccessful();

        $rule = RecurringTaskRule::where('task_id', $this->task->id)->first();
        $this->assertFalse($rule->is_active);
    }

    public function test_idempotency_does_not_duplicate_cloned_tasks_on_immediate_rerun(): void
    {
        $dueTime = now()->subMinutes(10);

        RecurringTaskRule::create([
            'task_id' => $this->task->id,
            'frequency' => RecurrenceFrequency::DAILY->value,
            'interval' => 1,
            'starts_at' => $dueTime,
            'next_run_at' => $dueTime,
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        // First run generates 1 task
        $this->artisan('tasks:process-recurring')
            ->expectsOutputToContain('Generated 1 recurring task instance(s)')
            ->assertSuccessful();

        // Second run finds 0 due tasks
        $this->artisan('tasks:process-recurring')
            ->expectsOutputToContain('Generated 0 recurring task instance(s)')
            ->assertSuccessful();
    }
}
