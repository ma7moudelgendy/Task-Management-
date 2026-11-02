<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusChangedNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $owner;
    protected User $manager;
    protected User $member;
    protected User $outsider;
    protected Team $team;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Notification Owner', 'email' => 'n_owner@example.com']);
        $this->manager = User::factory()->create(['name' => 'Notification Manager', 'email' => 'n_manager@example.com']);
        $this->member = User::factory()->create(['name' => 'Notification Member', 'email' => 'n_member@example.com']);
        $this->outsider = User::factory()->create(['name' => 'Notification Outsider', 'email' => 'n_outsider@example.com']);

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

        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::MEDIUM->value,
        ]);
    }

    public function test_assigning_user_to_task_dispatches_notification(): void
    {
        Notification::fake();

        Sanctum::actingAs($this->owner);

        $response = $this->putJson("/api/v1/tasks/{$this->task->id}/assignees", [
            'assignee_ids' => [$this->member->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        Notification::assertSentTo($this->member, TaskAssignedNotification::class, function ($notification) {
            return (int) $notification->task->id === (int) $this->task->id;
        });
    }

    public function test_task_status_change_dispatches_notification_to_creator_and_assignees(): void
    {
        Notification::fake();

        // Assign member to task
        $this->task->assignees()->attach($this->member->id, [
            'assigned_by' => $this->owner->id,
            'assigned_at' => now(),
        ]);

        // Manager transitions task status
        Sanctum::actingAs($this->manager);

        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
            'reason' => 'Work starting now',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        // Owner (creator) should receive notification
        Notification::assertSentTo($this->owner, TaskStatusChangedNotification::class);

        // Member (assignee) should receive notification
        Notification::assertSentTo($this->member, TaskStatusChangedNotification::class);

        // Manager (changer) should NOT receive notification
        Notification::assertNotSentTo($this->manager, TaskStatusChangedNotification::class);
    }

    public function test_user_can_list_their_notifications_with_pagination(): void
    {
        $this->member->notify(new TaskAssignedNotification($this->task, $this->owner));

        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ])
            ->assertJsonStructure([
                'key',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'data',
                            'read_at',
                            'created_at',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                ],
            ]);
    }

    public function test_user_cannot_access_another_users_notification(): void
    {
        $this->owner->notify(new TaskAssignedNotification($this->task, $this->manager));
        $ownerNotification = $this->owner->notifications()->first();

        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/notifications/{$ownerNotification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'not_found',
            ]);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $this->member->notify(new TaskAssignedNotification($this->task, $this->owner));
        $notification = $this->member->notifications()->first();

        $this->assertNull($notification->read_at);

        Sanctum::actingAs($this->member);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $this->member->notify(new TaskAssignedNotification($this->task, $this->owner));
        $this->member->notify(new TaskStatusChangedNotification($this->task, 'TODO', 'IN_PROGRESS', $this->owner));

        $this->owner->notify(new TaskAssignedNotification($this->task, $this->manager));
        $ownerNotification = $this->owner->notifications()->first();

        Sanctum::actingAs($this->member);

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'marked_as_read_count' => 2,
                ],
            ]);

        $this->assertSame(0, $this->member->unreadNotifications()->count());
        $this->assertNull($ownerNotification->fresh()->read_at);
    }
}
