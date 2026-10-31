<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
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

        $this->owner = User::factory()->create(['name' => 'Owner', 'email' => 'wf_owner@example.com']);
        $this->manager = User::factory()->create(['name' => 'Manager', 'email' => 'wf_manager@example.com']);
        $this->member = User::factory()->create(['name' => 'Member', 'email' => 'wf_member@example.com']);
        $this->viewer = User::factory()->create(['name' => 'Viewer', 'email' => 'wf_viewer@example.com']);
        $this->outsider = User::factory()->create(['name' => 'Outsider', 'email' => 'wf_outsider@example.com']);

        $this->team = Team::factory()->create(['created_by' => $this->owner->id]);
        $this->team->members()->attach($this->owner->id, ['role' => TeamRole::OWNER->value]);
        $this->team->members()->attach($this->manager->id, ['role' => TeamRole::MEMBER->value]);
        $this->team->members()->attach($this->member->id, ['role' => TeamRole::MEMBER->value]);
        $this->team->members()->attach($this->viewer->id, ['role' => TeamRole::MEMBER->value]);

        $this->project = Project::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->owner->id,
        ]);

        $this->project->members()->attach($this->owner->id, ['role' => ProjectRole::OWNER->value]);
        $this->project->members()->attach($this->manager->id, ['role' => ProjectRole::MANAGER->value]);
        $this->project->members()->attach($this->member->id, ['role' => ProjectRole::MEMBER->value]);
        $this->project->members()->attach($this->viewer->id, ['role' => ProjectRole::VIEWER->value]);
    }

    public function test_valid_status_transition_creates_audit_history_record(): void
    {
        Sanctum::actingAs($this->member);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->member->id,
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
            'reason' => 'Started development work',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
                'data' => [
                    'id' => $task->id,
                    'status' => TaskStatus::IN_PROGRESS->value,
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::IN_PROGRESS->value,
        ]);

        $this->assertDatabaseHas('task_status_histories', [
            'task_id' => $task->id,
            'changed_by' => $this->member->id,
            'old_status' => TaskStatus::TODO->value,
            'new_status' => TaskStatus::IN_PROGRESS->value,
            'reason' => 'Started development work',
        ]);
    }

    public function test_illegal_status_transition_is_rejected(): void
    {
        Sanctum::actingAs($this->member);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->member->id,
            'status' => TaskStatus::TODO,
        ]);

        // Attempt direct jump from TODO -> DONE
        $response = $this->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => TaskStatus::DONE->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);

        $this->assertEquals(TaskStatus::TODO, $task->fresh()->status);
    }

    public function test_completion_blocked_by_incomplete_prerequisite_dependency(): void
    {
        Sanctum::actingAs($this->owner);

        $taskA = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_REVIEW,
        ]);

        $taskB = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        // Task A depends on Task B
        $this->postJson("/api/v1/tasks/{$taskA->id}/dependencies", [
            'depends_on_task_id' => $taskB->id,
        ])->assertStatus(200)->assertJson(['key' => 'success', 'code' => 201]);

        // Attempting to complete Task A while Task B is IN_PROGRESS must fail
        $response = $this->postJson("/api/v1/tasks/{$taskA->id}/status", [
            'status' => TaskStatus::DONE->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
                'response_status' => [
                    'error' => true,
                ],
            ]);

        // Now advance Task B through the lifecycle to DONE
        $this->postJson("/api/v1/tasks/{$taskB->id}/status", [
            'status' => TaskStatus::IN_REVIEW->value,
        ])->assertStatus(200)->assertJson(['key' => 'success']);

        $this->postJson("/api/v1/tasks/{$taskB->id}/status", [
            'status' => TaskStatus::DONE->value,
        ])->assertStatus(200)->assertJson(['key' => 'success']);

        // Task A can now complete successfully
        $completeResponse = $this->postJson("/api/v1/tasks/{$taskA->id}/status", [
            'status' => TaskStatus::DONE->value,
        ]);

        $completeResponse->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
                'data' => [
                    'status' => TaskStatus::DONE->value,
                ],
            ]);

        $this->assertNotNull($taskA->fresh()->completed_at);
    }

    public function test_completion_blocked_by_incomplete_active_subtask(): void
    {
        Sanctum::actingAs($this->owner);

        $parent = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_REVIEW,
        ]);

        $subtask = Task::factory()->create([
            'project_id' => $this->project->id,
            'parent_task_id' => $parent->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $response = $this->postJson("/api/v1/tasks/{$parent->id}/status", [
            'status' => TaskStatus::DONE->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
            ]);

        // Complete subtask
        $this->postJson("/api/v1/tasks/{$subtask->id}/status", ['status' => TaskStatus::IN_REVIEW->value]);
        $this->postJson("/api/v1/tasks/{$subtask->id}/status", ['status' => TaskStatus::DONE->value]);

        // Parent can now complete
        $okResponse = $this->postJson("/api/v1/tasks/{$parent->id}/status", [
            'status' => TaskStatus::DONE->value,
        ]);

        $okResponse->assertStatus(200)->assertJson(['key' => 'success', 'code' => 200]);
    }

    public function test_reopening_task_clears_completed_at_timestamp(): void
    {
        Sanctum::actingAs($this->owner);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::DONE,
            'completed_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
            'reason' => 'Reopened due to QA issue',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'status' => TaskStatus::IN_PROGRESS->value,
                    'completed_at' => null,
                ],
            ]);

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_self_dependency_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/dependencies", [
            'depends_on_task_id' => $task->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
            ]);
    }

    public function test_cross_project_dependency_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $otherProject = Project::factory()->create(['team_id' => $this->team->id, 'created_by' => $this->owner->id]);
        $otherProject->members()->attach($this->owner->id, ['role' => ProjectRole::OWNER->value]);

        $taskA = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id]);
        $foreignTask = Task::factory()->create(['project_id' => $otherProject->id, 'created_by' => $this->owner->id]);

        $response = $this->postJson("/api/v1/tasks/{$taskA->id}/dependencies", [
            'depends_on_task_id' => $foreignTask->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
            ]);
    }

    public function test_circular_dependency_is_rejected_via_api(): void
    {
        Sanctum::actingAs($this->owner);

        $taskA = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id]);
        $taskB = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id]);

        // A depends on B
        $this->postJson("/api/v1/tasks/{$taskA->id}/dependencies", [
            'depends_on_task_id' => $taskB->id,
        ])->assertStatus(200)->assertJson(['key' => 'success']);

        // Attempt B depends on A
        $response = $this->postJson("/api/v1/tasks/{$taskB->id}/dependencies", [
            'depends_on_task_id' => $taskA->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'fail',
                'code' => 422,
            ]);
    }

    public function test_remove_dependency_unblocks_task(): void
    {
        Sanctum::actingAs($this->owner);

        $taskA = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_REVIEW,
        ]);
        $taskB = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $this->postJson("/api/v1/tasks/{$taskA->id}/dependencies", [
            'depends_on_task_id' => $taskB->id,
        ])->assertStatus(200);

        // Delete dependency
        $deleteResponse = $this->deleteJson("/api/v1/tasks/{$taskA->id}/dependencies/{$taskB->id}");
        $deleteResponse->assertStatus(200)->assertJson(['key' => 'success']);

        // Now taskA can be completed even though taskB is not DONE
        $completeResponse = $this->postJson("/api/v1/tasks/{$taskA->id}/status", [
            'status' => TaskStatus::DONE->value,
        ]);

        $completeResponse->assertStatus(200)->assertJson(['key' => 'success', 'code' => 200]);
    }

    public function test_get_dependencies_endpoint_returns_data(): void
    {
        Sanctum::actingAs($this->member);

        $taskA = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id]);
        $taskB = Task::factory()->create(['project_id' => $this->project->id, 'created_by' => $this->owner->id]);

        $taskA->dependencies()->attach($taskB->id);

        $response = $this->getJson("/api/v1/tasks/{$taskA->id}/dependencies");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'data' => [
                    'dependencies' => [
                        ['id' => $taskB->id],
                    ],
                ],
            ]);
    }

    public function test_get_history_endpoint_returns_paginated_audit_trail(): void
    {
        Sanctum::actingAs($this->member);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->member->id,
            'status' => TaskStatus::TODO,
        ]);

        $this->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
            'reason' => 'Step 1',
        ])->assertStatus(200)->assertJson(['key' => 'success']);

        $response = $this->getJson("/api/v1/tasks/{$task->id}/history");

        $response->assertStatus(200)
            ->assertJson([
                'key' => 'success',
                'code' => 200,
            ]);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals(TaskStatus::TODO->value, $data[0]['old_status']);
        $this->assertEquals(TaskStatus::IN_PROGRESS->value, $data[0]['new_status']);
    }

    public function test_viewer_cannot_change_status_or_manage_dependencies(): void
    {
        Sanctum::actingAs($this->viewer);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
        ]);

        $statusResponse = $this->postJson("/api/v1/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
        ]);
        $statusResponse->assertStatus(200)->assertJson(['key' => 'unauthorized', 'code' => 403]);

        $depResponse = $this->postJson("/api/v1/tasks/{$task->id}/dependencies", [
            'depends_on_task_id' => $task2->id,
        ]);
        $depResponse->assertStatus(200)->assertJson(['key' => 'unauthorized', 'code' => 403]);
    }
}

