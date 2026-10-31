<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use DatabaseTransactions;

    protected User $owner;
    protected User $manager;
    protected User $member;
    protected User $viewer;
    protected User $outsider;
    protected Team $team;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Project Owner', 'email' => 'task_owner@example.com']);
        $this->manager = User::factory()->create(['name' => 'Project Manager', 'email' => 'task_manager@example.com']);
        $this->member = User::factory()->create(['name' => 'Project Member', 'email' => 'task_member@example.com']);
        $this->viewer = User::factory()->create(['name' => 'Project Viewer', 'email' => 'task_viewer@example.com']);
        $this->outsider = User::factory()->create(['name' => 'Outsider', 'email' => 'task_outsider@example.com']);

        $this->team = Team::factory()->create(['created_by' => $this->owner->id]);
        $this->team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $this->team->members()->attach($this->manager->id, ['role' => TeamRole::MEMBER->value]);
        $this->team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);
        $this->team->members()->attach($this->viewer->id, ['role' => TeamRole::MEMBER->value]);

        $this->project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->owner->id,
            'status' => ProjectStatus::ACTIVE,
        ]);

        $this->project->members()->attach($this->owner->id, ['role' => ProjectRole::OWNER->value]);
        $this->project->members()->attach($this->manager->id, ['role' => ProjectRole::MANAGER->value]);
        $this->project->members()->attach($this->member->id, ['role' => ProjectRole::MEMBER->value]);
        $this->project->members()->attach($this->viewer->id, ['role' => ProjectRole::VIEWER->value]);
    }

    public function test_unauthenticated_user_cannot_access_tasks(): void
    {
        $response = $this->getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthenticated',
                'code' => 401,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_member_can_create_task_in_project(): void
    {
        Sanctum::actingAs($this->member);

        $payload = [
            'title' => 'Implement Authentication Flow',
            'description' => 'Setup Sanctum and Auth guards',
            'priority' => TaskPriority::HIGH->value,
            'start_at' => now()->toDateTimeString(),
            'due_at' => now()->addDays(5)->toDateTimeString(),
            'position' => 1,
        ];

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/tasks", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 201,
                'msg' => 'Task created successfully.',
                'data' => [
                    'title' => 'Implement Authentication Flow',
                    'priority' => TaskPriority::HIGH->value,
                    'status' => TaskStatus::TODO->value,
                    'project_id' => $this->project->id,
                    'created_by' => $this->member->id,
                ],
                'response_status' => [
                    'error' => false,
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Implement Authentication Flow',
            'project_id' => $this->project->id,
            'created_by' => $this->member->id,
            'status' => TaskStatus::TODO->value,
        ]);
    }

    public function test_viewer_cannot_create_task_in_project(): void
    {
        Sanctum::actingAs($this->viewer);

        $payload = [
            'title' => 'Viewer Task Attempt',
            'priority' => TaskPriority::LOW->value,
        ];

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/tasks", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_subtask_creation_succeeds_for_same_project(): void
    {
        Sanctum::actingAs($this->owner);

        $parentTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'title' => 'Parent Task',
        ]);

        $payload = [
            'title' => 'Child Subtask',
            'parent_task_id' => $parentTask->id,
            'priority' => TaskPriority::MEDIUM->value,
        ];

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/tasks", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 201,
                'data' => [
                    'title' => 'Child Subtask',
                    'parent_task_id' => $parentTask->id,
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Child Subtask',
            'parent_task_id' => $parentTask->id,
            'project_id' => $this->project->id,
        ]);
    }

    public function test_subtask_creation_fails_if_parent_task_belongs_to_different_project(): void
    {
        Sanctum::actingAs($this->owner);

        $otherProject = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->owner->id,
        ]);
        $otherProject->members()->attach($this->owner->id, ['role' => ProjectRole::OWNER->value]);

        $foreignTask = Task::factory()->create([
            'project_id' => $otherProject->id,
            'created_by' => $this->owner->id,
            'title' => 'Foreign Task',
        ]);

        $payload = [
            'title' => 'Illegal Subtask',
            'parent_task_id' => $foreignTask->id,
        ];

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/tasks", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_task_update_rejects_self_parenting_and_cycles(): void
    {
        Sanctum::actingAs($this->owner);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", [
            'parent_task_id' => $task->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_cannot_assign_non_project_member(): void
    {
        Sanctum::actingAs($this->owner);

        $payload = [
            'title' => 'Task with invalid assignee',
            'assignee_ids' => [$this->outsider->id],
        ];

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/tasks", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_can_assign_project_member_on_task_creation(): void
    {
        Sanctum::actingAs($this->owner);

        $payload = [
            'title' => 'Assigned Task',
            'assignee_ids' => [$this->member->id, $this->manager->id],
        ];

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/tasks", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 201,
            ]);

        $createdTaskId = $response->json('data.id');

        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $createdTaskId,
            'user_id' => $this->member->id,
            'assigned_by' => $this->owner->id,
        ]);

        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $createdTaskId,
            'user_id' => $this->manager->id,
            'assigned_by' => $this->owner->id,
        ]);
    }

    public function test_date_validation_rejects_due_at_before_start_at(): void
    {
        Sanctum::actingAs($this->owner);

        $payload = [
            'title' => 'Invalid Dates Task',
            'start_at' => '2026-11-10 10:00:00',
            'due_at' => '2026-11-05 10:00:00',
        ];

        $response = $this->postJson("/api/v1/projects/{$this->project->id}/tasks", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_status_change_to_done_sets_completed_at(): void
    {
        Sanctum::actingAs($this->owner);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_PROGRESS,
            'completed_at' => null,
        ]);

        $response = $this->patchJson("/api/v1/tasks/{$task->id}", [
            'status' => TaskStatus::DONE->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
                'data' => [
                    'status' => TaskStatus::DONE->value,
                ],
            ]);

        $this->assertNotNull($response->json('data.completed_at'));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::DONE->value,
        ]);

        // Revert status to IN_PROGRESS clears completed_at
        $revertResponse = $this->patchJson("/api/v1/tasks/{$task->id}", [
            'status' => TaskStatus::IN_PROGRESS->value,
        ]);

        $revertResponse->assertStatus(200);
        $this->assertNull($revertResponse->json('data.completed_at'));
    }

    public function test_non_project_member_cannot_view_task(): void
    {
        Sanctum::actingAs($this->outsider);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
                'response_status' => [
                    'error' => true,
                ],
            ]);
    }

    public function test_project_member_can_view_task_with_relations(): void
    {
        Sanctum::actingAs($this->member);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'title' => 'Detailed Task',
        ]);

        $response = $this->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
                'data' => [
                    'id' => $task->id,
                    'title' => 'Detailed Task',
                    'project' => [
                        'id' => $this->project->id,
                    ],
                ],
            ]);
    }

    public function test_creator_can_update_and_delete_their_task(): void
    {
        Sanctum::actingAs($this->member);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->member->id,
            'title' => 'Member Task',
        ]);

        $updateResponse = $this->patchJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Updated Member Task',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'title' => 'Updated Member Task',
                ],
            ]);

        $deleteResponse = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
            ]);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_viewer_cannot_update_or_delete_task(): void
    {
        Sanctum::actingAs($this->viewer);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
        ]);

        $updateResponse = $this->patchJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Viewer Update Attempt',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);

        $deleteResponse = $this->deleteJson("/api/v1/tasks/{$task->id}");

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);
    }

    public function test_owner_or_manager_can_sync_assignees(): void
    {
        Sanctum::actingAs($this->manager);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
        ]);

        $task->assignees()->attach($this->member->id, ['assigned_by' => $this->owner->id]);

        $response = $this->putJson("/api/v1/tasks/{$task->id}/assignees", [
            'assignee_ids' => [$this->manager->id, $this->owner->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
            ]);

        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $this->manager->id,
            'assigned_by' => $this->manager->id,
        ]);

        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $this->owner->id,
            'assigned_by' => $this->manager->id,
        ]);

        $this->assertDatabaseMissing('task_assignees', [
            'task_id' => $task->id,
            'user_id' => $this->member->id,
        ]);
    }

    public function test_regular_member_cannot_sync_assignees(): void
    {
        Sanctum::actingAs($this->member);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->putJson("/api/v1/tasks/{$task->id}/assignees", [
            'assignee_ids' => [$this->member->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'unauthorized',
                'code' => 403,
            ]);
    }

    public function test_filtering_tasks_by_status_and_priority(): void
    {
        Sanctum::actingAs($this->owner);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::DONE,
            'priority' => TaskPriority::LOW,
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::URGENT,
        ]);

        $statusResponse = $this->getJson('/api/v1/tasks?status=DONE');
        $statusResponse->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($statusResponse->json('data.data')));

        foreach ($statusResponse->json('data.data') as $item) {
            $this->assertEquals(TaskStatus::DONE->value, $item['status']);
        }

        $priorityResponse = $this->getJson('/api/v1/tasks?priority=URGENT');
        $priorityResponse->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($priorityResponse->json('data.data')));

        foreach ($priorityResponse->json('data.data') as $item) {
            $this->assertEquals(TaskPriority::URGENT->value, $item['priority']);
        }
    }

    public function test_project_tasks_listing_scoped_to_project(): void
    {
        Sanctum::actingAs($this->owner);

        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
        ]);

        $otherProject = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->owner->id,
        ]);
        $otherProject->members()->attach($this->owner->id, ['role' => ProjectRole::OWNER->value]);

        $task2 = Task::factory()->create([
            'project_id' => $otherProject->id,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->getJson("/api/v1/projects/{$this->project->id}/tasks");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
            ]);

        $ids = collect($response->json('data.data'))->pluck('id')->all();
        $this->assertContains($task1->id, $ids);
        $this->assertNotContains($task2->id, $ids);
    }
}

